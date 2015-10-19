<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

/**
 * Unset the raw data that we won't use after first analysis,
 * to free up memory during the process.
 */
class UnsetRawData extends AbstractProcessStep
{
    protected function process()
    {
        unset( $this->data->rawData );
    }
}
