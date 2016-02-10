<?php
namespace Czim\PxlCms\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne as EloquentHasOne;

class HasOne extends EloquentHasOne
{

    /**
     * The column name for the field id
     *
     * Note: for now we just take the 'images' field_id key and use it for ALL
     * special relations! This should probably use the config-defined field keys
     * for checkboxes/files separately, but since this will never change, leaving
     * it for now.
     *
     * @var string
     */
    protected static $fieldKey;

    /**
     * The field_id in the cms references
     *
     * @var int|null
     */
    protected $fieldId;


    /**
     * Create a new has one or many relationship instance for CMS special relations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Model   $parent
     * @param  string                                $foreignKey
     * @param  string                                $localKey
     * @param  int|null                              $fieldId
     */
    public function __construct(Builder $query, Model $parent, $foreignKey, $localKey, $fieldId = null)
    {
        $this->fieldId = $fieldId;

        if ( ! static::$fieldKey) {
            static::$fieldKey = config('pxlcms.relations.images.keys.field', 'from_field_id');
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
            $model->setAttribute(static::$fieldKey, $this->fieldId);
        }

        return parent::save($model);
    }

}
