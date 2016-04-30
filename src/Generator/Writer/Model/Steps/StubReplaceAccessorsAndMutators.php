<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Generator;

class StubReplaceAccessorsAndMutators extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{ACCESSORS}}\n?#i', $this->getAccessorsReplace());
    }


    /**
     * @return string
     */
    protected function getAccessorsReplace()
    {
        $accessors = $this->collectAccessors();

        if ( ! count($accessors)) return '';


        $replace = "\n" . $this->getAccessorReplaceIntro();

        foreach ($accessors as $name => $accessor) {

            $parameterString = '';
            $parameters = array_get($accessor, 'parameters', []);

            if (count($parameters)) {
                $parameterString = implode(', ', $parameters);
            }

            $replace .= $this->tab() . "public function get" . studly_case($name) . "Attribute({$parameterString})\n"
                      . $this->tab() . "{\n"
                      . $accessor['content']
                      . $this->tab() . "}\n"
                      . "\n";
        }

        return $replace;
    }

    /**
     * @return string
     */
    protected function getAccessorReplaceIntro()
    {
        return $this->tab() . "/*\n"
             . $this->tab() . " * Accessors & Mutators\n"
             . $this->tab() . " */\n\n";
    }

    /**
     * @return array    name => array with properties
     */
    protected function collectAccessors()
    {
        return $this->collectNormalBelongsToRelationAccessors();
    }

    /**
     * For belongs-to relationships that share foreign key & relation name
     *
     * @return array    name => array with properties
     */
    protected function collectNormalBelongsToRelationAccessors()
    {
        $accessors = [];

        foreach ($this->data['relationships']['normal'] as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO) continue;
            if ( ! empty($relationship['key']) && snake_case($name) !== $relationship['key']) continue;

            $attributeName = snake_case($name);

            $content = $this->tab(2) . "return \$this->getBelongsToRelationAttributeValue('{$name}');\n";

            $accessors[ $name ] = [
                'attribute' => $attributeName,
                'content'   => $content,
            ];
        }

        return $accessors;
    }

}
