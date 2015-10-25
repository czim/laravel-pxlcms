<?php
namespace Czim\PxlCms\Generator\Writer\Repository;

use Czim\Processor\PipelineProcessor;
use Czim\PxlCms\Generator\Writer\Repository\Steps;

class CmsRepositoryWriter extends PipelineProcessor
{
    // the FQN for the Eloquent collection and builder types (in ide-helper tag content)
    const FQN_FOR_COLLECTION = '\\Illuminate\\Database\\Eloquent\\Collection';
    const FQN_FOR_BUILDER    = '\\Illuminate\\Database\\Query\\Builder';


    protected $databaseTransaction = false;

    protected $processContextClass = RepositoryWriterContext::class;

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
            Steps\CheckConditionsAndSetup::class,

            //Steps\StubReplaceSimple::class,
            //Steps\StubReplaceDocBlock::class,
            //Steps\StubReplaceImportsAndTraits::class,

            Steps\WriteFile::class,
        ];
    }

}
