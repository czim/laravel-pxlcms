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
        $traits = $this->collectTraits();

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
     * @return array
     */
    protected function collectTraits()
    {
        return array_merge(
            $this->collectTranslationTraits(),
            $this->collectListifyTraits(),
            $this->collectRememberableTraits(),
            $this->collectGlobalScopeTraits(),
            $this->collectSluggableTraits()
        );
    }

    /**
     * @return array
     */
    protected function collectTranslationTraits()
    {
        $traits = [];

        if ($this->data['is_translated']) {
            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.traits.translatable_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE;
        }

        return $traits;
    }

    /**
     * @return array
     */
    protected function collectListifyTraits()
    {
        $traits = [];

        if ($this->data['is_listified']) {
            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.traits.listify_fqn')
            );
            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.traits.listify_constructor_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_LISTIFY;
        }

        return $traits;
    }

    /**
     * @return array
     */
    protected function collectRememberableTraits()
    {
        $traits = [];

        if ( ! $this->context->blockRememberableTrait) {
            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.traits.rememberable_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE;
        }

        return $traits;
    }

    /**
     * @return array
     */
    protected function collectGlobalScopeTraits()
    {
        $traits = [];

        if ($this->useScopeActive()) {
            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.traits.scope_active_fqn')
            );
        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE;
        }

        if ($this->useScopePosition()) {

            if (count($this->data['ordered_by'])) {
                $traits[] = $this->context->getClassNameFromNamespace(
                    config('pxlcms.generator.models.traits.scope_cmsordered_fqn')
                );
            } else {
                $traits[] = $this->context->getClassNameFromNamespace(
                    config('pxlcms.generator.models.traits.scope_position_fqn')
                );
            }

        } else {
            $this->context->importsNotUsed[] = CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER;
        }

        return $traits;
    }

    /**
     * @return array
     */
    protected function collectSluggableTraits()
    {
        $traits = [];

        if ($this->context->modelIsSluggable) {

            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.slugs.sluggable_trait')
            );

        } elseif ($this->context->modelIsParentOfSluggableTranslation) {

            $traits[] = $this->context->getClassNameFromNamespace(
                config('pxlcms.generator.models.slugs.sluggable_translated_trait')
            );
        }

        return $traits;
    }

    /**
     * Returns the replacement for the use use-imports placeholder
     *
     * @return string
     */
    protected function getImportsReplace()
    {
        $importLines = $this->collectImportLines();

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
     * @return array
     */
    protected function getUsedOptionalImportKeys()
    {
        return array_diff(
            [
                CmsModelWriter::IMPORT_TRAIT_LISTIFY,
                CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE,
                CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE,
                CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE,
                CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER,
            ],
            $this->context->importsNotUsed
        );
    }

    /**
     * @return array
     */
    protected function collectImportLines()
    {
        return array_merge(
            $this->collectExtendedModelImportLines(),
            $this->collectSpecialModelsImportLines(),
            $this->collectOptionalTraitImportLines(),
            $this->collectScopeTraitImportLines(),
            $this->collectSluggableImportLines()
        );
    }

    /**
     * @return array
     */
    protected function collectExtendedModelImportLines()
    {
        return [
            config('pxlcms.generator.models.extend_model')
        ];
    }

    /**
     * @return array
     */
    protected function collectSpecialModelsImportLines()
    {
        $lines = [];

        if (config('pxlcms.generator.models.include_namespace_of_standard_models')) {

            if (in_array(CmsModelWriter::STANDARD_MODEL_CATEGORY, $this->context->standardModelsUsed)) {
                $lines[] = config('pxlcms.generator.standard_models.category');
            }

            if (in_array(CmsModelWriter::STANDARD_MODEL_CHECKBOX, $this->context->standardModelsUsed)) {
                $lines[] = config('pxlcms.generator.standard_models.checkbox');
            }

            if (in_array(CmsModelWriter::STANDARD_MODEL_IMAGE, $this->context->standardModelsUsed)) {
                $lines[] = config('pxlcms.generator.standard_models.image');
            }

            if (in_array(CmsModelWriter::STANDARD_MODEL_FILE, $this->context->standardModelsUsed)) {
                $lines[] = config('pxlcms.generator.standard_models.file');
            }
        }

        return $lines;
    }

    /**
     * @return array
     */
    protected function collectOptionalTraitImportLines()
    {
        $lines = [];

        $imports = $this->getUsedOptionalImportKeys();

        if (in_array(CmsModelWriter::IMPORT_TRAIT_LISTIFY, $imports)) {
            $lines[] = config('pxlcms.generator.models.traits.listify_constructor_fqn');
            $lines[] = config('pxlcms.generator.models.traits.listify_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_TRANSLATABLE, $imports)) {
            $lines[] = config('pxlcms.generator.models.traits.translatable_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_REMEMBERABLE, $imports)) {
            $lines[] = config('pxlcms.generator.models.traits.rememberable_fqn');
        }

        return $lines;
    }

    /**
     * @return array
     */
    protected function collectScopeTraitImportLines()
    {
        $lines = [];

        $imports = $this->getUsedOptionalImportKeys();

        if (in_array(CmsModelWriter::IMPORT_TRAIT_SCOPE_ACTIVE, $imports)) {
            $lines[] = config('pxlcms.generator.models.traits.scope_active_fqn');
        }

        if (in_array(CmsModelWriter::IMPORT_TRAIT_SCOPE_ORDER, $imports)) {
            if (count($this->data['ordered_by'])) {
                $lines[] = config('pxlcms.generator.models.traits.scope_cmsordered_fqn');
            } else {
                $lines[] = config('pxlcms.generator.models.traits.scope_position_fqn');
            }
        }

        return $lines;
    }

    /**
     * @return array
     */
    protected function collectSluggableImportLines()
    {
        $lines = [];

        if ($this->context->modelIsSluggable) {

            $lines[] = config('pxlcms.generator.models.slugs.sluggable_interface');
            $lines[] = config('pxlcms.generator.models.slugs.sluggable_trait');

        } elseif($this->context->modelIsParentOfSluggableTranslation) {

            $lines[] = config('pxlcms.generator.models.slugs.sluggable_interface');
            $lines[] = config('pxlcms.generator.models.slugs.sluggable_translated_trait');
        }

        return $lines;
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
