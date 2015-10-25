<?php
namespace Czim\PxlCms\Generator;

use Czim\PxlCms\Generator\Exceptions\RepositoryFileAlreadyExistsException;
use Czim\PxlCms\Generator\Writer\Repository\CmsRepositoryWriter;
use Czim\PxlCms\Generator\Writer\Repository\WriterRepositoryData;
use InvalidArgumentException;

/**
 * Writes repository files to the app, based on provided
 * array with output of the CmsAnalyzer
 */
class RepositoryWriter
{

    /**
     * The data to write repositories on the basis of gud engrish
     *
     * @var array
     */
    protected $data = [];

    /**
     * Small log recording the repository writer's changes
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
     * Writes output files based on the set data
     *
     * @return bool
     */
    public function writeFiles()
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException("No data set, nothing to write");
        }

        $this->writeRepositories();


        return false;
    }

    protected function writeRepositories()
    {
        /** @var CmsRepositoryWriter $repositoryWriter */

        $totalToWrite             = count($this->data['models']);
        $countWritten             = 0;
        $countAlreadyExist        = 0;

        foreach ($this->data['models'] as $model) {

            try {

                $repositoryWriter = app( CmsRepositoryWriter::class );
                $repositoryWriter->process( app(WriterRepositoryData::class, [ $model ]) );

                $this->log("Wrote repository for model {$model['name']}.");
                $countWritten++;

            } catch (RepositoryFileAlreadyExistsException $e) {

                $this->log("File for repository for model {$model['name']} already exists, did not write.");
                $countAlreadyExist++;
            }
        }

        $this->log(
            "Repositories written: {$countWritten} of {$totalToWrite}",
            Generator::LOG_LEVEL_INFO
        );

        if ($countAlreadyExist) {
            $this->log(
                "{$countAlreadyExist} repositor" . ($countAlreadyExist == 1 ? 'y' : 'ies') . " already had files and "
                . ($countAlreadyExist == 1 ? 'was' : 'were') . " not (over)written",
                Generator::LOG_LEVEL_WARNING
            );
        }

    }


    /**
     * @param string $message
     * @param string $level
     */
    protected function log($message, $level = Generator::LOG_LEVEL_DEBUG)
    {
        $this->log[] = $message;

        event('pxlcms.logmessage', [ 'message' => $message, 'level' => $level ]);
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }
}

