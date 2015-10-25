<?php
namespace Czim\PxlCms\Generator\Writer\Repository;

use Czim\PxlCms\Generator\Writer\WriterContext;

class RepositoryWriterContext extends WriterContext
{

    /**
     * Returns default name of stub (without extension)
     *
     * @return string
     */
    protected function getDefaultStubName()
    {
        return 'repository';
    }

    /**
     * Build Fully Qualified Namespace from a model name for a repository
     *
     * @param string $name
     * @return string
     */
    public function makeFqnForModelName($name)
    {
        return config('pxlcms.generator.namespace.repositories') . "\\"
             . studly_case($name)
             . config('pxlcms.generator.repositories.name_postfix');
    }

}
