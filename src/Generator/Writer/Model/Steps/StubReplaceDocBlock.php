<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Generator\Writer\Model\CmsModelWriter;

class StubReplaceDocBlock extends AbstractProcessStep
{

    /**
     * @var array
     */
    protected $combinedAttributes = [];


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

        if ( ! $this->isDocBlockRequired()) return '';

        $rows = $this->collectDocBlockPropertyRows();


        if ( ! count($rows)) return '';

        $replace = "/**\n"
                 . $this->getDocBlockIntro();

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
     * @return string
     */
    protected function getDocBlockIntro()
    {
        $content = $this->getDocBlockClassLine();

        // only add empty line if we have any sort of intro
        if ( ! empty($content)) {
            $content .= " *\n";
        }

        return $content;
    }

    /**
     * @return string
     */
    protected function getDocBlockClassLine()
    {
        $class = studly_case(
            str_replace(
                $this->context->getNamespace($this->data->name) . '\\',
                '',
                $this->data->name
            )
        );

        return " * Class {$class}\n";
    }

    /**
     * @return array
     */
    protected function collectDocBlockPropertyRows()
    {
        return $this->collectIdeHelperRows();
    }

    /**
     * @return array
     */
    protected function collectIdeHelperRows()
    {
        $this->combinedAttributes = array_merge(
            $this->data['normal_fillable']     ?: [],
            $this->data['translated_fillable'] ?: []
        );

        return array_merge(
            $this->collectIdeHelperDirectAttributeRows(),
            $this->collectIdeHelperRelationRows(),
            $this->collectIdeHelperMagicPropertyRows(),
            $this->collectIdeHelperScopeRows()
        );
    }

    /**
     * @return array
     */
    protected function collectIdeHelperDirectAttributeRows()
    {
        if ( ! config('pxlcms.generator.models.ide_helper.tag_attribute_properties')) {
            return [];
        }

        $rows = [];

        foreach ($this->combinedAttributes as $attribute) {

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

        return $rows;
    }

    /**
     * @return array
     */
    protected function collectIdeHelperRelationRows()
    {
        if ( ! config('pxlcms.generator.models.ide_helper.tag_relationship_magic_properties')) {
            return [];
        }

        // we can trust these, because they were normalized in the relationsdata step
        $relationships = array_merge(
            $this->data['relationships']['normal'],
            $this->data['relationships']['reverse'],
            $this->data['relationships']['image'],
            $this->data['relationships']['file'],
            $this->data['relationships']['checkbox']
        );

        $rows = [];

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

        return $rows;
    }

    /**
     * @return array
     */
    protected function collectIdeHelperMagicPropertyRows()
    {
        $rows = [];

        if (config('pxlcms.generator.models.ide_helper.tag_magic_where_methods_for_attributes')) {

            foreach ($this->combinedAttributes as $attribute) {

                $rows[] = [
                    'tag'  => 'method',
                    'type' => 'static ' . CmsModelWriter::FQN_FOR_BUILDER . '|' . studly_case($this->data['name']),
                    'name' => 'where' . studly_case($attribute) . '($value)',
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array
     */
    protected function collectIdeHelperScopeRows()
    {
        $rows   = [];
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

        // special scope for sluggable
        if ($this->context->modelIsSluggable || $this->context->modelIsParentOfSluggableTranslation) {
            $scopes[] = [
                'name'       => 'whereSlug',
                'parameters' => (array_get($this->data['sluggable_setup'], 'translated'))
                    ?   [ '$slug', '$locale = null' ]
                    :   [ '$slug' ],
            ];
        }

        foreach ($scopes as $scope) {

            $rows[] = [
                'tag'  => 'method',
                'type' => 'static ' . CmsModelWriter::FQN_FOR_BUILDER . '|' . studly_case($this->data['name']),
                'name' => camel_case($scope['name'])
                    . '('
                    . (count($scope['parameters']) ? implode(', ', $scope['parameters']) : '')
                    . ')',
            ];
        }

        return $rows;
    }


    /**
     * @return bool
     */
    protected function isDocBlockRequired()
    {
        return config('pxlcms.generator.models.ide_helper.add_docblock', false);
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
