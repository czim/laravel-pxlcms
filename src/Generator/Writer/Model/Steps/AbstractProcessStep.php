<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Writer\AbstractProcessStep as WriterAbstractProcessStep;
use Czim\PxlCms\Generator\Writer\Model\ModelWriterContext;
use Czim\PxlCms\Generator\Writer\Model\WriterModelData;

abstract class AbstractProcessStep extends WriterAbstractProcessStep
{
    /**
     * @var ModelWriterContext
     */
    protected $context;

    /**
     * @var WriterModelData
     */
    protected $data;

}
