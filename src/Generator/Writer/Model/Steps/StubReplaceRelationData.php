<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Generator\Writer\Model\CmsModelWriter;
use Czim\PxlCms\Models\CmsModel;

class StubReplaceRelationData extends AbstractProcessStep
{
    /**
     * Name of the relationship to the standard CMS Category (not always required)
     * this will be a safe, nonconflicting name to use
     *
     * @var string
     */
    protected $categoryRelationName;


    protected function process()
    {
        $this->normalizeRelationsData()
             ->prepareForCategoryRelation();

        $this->stubPregReplace('# *{{RELATIONSCONFIG}}\n?#i', $this->getRelationsConfigReplace())
             ->stubPregReplace('# *{{RELATIONSHIPS}}\n?#i', $this->getRelationshipsReplace());
    }


    /**
     * Makes sure that expected relations data is traversable
     *
     * @return $this
     */
    protected function normalizeRelationsData()
    {
        if (is_null($this->data['relationships'])) {
            $this->data['relationships'] = [];
        }

        foreach ([ 'normal', 'reverse', 'image', 'file', 'checkbox', 'category' ] as $key) {

            if (    ! array_key_exists($key, $this->data['relationships'])
                ||  ! is_array($this->data['relationships'][ $key ])
            ) {
                $this->data->relationships[ $key ] = [];
            }
        }

        return $this;
    }

    /**
     * Prepares data & name for category relation (if required)
     *
     * @return $this
     */
    protected function prepareForCategoryRelation()
    {
        // also handle category relation name, if necessary
        if ($this->data['has_categories']) {

            $this->determineCategoryRelationName();

            // store the category name in the data
            if ( ! empty($this->categoryRelationName)) {
                $this->data->relationships['category'][ $this->categoryRelationName ] = [
                    'model' => config('pxlcms.generator.standard_models.category'),
                    'type'  => Generator::RELATIONSHIP_BELONGS_TO,
                ];
            }
        }

        return $this;
    }


    /**
     * Returns the replacement for the relations config placeholder
     *
     * @return string
     */
    protected function getRelationsConfigReplace()
    {
        $relationships = $this->collectRelationshipDataForConfig();

        if ( ! count($relationships)) return '';


        $replace = $this->tab() . "protected \$relationsConfig = [\n";

        foreach ($relationships as $name => $relationship) {

            $replace .= $this->tab(2) . "'" . $name . "' => [\n";

            $rows = [];

            if (array_get($relationship, 'field')) {
                $rows['field'] = $relationship['field'];
            }

            if (array_get($relationship, 'special')) {
                $rows['type'] = $relationship['specialString'];
            } else {
                $rows['parent'] = ($relationship['reverse'] ? 'false' : 'true');
            }

            if (array_get($relationship, 'translated') === true) {
                $rows['translated'] = 'true';
            }

            $longestPropertyLength = $this->getLongestKey($rows);

            foreach ($rows as $property => $value) {

                $replace .= $this->tab(3) . "'"
                    . str_pad($property . "'", $longestPropertyLength + 1)
                    . " => {$value},\n";
            }


            $replace .= $this->tab(2) . "],\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }

    /**
     * @return array
     */
    protected function collectRelationshipDataForConfig()
    {
        $relationships = [];

        foreach ($this->data['relationships']['normal'] as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO_MANY) continue;

            $relationship['reverse'] = true;
            $relationships[ $name ]  = $relationship;
        }

        foreach ($this->data['relationships']['reverse'] as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO_MANY) continue;

            $relationship['reverse'] = false;
            $relationships[ $name ]  = $relationship;
        }

        foreach ($this->data['relationships']['image'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_IMAGE;
            $relationship['specialString'] = 'self::RELATION_TYPE_IMAGE';
            $relationships[ $name ] = $relationship;
        }

        foreach ($this->data['relationships']['file'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_FILE;
            $relationship['specialString'] = 'self::RELATION_TYPE_FILE';
            $relationships[ $name ] = $relationship;
        }

        foreach ($this->data['relationships']['checkbox'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_CHECKBOX;
            $relationship['specialString'] = 'self::RELATION_TYPE_CHECKBOX';
            $relationships[ $name ] = $relationship;
        }

        foreach ($this->data['relationships']['category'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_CATEGORY;
            $relationship['specialString'] = 'self::RELATION_TYPE_CATEGORY';
            $relationships[ $name ] = $relationship;
        }

        return $relationships;
    }


    /**
     * Returns the replacement for the relationships placeholder
     *
     * @return string
     */
    protected function getRelationshipsReplace()
    {
        $totalCount = $this->getTotalRelevantRelationshipCount();

        if ( ! $totalCount) return '';

        $replace = "\n" . $this->getRelationshipsIntro();


        $replace .= $this->getReplaceForNormalRelationships()
                  . $this->getReplaceForSpecialRelationships();

        return $replace;
    }

    /**
     * @return array
     */
    protected function getCombinedRelationships()
    {
        return array_merge(
            $this->data['relationships']['normal'],
            $this->data['relationships']['reverse']
        );
    }

    /**
     * Returns the number of relationships to be considered
     * for building up the stub replacement.
     *
     * @return mixed
     */
    protected function getTotalRelevantRelationshipCount()
    {
        return count($this->getCombinedRelationships())
             + count( $this->data['relationships']['image'] )
             + count( $this->data['relationships']['file'] )
             + count( $this->data['relationships']['checkbox'] )
             + count( $this->data['relationships']['category'] );
    }

    /**
     * @return string
     */
    protected function getRelationshipsIntro()
    {
        return $this->tab() . "/*\n"
             . $this->tab() . " * Relationships\n"
             . $this->tab() . " */\n\n";
    }

    /**
     * @return string
     */
    protected function getReplaceForNormalRelationships()
    {
        $replace = '';

        $relationships = $this->getCombinedRelationships();

        foreach ($relationships as $name => $relationship) {

            $relatedClassName = studly_case($this->data['related_models'][ $relationship['model'] ]['name']);

            $data = array_merge(
                $relationship,
                [
                    'related_class'       => $relatedClassName,
                ]
            );

            $replace .= $this->buildReplaceForRelationship($name, $data);
        }

        return $replace;
    }

    /**
     * @param string $name
     * @param array  $data
     * @return string
     */
    protected function buildReplaceForRelationship($name, array $data)
    {
        $parameters         = array_get($data, 'parameters', '');
        $relationMethod     = array_get($data, 'type');
        $relationModel      = array_get($data, 'related_class');

        $relationParameters = '';
        if ($relationKey = array_get($data, 'key')) {
            $relationParameters = ", '{$relationKey}'";
        }

        return $this->tab() . "public function {$name}({$parameters})\n"
             . $this->tab() . "{\n"
             . $this->tab(2) . "return \$this->{$relationMethod}"
             . "({$relationModel}::class"
             . $relationParameters
             . ");\n"
             . $this->tab() . "}\n"
             . "\n";
    }

    /**
     * @return string
     */
    protected function getReplaceForSpecialRelationships()
    {
        $replace = '';

        /*
         * Images
         */

        $imageRelationships = $this->data['relationships']['image'] ?: [];

        if (count($imageRelationships)) {
            $this->context->standardModelsUsed[] = CmsModelWriter::STANDARD_MODEL_IMAGE;
            $replace .= $this->getRelationMethodSection($imageRelationships, CmsModel::RELATION_TYPE_IMAGE);
        }

        /*
         * Files
         */

        $fileRelationships = $this->data['relationships']['file'];

        if (count($fileRelationships)) {
            $this->context->standardModelsUsed[] = CmsModelWriter::STANDARD_MODEL_FILE;
            $replace .= $this->getRelationMethodSection($fileRelationships, CmsModel::RELATION_TYPE_FILE);
        }

        /*
         * Checkboxes
         */

        $checkboxRelationships = $this->data['relationships']['checkbox'];

        if (count($checkboxRelationships)) {
            $this->context->standardModelsUsed[] = CmsModelWriter::STANDARD_MODEL_CHECKBOX;
            $replace .= $this->getRelationMethodSection($checkboxRelationships, CmsModel::RELATION_TYPE_CHECKBOX);
        }

        /*
         * Category
         */

        $categoryRelationships = $this->data['relationships']['category'];

        if (count($categoryRelationships)) {
            $this->context->standardModelsUsed[] = CmsModelWriter::STANDARD_MODEL_CATEGORY;
            $replace .= $this->getRelationMethodSection($categoryRelationships, CmsModel::RELATION_TYPE_CATEGORY);
        }

        return $replace;
    }



    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

    /**
     * Attempts to determine a non-conflicting name for the relationship to the Category model
     *
     * @return string|false
     */
    protected function determineCategoryRelationName()
    {
        // pick a name and see if it conflicts with anything
        $tryNames = config('pxlcms.generator.standard_models.category_relation_names', []);

        $this->categoryRelationName = null;

        foreach ($tryNames as $tryName) {

            $tryName = trim($tryName);

            if ( ! $this->doesRelationNameConflict($tryName)) {

                $this->categoryRelationName = $tryName;
                break;
            }
        }

        if (empty($this->categoryRelationName)) {
            $this->context->log(
                "Unable to find a non-conflicting category relation name for model #{$this->data->module}. "
                . "Relation omitted.",
                Generator::LOG_LEVEL_ERROR
            );
        }

        return $this->categoryRelationName;
    }

    /**
     * Returns whether a given name is already in use by anything that it would conflict with
     * for this model
     *
     * @param string $name
     * @return bool
     */
    protected function doesRelationNameConflict($name)
    {
        // relationships
        $relationships = array_merge(
            $this->data['relationships']['normal'],
            $this->data['relationships']['reverse'],
            $this->data['relationships']['image'],
            $this->data['relationships']['file'],
            $this->data['relationships']['checkbox']
        );

        if (array_key_exists($name, $relationships)) return true;

        // attributes
        $attributes = array_merge(
            $this->data->normal_attributes,
            $this->data->translated_attributes
        );

        if (in_array(snake_case($name), $attributes)) return true;

        return false;
    }
    
    /**
     * Returns stub section for relation method
     *
     * @param array    $relationships
     * @param int|null $type            CmsModel::RELATION_TYPE_...
     * @return string
     */
    protected function getRelationMethodSection(array $relationships, $type = CmsModel::RELATION_TYPE_MODEL)
    {
        $replace = '';

        $relatedClassName = $this->context->getModelNamespaceForSpecialModel($type);

        foreach ($relationships as $name => $relationship) {

            $relationParameters       = '';
            $relationMethodParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            if (    array_get($relationship, 'translated')
                &&  $type !== CmsModel::RELATION_TYPE_MODEL
                &&  config('pxlcms.generator.models.allow_locale_override_on_translated_model_relation')
            ) {
                $relationMethodParameters = '$locale = null';

                // skip parameters not entered, pass on the optional locale key
                $relationParameters = (substr_count($relationParameters, ',') ? null : ', null')
                                    . ', null'
                                    . ', $locale';
            }

            $replace .= $this->tab() . "public function {$name}({$relationMethodParameters})\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . $this->tab() . "}\n"
                      . "\n";


            if ($type == CmsModel::RELATION_TYPE_IMAGE) {
                // since images require special attention for resize enrichment,
                // add an accessor method that will take care of it (through some magic)
                $replace .= $this->tab() . "public function get" . studly_case($name) . "Attribute()\n"
                          . $this->tab() . "{\n"
                          . $this->tab(2) . "return \$this->getImagesWithResizes();\n"
                          . $this->tab() . "}\n"
                          . "\n";
            }

        }

        return $replace;
    }

}
