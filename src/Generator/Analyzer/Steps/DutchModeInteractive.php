<?php
namespace Czim\PxlCms\Generator\Analyzer\Steps;

/**
 * Interactive: asks whether the user wants to enable Dutch mode
 */
class DutchModeInteractive extends AbstractProcessStep
{

    protected function process()
    {
        // if Dutch-mode has been fixed in config, enable it and don't ask
        if (config('pxlcms.generator.dutch-mode.enabled')) {

            $this->context->dutchMode = true;
            return;
        }

        // if no Dutch content was found, nothing to do
        if ( ! $this->context->dutchNames) return;


        // if we're in auto-mode, skip the question and change nothing (or do the most likely thing)
        // interactive? then ask what to do
        if (    ! $this->isInteractive()
            ||  $this->context->command->confirm("Dutch naming detected. Do you want to enable Dutch-mode?")
        ) {
            $this->context->dutchMode = true;
        }
    }

    /**
     * Returns whether there is user interaction
     *
     * @return bool
     */
    protected function isInteractive()
    {
        if ( ! $this->context->isInteractive()) return false;

        return (bool) config('pxlcms.generator.dutch-mode.interactive');
    }
}
