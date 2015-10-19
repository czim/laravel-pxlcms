<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;
use Czim\PxlCms\Generator\Generator;

/**
 * Analyzes models to see if they might be candidates for making sluggable.
 *
 * Interactive: asks whether you the user wants to add sluggable and for which column.
 */
class AnalyzeSluggableInteractive extends AbstractProcessStep
{
    const SLUGGABLE_NORMAL      = 'normal';
    const SLUGGABLE_TRANSLATED  = 'translated';
    const SLUGGABLE_SLUG_COLUMN = 'slug_column';

    /**
     * Which modules (ID values) to not slug
     *
     * @var array
     */
    protected $excludes = [];

    /**
     * Override sluggable setup for modules, keyed by module ID
     * @var array
     */
    protected $overrides = [];


    protected function process()
    {
        $this->excludes  = config('pxlcms.generator.override.sluggable.exclude');
        $this->overrides = config('pxlcms.generator.override.sluggable.force');


        // if slug handling is disabled entirely, do not perform this step
        if ( ! config('pxlcms.generator.models.slugs.enable')) {

            $this->context->log("Skipping slug analysis (disabled).");
            return;
        }

        // analyze which models might be candidates for sluggable
        // note that it will have to keep track of whether it is a translated slug source
        $candidates = $this->findSluggableCandidateModels();

        // if a model is to be sluggable'd, set its 'sluggable' property to true
        // and add the 'sluggable_setup' array, with a 'translated' key (which indicates whether
        // the parent model or the translated model is to be affected), the optional 'slug' and
        // required 'source' attribute.

        if ($this->isInteractive()) {

            // if a model has one sluggable column, ask a y/n for it
            // if a model has multiple, ask to make a choice which column to use


        } else {
            // if not interactive, use configuration settings where possible, skip anything else

        }


    }

    /**
     * Finds all the models that the user might want to make sluggable
     *
     * @return array
     */
    protected function findSluggableCandidateModels()
    {
        $candidates = [];

        foreach ($this->context->output['models'] as $model) {

            if ($this->isExcludedSluggable($model['module'])) continue;

            if ($result = $this->applyOverrideForSluggable($model)) {

                $candidates[ $model['module'] ] = $result;
                continue;
            }

            if ($result = $this->analyzeModelForSluggable($model)) {

                $candidates[ $model['module'] ] = $result;
            }
        }

        return $candidates;
    }



    /**
     * Returns overridden analysis result for model
     * after checking it against the model's data
     *
     * @param array $model
     * @return array|false
     */
    protected function applyOverrideForSluggable(array $model)
    {
        if ( ! array_key_exists($model['module'], $this->overrides)) {
            return false;
        }

        $override = $this->overrides[ $model['module'] ];

        $analysis = [
            'translated'              => false,
            'slug_column'             => null,
            'slug_sources_normal'     => [],
            'slug_sources_translated' => [],
        ];

        // set the source attribute (determine whether it is available and/or translatable)

        if ( ! array_key_exists('source', $override)) {
            $this->context->log(
                "Invalid slugs override configuration for model #{$model['module']}. No source.",
                Generator::LOG_LEVEL_ERROR
            );
            return false;
        }

        if (in_array($override['source'], $model['normal_attributes'])) {

            $analysis['slug_sources_normal'] = [ $override['source'] ];

        } elseif (in_array($override['source'], $model['translated_attributes'])) {

            $analysis['slug_sources_translated'] = [ $override['source'] ];

        } else {
            // couldn't find the attribute
            $this->context->log(
                "Invalid slugs override configuration for model #{$model['module']}. "
                . "Source attribute '{$override['source']}' does not exist.",
                Generator::LOG_LEVEL_ERROR
            );
            return false;
        }

        // if provided, check and set the slug target attribute (on the model itself)
        if (isset($override['slug'])) {

            if ($override['slug'] == $override['source']) {
                $this->context->log(
                    "Invalid slugs override configuration for model #{$model['module']}. "
                    . "Slug attribute '{$override['slug']}' is same as source attribute.",
                    Generator::LOG_LEVEL_ERROR
                );
                return false;
            }

            if (in_array($override['slug'], $model['normal_attributes'])) {

                $analysis['translated'] = false;

            } elseif (in_array($override['slug'], $model['translated_attributes'])) {

                $analysis['translated'] = true;

            } else {
                // couldn't find the attribute
                $this->context->log(
                    "Invalid slugs override configuration for model #{$model['module']}. "
                    . "Slug attribute '{$override['slug']}' does not exist.",
                    Generator::LOG_LEVEL_ERROR
                );
                return false;
            }

            $analysis['slug_column'] = $override['slug'];

            // if source is translated, slug must be, and vice versa!
            if (    $analysis['translated']   && count($analysis['slug_sources_normal'])
                ||  ! $analysis['translated'] && count($analysis['slug_sources_translated'])
            ) {
                $this->context->log(
                    "Invalid slugs override configuration for model #{$model['module']}. "
                    . "Either Slug and Source attribute must be translated, or neither.",
                    Generator::LOG_LEVEL_ERROR
                );
                return false;
            }

        }

        return $analysis;
    }

    /**
     * Determines whether the model data makes it a sluggable candidate
     * and returns analysis result
     *
     * @param array $model  model data
     * @return array|false      false if not a candidate
     */
    protected function analyzeModelForSluggable(array $model)
    {
        $analysis = [
            'translated'              => false,
            'slug_column'             => null,
            'slug_sources_normal'     => [],
            'slug_sources_translated' => [],
        ];

        // find out whether the model has a 'slug' column, preferring the first match we find
        $slugColumns = config('pxlcms.generator.models.slugs.slug_columns', []);

        foreach ($slugColumns as $slugColumn) {

            foreach ($model['normal_attributes'] as $attribute) {
                if ($attribute == $slugColumn) {
                    $analysis['slug_column'] = $attribute;
                    $analysis['translated']  = false;
                    break 2;
                }
            }

            foreach ($model['translated_attributes'] as $attribute) {
                if ($attribute == $slugColumn) {
                    $analysis['slug_column'] = $attribute;
                    $analysis['translated']  = true;
                    break 2;
                }
            }
        }

        // discard any matches that don't have a slug column on the model
        // if there is no slug structure present (because that could never work)
        if ( ! $this->context->slugStructurePresent && empty($analysis['slug_column'])) return false;

        // find out whether the model has slug source candidate columns
        $slugSources = config('pxlcms.generator.models.slugs.slug_source_columns', []);

        // make sure we keep slug and source column in the same model (translated or parent)
        if ($analysis['translated']) {
            $analysis['slug_sources_translated'] = array_values(array_intersect($model['translated_attributes'], $slugSources));
        } else {
            $analysis['slug_sources_normal'] = array_values(array_intersect($model['normal_attributes'], $slugSources));
        }


        // any matches? return as candidate
        if (    ! empty($analysis['slug_column'])
            ||  count($analysis['slug_sources_normal'])
            ||  count($analysis['slug_sources_translated'])
        ) {
            return $analysis;
        }

        return false;
    }


    /**
     * Whether the module is to be excluded from sluggable consideration
     *
     * @param int $moduleId
     * @return bool
     */
    protected function isExcludedSluggable($moduleId)
    {
        return (in_array($moduleId, $this->excludes));
    }

    /**
     * Returns whether there is user interaction
     *
     * @return bool
     */
    protected function isInteractive()
    {
        if (config('pxlcms.generator.models.slugs.interactive')) return true;

        return $this->context->isInteractive();
    }
}
