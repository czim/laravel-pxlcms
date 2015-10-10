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
        $this->mergeConfigFrom(
            __DIR__.'/config/pxlcms.php', 'pxlcms'
        );
    }
}
