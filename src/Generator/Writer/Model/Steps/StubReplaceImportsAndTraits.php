<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Writer\Model\CmsModelWriter;

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
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.translatable_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE;
        }

        if ($this->data['is_listified']) {
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.listify_fqn')
            );
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.listify_constructor_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_LISTIFY;
        }

        if ( ! $this->context->blockRememberableTrait) {
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.rememberable_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE;
        }

        // scopes

        if ($this->useScopeActive()) {
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.scope_active_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE;
        }

        if ($this->useScopePosition()) {
            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.traits.scope_position_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER;
        }

        // sluggable?
        if ($this->context->modelIsSluggable) {

            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.slugs.sluggable_trait')
            );

        } elseif ($this->context->modelIsParentOfSluggableTranslation) {

            $traits[] = $this->context->getModelNameFromNamespace(
                config('pxlcms.generator.models.slugs.sluggable_translated_trait')
            );
        }

        if ( ! count($traits)) return '';


        // set them in the right order
        if (config('pxlcms.generator.aesthetics.sort_imports_by_string_length')) {

            // sort from shortest to longest
            usort($traits, function ($a, $b) {
                return strlen($a) - strlen($b);
            });

        } else {
            sort($traits);
        }


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
                CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE,
                CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER,
            ],
            $this->context->importsNotUsed
        );


        // build up import lines
        $importLines = [
            config('pxlcms.generator.models.extend_model')
        ];


        if (config('pxlcms.generator.models.include_namespace_of_standard_models')) {

            if (in_array(CmsModelWriter::STANDARD_MODEL_CATEGORY, $this->context->standardModelsUsed)) {
                $importLines[] = config('pxlcms.generator.standard_models.category');
            }

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

        // scopes

        if (in_array(CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE, $imports)) {
            $importLines[] = config('pxlcms.generator.models.traits.scope_active_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER, $imports)) {
            $importLines[] = config('pxlcms.generator.models.traits.scope_position_fqn');
        }

        // sluggable

        if ($this->context->modelIsSluggable) {

            $importLines[] = config('pxlcms.generator.models.slugs.sluggable_interface');
            $importLines[] = config('pxlcms.generator.models.slugs.sluggable_trait');

        } elseif($this->context->modelIsParentOfSluggableTranslation) {

            $importLines[] = config('pxlcms.generator.models.slugs.sluggable_interface');
            $importLines[] = config('pxlcms.generator.models.slugs.sluggable_translated_trait');
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


    /**
     * Returns whether we're using a global scope for active
     *
     * @return bool
     */
    protected function useScopeActive()
    {
        if (is_null($this->data['scope_active'])) {
            return config('pxlcms.generator.models.scopes.only_active') === CmsModelWriter::SCOPE_GLOBAL;
        }

        return (bool) $this->data['scope_active'];
    }

    /**
     * Returns whether we're using a global scope for position
     *
     * @return bool
     */
    protected function useScopePosition()
    {
        if (is_null($this->data['scope_position'])) {
            return config('pxlcms.generator.models.scopes.position_order') === CmsModelWriter::SCOPE_GLOBAL;
        }

        return (bool) $this->data['scope_position'];
    }
}
