<?php
namespace Czim\PxlCms\Generator\Writer\Repository\Steps;


/**
 * Should always be executed last, since only then do we know
 * what to include for the imports.
 */
class StubReplaceImportsAndTraits extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{USETRAITS}}\n?#i', "\n")
             ->stubPregReplace('#{{USEIMPORTS}}\n?#i', $this->getImportsReplace());
    }



    /**
     * Returns the replacement for the use use-imports placeholder
     *
     * @return string
     */
    protected function getImportsReplace()
    {

        // build up import lines
        $importLines = [
            config('pxlcms.generator.repositories.extend_class')
        ];

        // model classname
        $importLines[] = $this->context->makeFqnForModelName( studly_case($this->data->name) );;



        // set them in the right order
        if (config('pxlcms.generator.aesthetics.sort_imports_by_string_length')) {

            // sort from shortest to longest
            usort($importLines, function ($a, $b) {
                return strlen($a) - strlen($b);
            });

        } else {
            sort($importLines);
        }


        // build the actual replacement string
        $replace = "\n";

        foreach ($importLines as $line) {
            $replace .= "use " . $line . ";\n";
        }

        $replace .= "\n";

        return $replace;
    }

}
