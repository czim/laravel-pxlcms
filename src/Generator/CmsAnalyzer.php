<?php
namespace Czim\PxlCms\Generator;

use Czim\Processor\PipelineProcessor;
use Czim\PxlCms\Generator\Analyzer\AnalyzerContext;
use Czim\PxlCms\Generator\Analyzer\Steps;

/**
 * Analyzes the meta-content of the CMS.
 */
class CmsAnalyzer extends PipelineProcessor
{
    protected $databaseTransaction = false;

    protected $processContextClass = AnalyzerContext::class;

    /**
     * Gathers the steps to pass the dataobject through as a collection
     * These are the steps for AFTER the initial checks and retrieval
     * has been handled.
     *
     * @return array
     */
    protected function processSteps()
    {
        return [
            Steps\CheckTables::class,
            Steps\LoadRawData::class,
            Steps\ResolveModuleNames::class,
            Steps\AnalyzeModels::class,
        ];
    }

    /**
     * Populate the result property based on the current process context
     * This is called after the pipeline completes (and only if no exceptions are thrown)
     */
    protected function populateResult()
    {
        // default: mark that the processor completed succesfully
        $this->result->setSuccess(true);

        // store output array in result data
        $this->result->output = $this->context->output;
    }
}
