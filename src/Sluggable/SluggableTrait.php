<?php
namespace Czim\PxlCms\Sluggable;

use Cviebrock\EloquentSluggable\SluggableTrait as CviebrokSluggableTrait;
use Czim\PxlCms\Models\CmsModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait SluggableTrait
{
    use CviebrokSluggableTrait {
        getSlug as CviebrockGetSlug;
        setSlug as CviebrockSetSlug;
        needsSlugging as CviebrockNeedsSlugging;
        scopeWhereSlug as CviebrockScopeWhereSlug;
        getExistingSlugs as CviebrockGetExistingSlugs;
    }

    // cached config() contents for CMS table slug handling
    protected static $slugsTable;
    protected static $slugsColumn;
    protected static $slugsEntryKey;
    protected static $slugsModuleKey;
    protected static $slugsLanguageKey;
    protected static $slugsActiveColumn;


    /**
     * Caches config on model boot
     */
    public static function bootSluggableTrait()
    {
        // cache config for this model
        static::$slugsTable        = config('pxlcms.slugs.table', 'cms_slugs');
        static::$slugsColumn       = config('pxlcms.slugs.column', 'slug');
        static::$slugsEntryKey     = config('pxlcms.slugs.keys.entry', 'entry_id');
        static::$slugsModuleKey    = config('pxlcms.slugs.keys.module', 'module_id');
        static::$slugsLanguageKey  = config('pxlcms.slugs.keys.language', 'language_id');
        static::$slugsActiveColumn = config('pxlcms.slugs.active_column', false);
    }


    /**
     * Get the current slug.
     *
     * @return mixed
     */
    public function getSlug()
    {
        if ($this->storeSlugLocally()) {
            return $this->CviebrockGetSlug();
        }

        return $this->getSlugFromCmsTable();
    }

    /**
     * @param string $slug
     * @param string $locale    optional, restrict search to given locale
     * @return mixed
     */
    public static function findBySlug($slug, $locale = null)
    {
        $model = new static;

        if ($model->storeSlugLocally()) {
            return $model::findBySlug($slug);
        }

        $id = $model->findRecordIdForSlugFromCmsTable($slug, $locale);

        // if it is translated, return by entry ID instead
        if ($model->isTranslationModel()) {

            return $model->where(config('pxlcms.translatable.translation_foreign_key'), $id)->first();
        }

        return $model->find($id);
    }

    /**
     * Query scope for finding a model by its slug.
     *
     * @param Builder $scope
     * @param string  $slug
     * @param string  $locale       if not set, matches for any locale
     * @param bool    $forHasQuery  if true, the scope is part of a has relation subquery
     * @return mixed
     */
    public function scopeWhereSlug($scope, $slug, $locale = null, $forHasQuery = false)
    {
        /** @var CmsModel|SluggableTrait $model */
        $model = new static;

        if ($model->storeSlugLocally()) {
            return $this->CviebrockScopeWhereSlug($scope, $slug);
        }

        // build scope with join to slugs table ..
        $scope = $scope->join(
            static::$slugsTable,
            function($join) use ($model, $locale) {

                $idKey = $this->isTranslationModel()
                    ?   config('pxlcms.translatable.translation_foreign_key')
                    :   $this->getKeyName();

                $join->on($model->getTable() . '.' . $idKey, '=', static::$slugsTable . '.' . static::$slugsEntryKey);
                $join->on(static::$slugsTable . '.' . static::$slugsModuleKey, '=', DB::raw( (int) $model->getModuleNumber()));

                if ( ! empty($locale) && $model->isTranslationModel()) {
                    $languageId = $model->lookUpLanguageIdForLocale($locale);

                    $join->on(static::$slugsTable . '.' . static::$slugsLanguageKey, '=', DB::raw( (int) $languageId));
                }
            }
        );

        return $scope->where(static::$slugsTable . '.' . static::$slugsColumn, $slug)
                     ->select($this->getTable() . '.' . ($forHasQuery ? 'id' : '*'));
    }


    /**
     * Set the slug manually.
     *
     * @param string $slug
     */
    protected function setSlug($slug)
    {
        $config  = $this->getSluggableConfig();
        $save_to = $config['save_to'];

        if ($this->storeSlugLocally()) {
            $this->setAttribute($save_to, $slug);
            return;
        }

        $this->setSlugInCmsTable($slug);
    }

    /**
     * Determines whether the model needs slugging.
     *
     * @return bool
     */
    protected function needsSlugging()
    {
        /** @var CmsModel|SluggableTrait $this */

        if ($this->storeSlugLocally()) {
            return $this->CviebrockNeedsSlugging();
        }

        $config    = $this->getSluggableConfig();
        $on_update = $config['on_update'];

        // check stored slug in shared table
        if ( ! $this->getSlugFromCmsTable()) return true;

        return ( ! $this->exists || $on_update);
    }

    /**
     * Get all existing slugs that are similar to the given slug.
     *
     * @param string $slug
     * @return array
     */
    protected function getExistingSlugs($slug)
    {
        // check for existing slugs in the slugs table or locally?
        if ($this->storeSlugLocally()) {
            return $this->cviebrockGetExistingSlugs($slug);
        }

        return $this->getAllSlugsForModuleFromCmsTable($slug);
    }

    /**
     * Returns current slug from CMS slugs table
     *
     * @return object|null
     */
    protected function getSlugRecordFromCmsTable()
    {
        /** @var CmsModel|SluggableTrait $this */

        $languageId = $this->storeSlugForLanguageId();

        $entryId = $this->isTranslationModel()
            ?   $this->getAttribute(config('pxlcms.translatable.translation_foreign_key'))
            :   $this->getKey();

        $existing = DB::table(static::$slugsTable)
            ->select([ 'id', static::$slugsColumn . ' as slug' ])
            ->where(static::$slugsModuleKey, $this->getModuleNumber())
            ->where(static::$slugsEntryKey, $entryId);

        // if language is null, we need to take into account that some weird
        // cms hook setups will use '0' or '' (and do not have nullable language columns)
        if (is_null($languageId)) {

            $existing = $existing->where(function($query) {
                return $query->whereNull(static::$slugsLanguageKey)
                    ->orWhere(static::$slugsLanguageKey, 0)
                    ->orWhere(static::$slugsLanguageKey, '');
            });

        } else {

            $existing = $existing->where(static::$slugsLanguageKey, $languageId);
        }

        $existing = $existing->limit(1)
                             ->first();

        if (empty($existing)) return null;

        return $existing;
    }

    /**
     * Returns the entry/model ID for a given slug
     *
     * @param string $slug
     * @param string $locale          the locale to limit for (if null, set limitToLanguage)
     * @param bool   $limitToLanguage if set, limits search to current language
     * @return int|null
     */
    public function findRecordIdForSlugFromCmsTable($slug, $locale = null, $limitToLanguage = false)
    {
        /** @var CmsModel|SluggableTrait $this */

        $existing = DB::table(static::$slugsTable)
            ->select([ static::$slugsEntryKey . ' as entry' ])
            ->where(static::$slugsModuleKey, $this->getModuleNumber())
            ->where(static::$slugsColumn, $slug);

        if ($locale || $limitToLanguage) {

            if ($locale) {
                $existing->where(static::$slugsLanguageKey, $this->lookUpLanguageIdForLocale($locale));
            } else {
                $existing->where(static::$slugsLanguageKey, $this->storeSlugForLanguageId());
            }
        }

        $existing = $existing->orderBy('id', 'asc')
                             ->limit(1)
                             ->first();

        if (empty($existing)) return null;

        return $existing->entry;
    }

    /**
     * Returns current slugs for this module from CMS slugs table
     *
     * @param string $likeSlug          if set, only returns slugs that are like the string
     * @param bool   $limitToLanguage   if true, only returns matches within the language
     * @return null|object
     */
    protected function getAllSlugsForModuleFromCmsTable($likeSlug = null, $limitToLanguage = true)
    {
        /** @var CmsModel|SluggableTrait $this */

        $config         = $this->getSluggableConfig();
        $includeTrashed = $config['include_trashed'];
        $separator      = $config['separator'];


        $existing = DB::table(static::$slugsTable)
            ->select([ 'id', static::$slugsColumn . ' as slug' ])
            ->where(static::$slugsModuleKey, $this->getModuleNumber())
            ->where(function ($query) use ($likeSlug, $separator) {
                $query->where(static::$slugsColumn, $likeSlug);
                $query->orWhere(static::$slugsColumn, 'LIKE', $likeSlug . $separator . '%');
            });


        if ( ! $includeTrashed && static::$slugsActiveColumn) {
            $existing->where(static::$slugsActiveColumn, true);
        }

        if ($limitToLanguage) {

            $existing->where(static::$slugsLanguageKey, $this->storeSlugForLanguageId());
        }

        $list = $existing->lists(static::$slugsColumn, static::$slugsEntryKey);

        // Laravel 5.0/5.1 check
        return $list instanceof Collection ? $list->all() : $list;
    }

    /**
     * Returns current slug string from CMS slugs table
     *
     * @return string|null
     */
    protected function getSlugFromCmsTable()
    {
        if ( ! ($slug = $this->getSlugRecordFromCmsTable())) return null;

        // slug has been normalized in the query, so the property is always 'slug'
        return $slug->slug;
    }

    /**
     * Update / store slug in dedicated table
     *
     * @param string $slug
     */
    protected function setSlugInCmsTable($slug)
    {
        /** @var CmsModel|SluggableTrait $this */
        $existing   = $this->getSlugRecordFromCmsTable();
        $languageId = $this->storeSlugForLanguageId();

        // update if exists
        if ($existing) {

            DB::table(static::$slugsTable)
                ->where('id', $existing->id)
                ->update([ static::$slugsColumn => $slug ]);

            return;
        }

        // if the model has no id, cannot store slug for it
        if ( ! $this->exists) return;

        $entryId = $this->isTranslationModel()
                    ?   $this->getAttribute(config('pxlcms.translatable.translation_foreign_key'))
                    :   $this->getKey();

        // create new entry
        DB::table(static::$slugsTable)
            ->insert([
                static::$slugsColumn      => $slug,
                static::$slugsModuleKey   => $this->getModuleNumber(),
                static::$slugsEntryKey    => $entryId,
                static::$slugsLanguageKey => $languageId,
            ]);
    }

    /**
     * Returns whether the slug should be stored on the model
     *
     * @return bool
     */
    public function storeSlugLocally()
    {
        return isset($this->cmsSluggableLocally) && $this->cmsSluggableLocally;
    }

    /**
     * Returns the language_id to store slugs for
     *
     * @return string
     */
    protected function storeSlugForLanguageId()
    {
        /** @var CmsModel|SluggableTrait $this */

        $config      = $this->getSluggableConfig();
        $languageKey = array_get($config, 'language_key');
        $localeKey   = array_get($config, 'locale_key');

        if ($languageKey) {
            return $this->getAttribute($languageKey);
        }

        if ($localeKey) {
            return $this->lookupLanguageIdForLocale( $this->getAttribute($localeKey) );
        }

        return null;
    }

    /**
     * Returns whether the model is a translation model for another model
     *
     * @return bool
     */
    protected function isTranslationModel()
    {
        $config = $this->getSluggableConfig();

        return (array_get($config, 'language_key') || array_get($config, 'locale_key'));
    }

}
