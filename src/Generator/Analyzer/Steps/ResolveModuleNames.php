<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Czim\PxlCms\Generator\Generator;
use Exception;

/**
 * If two modules share the same name, this would cause conflicting duplicate
 * models.
 *
 * This can be resolved either by configuring generated models to be
 * namespaced according to their group and/or section. In that case, this
 * step will be skipped.
 *
 * Alternatively, this step will attempt to resolve the duplicates by prefixing
 * first the section, and if required the group, to the module/model name.
 */
class ResolveModuleNames extends AbstractProcessStep
{
    /**
     * @var bool
     */
    protected $prefixSection = false;

    /**
     * @var bool
     */
    protected $prefixGroup = false;

    /**
     * @var bool
     */
    protected $prefixMenu = false;

    /**
     * Keeps track of whether a module (by ID) has been prefixed
     * @var array
     */
    protected $modulePrefixedSection = [];

    /**
     * Keeps track of whether a module (by ID) has been prefixed
     * @var array
     */
    protected $modulePrefixedGroup = [];

    /**
     * Keeps track of whether a module (by ID) has been prefixed
     * @var array
     */
    protected $modulePrefixedMenu = [];



    protected function process()
    {
        $this->prefixSection = config('pxlcms.generator.models.model_name.prefix_section_to_model_names');
        $this->prefixGroup   = config('pxlcms.generator.models.model_name.prefix_group_to_model_names');
        $this->prefixMenu    = config('pxlcms.generator.models.model_name.prefix_menu_to_model_names');


        // Note that we must ALWAYS check for duplicate model names, since
        // a module might be named OneTwo, while another may be named Two in a section called One.
        // probably unlikely to happen, but some fallback needs to be available.


        $this->prefixAllModuleNames();


        // prevent looping forever if it doesn't work out
        $safeguard = 100;

        // check for and attempt to resolve duplicates
        do {
            $safeguard--;

            $duplicates = $this->checkForDuplicateNames();

            if ( ! count($duplicates)) break;

            $this->attemptToResolveDuplicates($duplicates);

        } while ($safeguard);


        // did we fail? shoudn't happen, but you never know...
        if ($safeguard < 1) {
            throw new Exception("Failed to resolve duplicate names for modules: " . print_r($duplicates));
        }


        // log any name changes
        $this->reportModuleNamePrefixing();
    }

    /**
     * Checks all modules for conflicting module names, and returns modules per
     * duplicate set.
     *
     * @return array
     */
    protected function checkForDuplicateNames()
    {
        $normalized = [];

        foreach ($this->data->rawData['modules'] as $moduleId => $module) {
            $normalized[ $moduleId ] = $this->normalizeName(
                array_get($module, 'prefixed_name') ?: $module['name']
            );
        }

        return $this->getKeysForDuplicates($normalized);
    }

    /**
     * Attempts to remove duplicates by changing (prefixed) module names
     *
     * @param array $duplicates     an array with arrays of keys that are duplicates of one another
     */
    protected function attemptToResolveDuplicates(array $duplicates)
    {
        foreach ($duplicates as $set) {

            foreach ($set as $moduleId) {

                // change the module id by doing what's left to do

                // 1. prefix section
                if ( ! $this->prefixSection && ! array_key_exists($moduleId, $this->modulePrefixedSection)) {

                    $this->addOrInjectSectionNamePrefix($moduleId);
                    continue;
                }

                // 2. prefix group
                if ( ! $this->prefixGroup && ! array_key_exists($moduleId, $this->modulePrefixedGroup)) {

                    $this->addOrInjectGroupNamePrefix($moduleId);
                    continue;
                }

                // 3. prefix menu
                if ( ! $this->prefixMenu && ! array_key_exists($moduleId, $this->modulePrefixedMenu)) {

                    $this->addOrInjectMenuNamePrefix($moduleId);
                    continue;
                }

                // 4. append module id (last resort)
                $this->addModuleIdPostFix($moduleId);
            }
        }
    }

    /**
     * Prefixes module name with section name, keeping into account whether
     * other, higher-level prefixes have been applied
     *
     * @param int $moduleId
     */
    protected function addOrInjectSectionNamePrefix($moduleId)
    {
        $module = $this->data->rawData['modules'][ $moduleId ];
        $prefix = $module['parent_names']['section'];

        // mark so we don't prefix it twice
        $this->modulePrefixedSection[$moduleId] = true;

        // simple prefix if nothing else was prefixed yet
        if ( ! $this->prefixGroup && ! $this->prefixMenu) {
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $prefix
                );
        }

        // simple prefix won't work, we'll need to rebuild the name, prefixing the section first
        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
            $this->addPrefix(
                array_get($module, 'prefixed_name') ?: $module['name'],
                $module['parent_names']['section']
            );

        if ($this->prefixGroup) {
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['group']
                );
        }

        if ($this->prefixMenu) {
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['menu']
                );
        }
    }

    /**
     * Prefixes module name with group name, keeping into account whether
     * other, higher-level prefixes have been applied
     *
     * @param int $moduleId
     */
    protected function addOrInjectGroupNamePrefix($moduleId)
    {
        $module = $this->data->rawData['modules'][ $moduleId ];
        $prefix = $module['parent_names']['group'];

        // mark so we don't prefix it twice
        $this->modulePrefixedGroup[$moduleId] = true;

        // simple prefix if nothing else was prefixed yet
        if ( ! $this->prefixMenu) {
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $prefix
                );
        }

        // simple prefix won't work, we'll need to rebuild the name, prefixing the section and group first
        // note that the section will always have been prefixed, one way or the other, at this point!
        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
            $this->addPrefix(
                array_get($module, 'prefixed_name') ?: $module['name'],
                $module['parent_names']['section']
            );

        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
            $this->addPrefix(
                array_get($module, 'prefixed_name') ?: $module['name'],
                $module['parent_names']['group']
            );

        if ($this->prefixMenu) {
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['menu']
                );
        }
    }

    /**
     * Prefixes module name with menu name, keeping into account whether
     * other, higher-level prefixes have been applied
     *
     * @param int $moduleId
     */
    protected function addOrInjectMenuNamePrefix($moduleId)
    {
        $module = $this->data->rawData['modules'][ $moduleId ];
        $prefix = $module['parent_names']['menu'];

        // mark so we don't prefix it twice
        $this->modulePrefixedMenu[$moduleId] = true;

        // this is always a simple prefix
        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
            $this->addPrefix(
                array_get($module, 'prefixed_name') ?: $module['name'],
                $prefix
            );
    }

    /**
     * Postfixes the module name with the module ID, as a last resort to
     * making a unique module name.
     *
     * Ideally this should undo any other attempts by prefixes, since
     * they are useless anyway.. but this might be tricky in edge cases.
     * You'd have to recheck for duplicates or trust the module IDs.
     * We'll do the latter for now.
     *
     * @param $moduleId
     */
    protected function addModuleIdPostFix($moduleId)
    {
        // undo previously applied prefixes (if any)
        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] = null;
        $this->prefixModuleName($moduleId);

        // postfix the module ID
        $module = $this->data->rawData['modules'][ $moduleId ];

        $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
            trim(array_get($module, 'prefixed_name') ?: $module['name']) . ' ' . $moduleId;
    }


    /**
     * Normalizes a module name
     *
     * @param string $name
     * @return string
     */
    protected function normalizeName($name)
    {
        return snake_case(trim(preg_replace('#\s+#', ' ', $name)));
    }

    /**
     * Returns the keys for values that occur more than once in an array
     *
     * @param array $array
     * @return array
     */
    function getKeysForDuplicates(array $array)
    {
        $duplicates = $newArray = [];

        foreach ($array as $key => $value) {

            if ( ! isset($newArray[ $value ])) {
                $newArray[ $value ] = $key;
                continue;
            }

            if (isset($duplicates[ $value ])) {
                $duplicates[ $value ][] = $key;
            } else {
                $duplicates[ $value ] = [
                    $newArray[ $value ],
                    $key
                ];
            }
        }

        return $duplicates;
    }



    /**
     * Prefixes all modules with section, group and/or menu names to module names
     */
    protected function prefixAllModuleNames()
    {
        foreach ($this->data->rawData['modules'] as $moduleId => $module) {

            $this->prefixModuleName($moduleId);
        }
    }

    /**
     * Prefixes section, group and/or menu names to module names, if configured to
     *
     * @param int $moduleId
     */
    protected function prefixModuleName($moduleId)
    {
        $module = $this->data->rawData['modules'][ $moduleId ];

        if ($this->prefixSection) {

            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['section']
                );
        }

        if ($this->prefixGroup) {

            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['group']
                );
        }

        if ($this->prefixMenu) {

            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] =
                $this->addPrefix(
                    array_get($module, 'prefixed_name') ?: $module['name'],
                    $module['parent_names']['menu']
                );
        }
    }

    /**
     * Prefixes a name with a string, separating them by a space,
     * while trimming double spaces
     *
     * @param string $name
     * @param string $prefix
     * @return mixed
     */
    protected function addPrefix($name, $prefix)
    {
        return trim(preg_replace('#\s+#', ' ', $prefix . ' ' . $name));
    }

    /**
     * Fire log events for any module name that was prefixed / altered
     */
    protected function reportModuleNamePrefixing()
    {
        foreach ($this->data->rawData['modules'] as $moduleId => $module) {
            if ( ! array_get($module, 'prefixed_name')) continue;

            $this->context->log(
                "Prefixed or altered module name to avoid duplicates: #{$moduleId} ("
                . "{$module['name']}) is now known as {$module['prefixed_name']}",
                Generator::LOG_LEVEL_WARNING
            );
        }
    }

}
