<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

class StubReplaceSimple extends AbstractProcessStep
{

    protected function process()
    {
        $name = $this->data->name;

        $class = str_replace($this->context->getNamespace($name) . '\\', '', $name);

        $extends = $this->context->getModelNameFromNamespace(config('pxlcms.generator.models.extend_model'));

        $this->determineIfModelIsSluggable();

        $this->stubReplace('{{MODEL_CLASSNAME}}', studly_case($class))
             ->stubReplace('{{NAMESPACE}}', $this->context->getNamespace( $this->context->fqnName ))
             ->stubReplace('{{EXTENDS}}', $extends)
             ->stubPregReplace('#\s*{{IMPLEMENTS}}#i', $this->getImplementsReplace())
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

    /**
     * @return string
     */
    protected function getImplementsReplace()
    {
        if ( ! $this->context->modelIsSluggable && ! $this->context->modelIsParentOfSluggableTranslation) return '';

        // if the model is sluggable (or the parent of), it needs to implement the interface

        return ' implements '
              . $this->context->getModelNameFromNamespace(
                    config('pxlcms.generator.models.slugs.sluggable_interface')
                );
    }

    /**
     * Determines and stores whether model itself is sluggable
     * If so, it will have both the trait and implement the interface
     */
    protected function determineIfModelIsSluggable()
    {
        $this->context->modelIsSluggable                    = false;
        $this->context->modelIsParentOfSluggableTranslation = false;

        // if the model is sluggable and not translated, or this is the actual translation
        if ($this->data['sluggable']) {

            if (    ! isset($this->data->sluggable_setup['translated'])
                ||  ! $this->data->sluggable_setup['translated']
                ||  $this->data->is_translation
            ) {

                $this->context->modelIsSluggable = true;

            } else {
                // this is the parent of a translated sluggable model

                $this->context->modelIsParentOfSluggableTranslation = true;
            }
        }
    }
}
