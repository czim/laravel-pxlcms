<?php
namespace Czim\PxlCms;

use Illuminate\Support\ServiceProvider;

class PxlCmsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/pxlcms.php' => config_path('pxlcms.php'),
        ]);
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConsoleCommands();

        $this->mergeConfigFrom(
            __DIR__.'/config/pxlcms.php', 'pxlcms'
        );
    }

    /**
     * Register the package console commands.
     *
     * @return void
     */
    protected function registerConsoleCommands()
    {
        $this->registerPxlCmsGenerate();
        $this->commands([
            'pxlcms.generate'
        ]);
    }

    /**
     * Register the generate command with the container.
     *
     * @return void
     */
    protected function registerPxlCmsGenerate()
    {
        $this->app->singleton('pxlcms.generate', function($app)
        {
            return new Commands\GenerateCommand;
        });
    }
}
