<?php
namespace Czim\PxlCms\Generator\Writer\Model;

use Czim\PxlCms\Generator\Writer\WriterContext;
use Czim\PxlCms\Models\CmsModel;

class ModelWriterContext extends WriterContext
{

    /**
     * List of imports that aren't required for the model
     *
     * @var array
     * @todo phase out
     */
    public $importsNotUsed = [];

    /**
     * Which of the standard special relationships models were used
     * in this model's context
     *
     * @var array
     */
    public $standardModelsUsed = [];

    /**
     * Whether the rememberable trait is blocked for the model
     *
     * @var bool
     */
    public $blockRememberableTrait = false;

    /**
     * The 'use' imports to include
     *
     * @var array
     * @todo phase in
     */
    public $imports = [];

    /**
     * Whether the model needs the full sluggable treatment (not true if separate translated model does!)
     *
     * @var bool
     */
    public $modelIsSluggable = false;

    /**
     * Whether the model is the parent of a translation model that is sluggable
     *
     * @var bool
     */
    public $modelIsParentOfSluggableTranslation = false;


    /**
     * Returns default name of stub (without extension)
     *
     * @return string
     */
    protected function getDefaultStubName()
    {
        return 'model';
    }

    /**
     * Returns the model name (FQN if not to be imported) for a standard model
     * based on CmsModel const values for RELATION_TYPEs
     *
     * @param int $type
     * @return string
     */
    public function getModelNamespaceForSpecialModel($type)
    {
        $typeName = $this->getConfigNameForStandardModelType($type);

        if (    ! is_null($typeName)
            &&  config('pxlcms.generator.models.include_namespace_of_standard_models')
        ) {
            return $this->getModelNameFromNamespace(config('pxlcms.generator.standard_models.' . $typeName));
        }

        return '\\' . config('pxlcms.generator.standard_models.' . $typeName);
    }

    /**
     * Returns the special model type name used for config properties
     * based on CmsModel const values for RELATION_TYPEs
     *
     * @param int $type
     * @return null|string
     */
    protected function getConfigNameForStandardModelType($type)
    {
        switch ($type) {

            case CmsModel::RELATION_TYPE_IMAGE:
                return 'image';

            case CmsModel::RELATION_TYPE_FILE:
                return 'file';

            case CmsModel::RELATION_TYPE_CATEGORY:
                return 'category';

            case CmsModel::RELATION_TYPE_CHECKBOX:
                return 'checkbox';

            // default omitted on purpose
        }

        return null;
    }

}
