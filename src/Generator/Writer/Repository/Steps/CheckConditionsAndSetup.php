<?php
namespace Czim\PxlCms\Generator\Writer\Repository\Steps;

use Czim\PxlCms\Generator\Exceptions\RepositoryFileAlreadyExistsException;
use InvalidArgumentException;

class CheckConditionsAndSetup extends AbstractProcessStep
{

    protected function process()
    {
        $this->data->output = [];

        $name = $this->context->makeFqnForRepositoryName( $this->data->name );

        if (empty($name)) {
            throw new InvalidArgumentException("Empty name for repository, check the data parameter");
        }

        if ($this->context->alreadyExists($name)) {
            throw new RepositoryFileAlreadyExistsException("Repository with name {$name} already exists");
        }


        // update context
        $this->context->fqnName = $name;


        // store model information for output
        $this->data->output['name']    = $name;
        $this->data->output['path']    = $this->context->getPath($name);
        $this->data->output['content'] = $this->context->getStubContent();
    }
}
