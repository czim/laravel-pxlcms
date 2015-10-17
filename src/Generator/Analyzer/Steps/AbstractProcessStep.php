<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

use Czim\Processor\Steps\AbstractProcessStep as CzimAbstractProcessStep;
use Czim\PxlCms\Generator\Analyzer\AnalyzerContext;
use Czim\PxlCms\Generator\Analyzer\AnalyzerData;

abstract class AbstractProcessStep extends CzimAbstractProcessStep
{
    /**
     * @var AnalyzerContext
     */
    protected $context;

    /**
     * @var AnalyzerData
     */
    protected $data;


    /**
     * Get override configuration for a given model/module id
     *
     * @param int $moduleId
     * @return mixed
     */
    protected function getOverrideConfigForModel($moduleId)
    {
        return config('pxlcms.generator.override.models.' . (int) $moduleId, []);
    }

    /**
     * Converts a field name to the database column name based on CMS convention
     *
     * @param string $field
     * @return string
     */
    protected function fieldNameToDatabaseColumn($field)
    {
        // the PXL CMS Generator is very forgiving when using multiple spaces,
        // so we need to filter them out here
        $field = preg_replace('#\s+#', ' ', $field);

        return str_replace(' ', '_', trim(strtolower($field)));
    }

    /**
     * Returns the table prefix (cms_m#_ ...) for a module CMS table
     *
     * @param $moduleId
     * @return string
     */
    protected function getModuleTablePrefix($moduleId)
    {
        $moduleId = (int) $moduleId;

        return config('pxlcms.tables.prefix', 'cms_') . 'm' . $moduleId . '_';
    }
}
