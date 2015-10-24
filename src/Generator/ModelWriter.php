<?php
namespace Czim\PxlCms\Generator;

use Czim\PxlCms\Generator\Exceptions\ModelFileAlreadyExistsException;
use Czim\PxlCms\Generator\Writer\CmsModelWriter;
use Czim\PxlCms\Generator\Writer\WriterModelData;
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

        $totalToWrite             = count($this->data['models']);
        $countWritten             = 0;
        $countTranslationsWritten = 0;
        $countAlreadyExist        = 0;

        foreach ($this->data['models'] as $model) {

            // for tracking whether translation model has a written main model
            $wroteMainModel = false;

            try {

                $model = $this->appendRelatedModelsToModelData($model);

                $modelWriter = app( CmsModelWriter::class );
                $modelWriter->process( app(WriterModelData::class, [ $model ]) );

                $this->log("Wrote model {$model['name']}.");
                $countWritten++;
                $wroteMainModel = true;

            } catch (ModelFileAlreadyExistsException $e) {

                $this->log("File for model {$model['name']} already exists, did not write.");
                $countAlreadyExist++;
            }

            // also write translation?
            if ($model['is_translated']) {

                $translatedModel = $this->makeTranslatedDataFromModelData($model);

                try {

                    $modelWriter = app( CmsModelWriter::class );
                    $modelWriter->process( app(WriterModelData::class, [ $translatedModel ]) );

                    $this->log("Wrote translation for model {$model['name']}.");
                    $countTranslationsWritten++;

                    if ( ! $wroteMainModel) {
                        $this->log(
                            "Warning: translation for model {$model['name']} was written, but model itself was not (over)written.\n"
                            . "Delete the old model and try again, or check the translation setup of the model manually.",
                            Generator::LOG_LEVEL_ERROR
                        );
                    }

                } catch (ModelFileAlreadyExistsException $e) {

                    $this->log("File for translation of model {$model['name']} already exists, did not write.");
                    $countAlreadyExist++;
                }
            }
        }

        $this->log(
            "Models written: {$countWritten} of {$totalToWrite}"
            . ($countTranslationsWritten ? " (and {$countTranslationsWritten} translation models)" : null),
            Generator::LOG_LEVEL_INFO
        );

        if ($countAlreadyExist) {
            $this->log(
                "{$countAlreadyExist} models already had files and were not (over)written",
                Generator::LOG_LEVEL_WARNING
            );
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
        // translated model is sluggable if model is, but on translated property or column
        $sluggable = ($model['sluggable'] && array_get($model, 'sluggable_setup.translated'));

        return [
            'module'                => $model['module'],
            'name'                  => $model['name'] . config('pxlcms.generator.models.translation_model_postfix'),
            'table'                 => ! empty($model['table'])
                ? $model['table'] . snake_case(config('pxlcms.tables.translation_postfix', '_ml'))
                : null,
            'cached'                => $model['cached'],
            'is_translated'         => false,
            'is_listified'          => false,
            // attributes
            'normal_fillable'       => $model['translated_attributes'],
            'translated_fillable'   => [],
            'hidden'                => [],
            'casts'                 => [],
            'dates'                 => [],
            'normal_attributes'     => [],
            'translated_attributes' => [],
            'timestamps'            => null,
            // categories
            'categories_module'     => null,
            // relations
            'relations_config'      => [],
            'relationships'         => [
                'normal'   => [],
                'reverse'  => [],
                'image'    => [],
                'file'     => [],
                'checkbox' => [],
            ],
            // special
            'sluggable'       => $sluggable,
            'sluggable_setup' => $sluggable ? $model['sluggable_setup'] : [],
            'scope_active'    => false,
            'scope_position'  => false,
        ];
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

