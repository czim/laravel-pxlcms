<?php
namespace Czim\PxlCms\Test;

use Czim\PxlCms\PxlCmsServiceProvider;
use Czim\PxlCms\Test\Helpers\QueriesInterface;

abstract class TestCase extends \Orchestra\Testbench\TestCase
{

    /**
     * @var QueriesInterface
     */
    protected $queries;

    /**
     * @var string
     */
    protected $generatedPath;


    /**
     * @param \Illuminate\Foundation\Application $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            PxlCmsServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $config = $app['config'];

        // Setup default database to use sqlite :memory:
        $config->set('database.default', 'testbench');
        $config->set('database.connections.testbench', [

            // mysql test
            //'driver'   => 'mysql',
            //'host'     => 'localhost',
            //'database' => 'pxlcms_test',
            //'username'  => 'homestead',
            //'password'  => 'secret',
            //'charset'   => 'utf8',
            //'collation' => 'utf8_unicode_ci',
            //'prefix'   => '',

            // sqlite test
            'database' => ':memory:',
            'driver'   => 'sqlite',
        ]);

        $config->set('pxlcms.generator.namespace', [
            'models'       => 'Generated\\Models',
            'requests'     => 'Generated\\Requests',
            'repositories' => 'Generated\\Repositories',
        ]);

        $this->generatedPath = app_path('Generated');
    }


    public function setUp()
    {
        parent::setUp();

        $this->queries = new Helpers\SqliteQueries();

        $this->migrateDatabase();
        $this->seedDatabase();
    }


    protected function migrateDatabase()
    {
        foreach ($this->queries->getCreateQueries() as $query) {
            \DB::statement($query);
        }

        foreach ($this->queries->getCreateModuleQueries() as $query) {
            \DB::statement($query);
        }
    }

    protected function seedDatabase()
    {
        foreach ($this->queries->getBasicCmsContentQueries() as $query) {
            \DB::statement($query);
        }

        foreach ($this->queries->getModuleContentQueries() as $query) {
            \DB::statement($query);
        }
    }

}
