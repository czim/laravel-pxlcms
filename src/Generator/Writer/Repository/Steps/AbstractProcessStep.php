<?php
namespace Czim\PxlCms\Generator\Writer\Repository\Steps;

use Czim\PxlCms\Generator\Writer\AbstractProcessStep as WriterAbstractProcessStep;
use Czim\PxlCms\Generator\Writer\Repository\RepositoryWriterContext;
use Czim\PxlCms\Generator\Writer\Repository\WriterRepositoryData;

abstract class AbstractProcessStep extends WriterAbstractProcessStep
{
    /**
     * @var RepositoryWriterContext
     */
    protected $context;

    /**
     * @var WriterRepositoryData
     */
    protected $data;

}
