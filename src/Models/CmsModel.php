<?php
namespace Czim\PxlCms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use InvalidArgumentException;

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

    // todo: some columns (not really used yet, remove?)
    protected $activeColumn = 'e_active';
    protected $positionColumn = 'e_position';
    protected $categoryColumn = 'e_category_id';
    protected $userColumn = 'e_user_id';

    // stored as unix timestamps
    protected $dateFormat = 'U';

    /**
     * Information about CMS model-reference relationships
     *
     * @var array
     */
    protected $relationsConfig = [];

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

    /**
     * The default CMS model listify config
     *
     * @var array
     */
    protected $cmsListifyConfig = [
        'top_of_list' => 1,
        'column'      => 'e_position',
        'scope'       => '1 = 1',
        'add_new_at'  => 'bottom',
    ];

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
    //      Relationships
    // ------------------------------------------------------------------------------

    /**
     * Override for CMS naming
     *
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this));
    }

    /**
     * Override for CMS naming (single references table)
     *
     * @return string
     */
    public function getCmsJoiningTable()
    {
        $table = config('pxlcms.tables.references', 'cms_m_references');
        //return $table . ' as ' . uniqid($table);
        // todo: does not work.. how to add an alias for each unique relationship?
        return $table;
    }

    /**
     * Get standard belongsToMany reference key name for 'from' and 'to' models
     * Reversed gives the 'to' key
     *
     * @param string $relation
     * @param bool   $reversed (default: false)
     * @return string
     */
    public function getCmsReferenceKeyForRelation($relation, $reversed = false)
    {
        $isParent = (   array_key_exists($relation, $this->relationsConfig)
                    &&  array_key_exists('parent', $this->relationsConfig[$relation])
                    &&  (bool) $this->relationsConfig[$relation]['parent']
                    );

        if ($reversed) {
            $isParent = ! $isParent;
        }

        return $isParent
                ?   config('pxlcms.relations.references.keys.to', 'to_entry_id')
                :   config('pxlcms.relations.references.keys.from', 'from_entry_id');
    }

    /**
     * Returns the configured 'from_field_id' field id value for the reference relation
     *
     * @param string $relation
     * @return int|null
     */
    public function getCmsReferenceFieldId($relation)
    {
        if (    ! array_key_exists($relation, $this->relationsConfig)
            ||  ! array_key_exists('field', $this->relationsConfig[$relation])
        ) {
            return null;
        }

        return (int) $this->relationsConfig[$relation]['field'];
    }


    /**
     * Override for different naming convention
     *
     * @param  string  $related
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function belongsTo($related, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relation name was given, we will use this debug backtrace to extract
        // the calling method's name and use that as the relationship name as most
        // of the time this will be what we desire to use for the relationships.
        if (is_null($relation)) {
            list($current, $caller) = debug_backtrace(false, 2);

            $relation = $caller['function'];
        }

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            $foreignKey = Str::snake($relation);
        }

        return parent::belongsTo($related, $foreignKey, $otherKey, $relation);
    }

    /**
     * Override for special cms_m_references 'pivot' table
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        // If no relationship name was passed, we will pull backtraces to get the
        // name of the calling function. We will use that function name as the
        // title of this relation since that is a great convention to apply.
        if (is_null($relation)) {
            $relation = $this->getBelongsToManyCaller();
        }

        $foreignKey = $foreignKey ?: $this->getCmsReferenceKeyForRelation($relation);
        $otherKey   = $otherKey   ?: $this->getCmsReferenceKeyForRelation($relation, true);

        if (is_null($table)) {
            $table = $this->getCmsJoiningTable();
        }

        $belongsToMany = parent::belongsToMany($related, $table, $foreignKey, $otherKey);

        $fieldId = $this->getCmsReferenceFieldId($relation);
        if (empty($fieldId)) {
            throw new InvalidArgumentException("No 'field' id configured for relation/reference: '{$relation}'!");
        }

        // add constraints
        $belongsToMany->wherePivot(config('pxlcms.relations.references.keys.field', 'from_field_id'), $fieldId);

        // todo: add default sorting? autosort relation?

        return $belongsToMany;
    }

    /**
     * For when you still want to use the 'normal' belongsToMany relationship in a CmsModel
     * that should be related to non-CmsModels in the laravel convention
     *
     * @param  string  $related
     * @param  string  $table
     * @param  string  $foreignKey
     * @param  string  $otherKey
     * @param  string  $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToManyNormal($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        return parent::belongsToMany($related, $table, $foreignKey, $otherKey, $relation);
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
