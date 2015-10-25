<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

class StubReplaceSluggableData extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{SLUGGABLECONFIG}}\n?#i', $this->getSluggableConfigReplace());
    }


    /**
     * Returns the replacement for the sluggable config placeholder
     *
     * @return string
     */
    protected function getSluggableConfigReplace()
    {
        $setup = $this->data->sluggable_setup;

        if ( ! $this->context->modelIsSluggable || ! $this->data->sluggable || empty($setup)) return '';


        $replace = $this->tab() . "protected \$sluggable = [\n";

        $rows = [];

        if (array_get($setup, 'source')) {
            $rows['build_from'] = "'" . $setup['source'] . "'";
        }

        if (config('pxlcms.generator.models.slugs.resluggify_on_update')) {
            $rows['on_update'] = 'true';
        }

        // for now we don't support the locale key, all translated
        // tables will have a language_id column in the old CMS.
        if (array_get($setup, 'translated') === true) {
            $rows['language_key'] = "'" . config('pxlcms.slugs.keys.language', 'language_id') . "'";
            $rows['locale_key']   = 'null';
        }

        $longestPropertyLength = $this->getLongestKey($rows);

        foreach ($rows as $property => $value) {

            $replace .= $this->tab(2) . "'"
                . str_pad($property . "'", $longestPropertyLength + 1)
                . " => {$value},\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
    }

}
