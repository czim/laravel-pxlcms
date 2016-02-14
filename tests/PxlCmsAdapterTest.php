<?php
namespace Czim\PxlCms\Test;

use Czim\PxlCms\Generator\Generator;

class PxlCmsAdapterTest extends TestCase
{

    /**
     * @test
     */
    function it_can_be_instantiated()
    {
        new Generator();
    }

    /**
     * @test
     */
    function it_generates_models()
    {
        $pxlcms = new Generator();
        $pxlcms->generate();

        $models = [
            'Page',
            'PageTranslation',
            'Slug',
        ];

        // check if files were created
        foreach ($models as $model) {

            $file = $this->generatedPath
                  . DIRECTORY_SEPARATOR . 'Models'
                  . DIRECTORY_SEPARATOR . $model . '.php';

            $this->assertTrue($this->app['files']->exists($file), "model file should exist");
        }

        // check if models work
        // this needs some neat trick to be able to lead the just-generated models
        // either by copying them to psr4 and somehow getting them loaded
        // run-time, or by some other means...
    }


    // ------------------------------------------------------------------------------
    //      Set up and helpers
    // ------------------------------------------------------------------------------

    /**
     * Make sure that any previously generated files in the testbench fixture are removed
     *
     * @before
     */
    protected function clearGenerated()
    {
        $this->app['files']->deleteDirectory($this->generatedPath);
    }

}
