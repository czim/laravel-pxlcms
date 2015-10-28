<?php
namespace Czim\PxlCms\Models\Scopes;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class CmsOrderedScope implements ScopeInterface
{

    /**
     * Apply scope on the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $columns = $model->getQualifiedOrderByColumns();

        foreach ($columns as $column => $direction) {

            $builder->orderBy($column, $direction);
        }

        $this->addUnordered($builder);
    }

    /**
     * Remove scope from the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     */
    public function remove(Builder $builder, Model $model)
    {
        $query      = $builder->getQuery();
        $columns    = $model->getQualifiedOrderByColumns();

        foreach ($columns as $column => $direction) {

            foreach ((array) $query->orders as $key => $order) {

                if ($order['column'] == $column) {
                    $this->removeOrderBy($query, $key);
                }
            }
        }
    }

    /**
     * Remove scope constraint from the query
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  int                                $key
     */
    protected function removeOrderBy(BaseBuilder $query, $key)
    {
        unset($query->orders[ $key ]);

        $query->orders = array_values( $query->orders );

        if ( ! count($query->orders)) {
            $query->orders = null;
        }
    }

    /**
     * Extend Builder with custom method
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     */
    protected function addUnordered(Builder $builder)
    {
        $builder->macro('unordered', function (Builder $builder) {

            $this->remove($builder, $builder->getModel());
            return $builder;
        });
    }

}

