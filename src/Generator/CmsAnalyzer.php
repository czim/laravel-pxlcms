<?php
namespace Czim\PxlCms\Generator;

use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Analyzes the meta-content of the CMS.
 */
class CmsAnalyzer
{
    /**
     * Result output data after analysis
     *
     * @var array
     */
    protected $output = [
        'models' => [],
    ];

    /**
     * Raw data cache
     *
     * @var array
     */
    protected $rawData = [
        'modules' => [],
        'fields'  => [],
        'resizes' => [],
    ];

    /**
     * @var FieldType
     */
    protected $fieldType;

    /**
     * Simple log for analysis report
     *
     * @var array
     */
    protected $log = [];


    /**
     * Initializes the process, checking whether everything is ready to go
     */
    public function __construct()
    {
        $this->fieldType = new FieldType();

        // check if all tables required are present
        $this->checkTablePresence();

        // cache raw data
        $this->cacheData();
    }

    /**
     * Analyzes the CMS data, producing output data
     */
    public function analyze()
    {
        // analyze raw data
        $this->analyzeModules();

        return $this->output;
    }

    /**
     * Analyzes module data, producing output data for modules
     */
    protected function analyzeModules()
    {
        // build up a list of models
        foreach ($this->rawData['modules'] as $moduleId => $moduleData) {

            // custom modules don't have database content
            // or in any case it's impossible to be sure
            // so for now, ignore them
            if ($moduleData['is_custom']) {
                $this->log("Skipped custom module ({$moduleId}, '{$moduleData['name']}')");
                continue;
            }

            $overrides = $this->getOverrideConfigForModel($moduleId);

            if (array_key_exists('name', $overrides)) {

                $name = $overrides['name'];

            } else {

                $name = $moduleData['name'];

                if (config('pxlcms.generator.singularize_model_names')) {
                    $name = str_singular($name);
                }
            }

            // make sure we force set a table name if it does not follow convention
            $tableOverride = null;
            if (str_plural(snake_case($name)) != snake_case($moduleData['name'])) {
                $tableOverride = $this->getModuleTablePrefix($moduleId) . snake_case($moduleData['name']);
            }

            // force listified?
            $listified = array_key_exists('listify', $overrides)
                ? (bool) $overrides['listify']
                : ($moduleData['max_entries'] != 1);


            // override hidden attributes?
            $overrideHidden = [];

            if ( ! is_null(array_get($overrides, 'attributes.hidden'))
                && ! array_get($overrides, 'attributes.hidden-empty')
            ) {
                $overrideHidden = array_get($overrides, 'attributes.hidden');

                if ( ! is_array($overrideHidden)) $overrideHidden = [(string) $overrideHidden];
            }

            // override casts?
            $overrideCasts = array_get($overrides, 'attributes.casts', []);
            $removeCasts   = array_get($overrides, 'attributes.casts-remove', []);


            $model = [
                'module'                => $moduleId,
                'name'                  => $name,
                'table'                 => $tableOverride,   // default
                'cached'                => config('pxlcms.generator.enable_rememberable_cache'),
                'is_translated'         => false,
                'is_listified'          => $listified, // makes no sense for single-entry only
                'normal_fillable'       => [],
                'translated_fillable'   => [],
                'hidden'                => $overrideHidden,
                'casts'                 => $overrideCasts,
                'dates'                 => [],
                'relations_config'      => [],
                'normal_attributes'     => [],
                'translated_attributes' => [],
                'timestamps'            => null,

                'relationships'         => [
                    'normal'   => [],
                    'reverse'  => [],
                    'image'    => [],
                    'file'     => [],
                    'checkbox' => [],
                ],

            ];


            // ------------------------------------------------------------------------------
            //      Analyze fields
            // ------------------------------------------------------------------------------

            foreach ($moduleData['fields'] as $fieldId => $fieldData) {

                $attributeName = $this->fieldNameToDatabaseColumn($fieldData['name']);
                $relationName  = camel_case($attributeName);

                switch ($fieldData['field_type_id']) {

                    // references

                    case FieldType::TYPE_REFERENCE:
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
                        //if ($relationName !== studly_case($this->rawData['modules'][ $fieldData['refers_to_module'] ]['name'])) {
                        //    $keyName = $attributeName;
                        //}

                        $model['relationships']['normal'][ $relationName ] = [
                            'type'     => Generator::RELATIONSHIP_BELONGS_TO,    // reverse of hasOne
                            'model'    => $fieldData['refers_to_module'],
                            'single'   => ($fieldData['value_count'] == 1),
                            'count'    => $fieldData['value_count'],  // should always be 1 for single ref
                            'field'    => $fieldId,
                            'key'      => $keyName,
                            'negative' => ($fieldData['field_type_id'] == FieldType::TYPE_REFERENCE_NEGATIVE),
                        ];

                        if (config('pxlcms.generator.hide_foreign_key_attributes')) {
                            $model['hidden'][] = $attributeName;
                        }
                        break;

                    case FieldType::TYPE_REFERENCE_MANY:
                    case FieldType::TYPE_REFERENCE_AUTOSORT:
                    case FieldType::TYPE_REFERENCE_CHECKBOXES:
                        $model['relationships']['normal'][ $relationName ] = [
                            'type'     => Generator::RELATIONSHIP_BELONGS_TO_MANY,
                            'model'    => $fieldData['refers_to_module'],
                            'single'   => false,
                            'count'    => $fieldData['value_count'],    // 0 for no limit
                            'field'    => $fieldId,
                            'negative' => false,
                        ];
                        break;


                    // special references

                    case FieldType::TYPE_IMAGE:
                    case FieldType::TYPE_IMAGE_MULTI:
                        $model['relationships']['image'][ $relationName ] = [
                            'type'       => ($fieldData['value_count'] == 1)
                                ? Generator::RELATIONSHIP_HAS_ONE
                                : Generator::RELATIONSHIP_HAS_MANY,
                            'single'     => ($fieldData['value_count'] == 1),
                            'count'      => $fieldData['value_count'],
                            'field'      => $fieldId,
                            'translated' => (bool) $fieldData['multilingual'],
                            'resizes'    => $this->getImageResizesForField($fieldId),
                        ];
                        break;

                    case FieldType::TYPE_FILE:
                        $model['relationships']['file'][ $relationName ] = [
                            'type'       => ($fieldData['value_count'] == 1)
                                ? Generator::RELATIONSHIP_HAS_ONE
                                : Generator::RELATIONSHIP_HAS_MANY,
                            'single'     => ($fieldData['value_count'] == 1),
                            'count'      => $fieldData['value_count'],
                            'field'      => $fieldId,
                            'translated' => (bool) $fieldData['multilingual'],
                        ];
                        break;

                    case FieldType::TYPE_CHECKBOX:
                        $model['relationships']['checkbox'][ $relationName ] = [
                            'type'   => Generator::RELATIONSHIP_HAS_MANY,
                            'single' => false,
                            'count'  => $fieldData['value_count'],
                            'field'  => $fieldId,
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

                        switch ($fieldData['field_type_id']) {

                            case FieldType::TYPE_BOOLEAN:
                                $model['casts'][ $attributeName ] = 'boolean';
                                break;

                            case FieldType::TYPE_INTEGER:
                                $model['casts'][ $attributeName ] = 'integer';
                                break;

                            case FieldType::TYPE_NUMERIC:
                            case FieldType::TYPE_FLOAT:
                                $model['casts'][ $attributeName ] = 'float';
                                break;

                            case FieldType::TYPE_DATE:
                                if ( ! array_key_exists($attributeName, $overrideCasts)) {
                                    $model['dates'][] = $attributeName;
                                }
                                break;

                            // default omitted on purpose
                        }

                        if ($fieldData['multilingual']) {
                            $model['is_translated']           = true;
                            $model['translated_attributes'][] = $attributeName;
                            $model['translated_fillable'][]   = $attributeName;
                        } else {
                            $model['normal_attributes'][] = $attributeName;
                            $model['normal_fillable'][]   = $attributeName;
                        }
                        break;

                    case FieldType::TYPE_SLIDER:
                    case FieldType::TYPE_RANGE:
                    case FieldType::TYPE_LOCATION:
                    case FieldType::TYPE_REFERENCE_CROSS:
                    default:
                        throw new Exception(
                            "Unknown/unhandled field type {$fieldData['field_type_id']} "
                            . "({$fieldData['field_type_name']})"
                        );
                }
            }

            // force hidden override
            if (count($model['hidden']) && ! count($overrideHidden)) {
                $model['hidden'] = array_merge(
                    $model['hidden'],
                    config('pxlcms.generator.default_hidden_fields', [])
                );
            }


            // clear, set or remove fillable attributes by override
            if (array_get($overrides, 'attributes.fillable-empty')) {

                $model['normal_fillable']     = [];
                $model['translated_fillable'] = [];

            } elseif ($overrideFillable = array_get($overrides, 'attributes.fillable', [])) {

                $model['normal_fillable']     = $overrideFillable;
                $model['translated_fillable'] = [];

            } elseif ($removeFillable = array_get($overrides, 'attributes.fillable-remove', [])) {

                $model['normal_fillable']     = array_diff($model['normal_fillable'], $removeFillable);
                $model['translated_fillable'] = array_diff($model['translated_fillable'], $removeFillable);
            }


            // force casts as overridden
            foreach ($overrideCasts as $attributeName => $type) {
                if (array_key_exists($attributeName, $model['casts'])) {
                    $model['casts'][$attributeName] = $type;
                }
            }

            foreach ($removeCasts as $attributeName) {
                if (array_key_exists($attributeName, $model['casts'])) {
                    unset ($model['casts'][$attributeName]);
                }
            }


            // enable timestamps?
            if (    config('pxlcms.generator.enable_timestamps_on_models_with_suitable_attributes')
                &&  in_array('created_at', $model['dates'])
                &&  in_array('updated_at', $model['dates'])
            ) {
                $model['timestamps'] = true;
            }

            $this->output['models'][ $moduleId ] = $model;
        }


        // ------------------------------------------------------------------------------
        //      Generate reversed relationships
        // ------------------------------------------------------------------------------

        foreach ($this->output['models'] as $modelFromKey => $modelFrom) {

            foreach ($modelFrom['relationships']['normal'] as $relationName => $relationship) {

                // skip negative references for now, don't reverse them
                if ($relationship['negative']) {
                    $this->log(
                        "Skipped negative relationship for reversing (model: {$modelFromKey} to "
                        . "{$relationship['model']}, field: {$relationship['field']})"
                    );
                    continue;
                }

                // special case, referencing model is itself
                $selfReference     = ($modelFromKey == $relationship['model']);
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

                    } elseif ($relationName !== camel_case($this->output['models'][ $relationship['model'] ]['name'])) {

                        $reverseForeignKey = snake_case($relationName);
                    }
                }

                // determine type and one/many (count) for reverse relationship
                switch ($relationship['type']) {

                    case GENERATOR::RELATIONSHIP_HAS_ONE:
                    case GENERATOR::RELATIONSHIP_HAS_MANY:
                        $reverseType  = GENERATOR::RELATIONSHIP_BELONGS_TO;
                        break;

                    case GENERATOR::RELATIONSHIP_BELONGS_TO:
                        // for single-entry modules, a hasOne will do
                        if ($this->rawData['modules'][ $modelFromKey ]['max_entries'] == 1) {
                            $reverseType = GENERATOR::RELATIONSHIP_HAS_ONE;
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

                // name of the relationship reversed
                // default is name of the model referred to
                // self-referencing is an exception, since using the model name won't be useful
                if ($selfReference) {
                    $reverseName = $relationName . config('pxlcms.generator.relationship_reverse_postfix', 'Reverse');
                } else {
                    $reverseName = camel_case($modelFrom['name']);
                }

                $reverseNameSnakeCase = snake_case($reverseName);

                // if already taken by an attribute, add custom string
                if (    in_array($reverseNameSnakeCase, $this->output['models'][ $relationship['model'] ]['normal_attributes'])
                    ||  in_array($reverseNameSnakeCase, $this->output['models'][ $relationship['model'] ]['translated_attributes'])
                    ||  in_array($reverseName, $this->output['models'][ $relationship['model'] ]['relationships']['normal'])
                    ||  in_array($reverseName, $this->output['models'][ $relationship['model'] ]['relationships']['image'])
                    ||  in_array($reverseName, $this->output['models'][ $relationship['model'] ]['relationships']['file'])
                    ||  in_array($reverseName, $this->output['models'][ $relationship['model'] ]['relationships']['checkbox'])
                ) {
                    $reverseName .= config('pxlcms.generator.relationship_fallback_postfix', 'Reference');
                }


                $this->output['models'][ $relationship['model'] ]['relationships']['reverse'][$reverseName] = [
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
     * Returns data about resizes for an image field
     *
     * @param int $fieldId
     * @return array
     */
    protected function getImageResizesForField($fieldId)
    {
        if (    ! array_key_exists('resizes', $this->rawData['fields'][ $fieldId ])
            ||  ! count($this->rawData['fields'][ $fieldId ]['resizes'])
        ) {
            return [];
        }

        $resizes = [];

        foreach ($this->rawData['fields'][ $fieldId ]['resizes'] as $resizeId => $resize) {

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
     * Checks whether all required tables exist
     *
     * @throws Exception
     */
    protected function checkTablePresence()
    {
        $tables = DB::select('SHOW TABLES');

        $tableList = [];

        foreach ($tables as $tableObject) {

            $tableList[] = array_get(array_values((array) $tableObject), '0');
        }

        // check if relevant tables are present in the list
        foreach ([
                     config('pxlcms.tables.meta.modules'),
                     config('pxlcms.tables.meta.fields'),
                     config('pxlcms.tables.meta.field_options_choices'),
                     //config('pxlcms.tables.meta.groups'),
                     //config('pxlcms.tables.meta.sections'),
                     //config('pxlcms.tables.meta.field_types'),
                     //config('pxlcms.tables.meta.tabs'),
                     //config('pxlcms.tables.meta.users'),
                     //config('pxlcms.tables.meta.field_options_resizes'),

                     config('pxlcms.tables.languages'),
                     config('pxlcms.tables.categories'),
                     config('pxlcms.tables.files'),
                     config('pxlcms.tables.images'),
                     config('pxlcms.tables.references'),
                     config('pxlcms.tables.checkboxes'),
                     //config('pxlcms.tables.slugs'),

                 ] as $checkTable
        ) {
            if ( ! in_array($checkTable, $tableList)) {
                throw new Exception("Could not find expected CMS table in database: '{$checkTable}'");
            }
        }
    }

    /**
     * Loads and caches relevant raw data from cms module tables
     *
     * @throws Exception    if content is missing
     */
    protected function cacheData()
    {

        // ------------------------------------------------------------------------------
        //      Modules
        // ------------------------------------------------------------------------------

        $moduleData = DB::table(config('pxlcms.tables.meta.modules'))->get();

        if (empty($moduleData)) {
            throw new Exception("No module data found in database...");
        }

        foreach ($moduleData as $moduleObject) {

            $moduleArray = (array) $moduleObject;

            $this->rawData['modules'][ $moduleArray['id'] ] = [];

            foreach ([  'name', 'max_entries', 'is_custom',
                        'allow_create', 'allow_update', 'allow_delete',
                        'override_table_name', 'simulate_categories_for',
                     ] as $key
            ) {
                $this->rawData['modules'][ $moduleArray['id'] ][ $key ] = $moduleArray[ $key ];
            }

            $this->rawData['modules'][ $moduleArray['id'] ]['fields'] = [];

        }

        unset($moduleData, $moduleObject);


        // ------------------------------------------------------------------------------
        //      Fields
        // ------------------------------------------------------------------------------

        $fieldData = DB::table(config('pxlcms.tables.meta.fields'))->get();

        if (empty($fieldData)) {
            throw new Exception("No field data found in database...");
        }

        foreach ($fieldData as $fieldObject) {

            $fieldArray = (array) $fieldObject;
            $fieldId  = $fieldArray['id'];
            $moduleId = $fieldArray['module_id'];

            $this->rawData['fields'][ $fieldId ] = [];

            foreach ([  'module_id', 'field_type_id',
                        'name', 'display_name',
                        'value_count', 'refers_to_module',
                        'multilingual', 'options',
                     ] as $key
            ) {
                $this->rawData['fields'][ $fieldId ][ $key ] = $fieldArray[ $key ];
            }

            $this->rawData['fields'][ $fieldId ]['field_type_name'] = $this->fieldType->getFriendlyNameForId(
                $this->rawData['fields'][ $fieldId ]['field_type_id']
            );

            $this->rawData['fields'][ $fieldId ]['resizes'] = [];

            // also save in modules
            $this->rawData['modules'][ $moduleId ]['fields'][ $fieldId ] = $this->rawData['fields'][ $fieldId ];
        }

        unset($fieldData, $fieldObject);


        // ------------------------------------------------------------------------------
        //      Resizes
        // ------------------------------------------------------------------------------

        $resizeData = DB::table(config('pxlcms.tables.meta.field_options_resizes'))->get();

        foreach ($resizeData as $resizeObject) {

            $resizeArray = (array) $resizeObject;
            $resizeId = $resizeArray['id'];
            $fieldId  = $resizeArray['field_id'];

            $this->rawData['resizes'][ $resizeId ] = [];

            foreach ([  'field_id', 'prefix',
                         'width', 'height',
                     ] as $key
            ) {
                $this->rawData['resizes'][ $resizeId ][ $key ] = $resizeArray[ $key ];
            }

            // also save in fields
            $this->rawData['fields'][ $fieldId ]['resizes'][ $resizeId ] = $this->rawData['resizes'][ $resizeId ];
        }

        unset($resizeData, $resizeObject);
    }


    /**
     * Converts a field name to the database column name based on CMS convention
     *
     * @param string $field
     * @return string
     */
    protected function fieldNameToDatabaseColumn($field)
    {
        return str_replace(' ', '_', trim(strtolower($field)));
    }

    /**
     * Returns the table prefix (cms_m#_ ...) for a module CMS table
     *
     * @param $moduleId
     * @return string
     */
    protected function getModuleTablePrefix($moduleId)
    {
        $moduleId = (int) $moduleId;

        return config('pxlcms.tables.prefix', 'cms_') . 'm' . $moduleId . '_';
    }


    /**
     * @param string $message
     * @param string $level
     */
    protected function log($message, $level = Generator::LOG_LEVEL_DEBUG)
    {
        $this->log[] = $message;

        event('pxlcms.logmessage', [ 'message' => $message, 'level' => $level ]);
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Get override configuration for a given model/module id
     *
     * @param int $moduleId
     * @return mixed
     */
    protected function getOverrideConfigForModel($moduleId)
    {
        return config('pxlcms.generator.override.models.' . (int) $moduleId, []);
    }
}
