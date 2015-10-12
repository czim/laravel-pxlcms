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

                $model = $this->appendRelatedModelsToModelData($model);

                $modelWriter->write($model);

                $this->log("Wrote model {$model['name']}.");

            } catch (ModelFileAlreadyExistsException $e) {

                $this->log("File for model {$model['name']} already exists, did not write.");
            }

            // also write translation?
            if ($model['is_translated']) {

                $translatedModel = $this->makeTranslatedDataFromModelData($model);

                try {

                    $modelWriter->write($translatedModel);

                    $this->log("Wrote translation for model {$model['name']}.");

                } catch (ModelFileAlreadyExistsException $e) {

                    $this->log("File for translation of model {$model['name']} already exists, did not write.");
                }
            }
        }
    }

    /**
     * Append 'related' model data for related models
     *
     * @param array $model
     * @return array
     */
    protected function appendRelatedModelsToModelData(array $model)
    {
        $relationships = array_merge(
            array_get($model, 'relationships.normal'),
            array_get($model, 'relationships.reverse')
        );

        $model['related_models'] = [];

        foreach ($relationships as $name => $relationship) {

            $relatedModelId = $relationship['model'];

            if (isset($model['related_models'][ $relatedModelId ])) continue;

            $model['related_models'][ $relatedModelId ] = $this->data['models'][ $relatedModelId ];
        }

        return $model;
    }


    /**
     * Make model data array for translation model
     *
     * @param array $model
     * @return array
     */
    protected function makeTranslatedDataFromModelData(array $model)
    {
        $translatedModel = [
            'module'                 => $model['module'],
            'name'                   => $model['name'] . config('pxlcms.generator.translation_model_postfix'),
            'table'                  => ! empty($model['table'])
                ? $model['table'] . snake_case(config('pxlcms.tables.translation_postfix', '_ml'))
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

