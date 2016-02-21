<?php
namespace Czim\PxlCms\Test;

use Czim\PxlCms\Generator\Generator;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;

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
            'News',
            'NewsTranslation',
        ];

        /** @var Filesystem $files */
        $files = $this->app['files'];

        // check if files were created and whether the content matches
        // what was pre-generated since we cannot actually load the run-time
        // generated models, this is as far as the test can go. We can
        // separately test the actual pre-generated models later, since they
        // were tested to be the same.

        foreach ($models as $model) {

            $file = $this->generatedPath
                  . DIRECTORY_SEPARATOR . 'Models'
                  . DIRECTORY_SEPARATOR . $model . '.php';

            $this->assertTrue($files->exists($file), "model file should exist");

            $pregeneratedFile = $this->getGeneratedContentPath()
                              . DIRECTORY_SEPARATOR . 'Models'
                              . DIRECTORY_SEPARATOR . $model . '.php';

            $this->assertEquals(
                $files->get($pregeneratedFile),
                $files->get($file),
                "The generated content for model '{$model}' does not match the pregenerated file."
            );
        }
    }

    /**
     * @test
     */
    function it_generates_models_that_can_retrieve_data()
    {
        $this->seedDatabaseCmsContent();

        $records = \Generated\Models\Page::all();

        // 2 active, 3 with inactive
        $this->assertCount(2, $records);
        $this->assertEquals(3, \Generated\Models\Page::withInactive()->count());

        // check the order, by position, 3 should be first
        $this->assertEquals(3, $records->first()->id, "Position 1 page is not first");
        $this->assertEquals(1, $records->last()->id, "Position 3 page is not first");

        // before eager loading, the news attribute should return the foreign key
        $this->assertEquals(3, $records->first()->news, "Unloaded news attribute should return foreign key");

        // magic property after loading should get related record
        $records->first()->load('news');
        $related = $records->first()->news;
        $this->assertInstanceOf('Generated\\Models\\News', $related, "Loaded news attribute should return a news model");
        $this->assertEquals(3, $related->id);

        // lookup through relation
        $related = $records->first()->news()->first();
        $this->assertInstanceOf('Generated\\Models\\News', $related, "Relation acces after eager loading should return a news model");
        $this->assertEquals(3, $related->id);
    }

    /**
     * @test
     */
    function it_generates_models_that_can_save_data()
    {
        $page = new \Generated\Models\Page();

        $this->assertEquals(0, \Generated\Models\Page::count(), "Should be no models before creating");

        // basic attributes
        $page->e_user_id = 0;

        // translated attributes
        $page->name            = "test";
        $page->title           = "testing title";
        $page->content         = "created testing content";
        $page->seo_title       = "seo test en";
        $page->seo_description = "seo description en";

        $page->save();

        unset($page);

        // check if database has the right content
        $this->seeInDatabase('cms_m22_pages', [
            'id'       => 1,
            'e_active' => 1,
        ]);

        $this->seeInDatabase('cms_m22_pages_ml', [
            'entry_id'        => 1,
            'language_id'     => 38,
            'name'            => 'test',
            'title'           => 'testing title',
            'content'         => 'created testing content',
            'seo_title'       => 'seo test en',
            'seo_description' => 'seo description en',
        ]);

        // check if newly created model is returned correctly
        $this->assertEquals(1, \Generated\Models\Page::count(), "Should be one new model after creating");

        /** @var \Generated\Models\Page $page */
        $page = \Generated\Models\Page::find(1);

        $this->assertInstanceOf('Generated\\Models\\Page', $page);

        // test translations
        $this->assertEquals('testing title', $page->title, 'translated value incorrect');
        $this->assertInstanceOf('Generated\\Models\\PageTranslation', $page->getTranslation('en'));

        app()->setLocale('nl');
        $this->assertEmpty($page->title, 'translation for different language should be empty');
        $this->assertEmpty($page->getTranslation('nl'), 'translation for different language should be empty');
    }

    /**
     * @test
     */
    function it_generates_models_that_can_save_data_through_belongs_to_and_has_many_relations()
    {
        $this->seedDatabaseCmsContent();

        /** @var \Generated\Models\Page $page */
        $page = \Generated\Models\Page::withInactive()->with('news')->find(2);

        $this->assertInstanceOf('Generated\\Models\\Page', $page, "setup model not found");
        $this->assertEmpty($page->news, "should not have news relation yet");

        // create and associate belongsto item
        $news = new \Generated\Models\News();

        $news->content   = "new news test";
        $news->author    = "test author";
        $news->e_user_id = 0;

        $news->save();

        $page->news()->associate($news);
        $page->save();

        $this->assertInstanceOf('Generated\\Models\\News', $page->news, "BelongsTo save() didn't work");

        $newsId = 5;

        $this->seeInDatabase('cms_m22_pages', [
            'id'   => 2,
            'news' => $newsId,
        ]);

        $this->seeInDatabase('cms_m40_news', [
            'id'     => $newsId,
            'author' => 'test author',
        ]);

        $this->seeInDatabase('cms_m40_news_ml', [
            'entry_id'    => $newsId,
            'content'     => 'new news test',
            'language_id' => 38,
        ]);
    }

    /**
     * @test
     */
    function it_generates_models_that_can_save_data_through_belongs_to_many_relations()
    {
        $this->seedDatabaseCmsContent();

        /** @var \Generated\Models\News $news */
        $news = \Generated\Models\News::find(2);

        $this->assertInstanceOf('Generated\\Models\\News', $news, "setup model not found");
        $this->assertEmpty($news->relevantNews);

        // attach two new relevant news items
        $relatedNewsA = new \Generated\Models\News();
        $relatedNewsA->content   = "content A";
        $relatedNewsA->author    = "author A";
        $relatedNewsA->e_user_id = 0;

        $relatedNewsB = new \Generated\Models\News();
        $relatedNewsB->content   = "content B";
        $relatedNewsB->author    = "author B";
        $relatedNewsB->e_user_id = 0;

        $news->relevantNews()->saveMany([
            $relatedNewsA,
            $relatedNewsB,
        ]);

        // see if we can load them through eloquent
        $news->load('relevantNews');
        $related = $news->relevantNews;

        $this->assertInstanceOf('Illuminate\\Database\\Eloquent\\Collection', $related, "eloquent related data incorrect");
        $this->assertCount(2, $related, "eloquent related data incorrect");
        $this->assertInstanceOf('Generated\\Models\\News', $related->first(), "eloquent related data incorrect");

        // and the reverse relations
        /** @var Collection $relatedReverse */
        $relatedReverse = $related->first()->relevantNewsReverse()->get();

        $this->assertInstanceOf('Illuminate\\Database\\Eloquent\\Collection', $relatedReverse, "eloquent reversed related data incorrect");
        $this->assertCount(1, $relatedReverse, "eloquent reversed related data incorrect");
        $this->assertInstanceOf('Generated\\Models\\News', $relatedReverse->first(), "eloquent reversed related data incorrect");

        // check if database was updated
        $this->seeInDatabase('cms_m40_news', [
            'id'     => 5,
            'author' => 'author A',
        ]);
        $this->seeInDatabase('cms_m40_news', [
            'id'     => 6,
            'author' => 'author B',
        ]);

        $this->seeInDatabase('cms_m40_news_ml', [
            'entry_id'    => 5,
            'content'     => 'content A',
            'language_id' => 38,
        ]);
        $this->seeInDatabase('cms_m40_news_ml', [
            'entry_id'    => 6,
            'content'     => 'content B',
        ]);

        $this->seeInDatabase('cms_m_references', [
            'from_field_id' => 185,
            'from_entry_id' => 2,
            'to_entry_id'   => 5,
        ]);
        $this->seeInDatabase('cms_m_references', [
            'from_field_id' => 185,
            'from_entry_id' => 2,
            'to_entry_id'   => 6,
        ]);
    }

    /**
     * @test
     */
    function it_generates_models_that_can_save_related_images()
    {

    }

    /**
     * @test
     */
    function it_generates_models_that_can_save_related_checkboxes()
    {

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

    /**
     * Returns path to generated content
     *
     * @return string
     */
    protected function getGeneratedContentPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'Generated';
    }

}
