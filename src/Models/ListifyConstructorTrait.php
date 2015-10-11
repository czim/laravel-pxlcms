<?php
namespace Czim\PxlCms\Models;

/**
 * Trait to include for initializing Listify with
 * default CMS configuration
 */
trait ListifyConstructorTrait
{

    /**
     * @param array $attributes
     * @param bool  $exists
     */
    public function __construct(array $attributes = array(), $exists = false)
    {
        parent::__construct($attributes, $exists);

        $this->initListify($this->cmsListifyConfig);
    }
}
