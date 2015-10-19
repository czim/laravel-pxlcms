<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Czim\PxlCms\Generator\Generator;
use Exception;
use Illuminate\Support\Facades\DB;

class CheckTables extends AbstractProcessStep
{
    /**
     * List of tables in the CMS database
     *
     * @var array
     */
    protected $tables;


    protected function process()
    {
        $this->loadTableList();

        $this->checkRequiredTables();

        $this->detectSlugStructure();
    }


    /**
     * Checks if all required tables are present
     *
     * @throws Exception    if a table is not found
     */
    protected function checkRequiredTables()
    {

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
            if ( ! in_array($checkTable, $this->tables)) {

                throw new Exception("Could not find expected CMS table in database: '{$checkTable}'");
            }
        }
    }

    /**
     * Detects whether this CMS has the 'typical' slug table setup
     */
    protected function detectSlugStructure()
    {
        $this->context->slugStructurePresent = false;

        $slugsTable = config('pxlcms.slugs.table');

        if ( ! in_array($slugsTable, $this->tables)) {

            $this->context->log("No slugs table detected.");
            return;
        }

        // the table exists.. does it have the content we need / expect?
        // Note: cannot escape/PDO this, so be careful with your config
        $columns = $this->loadColumnListForTable($slugsTable);

        foreach ([
                     config('pxlcms.slugs.keys.module'),
                     config('pxlcms.slugs.keys.entry'),
                     config('pxlcms.slugs.keys.language'),
                 ] as $requiredColumn
        ) {
            if (in_array($requiredColumn, $columns)) continue;

            // column does not exist!
            $this->context->log(
                "Slugs table detected but not usable for Sluggable handling!"
                . " Missing required column '{$requiredColumn}'.",
                Generator::LOG_LEVEL_WARNING
            );
            return;
        }

        $this->context->slugStructurePresent = true;
        $this->context->log("Slugs table detected and considered usable for Sluggable handling.");
    }


    /**
     * Caches the list of tables in the database
     */
    protected function loadTableList()
    {
        $tables = DB::select('SHOW TABLES');

        $this->tables = [];

        foreach ($tables as $tableObject) {

            $this->tables[] = array_get(array_values((array) $tableObject), '0');
        }
    }

    /**
     * Returns the column names for a table
     *
     * @param string $table
     * @return array
     */
    protected function loadColumnListForTable($table)
    {
        $columnResults = DB::select("SHOW columns FROM `{$table}`");
        $columns       = [];

        foreach ($columnResults as $columnObject) {

            $columns[] = array_get(array_values((array) $columnObject), '0');
        }

        return $columns;
    }
}
