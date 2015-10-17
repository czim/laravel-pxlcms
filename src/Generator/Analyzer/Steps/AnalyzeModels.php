<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Czim\PxlCms\Generator\FieldType;
use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Models\CmsModel;
use Exception;

class AnalyzeModels extends AbstractProcessStep
{

    protected function process()
    {
        // build up a list of models
        foreach ($this->data->rawData['modules'] as $moduleId => $moduleData) {

            // custom modules don't have database content
            // or in any case it's impossible to be sure
            // so for now, ignore them
            if ($moduleData['is_custom']) {
                $this->context->log("Skipped custom module ({$moduleId}, '{$moduleData['name']}')");
                continue;
            }

            $overrides = $this->getOverrideConfigForModel($moduleId);

            if (array_key_exists('name', $overrides)) {

                $name = $overrides['name'];

            } else {

                $name = array_get($moduleData, 'prefixed_name') ?: $moduleData['name'];

                if (config('pxlcms.generator.models.model_name.singularize_model_names')) {
                    $name = str_singular($name);
                }
            }

            // make sure we force set a table name if it does not follow convention
            $tableOverride = null;
            if (str_plural($this->normalizeDb($name)) != $this->normalizeDb($moduleData['name'])) {
                $tableOverride = $this->getModuleTablePrefix($moduleId) . $this->normalizeDb($moduleData['name']);
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
                'cached'                => config('pxlcms.generator.models.enable_rememberable_cache'),
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

                $attributeName = $this->normalizeDb($fieldData['name']);
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
                        //if ($relationName !== studly_case($this->data->rawData['modules'][ $fieldData['refers_to_module'] ]['name'])) {
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
                            'special'  => CmsModel::RELATION_TYPE_MODEL,
                        ];

                        if (config('pxlcms.generator.models.hide_foreign_key_attributes')) {
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
                            'special'    => CmsModel::RELATION_TYPE_MODEL,
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
                            'special'    => CmsModel::RELATION_TYPE_IMAGE,
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
                            'special'    => CmsModel::RELATION_TYPE_FILE,
                        ];
                        break;

                    case FieldType::TYPE_CHECKBOX:
                        $model['relationships']['checkbox'][ $relationName ] = [
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

                        switch ($fieldData['field_type_id']) {

                            case FieldType::TYPE_BOOLEAN:
                                $model['casts'][ $attributeName ] = 'boolean';
                                break;

                            case FieldType::TYPE_INTEGER:
                            case FieldType::TYPE_SLIDER:
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
                    config('pxlcms.generator.models.default_hidden_fields', [])
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
            if (    config('pxlcms.generator.models.enable_timestamps_on_models_with_suitable_attributes')
                &&  in_array('created_at', $model['dates'])
                &&  in_array('updated_at', $model['dates'])
            ) {
                $model['timestamps'] = true;
            }

            $this->context->output['models'][ $moduleId ] = $model;
        }


        // ------------------------------------------------------------------------------
        //      Generate reversed relationships
        // ------------------------------------------------------------------------------

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

                    } elseif ($relationName !== camel_case($this->context->output['models'][ $relationship['model'] ]['name'])) {

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
                        if ($this->data->rawData['modules'][ $modelFromKey ]['max_entries'] == 1) {
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

                // pluralize the name if configured to and it's a to many relationship
                $pluralizeName = (  $reverseCount !== 1
                                &&  config('pxlcms.generator.models.pluralize_reversed_relationship_names')
                                &&  (   ! $selfReference
                                    ||  config('pxlcms.generator.models.pluralize_reversed_relationship_names_for_self_reference')
                                    )
                                );

                // name of the relationship reversed
                // default is name of the model referred to
                // self-referencing is an exception, since using the model name won't be useful
                if ($selfReference) {
                    $reverseName = ($pluralizeName ? str_plural($relationName) : $relationName)
                                 . config('pxlcms.generator.models.relationship_reverse_postfix', 'Reverse');
                } else {
                    $reverseName = camel_case(
                        $pluralizeName ? str_plural($modelFrom['name']) : $modelFrom['name']
                    );
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
     * Standard database field normalization
     *
     * @param string $string
     * @return string
     */
    protected function normalizeDb($string)
    {
        return $this->context->normalizeNameForDatabase($string);
    }
}
