<?php
namespace Generated\Models;

use Czim\PxlCms\Models\Checkbox;
use Czim\PxlCms\Models\CmsModel;
use Czim\PxlCms\Models\ListifyConstructorTrait;
use Czim\PxlCms\Models\Scopes\OnlyActive;
use Czim\PxlCms\Models\Scopes\PositionOrdered;
use Czim\PxlCms\Translatable\Translatable;
use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Class Page
 *
 * @property integer $id
 * @property string $title
 * @property string $name
 * @property string $content
 * @property string $seo_title
 * @property string $seo_description
 * @property-read \Illuminate\Database\Eloquent\Collection|News[] $news
 * @property-read \Illuminate\Database\Eloquent\Collection|Checkbox[] $showInMenu
 */
class Page extends CmsModel
{
    use Listify,
        ListifyConstructorTrait,
        OnlyActive,
        PositionOrdered,
        Rememberable,
        Translatable;

    protected $cmsModule = 22;

    protected $fillable = [
        'title',
        'name',
        'content',
        'seo_title',
        'seo_description',
    ];

    protected $translatedAttributes = [
        'title',
        'name',
        'content',
        'seo_title',
        'seo_description',
    ];

    protected $hidden = [
        'news',
        'e_active',
        'e_position',
        'e_category_id',
        'e_user_id',
    ];

    protected $relationsConfig = [
        'showInMenu' => [
            'field' => 102,
            'type'  => self::RELATION_TYPE_CHECKBOX,
        ],
    ];


    /*
     * Accessors & Mutators
     */

    public function getNewsAttribute()
    {
        return $this->getBelongsToRelationAttributeValue('news');
    }


    /*
     * Relationships
     */

    public function news()
    {
        return $this->belongsTo(News::class);
    }

    public function showInMenu()
    {
        return $this->hasMany(Checkbox::class);
    }

}
