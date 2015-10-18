<?php
namespace Czim\PxlCms\Models\Scopes;

trait OnlyActive
{

    /**
     * Boot the scope
     */
    public static function bootOnlyActive()
    {
        static::addGlobalScope( new OnlyActiveScope );
    }

    /**
     * Get the name of the "e_active" column.
     *
     * @return string
     */
    public function getActiveColumn()
    {
        return defined('static::ACTIVE_COLUMN')
            ?   static::ACTIVE_COLUMN
            :   config('pxlcms.scopes.only_active.column');
    }

    /**
     * Get the fully qualified column name for applying the scope
     *
     * @return string
     */
    public function getQualifiedActiveColumn()
    {
        return $this->getTable() . '.' . $this->getActiveColumn();
    }

    /**
     * Get the query builder without the scope applied
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function withInactive()
    {
        return with(new static)->newQueryWithoutScope( new OnlyActiveScope );
    }
}
