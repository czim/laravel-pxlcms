<?php
namespace Czim\PxlCms\Generator\Writer\Steps;

class WriteFile extends AbstractProcessStep
{

    protected function process()
    {
        $path = $this->data->output['path'];

        $this->context->makeDirectory($path);

        $this->context->writeFile($path, $this->data->output['content']);
    }
}
