<?php
namespace Czim\PxlCms\Models;

use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Note that some categories can have themselves as their own parent,
 * so be careful.
 */
class Category extends CmsModel
{
    use Rememberable,
        Listify;

    protected $table = 'cms_categories';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $hidden = [
        'module_id',
        'parent_category_id',
        'depth',
        'position',
    ];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_category_id')
            ->where('parent_category_id', '!=', $this->id);
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_category_id')
            ->where('id', '!=', $this->id);
    }
}
