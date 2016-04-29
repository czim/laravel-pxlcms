<?php
namespace Czim\PxlCms\Models;

use Czim\PxlCms\Helpers\Paths;
use Czim\PxlCms\Relations\BelongsToMany;
use Czim\PxlCms\Relations\HasMany;
use Czim\PxlCms\Relations\HasOne;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * CmsModel Adapter
 *
 * Based on Eloquent, but with overrides and adaptations for accessing
 * PXL CMS database content.
 *
 * @property int     $cmsModule
 * @property boolean $e_active
 * @property int     $e_position
 * @property int     $e_category_id
 * @property int     $e_user_id
 */
class CmsModel extends Model
{

    // relationsConfig special / standard model types
    const RELATION_TYPE_MODEL    = 0;
    const RELATION_TYPE_IMAGE    = 1;
    const RELATION_TYPE_FILE     = 2;
    const RELATION_TYPE_CHECKBOX = 3;
    const RELATION_TYPE_CATEGORY = 4;


    /**
     * The has relationship methods.
     *
     * @var array
     */
    public static $hasRelationMethods = ['hasOne', 'hasMany', 'hasManyThrough'];

    /**
     * Disable timestamps for CMS models by default
     * 
     * @var bool
     */
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

    /**
     * The column to use for active scoping
     *
     * @var string
     */
    protected $cmsActiveColumn = 'e_active';

    /**
     * The column to use for position ordering
     *
     * @var string
     */
    protected $cmsPositionColumn = 'e_position';

    /**
     * Stored in database as Unix timestamps
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * Information about CMS model-reference relationships
     *
     * @var array
     */
    protected $relationsConfig = [];

    /**
     * By default, fall back to translation fallback with Translatable
     *
     * @var bool
     */
    protected $useTranslationFallback = true;

    /**
     * The model class which represents the cms_languages content
     *
     * @var string
     */
    protected static $cmsLanguageModel = Language::class;

    /**
     * The model class which represents the cms_images content
     *
     * @var string
     */
    protected static $cmsImageModel = Image::class;

    /**
     * The model class which represents the resizes in the cms
     *
     * @var string
     */
    protected static $cmsResizeModel = Resize::class;

    /**
     * Whether, if sluggable model, the slug is to be saved on the model itself
     *
     * @var bool
     */
    protected $cmsSluggableLocally;

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

    /**
     * The default configured CMS sorting clauses, in attribute => direction pairs
     * Only relevant when not sorting by position / listify !
     *
     * @var array
     */
    protected $cmsOrderBy = [];


    // ------------------------------------------------------------------------------
    //      Date adjustments for Unix Timestamp storage
    // ------------------------------------------------------------------------------


    /**
     * Return a timestamp as DateTime object.
     *
     * @inheritdoc
     */
    protected function asDateTime($value)
    {
        if (0 === $value || null === $value) {
            return null;
        }

        return parent::asDateTime($value);
    }

    /**
     * Convert the model's attributes to an array.
     *
     * @inheritdoc
     */
    public function attributesToArray()
    {
        // unset date values that are 0, so they don't cause problems with serializeDate()
        foreach ($this->getDates() as $key) {
            if ( ! isset($this->attributes[$key])) continue;

            // 0 should be considered 'unset' aswell
            if (0 === $this->attributes[$key]) {
                $this->attributes[$key] = null;
            }
        }

        return parent::attributesToArray();
    }


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
        return config('pxlcms.tables.references', 'cms_m_references');
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

        return (int) $this->relationsConfig[ $relation ]['field'];
    }

    /**
     * Returns the configured special standard model type for the reference relation
     *
     * @param string $relation
     * @return string|null
     */
    public function getCmsSpecialRelationType($relation)
    {
        if (    ! array_key_exists($relation, $this->relationsConfig)
            ||  ! array_key_exists('type', $this->relationsConfig[$relation])
        ) {
            return null;
        }

        return $this->relationsConfig[ $relation ]['type'];
    }

    /**
     * Returns whether the special standard model relation is translated
     * (locale-dependent)
     *
     * @param string $relation
     * @return string|null
     */
    public function getCmsSpecialRelationTranslated($relation)
    {
        if (    ! array_key_exists($relation, $this->relationsConfig)
            ||  ! array_key_exists('translated', $this->relationsConfig[$relation])
        ) {
            return false;
        }

        return (bool) $this->relationsConfig[ $relation ]['translated'];
    }


    /**
     * Override for different naming convention
     *
     * @param  string $related
     * @param  string $foreignKey
     * @param  string $otherKey
     * @param  string $relation
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

        $specialType = $this->getCmsSpecialRelationType($relation);

        // If no foreign key was supplied, we can use a backtrace to guess the proper
        // foreign key name by using the name of the relationship function, which
        // when combined with an "_id" should conventionally match the columns.
        if (is_null($foreignKey)) {
            if ($specialType === self::RELATION_TYPE_CATEGORY) {
                $foreignKey = config('pxlcms.relations.categories.keys.category');
            } else {
                $foreignKey = Str::snake($relation);
            }
        }

        // If the relation name is the same (casing aside) as the foreign key attribute,
        // we must make sure that the foreign key property is at least a key in the
        // attributes array. If it is not, there will be recursive lookup problems
        // where the attribute is never resolved and 'property does not exist' exceptions occur.
        if (    snake_case($relation) == $foreignKey
            &&  ! array_key_exists($foreignKey, $this->attributes)
        ) {
            $this->attributes[ $foreignKey ] = null;
        }

        /** @var Builder $belongsTo */
        $belongsTo = parent::belongsTo($related, $foreignKey, $otherKey, $relation);

        // category relation must be filtered by module id
        if ($specialType === self::RELATION_TYPE_CATEGORY) {
            $belongsTo->where(
                config('pxlcms.relations.categories.keys.module'),
                $this->getModuleNumber()
            );
        }

        return $belongsTo;
    }

    /**
     * Override for special cms_m_references 'pivot' table
     *
     * @param  string $related
     * @param  string $table
     * @param  string $foreignKey
     * @param  string $otherKey
     * @param  string $relation
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

        $fieldId = $this->getCmsReferenceFieldId($relation);
        if (empty($fieldId)) {
            throw new InvalidArgumentException("No 'field' id configured for relation/reference: '{$relation}'!");
        }

        // originally, it was possible to just get the parent's BelongsToMany instance, but
        // to avoid problems when saving on the relationship, we need a specialized extension
        // of the relation class for the CMS.
        //$belongsToMany = parent::belongsToMany($related, $table, $foreignKey, $otherKey);

        /** @var Model $instance */
        $instance = new $related;

        $query = $instance->newQuery();

        $belongsToMany = new BelongsToMany($query, $this, $table, $foreignKey, $otherKey, $relation, $fieldId);

        // add constraints
        $belongsToMany->wherePivot(config('pxlcms.relations.references.keys.field', 'from_field_id'), $fieldId);

        return $belongsToMany;
    }

    /**
     * For when you still want to use the 'normal' belongsToMany relationship in a CmsModel
     * that should be related to non-CmsModels in the laravel convention
     *
     * @param  string $related
     * @param  string $table
     * @param  string $foreignKey
     * @param  string $otherKey
     * @param  string $relation
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function belongsToManyNormal($related, $table = null, $foreignKey = null, $otherKey = null, $relation = null)
    {
        return parent::belongsToMany($related, $table, $foreignKey, $otherKey, $relation);
    }

    /**
     * Overridden to catch special relationships to standard CMS models
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * @param string $locale        only used as an override, and only for ML images
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function hasMany($related, $foreignKey = null, $localKey = null, $locale = null)
    {
        $relation = $this->getHasOneOrManyCaller();

        if ( ! ($specialType = $this->getCmsSpecialRelationType($relation))) {
            return parent::hasMany($related, $foreignKey, $localKey);
        }

        list($foreignKey, $fieldKey) = $this->getKeysForSpecialRelation($specialType, $foreignKey);

        $fieldId = $this->getCmsReferenceFieldId($relation);

        /** @var Builder $hasMany */
        if (    $specialType === static::RELATION_TYPE_CHECKBOX
            ||  $specialType === static::RELATION_TYPE_FILE
            ||  $specialType === static::RELATION_TYPE_IMAGE
        ) {
            /** @var Model $instance */
            $instance = new $related;

            $localKey = $localKey ?: $this->getKeyName();

            $hasMany = new HasMany(
                $instance->newQuery(),
                $this, $instance->getTable() . '.' . $foreignKey,
                $localKey,
                $fieldId,
                $specialType
            );

        } else {

            $hasMany = parent::hasMany($related, $foreignKey, $localKey);
        }

        // add field key for wheres
        $hasMany->where($fieldKey, $fieldId);

        // limit to selected locale, if translated
        if ($this->getCmsSpecialRelationTranslated($relation)) {

            if (is_null($locale)) $locale = app()->getLocale();

            $hasMany->where(
                config('pxlcms.translatable.locale_key'),
                $this->lookUpLanguageIdForLocale($locale)
            );
        }

        return $hasMany;
    }

    /**
     * Overridden to catch special relationships to standard CMS models
     *
     * @param string $related
     * @param string $foreignKey
     * @param string $localKey
     * @param string $locale        only used as an override, and only for ML images
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function hasOne($related, $foreignKey = null, $localKey = null, $locale = null)
    {
        $relation = $this->getHasOneOrManyCaller();

        if ( ! ($specialType = $this->getCmsSpecialRelationType($relation))) {
            return parent::hasOne($related, $foreignKey, $localKey);
        }

        list($foreignKey, $fieldKey) = $this->getKeysForSpecialRelation($specialType, $foreignKey);

        $fieldId = $this->getCmsReferenceFieldId($relation);

        // create the correct hasOne, for special relations that must save field_id values
        /** @var Builder $hasOne */
        if (    $specialType === static::RELATION_TYPE_CHECKBOX
            ||  $specialType === static::RELATION_TYPE_FILE
            ||  $specialType === static::RELATION_TYPE_IMAGE
        ) {
            /** @var Model $instance */
            $instance = new $related;

            $localKey = $localKey ?: $this->getKeyName();

            $hasOne = new HasOne(
                $instance->newQuery(),
                $this,
                $instance->getTable() . '.' . $foreignKey,
                $localKey,
                $fieldId,
                $specialType
            );

        } else {

            $hasOne = parent::hasOne($related, $foreignKey, $localKey);
        }

        // add field key for wheres
        $hasOne->where($fieldKey, $fieldId);

        // limit to selected locale, if translated
        if ($this->getCmsSpecialRelationTranslated($relation)) {

            if (is_null($locale)) $locale = app()->getLocale();

            $hasOne->where(
                config('pxlcms.translatable.locale_key'),
                $this->lookUpLanguageIdForLocale($locale)
            );
        }

        return $hasOne;
    }

    /**
     * Get the relationship name of the has one/many
     *
     * @return string
     */
    protected function getHasOneOrManyCaller()
    {
        $self = __FUNCTION__;

        $caller = Arr::first(debug_backtrace(false), function ($key, $trace) use ($self) {
            $caller = $trace['function'];

            return ! in_array($caller, CmsModel::$hasRelationMethods) && $caller != $self;
        });

        return ! is_null($caller) ? $caller['function'] : null;
    }

    /**
     * Get the foreign and field keys for the special relation's standard CMS model
     *
     * @param int    $specialType
     * @param string $foreignKey
     * @return array    [ foreignKey, secondaryKey ]
     */
    protected function getKeysForSpecialRelation($specialType, $foreignKey = null)
    {
        switch ($specialType) {

            case static::RELATION_TYPE_FILE:
                $foreignKey   = $foreignKey ?: config('pxlcms.relations.files.keys.entry');
                $secondaryKey = config('pxlcms.relations.files.keys.field');
                break;

            case static::RELATION_TYPE_CHECKBOX:
                $foreignKey   = $foreignKey ?: config('pxlcms.relations.checkboxes.keys.entry');
                $secondaryKey = config('pxlcms.relations.checkboxes.keys.field');
                break;

            case static::RELATION_TYPE_IMAGE:
            default:
                $foreignKey   = $foreignKey ?: config('pxlcms.relations.images.keys.entry');
                $secondaryKey = config('pxlcms.relations.images.keys.field');
        }

        return [ $foreignKey, $secondaryKey ];
    }

    /**
     * Returns value for either foreign key or eager loaded contents of relation,
     * depending on what is expected
     *
     * This should not break calls to the belongsTo relation method, including after
     * using the load() method to eager load the relation's contents
     *
     * @param string $key
     * @return mixed
     */
    public function getBelongsToRelationAttributeValue($key)
    {
        if ($this->relationLoaded($key)) {

            // check to make sure we don't break eager loading and internal
            // lookups for the foreign key
            $self = __FUNCTION__;

            $caller = array_first(debug_backtrace(false), function ($key, $trace) use ($self) {
                $caller = $trace['function'];

                // skip first two (since that includes the Model's generated method)
                if ($key < 2) {
                    return false;
                }

                if (array_get($trace, 'class') === 'Illuminate\Database\Eloquent\Relations\BelongsTo') {
                    return false;
                }

                return $caller != $self
                    && $caller != 'mutateAttribute'
                    && $caller != 'getAttributeValue'
                    && $caller != 'getAttribute'
                    && $caller != '__get';
            });

            if (    ! array_key_exists('class', $caller)
                ||  (   $caller['class'] !== 'Illuminate\Database\Eloquent\Model'
                    &&  $caller['class'] !== 'Illuminate\Database\Eloquent\Builder'
                    &&  $caller['class'] !== 'Illuminate\Database\Eloquent\Relations\Relation'
                    )
            ) {
                return $this->relations[$key];
            }
        }

        return $this->attributes[$key];
    }


    // ------------------------------------------------------------------------------
    //      Images
    // ------------------------------------------------------------------------------

    /**
     * Returns resize-enriched images for a special CMS model image relation
     *
     * To be called from an accessor, so it can return images based on its name,
     * which should be get<relationname>Attribute().
     *
     * @return Collection
     */
    protected function getImagesWithResizes()
    {
        // first get the images through the relation
        $relation = $this->getRelationForImagesWithResizesCaller();

        $images = $this->{$relation}()->get();

        if (empty($images)) return $images;

        // then get extra info and retrieve the resizes for it
        $fieldId = $this->getCmsReferenceFieldId($relation);

        $resizes = $this->getResizesForFieldId($fieldId);

        if (empty($resizes)) return $images;

        // decorate the images with resizes
        foreach ($images as $image) {

            $fileName = $image->file;
            $imageResizes = [];

            foreach ($resizes as $resize) {

                $imageResizes[ $resize->prefix ] = [
                    'id'     => $resize->id,
                    'prefix' => $resize->prefix,
                    'file'   => $resize->prefix . $fileName,
                    'url'    => Paths::images($resize->prefix . $fileName),
                    'width'  => $resize->width,
                    'height' => $resize->height,
                ];
            }

            // append full resizes info
            $image->resizes = $imageResizes;
        }

        return $images;
    }

    /**
     * @param int $fieldId
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    protected function getResizesForFieldId($fieldId)
    {
        $resizeModel = static::$cmsResizeModel;

        /** @var Builder|\Watson\Rememberable\Query\Builder $resizes */
        $resizes = $resizeModel::where('field_id', (int) $fieldId);

        if ($cacheTime = config('pxlcms.cache.resizes')) {
            $resizes->remember($cacheTime);
        }

        return $resizes->get();
    }

    /**
     * Get the relationship name of the image accessor for which images are enriched
     *
     * @return string
     */
    protected function getRelationForImagesWithResizesCaller()
    {
        $self = __FUNCTION__;

        $caller = Arr::first(debug_backtrace(false), function ($key, $trace) use ($self) {
            $caller = $trace['function'];

            return ! in_array($caller, ['getImagesWithResizes']) && $caller != $self;
        });

        if (is_null($caller)) return null;

        // strip 'get' from front and 'attribute' from rear
        return Str::camel(substr($caller['function'], 3, -9));
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
    public function lookUpLanguageIdForLocale($locale)
    {
        $locale = $this->normalizeLocale($locale);

        /** @var Model $languageModel */
        $languageModel = static::$cmsLanguageModel;

        $language = $languageModel::where(config('pxlcms.translatable.locale_code_column'), $locale)
            ->remember((config('pxlcms.cache.languages', 15)))
            ->first();

        if (empty($language)) return null;

        return $language->id;
    }

    /**
     * Retrieves locale for a given language ID code
     *
     * @param int $languageId
     * @return string|null  of language for ID was not found
     */
    public function lookupLocaleForLanguageId($languageId)
    {
        /** @var Model $languageModel */
        $languageModel = static::$cmsLanguageModel;

        $language = $languageModel::find($languageId);

        if (empty($language)) return null;

        return $this->normalizeToLocale($language->code);
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

    /**
     * Normalizes the language code in the CMS languages table so it
     * matches locales.
     *
     * @param string $languageCode
     * @return string
     */
    protected function normalizeToLocale($languageCode)
    {
        return strtolower($languageCode);
    }

}
