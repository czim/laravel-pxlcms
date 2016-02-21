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

        $accessors = [];

        // for belongs-to relationships that share foreign key & relation name

        foreach ($this->data['relationships']['normal'] as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO) continue;
            if ( ! empty($relationship['key']) && snake_case($name) !== $relationship['key']) continue;

            $attributeName = snake_case($name);

            $content = $this->tab(2) . "return \$this->getBelongsToRelationAttributeValue('{$name}');\n";

            $accessors[ $name ] = [
                'content' => $content,
            ];
        }


        if ( ! count($accessors)) return '';


        $replace = "\n"
                 . $this->tab() . "/*\n"
                 . $this->tab() . " * Accessors & Mutators\n"
                 . $this->tab() . " */\n\n";


        foreach ($accessors as $name => $accessor) {

            $replace .= $this->tab() . "public function get" . studly_case($name) . "Attribute()\n"
                      . $this->tab() . "{\n"
                      . $accessor['content']
                      . $this->tab() . "}\n"
                      . "\n";
        }

        return $replace;
    }

}
