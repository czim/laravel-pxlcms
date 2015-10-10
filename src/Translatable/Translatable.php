<?php
namespace Czim\PxlCms\Translatable;

/**
 * Extension / Decorator for dimsav/laravel-translatable
 */
trait Translatable
{
    use \Dimsav\Translatable\Translatable {
        getLocaleKey as DimsavGetLocaleKey;
        getRelationKey as DimsavGetRelationKey;
        getNewTranslation as DimsavGetNewTranslation;
        getTranslationByLocaleKey as DimsavGetTranslationByLocaleKey;
    }

    /**
     * @return string
     */
    public function getLocaleKey()
    {
        return $this->localeKey ?: config('pxlcms.translatable.locale_key', 'language_id');
    }

    /**
     * Override to use the cms's standard for a foreign key as 'entry_id'
     *
     * @return string
     */
    public function getRelationKey()
    {
        return config('pxlcms.translatable.translation_foreign_key') ?: $this->getForeignKey();
    }

    /**
     * Override to convert locale into language_id
     *
     * @param string $key
     */
    private function getTranslationByLocaleKey($key)
    {
        $key = $this->lookUpLanguageIdForLocale($key);

        return $this->DimsavGetTranslationByLocaleKey($key);
    }

    /**
     * Override to convert locale into language_id
     *
     * @param string $locale
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getNewTranslation($locale)
    {
        $locale = $this->lookUpLanguageIdForLocale($locale);

        return $this->DimsavGetNewTranslation($locale);
    }
}
