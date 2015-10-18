<?php
namespace Czim\PxlCms\Generator\Writer;

use Czim\Processor\PipelineProcessor;
use Czim\PxlCms\Generator\Writer\Steps;

class CmsModelWriter extends PipelineProcessor
{
    const IMPORT_TRAIT_LISTIFY      = 'listify';
    const IMPORT_TRAIT_REMEMBERABLE = 'rememberable';
    const IMPORT_TRAIT_TRANSLATABLE = 'translatable';

    const STANDARD_MODEL_FILE     = 'file';
    const STANDARD_MODEL_IMAGE    = 'image';
    const STANDARD_MODEL_CATEGORY = 'category';
    const STANDARD_MODEL_CHECKBOX = 'checkbox';

    // the FQN for the Eloquent collection and builder types (in ide-helper tag content)
    const FQN_FOR_COLLECTION = '\\Illuminate\\Database\\Eloquent\\Collection';
    const FQN_FOR_BUILDER    = '\\Illuminate\\Database\\Query\\Builder';


    protected $databaseTransaction = false;

    protected $processContextClass = WriterContext::class;

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

            Steps\StubReplaceSimple::class,
            Steps\StubReplaceAttributeData::class,
            Steps\StubReplaceRelationData::class,
            Steps\StubReplaceDocBlock::class,
            Steps\StubReplaceImportsAndTraits::class,

            Steps\WriteFile::class,
        ];
    }

}
