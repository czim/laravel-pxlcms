<?php
namespace Czim\PxlCms\Generator;

use Czim\PxlCms\Generator\Exceptions\ModelFileAlreadyExistsException;
use Czim\PxlCms\Generator\Writer\CmsModelWriter;
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
     * Writes output files based on the set data
     *
     * @return bool
     */
    public function writeFiles()
    {
        if (empty($this->data)) {
            throw new InvalidArgumentException("No data set, nothing to write");
        }

        $this->writeModels();


        return false;
    }

    protected function writeModels()
    {
        // find out which modules already written, do not overwrite them
        // write new models
        // warn about models not written or refernced but not updated on the other end

        /** @var CmsModelWriter $modelWriter */
        $modelWriter = app()->make(CmsModelWriter::class);

        foreach ($this->data['models'] as $model) {

            try {

                $modelWriter->write($model);

            } catch (ModelFileAlreadyExistsException $e) {

                $this->log("File for model {$model['name']} already exists, did not write.");
            }

            // also write translation?
            if ($model['is_translated']) {

                $translatedModel = $this->makeTranslatedDataFromModelData($model);

                try {

                    $modelWriter->write($translatedModel);

                } catch (ModelFileAlreadyExistsException $e) {

                    $this->log("File for translation of model {$model['name']} already exists, did not write.");
                }
            }
        }
    }

    protected function makeTranslatedDataFromModelData(array $model)
    {
        $translatedModel = [
            'module'                 => $model['module'],
            'name'                   => $model['name'] . config('pxlcms.generator.translation_model_postfix'),
            'table'                  => ! empty($model['table'])
                ? $model['table'] . '_' . snake_case(config('pxlcms.generator.translation_model_postfix'))
                : null,
            'is_translated'          => false,
            'is_listified'           => false,
            'normal_fillable'        => $model['translated_attributes'],
            'translated_fillable'    => [],
            'hidden'                 => [],
            'casts'                  => [],
            'dates'                  => [],
            'relations_config'       => [],
            'normal_attributes'      => [],
            'translated_attributes'  => [],
            'relationships'          => [
                'normal'   => [],
                'reverse'  => [],
                'image'    => [],
                'file'     => [],
                'checkbox' => [],
            ],
        ];

        return $translatedModel;
    }


    /**
     * @param string $message
     */
    protected function log($message)
    {
        $this->log[] = $message;
    }

    /**
     * @return array
     */
    public function getLog()
    {
        return $this->log;
    }
}

