<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Exception;
use Illuminate\Support\Facades\DB;

class LoadRawData extends AbstractProcessStep
{

    protected function process()
    {
        // initialize raw data array
        $this->data->rawData = [
            'modules' => [],
            'fields'  => [],
            'resizes' => [],
        ];


        // ------------------------------------------------------------------------------
        //      Modules
        // ------------------------------------------------------------------------------

        $moduleData = DB::table(config('pxlcms.tables.meta.modules'))->get();

        if (empty($moduleData)) {
            throw new Exception("No module data found in database...");
        }

        foreach ($moduleData as $moduleObject) {

            $moduleArray = (array) $moduleObject;

            $this->data->rawData['modules'][ $moduleArray['id'] ] = [];

            foreach ([  'name', 'max_entries', 'is_custom',
                         'allow_create', 'allow_update', 'allow_delete',
                         'override_table_name', 'simulate_categories_for',
                     ] as $key
            ) {
                $this->data->rawData['modules'][ $moduleArray['id'] ][ $key ] = $moduleArray[ $key ];
            }

            $this->data->rawData['modules'][ $moduleArray['id'] ]['fields'] = [];

        }

        unset($moduleData, $moduleObject);


        // ------------------------------------------------------------------------------
        //      Fields
        // ------------------------------------------------------------------------------

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

            $this->data->rawData['fields'][ $fieldId ]['field_type_name'] = $this->context->fieldType->getFriendlyNameForId(
                $this->data->rawData['fields'][ $fieldId ]['field_type_id']
            );

            $this->data->rawData['fields'][ $fieldId ]['resizes'] = [];

            // also save in modules
            $this->data->rawData['modules'][ $moduleId ]['fields'][ $fieldId ] = $this->data->rawData['fields'][ $fieldId ];
        }

        unset($fieldData, $fieldObject);


        // ------------------------------------------------------------------------------
        //      Resizes
        // ------------------------------------------------------------------------------

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
    }
}
