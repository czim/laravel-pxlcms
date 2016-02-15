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

    /**
     * Fill the database with basic content for testing
     */
    protected function seedDatabaseCmsContent()
    {
        /** @var \DB $db */
        $db = $this->app['db'];


        $db->table('cms_m_images')->delete();
        $db->table('cms_m_checkboxes')->delete();
        $db->table('cms_m_references')->delete();
        $db->table('cms_m22_pages')->delete();
        $db->table('cms_m22_pages_ml')->delete();
        $db->table('cms_m1_slugs')->delete();

        /*
         * Pages
         */

        $db->table('cms_m22_pages')->insert([
            'id'         => 1,
            'e_active'   => true,
            'e_position' => 3,
            'e_user_id'  => 0,
            'news'       => 2,
        ]);

        $db->table('cms_m22_pages')->insert([
            'id'         => 2,
            'e_active'   => false,
            'e_position' => 2,
            'e_user_id'  => 0,
        ]);

        $db->table('cms_m22_pages')->insert([
            'id'         => 3,
            'e_active'   => true,
            'e_position' => 1,
            'e_user_id'  => 0,
            'news'       => 3,
        ]);


        $db->table('cms_m22_pages_ml')->insert([
            'entry_id'        => 1,
            'language_id'     => 116,
            'title'           => 'Testing Nederland',
            'name'            => 'Testing Name',
            'content'         => 'Test content',
            'seo_title'       => 'Seo Title Nederlands',
            'seo_description' => 'Seo Description Nederlands',
        ]);

        $db->table('cms_m22_pages_ml')->insert([
            'entry_id'        => 1,
            'language_id'     => 38,
            'title'           => 'Testing English',
            'name'            => 'Testing Name',
            'content'         => 'Test content',
            'seo_title'       => 'Seo Title English',
            'seo_description' => 'Seo Description English',
        ]);

        $db->table('cms_m22_pages_ml')->insert([
            'entry_id'        => 2,
            'language_id'     => 116,
            'title'           => 'Testing B',
            'name'            => 'Testing Name B',
            'content'         => 'Test content B',
            'seo_title'       => '',
            'seo_description' => '',
        ]);

        $db->table('cms_m22_pages_ml')->insert([
            'entry_id'        => 3,
            'language_id'     => 116,
            'title'           => 'Testing C',
            'name'            => 'Testing Name C',
            'content'         => 'Test content C',
            'seo_title'       => '',
            'seo_description' => '',
        ]);

        /*
         * Slugs
         */

        $db->table('cms_m1_slugs')->insert([
            'id'            => 1,
            'ref_module_id' => 22,
            'entry_id'      => 1,
            'language_id'   => 116,
            'slug'          => 'test-nl-a',
            'e_active'      => true,
            'e_position'    => 0,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m1_slugs')->insert([
            'id'            => 2,
            'ref_module_id' => 22,
            'entry_id'      => 1,
            'language_id'   => 38,
            'slug'          => 'test-en-a',
            'e_active'      => true,
            'e_position'    => 0,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m1_slugs')->insert([
            'id'            => 3,
            'ref_module_id' => 22,
            'entry_id'      => 2,
            'language_id'   => 116,
            'slug'          => 'test-nl-b',
            'e_active'      => true,
            'e_position'    => 0,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m1_slugs')->insert([
            'id'            => 4,
            'ref_module_id' => 22,
            'entry_id'      => 3,
            'language_id'   => 116,
            'slug'          => 'test-nl-c',
            'e_active'      => true,
            'e_position'    => 0,
            'e_user_id'     => 0,
        ]);

        /*
         * News
         */

        $db->table('cms_m40_news')->insert([
            'id'            => 1,
            'date'          => 1448615714,
            'author'        => 'Test Testington',
            'e_active'      => true,
            'e_position'    => 1,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m40_news')->insert([
            'id'            => 2,
            'date'          => 1448616814,
            'author'        => 'Test Testington The Second',
            'e_active'      => true,
            'e_position'    => 2,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m40_news')->insert([
            'id'            => 3,
            'date'          => 1444116814,
            'author'        => 'Test Testington Jr.',
            'e_active'      => true,
            'e_position'    => 3,
            'e_user_id'     => 0,
        ]);

        $db->table('cms_m40_news')->insert([
            'id'            => 4,
            'date'          => 1444336814,
            'author'        => 'Test Testington Jr.',
            'e_active'      => true,
            'e_position'    => 4,
            'e_user_id'     => 0,
        ]);


        $db->table('cms_m40_news_ml')->insert([
            'id'          => 1,
            'entry_id'    => 1,
            'language_id' => 116,
            'name'        => 'Lorem testum dolor',
            'content'     => 'Some test content',
        ]);

        $db->table('cms_m40_news_ml')->insert([
            'id'          => 2,
            'entry_id'    => 2,
            'language_id' => 116,
            'name'        => 'Explicatum testo tetington',
            'content'     => 'Some test content test',
        ]);

        $db->table('cms_m40_news_ml')->insert([
            'id'          => 3,
            'entry_id'    => 3,
            'language_id' => 116,
            'name'        => 'More testing title',
            'content'     => 'Some test content test more',
        ]);

        $db->table('cms_m40_news_ml')->insert([
            'id'          => 4,
            'entry_id'    => 4,
            'language_id' => 116,
            'name'        => 'More testing dolor',
            'content'     => 'Some test content test some more',
        ]);


        /*
         * Many to Many references
         */

        $db->table('cms_m_references')->insert([
            'from_field_id' => 185,
            'from_entry_id' => 1,
            'to_entry_id'   => 2,
            'position'      => 3,
        ]);

        $db->table('cms_m_references')->insert([
            'from_field_id' => 185,
            'from_entry_id' => 1,
            'to_entry_id'   => 3,
            'position'      => 2,
        ]);

        $db->table('cms_m_references')->insert([
            'from_field_id' => 185,
            'from_entry_id' => 1,
            'to_entry_id'   => 4,
            'position'      => 1,
        ]);
    }
}
