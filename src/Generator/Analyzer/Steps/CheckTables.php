<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Exception;
use Illuminate\Support\Facades\DB;

class CheckTables extends AbstractProcessStep
{
    protected function process()
    {
        $tables = DB::select('SHOW TABLES');

        $tableList = [];

        foreach ($tables as $tableObject) {

            $tableList[] = array_get(array_values((array) $tableObject), '0');
        }

        // check if relevant tables are present in the list
        foreach ([
                     config('pxlcms.tables.meta.modules'),
                     config('pxlcms.tables.meta.fields'),
                     config('pxlcms.tables.meta.field_options_choices'),
                     //config('pxlcms.tables.meta.groups'),
                     //config('pxlcms.tables.meta.sections'),
                     //config('pxlcms.tables.meta.field_types'),
                     //config('pxlcms.tables.meta.tabs'),
                     //config('pxlcms.tables.meta.users'),
                     //config('pxlcms.tables.meta.field_options_resizes'),

                     config('pxlcms.tables.languages'),
                     config('pxlcms.tables.categories'),
                     config('pxlcms.tables.files'),
                     config('pxlcms.tables.images'),
                     config('pxlcms.tables.references'),
                     config('pxlcms.tables.checkboxes'),
                     //config('pxlcms.tables.slugs'),

                 ] as $checkTable
        ) {
            if ( ! in_array($checkTable, $tableList)) {
                throw new Exception("Could not find expected CMS table in database: '{$checkTable}'");
            }
        }
    }
}
