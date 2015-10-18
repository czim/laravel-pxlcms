<?php
namespace Czim\PxlCms\Models\Scopes;

trait PositionOrdered
{

    /**
     * Boot the scope
     */
    public static function bootPositionOrdered()
    {
        static::addGlobalScope( new PositionOrderedScope );
    }

    /**
     * Get the name of the "e_position" column.
     *
     * @return string
     */
    public function getPositionColumn()
    {
        return defined('static::POSITION_COLUMN')
            ?   static::POSITION_COLUMN
            :   config('pxlcms.scopes.position_order.column');
    }

    /**
     * Get the fully qualified column name for applying the scope
     *
     * @return string
     */
    public function getQualifiedPositionColumn()
    {
        return $this->getTable() . '.' . $this->getPositionColumn();
    }

    /**
     * Get the query builder without the scope applied
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function unordered()
    {
        return with(new static)->newQueryWithoutScope( new PositionOrderedScope );
    }
}
