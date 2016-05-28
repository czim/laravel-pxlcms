<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Czim\DutchHelper\DutchHelper;
use Czim\PxlCms\Generator\FieldType;
use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Models\CmsModel;
use Exception;

class AnalyzeModels extends AbstractProcessStep
{

    /**
     * ID of the module currently being processed
     *
     * @var int
     */
    protected $moduleId;

    /**
     * Temporary data for module currently being processed
     *
     * @var array
     */
    protected $module = [];

    /**
     * Temporary data for the model currently being built up from the processed module data
     *
     * @var array
     */
    protected $model = [];

    /**
     * Temporary data for overrides for module currently being processed
     *
     * @var array
     */
    protected $overrides = [];

    /**
     * Temporary data specific for attributes hidden property
     *
     * @var array
     */
    protected $overrideHidden = [];

    /**
     * @var DutchHelper
     */
    protected $dutchHelper;


    protected function process()
    {
        if ($this->context->dutchMode) {
            $this->dutchHelper = app(DutchHelper::class);
        }

        // build up a list of models
        foreach ($this->data->rawData['modules'] as $moduleId => $moduleData) {

            // custom modules don't have database content
            // or in any case it's impossible to be sure
            // so for now, ignore them
            if ($moduleData['is_custom']) {
                $this->context->log("Skipped custom module ({$moduleId}, '{$moduleData['name']}')");
                continue;
            }

            $this->processModule($moduleId, $moduleData);
        }

        // for each model we have built up, generated reversed relationships
        $this->generateReversedRelationships();
    }

    /**
     * Process a single module (to model)
     *
     * @param int   $moduleId
     * @param array $moduleData
     * @throws Exception
     */
    protected function processModule($moduleId, array $moduleData)
    {
        $this->resetTemporaryModelCache();

        // fill cache with current module's data
        $this->moduleId = $moduleId;
        $this->module   = $moduleData;

        // set default model data
        $this->model = [
            'module'                => $moduleId,
            'name'                  => $this->module['name'],
            'table'                 => null,
            'cached'                => config('pxlcms.generator.models.enable_rememberable_cache'),
            'is_translated'         => false,
            'is_translation'        => false,
            'is_listified'          => false,
            'ordered_by'            => [],
            'timestamps'            => null,
            // attributes
            'normal_fillable'       => [],
            'translated_fillable'   => [],
            'hidden'                => [],
            'casts'                 => [],
            'dates'                 => [],
            'defaults'              => [],
            'normal_attributes'     => [],
            'translated_attributes' => [],
            // categories
            'has_categories'        => (bool) array_get($this->module, 'client_cat_control'),
            // relationships
            'relations_config'      => [],
            'relationships'         => [
                'normal'   => [],
                'reverse'  => [],
                'image'    => [],
                'file'     => [],
                'checkbox' => [],
            ],
            // special
            'sluggable'       => false,
            'sluggable_setup' => [],
            'scope_active'    => null,   // config determines
            'scope_position'  => null,   // config determines
        ];

        // process manual/automatic sorting of entries
        $this->processModuleSorting();

        // set overrides in model data
        $this->prepareModelOverrides();

        // add module fields as attribute data
        $this->processModuleFields();

        // override relationship data found while processing fields
        $this->applyOverridesForRelationships();

        // make sure we do not use a reserved class name
        $this->checkForReservedClassName($this->model['name']);

        // save completely built up model to context
        $this->context->output['models'][ $moduleId ] = $this->model;
    }

    /**
     * Resets cache properties, preparing for the next module processing step
     */
    protected function resetTemporaryModelCache()
    {
        $this->moduleId  = 0;
        $this->module    = [];
        $this->model     = [];
        $this->overrides = [];
    }

    /**
     * Determines and stores information about the sorting configuration
     * found for entries of the module
     */
    protected function processModuleSorting()
    {
        $this->model['is_listified'] = false;
        $this->model['ordered_by']   = [];

        // if there can be only 1 entry, it makes no sense to listify or order in any case
        if ($this->module['max_entries'] == 1) {
            return;
        }

        // if manually sorted, use listify and do not set sorting columns
        if ($this->module['sort_entries_manually']) {
            $this->model['is_listified'] = true;
            return;
        }

        // if automatically sorted, check and extract the sorting columns
        // listify is disabled by default, but may be overridden (might be useful some day)
        $this->model['is_listified'] = false;

        $sorting = trim(strtolower($this->module['sort_entries_by']));

        if (empty($sorting)) return;

        foreach (explode(',', $sorting) as $sortClauseString) {

            if ( ! preg_match("#^\s*[`'](.*)[`']\s+(asc|desc)\s*$#i", $sortClauseString, $matches)) {
                $this->context->log(
                    "Could not interpret sorting clause '{$sortClauseString}' for module #{$this->moduleId}",
                    Generator::LOG_LEVEL_ERROR
                );
                continue;
            }

            // store direction per column as key
            $this->model['ordered_by'][ $matches[1] ] = $matches[2];
        }
    }

    /**
     * Prepares override cache for current model being assembled
     */
    protected function prepareModelOverrides()
    {
        $this->overrides = $this->getOverrideConfigForModel($this->moduleId);

        // determine name to use, handle pluralization and check database table override
        if (array_key_exists('name', $this->overrides)) {

            $name = $this->overrides['name'];

        } else {

            $name = array_get($this->module, 'prefixed_name') ?: $this->module['name'];

            if (config('pxlcms.generator.models.model_name.singularize_model_names')) {

                if ($this->context->dutchMode) {
                    $name = $this->dutchSingularize($name);
                } else {
                    $name = str_singular($name);
                }
            }
        }

        // make sure we force set a table name if it does not follow convention
        $tableOverride = null;

        if (str_plural($this->normalizeDb($name)) != $this->normalizeDb($this->module['name'])) {

            $tableOverride = $this->getModuleTablePrefix($this->moduleId)
                           . $this->normalizeDb($this->module['name']);
        }

        // force listified?
        $listified = array_key_exists('listify', $this->overrides)
                   ?    (bool) $this->overrides['listify']
                   :    $this->model['is_listified'];


        // override hidden attributes?
        $this->overrideHidden = [];

        if (    ! is_null(array_get($this->overrides, 'attributes.hidden'))
            &&  ! array_get($this->overrides, 'attributes.hidden-empty')
        ) {
            $this->overrideHidden = array_get($this->overrides, 'attributes.hidden');

            if ( ! is_array($this->overrideHidden)) $this->overrideHidden = [ (string) $this->overrideHidden ];
        }


        // apply overrides to model data
        $this->model['name']         = $name;
        $this->model['table']        = $tableOverride;
        $this->model['is_listified'] = $listified;
        $this->model['hidden']       = $this->overrideHidden;
        $this->model['casts']        = array_get($this->overrides, 'attributes.casts', []);
    }

    /**
     * Processes field data from the module
     */
    protected function processModuleFields()
    {
        $overrideCasts = array_get($this->overrides, 'attributes.casts', []);
        $removeCasts   = array_get($this->overrides, 'attributes.casts-remove', []);


        foreach ($this->module['fields'] as $fieldId => $fieldData) {

            $attributeName = $this->normalizeDb($fieldData['name']);
            $relationName  = camel_case($attributeName);

            switch ($fieldData['field_type_id']) {

                // references

                case FieldType::TYPE_REFERENCE:
                case FieldType::TYPE_REFERENCE_CROSS:
                case FieldType::TYPE_REFERENCE_NEGATIVE:

                    // add foreign key if it is different than the targeted model name
                    // (would break the convention) -- this is NOT necessary, since the convention
                    // for the CmsModel class is to use the relation name anyway!
                    //
                    // this only needs to be set if the relation name ends up being different
                    // from the relation name
                    //
                    // todo: so keep a close eye on the reversed relationships!
                    $keyName = null;
                    //if ($relationName !== studly_case($this->data->rawData['modules'][ $fieldData['refers_to_module'] ]['name'])) {
                    //    $keyName = $attributeName;
                    //}

                    // attribute names with numbers in them wreak havoc on the name conversion methods
                    // so always add the key for those
                    if (preg_match('#\d#', $relationName)) {
                        $keyName = $attributeName;
                    }

                    // in some weird cases, cmses have been destroyed by leaving in relationships
                    // that do not refer to any model; these should be skipped
                    if (empty($fieldData['refers_to_module'])) {
                        $this->context->log(
                            "Relation '{$relationName}', field #{$fieldId} does not refer to any module, skipped.",
                            Generator::LOG_LEVEL_ERROR
                        );
                        break;
                    }

                    $this->model['relationships']['normal'][ $relationName ] = [
                        'type'     => Generator::RELATIONSHIP_BELONGS_TO,    // reverse of hasOne
                        'model'    => $fieldData['refers_to_module'],
                        'single'   => ($fieldData['value_count'] == 1),
                        'count'    => $fieldData['value_count'],  // should always be 1 for single ref
                        'field'    => $fieldId,
                        'key'      => $keyName,
                        'negative' => ($fieldData['field_type_id'] == FieldType::TYPE_REFERENCE_NEGATIVE),
                        'special'  => CmsModel::RELATION_TYPE_MODEL,
                    ];

                    if (config('pxlcms.generator.models.hide_foreign_key_attributes')) {
                        $this->model['hidden'][] = $attributeName;
                    }
                    break;

                case FieldType::TYPE_REFERENCE_MANY:
                case FieldType::TYPE_REFERENCE_AUTOSORT:
                case FieldType::TYPE_REFERENCE_CHECKBOXES:

                    if (empty($fieldData['refers_to_module'])) {
                        $this->context->log(
                            "Relation '{$relationName}', field #{$fieldId} does not refer to any module, skipped.",
                            Generator::LOG_LEVEL_ERROR
                        );
                        break;
                    }

                    $this->model['relationships']['normal'][ $relationName ] = [
                        'type'     => Generator::RELATIONSHIP_BELONGS_TO_MANY,
                        'model'    => $fieldData['refers_to_module'],
                        'single'   => false,
                        'count'    => $fieldData['value_count'],    // 0 for no limit
                        'field'    => $fieldId,
                        'negative' => false,
                        'special'  => CmsModel::RELATION_TYPE_MODEL,
                    ];
                    break;


                // special references

                case FieldType::TYPE_IMAGE:
                case FieldType::TYPE_IMAGE_MULTI:
                    $this->model['relationships']['image'][ $relationName ] = [
                        'type'       => ($fieldData['value_count'] == 1)
                                            ? Generator::RELATIONSHIP_HAS_ONE
                                            : Generator::RELATIONSHIP_HAS_MANY,
                        'single'     => ($fieldData['value_count'] == 1),
                        'count'      => $fieldData['value_count'],
                        'field'      => $fieldId,
                        'translated' => (bool) $fieldData['multilingual'],
                        'resizes'    => $this->getImageResizesForField($fieldId),
                        'special'    => CmsModel::RELATION_TYPE_IMAGE,
                    ];
                    break;

                case FieldType::TYPE_FILE:
                    $this->model['relationships']['file'][ $relationName ] = [
                        'type'       => ($fieldData['value_count'] == 1)
                                            ? Generator::RELATIONSHIP_HAS_ONE
                                            : Generator::RELATIONSHIP_HAS_MANY,
                        'single'     => ($fieldData['value_count'] == 1),
                        'count'      => $fieldData['value_count'],
                        'field'      => $fieldId,
                        'translated' => (bool) $fieldData['multilingual'],
                        'special'    => CmsModel::RELATION_TYPE_FILE,
                    ];
                    break;

                case FieldType::TYPE_CHECKBOX:
                    $this->model['relationships']['checkbox'][ $relationName ] = [
                        'type'    => Generator::RELATIONSHIP_HAS_MANY,
                        'single'  => false,
                        'count'   => $fieldData['value_count'],
                        'field'   => $fieldId,
                        'special' => CmsModel::RELATION_TYPE_CHECKBOX,
                    ];
                    break;


                // normal fields

                case FieldType::TYPE_INPUT:
                case FieldType::TYPE_DROPDOWN:
                case FieldType::TYPE_LABEL:
                case FieldType::TYPE_COLORCODE:
                case FieldType::TYPE_TEXT:
                case FieldType::TYPE_TEXT_HTML_FLEX:
                case FieldType::TYPE_TEXT_HTML_RAW:
                case FieldType::TYPE_TEXT_HTML_FCK:
                case FieldType::TYPE_TEXT_HTML_ALOHA:
                case FieldType::TYPE_TEXT_LONG:
                case FieldType::TYPE_BOOLEAN:
                case FieldType::TYPE_INTEGER:
                case FieldType::TYPE_NUMERIC:
                case FieldType::TYPE_FLOAT:
                case FieldType::TYPE_DATE:
                case FieldType::TYPE_CUSTOM_HIDDEN:
                case FieldType::TYPE_CUSTOM:
                case FieldType::TYPE_SLIDER:
                case FieldType::TYPE_LOCATION:

                    switch ($fieldData['field_type_id']) {

                        case FieldType::TYPE_BOOLEAN:
                            $this->model['casts'][ $attributeName ] = 'boolean';
                            break;

                        case FieldType::TYPE_INTEGER:
                        case FieldType::TYPE_SLIDER:
                            $this->model['casts'][ $attributeName ] = 'integer';
                            break;

                        case FieldType::TYPE_NUMERIC:
                        case FieldType::TYPE_FLOAT:
                            $this->model['casts'][ $attributeName ] = 'float';
                            break;

                        case FieldType::TYPE_DATE:
                            if ( ! array_key_exists($attributeName, $overrideCasts)) {
                                $this->model['dates'][] = $attributeName;
                            }
                            break;

                        case FieldType::TYPE_LOCATION:
                            $this->model['casts'][ $attributeName ] = 'array';
                            break;

                        // default omitted on purpose
                    }

                    if ($fieldData['multilingual']) {
                        $this->model['is_translated']           = true;
                        $this->model['translated_attributes'][] = $attributeName;
                        $this->model['translated_fillable'][]   = $attributeName;
                    } else {
                        $this->model['normal_attributes'][] = $attributeName;
                        $this->model['normal_fillable'][]   = $attributeName;
                    }

                    // if a default is set, store it
                    if (    $fieldData['default'] !== null
                        &&  $fieldData['default'] !== ''
                    ) {

                        $default = "'" . $fieldData['default'] . "'";

                        if (array_key_exists($attributeName, $this->model['casts'])) {

                            switch ($this->model['casts'][ $attributeName ]) {

                                case 'boolean':
                                    $default = ($fieldData['default'] == '1' || $fieldData['default'] == 'true') ? 'true' : 'false';
                                    break;

                                case 'integer':
                                case 'float':
                                    $default = $fieldData['default'];
                                    break;

                                // default omitted on purpose
                            }
                        }

                        $this->model['defaults'][ $attributeName ] = $default;
                    }

                    break;

                case FieldType::TYPE_RANGE:
                default:
                    throw new Exception(
                        "Unknown/unhandled field type {$fieldData['field_type_id']} "
                        . "({$fieldData['field_type_name']})"
                    );
            }
        }

        // force hidden override
        if (count($this->model['hidden']) && ! count($this->overrideHidden)) {

            $this->model['hidden'] = array_merge(
                $this->model['hidden'],
                config('pxlcms.generator.models.default_hidden_fields', [])
            );
        }


        // clear, set or remove fillable attributes by override
        if (array_get($this->overrides, 'attributes.fillable-empty')) {

            $this->model['normal_fillable']     = [];
            $this->model['translated_fillable'] = [];

        } elseif ($overrideFillable = array_get($this->overrides, 'attributes.fillable', [])) {

            $this->model['normal_fillable']     = $overrideFillable;
            $this->model['translated_fillable'] = [];

        } elseif ($removeFillable = array_get($this->overrides, 'attributes.fillable-remove', [])) {

            $this->model['normal_fillable']     = array_diff($this->model['normal_fillable'], $removeFillable);
            $this->model['translated_fillable'] = array_diff($this->model['translated_fillable'], $removeFillable);
        }


        // force casts as overridden
        foreach ($overrideCasts as $attributeName => $type) {
            if (array_key_exists($attributeName, $this->model['casts'])) {
                $this->model['casts'][ $attributeName ] = $type;
            }
        }

        foreach ($removeCasts as $attributeName) {
            if (array_key_exists($attributeName, $this->model['casts'])) {
                unset ($this->model['casts'][ $attributeName ]);
            }
        }

        // enable timestamps?
        if (    config('pxlcms.generator.models.enable_timestamps_on_models_with_suitable_attributes')
            &&  in_array('created_at', $this->model['dates'])
            &&  in_array('updated_at', $this->model['dates'])
        ) {
            $this->model['timestamps'] = true;
        }
    }

    /**
     * Applies overrides set in the config for analyzed relationships
     */
    protected function applyOverridesForRelationships()
    {
        $overrides = array_get($this->overrides, 'relationships');

        if (empty($overrides)) return;

        $removeRelations = [];

        foreach ($overrides as $overrideName => $override) {

            // find the relationship
            foreach (['normal', 'image', 'file', 'checkbox'] as $type) {

                foreach ($this->model['relationships'][ $type ] as $name => &$relationData) {

                    if ($name === $overrideName) {

                        // apply overrides and change the model data

                        $newName        = array_get($override, 'name');
                        $preventReverse = array_get($override, 'prevent_reverse', false);
                        $reverseName    = array_get($override, 'reverse_name', false);

                        if ($preventReverse) {
                            $relationData['prevent_reverse'] = true;
                        }

                        if ( ! empty($reverseName)) {
                            $relationData['reverse_name'] = $reverseName;
                        }

                        if ( ! empty($newName)) {

                            $this->model['relationships'][ $type ][ $newName ] = $relationData;

                            $removeRelations[] = $type . '.' . $overrideName;
                            $removeRelations = array_diff($removeRelations, [ $newName ]);
                        }


                        $this->context->log(
                            "Override applied for relationship {$overrideName} on module {$this->moduleId}."
                        );

                        break 2;
                    }
                }
            }
        }

        unset($relationData);

        // remove now 'moved' relationships
        array_forget($this->model['relationships'], $removeRelations);
    }


    /**
     * Generates model data for reversed relationships, for all available models
     */
    protected function generateReversedRelationships()
    {

        foreach ($this->context->output['models'] as $modelFromKey => $modelFrom) {

            foreach ($modelFrom['relationships']['normal'] as $relationName => $relationship) {

                // skip negative references for now, don't reverse them
                if ($relationship['negative']) {
                    $this->context->log(
                        "Skipped negative relationship for reversing (model: {$modelFromKey} to "
                        . "{$relationship['model']}, field: {$relationship['field']})"
                    );
                    continue;
                }

                // also skip any relationship we've overridden not to reverse
                if (array_get($relationship, 'prevent_reverse')) {
                    $this->context->log(
                        "Skipped relationship for reversing due to override (model: {$modelFromKey} to "
                        . "{$relationship['model']}, field: {$relationship['field']})"
                    );
                    continue;
                }

                // special case, referencing model is itself
                $selfReference     = ($modelFromKey == $relationship['model']);
                $partOfMultiple    = $this->multipleBelongsToRelationsBetweenModels($modelFromKey, $relationship['model']);
                $reverseType       = null;
                $reverseCount      = 0;
                $reverseForeignKey = null;


                // For non-cms_m_references relations, we need to be careful about
                // foreign keys not following the expected laravel convention
                // since they expect the name to be based on the model names, whereas
                // it is actually defined by the field name in the CMS module,
                // which conforms to the relation name of the OPPOSITE relation.
                //
                // Too tricky to determine automatically, so force a foreign key parameter
                // if required.
                if ($relationship['type'] !== GENERATOR::RELATIONSHIP_BELONGS_TO_MANY) {

                    if (array_get($relationship, 'key')) {

                        $reverseForeignKey = $relationship['key'];

                    } elseif ($relationName !== camel_case($this->context->output['models'][ $relationship['model'] ]['name'])) {

                        $reverseForeignKey = $this->normalizeDb($relationName);
                    }
                }

                // determine type and one/many (count) for reverse relationship
                switch ($relationship['type']) {

                    case GENERATOR::RELATIONSHIP_HAS_ONE:
                    case GENERATOR::RELATIONSHIP_HAS_MANY:
                        $reverseType = GENERATOR::RELATIONSHIP_BELONGS_TO;
                        break;

                    case GENERATOR::RELATIONSHIP_BELONGS_TO:
                        // for single-entry modules, a hasOne will do
                        if ($this->data->rawData['modules'][ $modelFromKey ]['max_entries'] == 1) {
                            $reverseType  = GENERATOR::RELATIONSHIP_HAS_ONE;
                            $reverseCount = 1;
                        } else {
                            $reverseType = GENERATOR::RELATIONSHIP_HAS_MANY;
                        }
                        break;

                    case GENERATOR::RELATIONSHIP_BELONGS_TO_MANY:
                        $reverseType = GENERATOR::RELATIONSHIP_BELONGS_TO_MANY;
                        break;

                    // default omitted on purpose
                }

                // pluralize the name if configured to and it's a to many relationship
                $pluralizeName = (
                        $reverseCount !== 1
                    &&  config('pxlcms.generator.models.pluralize_reversed_relationship_names')
                    &&  ! $partOfMultiple
                    &&  (   ! $selfReference
                        ||  config('pxlcms.generator.models.pluralize_reversed_relationship_names_for_self_reference')
                        )
                );

                // name of the relationship reversed
                // default is name of the model referred to
                $baseName = $relationName;

                if ($partOfMultiple) {

                    $baseName = camel_case($modelFrom['name']) . ucfirst($baseName);

                } elseif ( ! $selfReference) {

                    $baseName = $modelFrom['name'];
                }

                if ($pluralizeName) {

                    $baseName = ($this->context->dutchMode) ? $this->dutchPluralize($baseName) : str_plural($baseName);
                }


                if (array_get($relationship, 'reverse_name')) {
                    // hard override is applied without any checks

                    $reverseName = $relationship['reverse_name'];

                } elseif ($selfReference || $partOfMultiple) {
                    // self-referencing is an exception, since using the model name won't be useful
                    // part of multiple belongsto is an exception, to avoid duplicates

                    $reverseName = $baseName
                                 . config('pxlcms.generator.models.relationship_reverse_postfix', 'Reverse');

                } else {

                    $reverseName = camel_case($baseName);
                }

                $reverseNameSnakeCase = snake_case($reverseName);

                // if already taken by an attribute, add custom string
                if (    in_array($reverseNameSnakeCase, $this->context->output['models'][ $relationship['model'] ]['normal_attributes'])
                    ||  in_array($reverseNameSnakeCase, $this->context->output['models'][ $relationship['model'] ]['translated_attributes'])
                    ||  in_array($reverseName, $this->context->output['models'][ $relationship['model'] ]['relationships']['normal'])
                    ||  in_array($reverseName, $this->context->output['models'][ $relationship['model'] ]['relationships']['image'])
                    ||  in_array($reverseName, $this->context->output['models'][ $relationship['model'] ]['relationships']['file'])
                    ||  in_array($reverseName, $this->context->output['models'][ $relationship['model'] ]['relationships']['checkbox'])
                ) {
                    $reverseName .= config('pxlcms.generator.models.relationship_fallback_postfix', 'Reference');
                }


                $this->context->output['models'][ $relationship['model'] ]['relationships']['reverse'][ $reverseName ] = [
                    'type'     => $reverseType,
                    'model'    => $modelFromKey,
                    'single'   => ($reverseCount == 1),
                    'count'    => $reverseCount,
                    'field'    => $relationship['field'],
                    'key'      => $reverseForeignKey,
                    'negative' => false,
                ];
            }
        }
    }


    /**
     * Returns whether there are multiple relationships from one model to the other
     *
     * @param int $fromModuleId
     * @param int $toModuleId
     * @return bool
     */
    protected function multipleBelongsToRelationsBetweenModels($fromModuleId, $toModuleId)
    {
        $count = 0;

        foreach ($this->context->output['models'][ $fromModuleId ]['relationships']['normal'] as $relationship) {

            if (    $relationship['model'] == $toModuleId
                &&  $relationship['type'] == Generator::RELATIONSHIP_BELONGS_TO
                &&  ! array_get($relationship, 'negative')
            ) {
                $count++;
            }
        }

        return ($count > 1);
    }

    /**
     * Returns data about resizes for an image field
     *
     * @param int $fieldId
     * @return array
     */
    protected function getImageResizesForField($fieldId)
    {
        if (    ! array_key_exists('resizes', $this->data->rawData['fields'][ $fieldId ])
            ||  ! count($this->data->rawData['fields'][ $fieldId ]['resizes'])
        ) {
            return [];
        }

        $resizes = [];

        foreach ($this->data->rawData['fields'][ $fieldId ]['resizes'] as $resizeId => $resize) {

            $resizes[] = [
                'resize' => $resizeId,
                'prefix' => $resize['prefix'],
                'width'  => (int) $resize['width'],
                'height' => (int) $resize['height'],
            ];
        }

        // sort resizes.. by prefix name, or by image size?
        uasort($resizes, function ($a, $b) {
            //return $a['width'] * $a['height'] - $b['width'] * $b['height'];
            return strcmp($a['prefix'], $b['prefix']);
        });

        return $resizes;
    }

    /**
     * Checks whether a given name is on the reserved list
     *
     * @param string $name
     */
    protected function checkForReservedClassName($name)
    {
        $name = trim(strtolower($name));

        if (in_array($name, config('pxlcms.generator.reserved', []))) {
            throw new \InvalidArgumentException("Cannot use {$name} as a module name, it is reserved!");
        }
    }

    /**
     * Standard database field normalization
     *
     * @param string $string
     * @return string
     */
    protected function normalizeDb($string)
    {
        return $this->context->normalizeNameForDatabase($string);
    }

    /**
     * Singularizes a noun/name for Dutch content
     *
     * @param string $name
     * @return string
     */
    protected function dutchSingularize($name)
    {
        if (empty($this->dutchHelper)) return $name;

        return $this->dutchHelper->singularize($name);
    }

    /**
     * Pluralizes a noun/name for Dutch content
     *
     * @param string $name
     * @return string
     */
    protected function dutchPluralize($name)
    {
        if (empty($this->dutchHelper)) return $name;

        return $this->dutchHelper->pluralize($name);
    }

}
