<?php
namespace Czim\PxlCms\Generator\Writer\Model\Steps;

use Czim\PxlCms\Generator\Exceptions\ModelFileAlreadyExistsException;
use InvalidArgumentException;

class CheckConditionsAndSetup extends AbstractProcessStep
{

    protected function process()
    {
        $this->data->output = [];

        $name = $this->context->makeFqnForModelName( $this->data->name );


        if (empty($name)) {
            throw new InvalidArgumentException("Empty name for module, check the data parameter");
        }

        if ($this->context->alreadyExists($name)) {
            throw new ModelFileAlreadyExistsException("Model with name {$name} already exists");
        }


        // update context
        $this->context->fqnName = $name;

        if ( ! $this->data->cached) {
            $this->context->blockRememberableTrait = true;
        }


        // store model information for output
        $this->data->output['name']    = $name;
        $this->data->output['path']    = $this->context->getPath($name);
        $this->data->output['content'] = $this->context->getStubContent();
    }
}
