<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

use Czim\Processor\Steps\AbstractProcessStep as CzimAbstractProcessStep;
use Czim\PxlCms\Generator\Writer\WriterContext;
use Czim\PxlCms\Generator\Writer\WriterModelData;


abstract class AbstractProcessStep extends CzimAbstractProcessStep
{
    /**
     * @var WriterContext
     */
    protected $context;

    /**
     * @var WriterModelData
     */
    protected $data;


    /**
     * Replaces output stub content placeholders with actual content
     *
     * @param string $placeholder
     * @param string $replace
     * @param bool   $pregReplace   whether to use preg_replace
     * @return $this
     */
    protected function stubReplace($placeholder, $replace, $pregReplace = false)
    {
        if ($pregReplace) {

            $this->data->output['content'] = preg_replace(
                $placeholder,
                $replace,
                $this->data->output['content']
            );

        } else {

            $this->data->output['content'] = str_replace(
                $placeholder,
                $replace,
                $this->data->output['content']
            );
        }

        return $this;
    }

    /**
     * Replaces output stub content placeholders with actual content, using preg_replace
     *
     * @param string $placeholder
     * @param string $replace
     * @return $this
     */
    protected function stubPregReplace($placeholder, $replace)
    {
        return $this->stubReplace($placeholder, $replace, true);
    }


    /**
     * Returns a number of 'tabs' in the form of 4 spaces each
     *
     * @param int $count
     * @return string
     */
    protected function tab($count = 1)
    {
        return str_repeat(' ', 4 * $count);
    }

    /**
     * Returns length of longest key in key-value pair array
     *
     * @param array $array
     * @return int
     */
    protected function getLongestKey(array $array)
    {
        $longest = 0;

        foreach ($array as $key => $value) {
            if ($longest > strlen($key)) continue;

            $longest = strlen($key);
        }

        return $longest;
    }


}
