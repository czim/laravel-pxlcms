<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Exception;
use Illuminate\Support\Facades\DB;

class LoadRawData extends AbstractProcessStep
{
    /**
     * Whether to normalize names before storing them
     *
     * @var bool
     */
    protected $normalizeNames = true;


    protected function process()
    {
        // initialize raw data array
        $this->data->rawData = [
            'modules'  => [],
            'fields'   => [],
            'resizes'  => [],

            'menus'    => [],
            'groups'   => [],
            'sections' => [],
        ];

        $this->loadMenus()
             ->loadGroups()
             ->loadSections()
             ->loadModules()
             ->loadFields()
             ->loadResizes();
    }


    /**
     * @return $this
     * @throws Exception
     */
    protected function loadModules()
    {
        $moduleData = DB::table(config('pxlcms.tables.meta.modules'))->get();

        if (empty($moduleData)) {
            throw new Exception("No module data found in database...");
        }

        foreach ($moduleData as $moduleObject) {

            $moduleArray = (array) $moduleObject;
            $moduleId    = $moduleArray['id'];
            $sectionId   = $moduleArray['section_id'];

            $this->data->rawData['modules'][ $moduleId ] = [];
            $this->data->rawData['modules'][ $moduleId ]['prefixed_name'] = null;

            // note that we do not need 'simulate_categories_for', since this
            // is strictly a CMS-feature -- the reference used for it is already
            // accounted for in the model

            foreach ([  'name', 'max_entries', 'is_custom',
                         'allow_create', 'allow_update', 'allow_delete',
                         'client_cat_control', 'max_cat_depth',
                         'section_id',
                         'override_table_name',
                     ] as $key
            ) {
                $this->data->rawData['modules'][ $moduleId ][ $key ] = array_get($moduleArray, $key);
            }

            // normalize names
            if ($this->normalizeNames) {
                foreach (['name'] as $key) {
                    $this->data->rawData['modules'][ $moduleId ][ $key ] =
                        $this->normalize( $this->data->rawData['modules'][ $moduleId ][ $key ] );
                }
            }

            // prepare for field data
            $this->data->rawData['modules'][ $moduleId ]['fields'] = [];

            // load higher level names (for later name resolution)
            $this->data->rawData['modules'][ $moduleId ]['parent_names'] = [
                'section' => array_get($this->data->rawData['sections'], $sectionId .'.name'),
                'group'   => array_get($this->data->rawData['sections'], $sectionId . '.group_name'),
                'menu'    => array_get($this->data->rawData['sections'], $sectionId . '.menu_name'),
            ];
        }

        unset($moduleData, $moduleObject);

        return $this;
    }


    /**
     * @return $this
     * @throws Exception
     */
    protected function loadFields()
    {
        $fieldData = DB::table(config('pxlcms.tables.meta.fields'))->get();

        if (empty($fieldData)) {
            throw new Exception("No field data found in database...");
        }

        foreach ($fieldData as $fieldObject) {

            $fieldArray = (array) $fieldObject;
            $fieldId  = $fieldArray['id'];
            $moduleId = $fieldArray['module_id'];

            $this->data->rawData['fields'][ $fieldId ] = [];

            foreach ([  'module_id', 'field_type_id',
                         'name', 'display_name',
                         'value_count', 'refers_to_module',
                         'multilingual', 'options',
                     ] as $key
            ) {
                $this->data->rawData['fields'][ $fieldId ][ $key ] = $fieldArray[ $key ];
            }

            // normalize names
            if ($this->normalizeNames) {
                foreach (['name'] as $key) {
                    $this->data->rawData['fields'][ $fieldId ][ $key ] =
                        $this->normalize( $this->data->rawData['fields'][ $fieldId ][ $key ] );
                }
            }

            $this->data->rawData['fields'][ $fieldId ]['field_type_name'] = $this->context->fieldType->getFriendlyNameForId(
                $this->data->rawData['fields'][ $fieldId ]['field_type_id']
            );

            $this->data->rawData['fields'][ $fieldId ]['resizes'] = [];

            // also save in modules
            $this->data->rawData['modules'][ $moduleId ]['fields'][ $fieldId ] = $this->data->rawData['fields'][ $fieldId ];
        }

        unset($fieldData, $fieldObject);

        return $this;
    }


    // ------------------------------------------------------------------------------
    //      Meta / CMS Structure
    // ------------------------------------------------------------------------------

    /**
     * @return $this
     */
    protected function loadMenus()
    {
        $menuData = DB::table(config('pxlcms.tables.meta.menu'))->get();

        foreach ($menuData as $menuObject) {

            $menuArray = (array) $menuObject;
            $menuId    = $menuArray['id'];

            $this->data->rawData['menus'][ $menuId ] = [];

            foreach ([ 'name' ] as $key) {
                $this->data->rawData['menus'][ $menuId ][ $key ] = $menuArray[ $key ];
            }

            // normalize names
            if ($this->normalizeNames) {
                foreach (['name'] as $key) {
                    $this->data->rawData['menus'][ $menuId ][ $key ] =
                        $this->normalize( $this->data->rawData['menus'][ $menuId ][ $key ] );
                }
            }
        }

        unset($menuData, $menuObject);

        return $this;
    }

    /**
     * @return $this
     */
    protected function loadGroups()
    {
        $groupData = DB::table(config('pxlcms.tables.meta.groups'))->get();

        foreach ($groupData as $groupObject) {

            $groupArray = (array) $groupObject;
            $groupId    = $groupArray['id'];
            $menuId     = $groupArray['menu_id'];

            $this->data->rawData['groups'][ $groupId ] = [];

            foreach ([ 'name', 'menu_id' ] as $key) {
                $this->data->rawData['groups'][ $groupId ][ $key ] = $groupArray[ $key ];
            }

            // normalize names
            if ($this->normalizeNames) {
                foreach (['name'] as $key) {
                    $this->data->rawData['groups'][ $groupId ][ $key ] =
                        $this->normalize( $this->data->rawData['groups'][ $groupId ][ $key ] );
                }
            }

            $this->data->rawData['groups'][ $groupId ]['menu_name'] = $this->data->rawData['menus'][ $menuId ]['name'];
        }

        unset($groupData, $groupObject);

        return $this;
    }


    /**
     * @return $this
     */
    protected function loadSections()
    {
        $sectionData = DB::table(config('pxlcms.tables.meta.sections'))->get();

        foreach ($sectionData as $sectionObject) {

            $sectionArray = (array) $sectionObject;
            $sectionId = $sectionArray['id'];
            $groupId   = $sectionArray['group_id'];

            $this->data->rawData['sections'][ $sectionId ] = [];

            foreach ([ 'name', 'group_id' ] as $key) {
                $this->data->rawData['sections'][ $sectionId ][ $key ] = $sectionArray[ $key ];
            }

            // normalize names
            if ($this->normalizeNames) {
                foreach (['name'] as $key) {
                    $this->data->rawData['sections'][ $sectionId ][ $key ] =
                        $this->normalize( $this->data->rawData['sections'][ $sectionId ][ $key ] );
                }
            }

            $this->data->rawData['sections'][ $sectionId ]['group_name'] = $this->data->rawData['groups'][ $groupId ]['name'];
            $this->data->rawData['sections'][ $sectionId ]['menu_name']  = $this->data->rawData['groups'][ $groupId ]['menu_name'];
        }

        unset($sectionData, $sectionObject);

        return $this;
    }


    // ------------------------------------------------------------------------------
    //      Extra
    // ------------------------------------------------------------------------------

    /**
     * @return $this
     */
    protected function loadResizes()
    {
        $resizeData = DB::table(config('pxlcms.tables.meta.field_options_resizes'))->get();

        foreach ($resizeData as $resizeObject) {

            $resizeArray = (array) $resizeObject;
            $resizeId = $resizeArray['id'];
            $fieldId  = $resizeArray['field_id'];

            $this->data->rawData['resizes'][ $resizeId ] = [];

            foreach ([  'field_id', 'prefix',
                         'width', 'height',
                     ] as $key
            ) {
                $this->data->rawData['resizes'][ $resizeId ][ $key ] = $resizeArray[ $key ];
            }

            // also save in fields
            $this->data->rawData['fields'][ $fieldId ]['resizes'][ $resizeId ] = $this->data->rawData['resizes'][ $resizeId ];
        }

        unset($resizeData, $resizeObject);

        return $this;
    }

    /**
     * Standard name normalization
     *
     * @param string $string
     * @return string
     */
    protected function normalize($string)
    {
        return strtolower( $this->context->normalizeCmsDatabaseString($string, ' ') );
    }
}
