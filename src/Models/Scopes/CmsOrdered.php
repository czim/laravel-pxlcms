<?php
namespace Czim\PxlCms\Models\Scopes;

trait CmsOrdered
{

    /**
     * Boot the scope
     */
    public static function bootCmsOrdered()
    {
        static::addGlobalScope( new CmsOrderedScope );
    }

    /**
     * Get the fully qualified column name for applying the scope
     *
     * @return string
     */
    public function getQualifiedOrderByColumns()
    {
        $columns   = $this->cmsOrderBy;
        $qualified = [];

        if (empty($columns)) return [];

        foreach ($columns as $column => $direction) {
            $qualified[ $this->getTable() . '.' . $column ] = $direction;
        }

        return $qualified;
    }

    /**
     * Get the query builder without the scope applied
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function unordered()
    {
        return with(new static)->newQueryWithoutScope( new CmsOrderedScope );
    }
}
