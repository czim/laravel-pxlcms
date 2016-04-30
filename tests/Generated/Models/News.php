<?php
namespace Generated\Models;

use Czim\PxlCms\Models\CmsModel;
use Czim\PxlCms\Models\Image;
use Czim\PxlCms\Models\ListifyConstructorTrait;
use Czim\PxlCms\Models\Scopes\OnlyActive;
use Czim\PxlCms\Models\Scopes\PositionOrdered;
use Czim\PxlCms\Translatable\Translatable;
use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Class News
 *
 * @property \Carbon\Carbon $date
 * @property string $author
 * @property string $name
 * @property string $content
 * @property-read \Illuminate\Database\Eloquent\Collection|News[] $relevantNews
 * @property-read \Illuminate\Database\Eloquent\Collection|Page[] $pages
 * @property-read \Illuminate\Database\Eloquent\Collection|News[] $relevantNewsReverse
 * @property-read Image $image
 */
class News extends CmsModel
{
    use Listify,
        ListifyConstructorTrait,
        OnlyActive,
        PositionOrdered,
        Rememberable,
        Translatable;

    protected $cmsModule = 40;

    protected $fillable = [
        'date',
        'author',
        'name',
        'content',
    ];

    protected $translatedAttributes = [
        'name',
        'content',
    ];

    protected $dates = [
        'date',
    ];

    protected $relationsConfig = [
        'relevantNews' => [
            'field'  => 185,
            'parent' => false,
        ],
        'relevantNewsReverse' => [
            'field'  => 185,
            'parent' => true,
        ],
        'image' => [
            'field' => 182,
            'type'  => self::RELATION_TYPE_IMAGE,
        ],
    ];


    /*
     * Relationships
     */

    public function relevantNews()
    {
        return $this->belongsToMany(News::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function relevantNewsReverse()
    {
        return $this->belongsToMany(News::class);
    }

    public function image()
    {
        return $this->hasOne(Image::class);
    }

    public function getImageAttribute()
    {
        return $this->getImagesWithResizes();
    }

}
