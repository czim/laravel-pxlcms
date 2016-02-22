<?php
namespace Czim\PxlCms\Relations;

use Czim\PxlCms\Models\CmsModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;

class HasOne extends EloquentHasOne
{

    /**
     * The column name for the field id
     *
     * @var string
     */
    protected $fieldKey;

    /**
     * The field_id in the cms references
     *
     * @var int|null
     */
    protected $fieldId;


    /**
     * Create a new has one or many relationship instance for CMS special relations.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Illuminate\Database\Eloquent\Model   $parent
     * @param string                                $foreignKey
     * @param string                                $localKey
     * @param int|null                              $fieldId
     * @param int                                   $type
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $foreignKey,
        $localKey,
        $fieldId = null,
        $type = CmsModel::RELATION_TYPE_IMAGE
    ) {
        $this->fieldId = $fieldId;

        switch ($type) {

            case CmsModel::RELATION_TYPE_CHECKBOX:
                $this->fieldKey = config('pxlcms.relations.checkboxes.keys.field', 'field_id');
                break;

            case CmsModel::RELATION_TYPE_FILE:
                $this->fieldKey = config('pxlcms.relations.files.keys.field', 'field_id');
                break;

            case CmsModel::RELATION_TYPE_IMAGE:
            default:
                $this->fieldKey = config('pxlcms.relations.images.keys.field', 'from_field_id');
        }

        parent::__construct($query, $parent, $foreignKey, $localKey);
    }

    /**
     * Attach a model instance to the parent model.
     *
     * This adds the field_id value
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model)
    {
        if ($this->fieldId) {
            $model->setAttribute($this->fieldKey, $this->fieldId);
        }

        return parent::save($model);
    }

}
