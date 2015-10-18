<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Generator\Writer\CmsModelWriter;

class StubReplaceDocBlock extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('#{{DOCBLOCK}}\n?#i', $this->getDocBlockReplace());
    }


    /**
     * Returns the replacement for the docblock placeholder
     *
     * @return string
     */
    protected function getDocBlockReplace()
    {
        // For now, docblocks will only contain the ide-helper content

        if ( ! config('pxlcms.generator.models.ide_helper.add_docblock')) return '';

        $rows = [];

        /*
         * Direct Attributes (and translated
         */

        $attributes = array_merge(
            $this->data['normal_fillable'] ?: [],
            $this->data['translated_fillable'] ?: []
        );

        if (config('pxlcms.generator.models.ide_helper.tag_attribute_properties')) {

            foreach ($attributes as $attribute) {

                // in some CMSes, column names pop up that start with a digit
                // since this is not allowed as a property or variable
                // (only as a variable variable, ${'4life'}), tags for these will NOT
                // be added.
                if (preg_match('#^\d#', $attribute)) {

                    $this->context->log(
                        "Not adding @property tag to DocBlock for attribute '{$attribute}'"
                        . " (module #{$this->data->module}).",
                        Generator::LOG_LEVEL_WARNING
                    );
                    continue;
                }


                // determine type for property

                if (in_array($attribute, $this->data['dates'] ?: [])) {
                    // could be a date, in which case it is (probably) Carbon
                    $type = config('pxlcms.generator.models.date_property_fqn', '\\Carbon\\Carbon');

                } else {
                    // otherwise, check the casts to termine the type

                    switch (array_get($this->data['casts'],  $attribute)) {

                        case 'boolean':
                        case 'integer':
                        case 'float':
                            $type = array_get($this->data['casts'], $attribute);
                            break;

                        case 'array':
                        case 'json':
                            $type = 'array';
                            break;


                        case 'string':
                        default:
                            // todo: consider better fallback approach with 'mixed' where unknown
                            $type = 'string';
                    }
                }

                $rows[] = [
                    'tag'  => 'property',
                    'type' => $type,
                    'name' => '$' . $attribute,
                ];
            }
        }

        /*
         * Relationships (normal)
         */

        // we can trust these, because they were normalized in the relationsdata step
        $relationships = array_merge(
            $this->data['relationships']['normal'],
            $this->data['relationships']['reverse'],
            $this->data['relationships']['image'],
            $this->data['relationships']['file'],
            $this->data['relationships']['checkbox']
        );

        if (config('pxlcms.generator.models.ide_helper.tag_relationship_magic_properties')) {

            foreach ($relationships as $relationName => $relationship) {

                // special relationships have defined model names, otherwise use the relation model name
                $relatedClassName = ($specialType = (int) array_get($relationship, 'special'))
                    ?   $this->context->getModelNamespaceForSpecialModel($specialType)
                    :   studly_case( $this->data['related_models'][ $relationship['model'] ]['name'] );

                // single relationships returns a single of the related model type
                // multiples return collections/arrays with related model type entries
                if ($relationship['count'] == 1) {
                    $type = $relatedClassName;
                } else {
                    $type = CmsModelWriter::FQN_FOR_COLLECTION . '|' . $relatedClassName . '[]';
                }

                // the read-only magic property for the relation
                $rows[] = [
                    'tag'  => 'property-read',
                    'type' => $type,
                    'name' => '$' . $relationName,
                ];
            }
        }


        /*
         * Magic static methods (where<Attribute>)
         */

        if (config('pxlcms.generator.models.ide_helper.tag_magic_where_methods_for_attributes')) {

            foreach ($attributes as $attribute) {

                $rows[] = [
                    'tag'  => 'method',
                    'type' => 'static ' . CmsModelWriter::FQN_FOR_BUILDER . '|' . studly_case($this->data['name']),
                    'name' => 'where' . studly_case($attribute) . '($value)',
                ];
            }
        }

        /*
         * Scopes
         */

        $scopes = [];

        if ($this->useScopeActive()) {
            $scopes[] = [
                'name'       => config('pxlcms.generator.models.scopes.only_active_method'),
                'parameters' => [],
            ];
        }

        if ($this->useScopePosition()) {
            $scopes[] = [
                'name'       => config('pxlcms.generator.models.scopes.position_order_method'),
                'parameters' => [],
            ];
        }

        foreach ($scopes as $scope) {

            $rows[] = [
                'tag'  => 'method',
                'type' => 'static ' . CmsModelWriter::FQN_FOR_BUILDER . '|' . studly_case($this->data['name']),
                'name' => camel_case($scope['name']) . '($query)',
            ];
        }


        if ( ! count($rows)) return '';

        $replace = "/**\n";

        foreach ($rows as $row) {

            $replace .= ' * '
                      . "@{$row['tag']} "
                      . "{$row['type']} "
                      . "{$row['name']}\n";
        }

        $replace .= " */\n";

        return $replace;
    }


    /**
     * Returns whether we're using a global scope for active
     *
     * @return bool
     */
    protected function useScopeActive()
    {
        if (is_null($this->data['scope_active'])) {
            return config('pxlcms.generator.models.scopes.only_active') === CmsModelWriter::SCOPE_METHOD;
        }

        return (bool) $this->data['scope_active'];
    }

    /**
     * Returns whether we're using a global scope for position
     *
     * @return bool
     */
    protected function useScopePosition()
    {
        if (is_null($this->data['scope_position'])) {
            return config('pxlcms.generator.models.scopes.position_order') === CmsModelWriter::SCOPE_METHOD;
        }

        return (bool) $this->data['scope_position'];
    }
}
