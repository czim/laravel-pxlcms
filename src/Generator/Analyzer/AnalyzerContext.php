<?php
namespace Czim\PxlCms\Generator\Analyzer;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Processor\Contexts\AbstractProcessContext;
use Czim\PxlCms\Generator\FieldType;
use Czim\PxlCms\Generator\Generator;

class AnalyzerContext extends AbstractProcessContext
{
    /**
     * FieldType Helper
     *
     * @var FieldType
     */
    public $fieldType;

    /**
     * Analyzer result output
     *
     * @var array
     */
    public $output = [
        'models',
    ];


    /**
     * @param DataObjectInterface $data
     * @param array|null          $settings
     */
    public function __construct(DataObjectInterface $data, array $settings = null)
    {
        parent::__construct($data, $settings);

        $this->fieldType = new FieldType();
    }


    /**
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = Generator::LOG_LEVEL_DEBUG)
    {
        event('pxlcms.logmessage', [ 'message' => $message, 'level' => $level ]);
    }
}
