<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

use Czim\PxlCms\Generator\Writer\CmsModelWriter;

/**
 * Should always be executed last, since only then do we know
 * what to include for the imports.
 */
class StubReplaceImportsAndTraits extends AbstractProcessStep
{

    protected function process()
    {
        $this->stubPregReplace('# *{{USETRAITS}}\n?#i', $this->getTraitsReplace())
             ->stubPregReplace('#{{USEIMPORTS}}\n?#i', $this->getImportsReplace());
    }


    /**
     * @return string
     */
    protected function getTraitsReplace()
    {
        $traits = [];

        if ($this->data['is_translated']) {
            $traits[] = 'Translatable';
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE;
        }

        if ($this->data['is_listified']) {
            $traits[] = 'Listify';
            $traits[] = 'ListifyConstructorTrait';
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_LISTIFY;
        }

        if ( ! $this->context->blockRememberableTrait) {
            $traits[] = 'Rememberable';
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE;
        }

        if ( ! count($traits)) return '';

        $lastIndex = count($traits) - 1;

        $replace = $this->tab() . 'use ';

        foreach ($traits as $index => $trait) {

            $replace .= ($index > 0 ? $this->tab(2) : null)
                . $trait
                . ($index == $lastIndex ? ";\n" : ',')
                . "\n";
        }

        return $replace;
    }


    /**
     * Returns the replacement for the use use-imports placeholder
     *
     * @return string
     */
    protected function getImportsReplace()
    {
        $imports = array_diff(
            [
                CmsModelWriter::IMPORT_TRAIT_LISTIFY,
                CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE,
                CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE,
            ],
            $this->context->importsNotUsed
        );


        // build up import lines
        $importLines = [
            config('pxlcms.generator.models.extend_model')
        ];


        if (config('pxlcms.generator.models.include_namespace_of_standard_models')) {

            if (in_array(CmsModelWriter::STANDARD_MODEL_CHECKBOX, $this->context->standardModelsUsed)) {
                $importLines[] = config('pxlcms.generator.standard_models.checkbox');
            }

            if (in_array(CmsModelWriter::STANDARD_MODEL_IMAGE, $this->context->standardModelsUsed)) {
                $importLines[] = config('pxlcms.generator.standard_models.image');
            }

            if (in_array(CmsModelWriter::STANDARD_MODEL_FILE, $this->context->standardModelsUsed)) {
                $importLines[] = config('pxlcms.generator.standard_models.file');
            }
        }


        if (in_array(CmsModelWriter::IMPORT_TRAIT_LISTIFY, $imports)) {
            $importLines[] = config('pxlcms.generator.models.traits.listify_constructor_fqn');
            $importLines[] = config('pxlcms.generator.models.traits.listify_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE, $imports)) {
            $importLines[] = config('pxlcms.generator.models.traits.translatable_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE, $imports)) {
            $importLines[] = config('pxlcms.generator.models.traits.rememberable_fqn');
        }


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
