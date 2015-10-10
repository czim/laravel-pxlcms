<?php
namespace Czim\PxlCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CmsModel extends Model
{
    public $timestamps = false;

    /**
     * Default hidden properties, standard for CMS table
     *
     * @var array
     */
    protected $hidden = [
        'e_active',
        'e_position',
        'e_category_id',
        'e_user_id',
    ];

    protected $activeColumn = 'e_active';
    protected $positionColumn = 'e_position';
    protected $categoryColumn = 'e_category_id';
    protected $userColumn = 'e_user_id';

    // stored as unix timestamps
    protected $dateFormat = 'U';

    /**
     * The model class which represents the cms_languages content
     *
     * @var string
     */
    protected $languageModel = Language::class;

    /**
     * By default, fall back to translation fallback with Translatable
     *
     * @var bool
     */
    protected $useTranslationFallback = true;

    /**
     * The model class which represents the cms_images content
     *
     * @var string
     */
    protected $imageModel = Image::class;


    // global scope by default for:
    // position
    // active


    // ------------------------------------------------------------------------------
    //      Naming conventions
    // ------------------------------------------------------------------------------

    /**
     * Returns the module number for the module, if any
     *
     * @return int|null
     */
    public function getModuleNumber()
    {
        return $this->cmsModule ?: null;
    }

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }

        $moduleNumber = $this->getModuleNumber();

        $baseName = str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));

        // if it is a translated table, convert to CMS convention
        $translationExtension = '_' . str_plural(strtolower(config('translatable.translation_suffix', 'translation')));

        if (ends_with($baseName, $translationExtension)) {
            $baseName = str_plural(substr($baseName, 0, -1 * strlen($translationExtension)))
                      . config('pxlcms.translatable.translation_table_postfix');
        }

        return config('pxlcms.tables.prefix')
            . ($moduleNumber ? 'm' . $moduleNumber . '_' : '')
            . $baseName;
    }


    // ------------------------------------------------------------------------------
    //      Translatable support
    // ------------------------------------------------------------------------------

    /**
     * Retrieves (and caches) the locale
     *
     * @param string $locale
     * @return int|null     null if language was not found for locale
     */
    protected function lookUpLanguageIdForLocale($locale)
    {
        $locale = $this->normalizeLocale($locale);

        $languageModel = $this->languageModel;
        $language = $languageModel::where(config('pxlcms.translatable.locale_code_column'), $locale)
            ->remember((config('pxlcms.cache.languages-ttl')))
            ->first();

        if (empty($language)) return null;

        return $language->id;
    }

    /**
     * Normalizes the locale so it will match the CMS's language code
     *
     * en-US to en, for instance?
     *
     * @param string $locale
     * @return string
     */
    protected function normalizeLocale($locale)
    {
        return strtolower($locale);
    }

}
