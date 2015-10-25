<?php
namespace Czim\PxlCms\Generator;

use Czim\Processor\PipelineProcessor;
use Czim\PxlCms\Generator\Analyzer\AnalyzerContext;
use Czim\PxlCms\Generator\Analyzer\Steps;
use Illuminate\Console\Command;

/**
 * Analyzes the meta-content of the CMS.
 */
class CmsAnalyzer extends PipelineProcessor
{
    protected $databaseTransaction = false;

    protected $processContextClass = AnalyzerContext::class;

    /**
     * The console command that called the generator
     *
     * @var Command     null if not called by console
     */
    protected $command;


    /**
     * @param Command|null $command
     */
    public function __construct(Command $command = null)
    {
        $this->command = $command;

        parent::__construct();
    }

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
            Steps\DutchModeInteractive::class,
            Steps\ResolveModuleNames::class,
            Steps\AnalyzeModels::class,
            Steps\UnsetRawData::class,
            Steps\AnalyzeSluggableInteractive::class,
        ];
    }

    /**
     * Extend this class to configure your own process context setup
     * Builds a generic processcontext with only the process data injected.
     * If a context was injected in the constructor, data for it is set,
     * but settings are not applied.
     */
    protected function prepareProcessContext()
    {
        $this->context = app($this->processContextClass, [ $this->data, $this->settings ]);

        $this->context->command = $this->command;
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
