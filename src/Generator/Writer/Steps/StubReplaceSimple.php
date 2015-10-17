<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

class StubReplaceSimple extends AbstractProcessStep
{

    protected function process()
    {
        $name = $this->data->name;

        $class = str_replace($this->context->getNamespace($name) . '\\', '', $name);

        $extends = $this->context->getModelNameFromNamespace(config('pxlcms.generator.models.extend_model'));


        $this->stubReplace('{{MODEL_CLASSNAME}}', studly_case($class))
             ->stubReplace('{{NAMESPACE}}', $this->context->getNamespace( $this->context->fqnName ))
             ->stubReplace('{{EXTENDS}}', $extends)
             ->stubReplace('{{MODULE_NUMBER}}', $this->data['module'])
             ->stubPregReplace('# *{{TABLE}}\n?#i', $this->getTableReplace())
             ->stubPregReplace('# *{{TIMESTAMPS}}\n?#i', $this->getTimestampReplace());

    }


    /**
     * @return string
     */
    protected function getTableReplace()
    {
        $table = $this->data['table'];

        if (empty($table)) return '';

        return "\n"
            . $this->tab()
            . "protected \$table = '" . $table . "';\n";
    }

    /**
     * @return string
     */
    protected function getTimestampReplace()
    {
        if (is_null($this->data['timestamps'])) return '';

        return "\n"
            . $this->tab()
            . "public \$timestamps = "
            . ($this->data['timestamps'] ? 'true' : 'false')
            .";\n";
    }
}
