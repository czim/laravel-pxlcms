<?php
namespace Czim\PxlCms\Sluggable;

use Cviebrock\EloquentSluggable\SluggableInterface;
use Dimsav\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * To be used on a parent of a translation model that has the SluggableTrait.
 * This will redirect standard Sluggable operations to the translation model,
 * making the use of Sluggable methods transparent and identical between
 * translated and untranslated models.
 */
trait SluggableTranslatedTrait
{

    /**
     * Find by slug on translated attribute
     *
     * @param string $slug
     * @param string $locale optional, if omitted returns for any locale
     * @return $this|null
     */
    public static function findBySlug($slug, $locale = null)
    {
        /** @var Translatable $model */
        $model = (new static);

        /** @var SluggableTrait $translationModel */
        $translationModel = $model->getTranslationModelName();
        $parentKey        = $model->getRelationKey();

        $translationInstance = $translationModel::findBySlug($slug, $locale);

        if (empty($translationInstance)) return null;

        return static::find($translationInstance->{$parentKey});
    }

    /**
     * Scopes query for slug on translated attribute
     *
     * @param Builder $query
     * @param string  $slug
     * @param string  $locale
     * @return $this|null
     */
    public function scopeWhereSlug($query, $slug, $locale = null)
    {
        return $query->whereHas('translations', function ($query) use ($slug, $locale) {
            return $query->whereSlug($slug, $locale);
        });
    }


    /**
     * Gets the slug (for the default/active locale)
     *
     * @return string|null
     */
    public function getSlug()
    {
        /** @var Translatable $this */
        /** @var SluggableInterface $translation */
        $translation = $this->getTranslation();

        if (empty($translation)) return null;

        return $translation->getSlug();
    }

    /**
     * Sluggifies the translation model (for default/active locale)
     *
     * @param bool $force
     * @return $this
     */
    public function sluggify($force = false)
    {
        /** @var Translatable $this */
        /** @var SluggableInterface $translation */
        $translation = $this->getTranslation();

        if ( ! empty($translation)) {

            $translation->sluggify($force);
        }

        return $this;
    }

    /**
     * Sluggifies the translation model (for default/active locale), forced
     *
     * @return $this
     */
    public function resluggify()
    {
        return $this->sluggify(true);
    }

}
