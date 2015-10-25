<?php
namespace Czim\PxlCms\Generator\Writer\Repository\Steps;

class StubReplaceSimple extends AbstractProcessStep
{

    protected function process()
    {
        $name = $this->context->fqnName;

        $class = str_replace($this->context->getNamespace($name) . '\\', '', $name);

        $extends = $this->context->getClassNameFromNamespace(config('pxlcms.generator.repositories.extend_class'));

        $this->stubReplace('{{REPOSITORY_CLASSNAME}}', studly_case($class))
            ->stubReplace('{{NAMESPACE}}', $this->context->getNamespace($name))
            ->stubReplace('{{EXTENDS}}', $extends)
            ->stubPregReplace('#\s*{{IMPLEMENTS}}#i', '')
            ->stubPregReplace('#\s*{{DOCBLOCK}}#i', '')
            ->stubPregReplace('#\s*{{MODEL_CLASSNAME_METHOD}}#i', $this->getModelClassMethodReplace());
    }

    /**
     * @return string
     */
    protected function getModelClassMethodReplace()
    {
        $name = $this->data->name;

        $class = str_replace($this->context->getNamespace($name) . '\\', '', $name);

        return "\n"
             . $this->tab() . "public function model()\n"
             . $this->tab() . "{\n"
             . $this->tab(2) . "return " . studly_case($class) . "::class;\n"
             . $this->tab() . "}\n";
    }

}
