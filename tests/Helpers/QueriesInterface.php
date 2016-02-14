<?php
namespace Czim\PxlCms\Test\Helpers;

interface QueriesInterface
{

    /**
     * Returns queries to set up base CMS structure
     *
     * @return string[]
     */
    public function getCreateQueries();

    /**
     * Return queries to create the module tables
     *
     * @return string[]
     */
    public function getCreateModuleQueries();

    /**
     * Returns queries to fill basic CMS content
     *
     * @return string[]
     */
    public function getBasicCmsContentQueries();

    /**
     * Returns queries to fill module-related (specific) data
     *
     * @return string[]
     */
    public function getModuleContentQueries();

}
