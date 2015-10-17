<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Generator\Writer\CmsModelWriter;
use Czim\PxlCms\Models\CmsModel;

class StubReplaceRelationData extends AbstractProcessStep
{

    protected function process()
    {
        $this->normalizeRelationsData();

        $this->stubPregReplace('# *{{RELATIONSCONFIG}}\n?#i', $this->getRelationsConfigReplace())
             ->stubPregReplace('# *{{RELATIONSHIPS}}\n?#i', $this->getRelationshipsReplace());
    }


    /**
     * Makes sure that expected relations data is traversable
     */
    protected function normalizeRelationsData()
    {
        if (is_null($this->data['relationships'])) {
            $this->data['relationships'] = [];
        }

        foreach ([ 'normal', 'reverse', 'image', 'file', 'checkbox' ] as $key) {

            if (    ! array_key_exists($key, $this->data['relationships'])
                ||  ! is_array($this->data['relationships'][ $key ])
            ){
                $this->data['relationships'][ $key ] = [];
            }
        }
    }


    /**
     * Returns the replacement for the relations config placeholder
     *
     * @return string
     */
    protected function getRelationsConfigReplace()
    {
        // only for many to many relationships
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
            $relationship['specialString'] = "self::RELATION_TYPE_IMAGE";
            $relationships[ $name ]  = $relationship;
        }

        foreach ($this->data['relationships']['file'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_FILE;
            $relationship['specialString'] = "self::RELATION_TYPE_FILE";
            $relationships[ $name ]  = $relationship;
        }

        foreach ($this->data['relationships']['checkbox'] as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_CHECKBOX;
            $relationship['specialString'] = "self::RELATION_TYPE_CHECKBOX";
            $relationships[ $name ]  = $relationship;
        }

        if ( ! count($relationships)) return '';


        $replace = $this->tab() . "protected \$relationsConfig = [\n";

        foreach ($relationships as $name => $relationship) {

            $replace .= $this->tab(2) . "'" . $name . "' => [\n";

            $rows = [
                'field' => $relationship['field'],
            ];

            if (array_get($relationship, 'special')) {
                $rows['type'] = $relationship['specialString'];
            } else {
                $rows['parent'] = ($relationship['reverse'] ? 'false' : 'true');
            }

            if (isset($relationship['translated']) && $relationship['translated']) {
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
     * Returns the replacement for the relationships placeholder
     *
     * @return string
     */
    protected function getRelationshipsReplace()
    {
        $relationships = array_merge(
            $this->data['relationships']['normal'],
            $this->data['relationships']['reverse']
        );

        $totalCount = count($relationships)
            + count( $this->data['relationships']['image'] )
            + count( $this->data['relationships']['file'] )
            + count( $this->data['relationships']['checkbox'] );

        if ( ! $totalCount) return '';

        $replace = "\n" . $this->tab() . "/*\n"
            . $this->tab() . " * Relationships\n"
            . $this->tab() . " */\n\n";


        /*
         * Normal and Reversed relationships
         */

        foreach ($relationships as $name => $relationship) {

            $relatedClassName = studly_case($this->data['related_models'][ $relationship['model'] ]['name']);

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= $this->tab() . "public function {$name}()\n"
                . $this->tab() . "{\n"
                . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                . $relationParameters
                . ");\n"
                . $this->tab() . "}\n"
                . "\n";
        }

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


        return $replace;
    }


    // ------------------------------------------------------------------------------
    //      Helpers
    // ------------------------------------------------------------------------------

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
