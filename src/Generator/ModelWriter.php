<?php
namespace Czim\PxlCms\Generator;

use InvalidArgumentException;

/**
 * Writes model files to the app, based on provided
 * array with output of the CmsAnalyzer
 */
class ModelWriter
{

    /**
     * The data to write models on the basis of gud engrish
     *
     * @var array
     */
    protected $data = [];

    /**
     * Small log recording the model writer's changes
     *
     * @var array
     */
    protected $log = [];

    /**
     * Stores data to use to write files
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }


    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Writes output files based on the set data
     *
     * @return bool
     */
    public function writeFiles()
    {
        if (empty($data)) {
            throw new InvalidArgumentException("No data set, nothing to write");
        }

        $this->writeModuleFiles();


        return false;
    }

    protected function writeModuleFiles()
    {
        // find out which modules already written, do not overwrite them
        // write new models
        // warn about models not written or refernced but not updated on the other end
    }
}
