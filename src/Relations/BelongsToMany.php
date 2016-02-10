<?php
namespace Czim\PxlCms\Relations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;

class BelongsToMany extends EloquentBelongsToMany
{

    /**
     * The column name for the from field id
     *
     * @var string
     */
    protected static $fromFieldKey;

    /**
     * The from_field_id in the cms_m_references table
     *
     * @var int|null
     */
    protected $fromFieldId;


    /**
     * Create a new belongs to many relationship instance (CMSModel specific)
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Model   $parent
     * @param  string                                $table
     * @param  string                                $foreignKey
     * @param  string                                $otherKey
     * @param  string                                $relationName
     * @param  int                                   $fromFieldId
     */
    public function __construct(
        Builder $query,
        Model $parent,
        $table,
        $foreignKey,
        $otherKey,
        $relationName = null,
        $fromFieldId = null
    ) {
        $this->fromFieldId = $fromFieldId;

        if ( ! static::$fromFieldKey) {
            static::$fromFieldKey = config('pxlcms.relations.references.keys.field', 'from_field_id');
        }

        parent::__construct($query, $parent, $table, $foreignKey, $otherKey, $relationName);
    }



    /**
     * Save a new model and attach it to the parent model.
     *
     * This makes sure we add the from_field_id to the 'pivot' table
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  array  $joining
     * @param  bool   $touch
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function save(Model $model, array $joining = [], $touch = true)
    {
        if (empty($joining) && $this->fromFieldId) {
            $joining = [ static::$fromFieldKey => $this->fromFieldId ];
        }

        return parent::save($model, $joining, $touch);
    }

    /**
     * Create a full attachment record payload.
     *
     * This includes the from_field_id in the attachment payload
     *
     * @param  int    $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @param  bool   $timed
     * @return array
     */
    protected function attacher($key, $value, $attributes, $timed)
    {
        if (empty($attributes)) {
            $attributes = [ static::$fromFieldKey => $this->fromFieldId ];
        }

        return parent::attacher($key, $value, $attributes, $timed);
    }

}
