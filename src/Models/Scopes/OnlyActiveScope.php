<?php
namespace Czim\PxlCms\Models\Scopes;

use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ScopeInterface;

class OnlyActiveScope implements ScopeInterface
{

    /**
     * Apply scope on the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model   $model
     */
    public function apply(Builder $builder, Model $model)
    {
        $column = $model->getQualifiedActiveColumn();

        $builder->where($column, true);

        $this->addWithInactive($builder);
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
        $column     = $model->getQualifiedActiveColumn();
        $bindingKey = 0;

        foreach ((array) $query->wheres as $key => $where) {

            if ($this->isActiveConstraint($where, $column)) {

                $this->removeWhere($query, $key);
                $this->removeBinding($query, $bindingKey);
            }

            if ( ! in_array($where['type'], ['Null', 'NotNull'])) {
                $bindingKey++;
            }
        }
    }

    /**
     * Remove scope constraint from the query
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  int                                $key
     */
    protected function removeWhere(BaseBuilder $query, $key)
    {
        unset($query->wheres[ $key ]);

        $query->wheres = array_values( $query->wheres );
    }

    /**
     * Remove scope constraint from the query
     *
     * @param  \Illuminate\Database\Query\Builder $query
     * @param  int                                $key
     */
    protected function removeBinding(BaseBuilder $query, $key)
    {
        $bindings = $query->getRawBindings()['where'];

        unset($bindings[ $key ]);

        $query->setBindings($bindings);
    }

    /**
     * Check if given where is the scope constraint
     *
     * @param  array  $where
     * @param  string $column
     * @return boolean
     */
    protected function isActiveConstraint(array $where, $column)
    {
        return (    $where['type'] == 'Basic'
                &&  $where['column'] == $column
                &&  $where['value'] == true
               );
    }

    /**
     * Extend Builder with custom method
     *
     * @param \Illuminate\Database\Eloquent\Builder $builder
     */
    protected function addWithInactive(Builder $builder)
    {
        $builder->macro('withInactive', function (Builder $builder) {

            $this->remove($builder, $builder->getModel());
            return $builder;
        });
    }

}
