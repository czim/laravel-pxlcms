<?php
namespace Czim\PxlCms\Models;

use Watson\Rememberable\Rememberable;

/**
 * @property string $slug
 * @property int    $ref_module_id
 * @property int    $entry_id
 * @property int    $language_id
 * @property-read string $locale
 */
class Slug extends CmsModel
{
    use Rememberable;

    protected $table = 'cms_slugs';

    public $timestamps = false;

    protected $fillable = [
        'slug',
    ];

    protected $hidden = [
        'ref_module_id',
        'module_id',
        'entry_id',
        'language_id',
    ];

    protected $appends = [
        'locale',
    ];

    /**
     * @param        $query
     * @param string $locale
     */
    public function scopeLocale($query, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();

        return $query->where('language_id', $this->lookUpLanguageIdForLocale($locale));
    }


    /*
     * Accessors / Mutators for Appends
     */

    public function getLocaleAttribute()
    {
        return $this->lookupLocaleForLanguageId($this->language_id);
    }

    public function setLocaleAttribute()
    {
        return null;
    }

}
