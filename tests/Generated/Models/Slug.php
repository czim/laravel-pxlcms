<?php
namespace Generated\Models;

use Czim\PxlCms\Models\CmsModel;
use Czim\PxlCms\Models\ListifyConstructorTrait;
use Czim\PxlCms\Models\Scopes\OnlyActive;
use Czim\PxlCms\Models\Scopes\PositionOrdered;
use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Class Slug
 *
 * @property integer $id
 * @property string $ref_module_id
 * @property string $entry_id
 * @property string $language_id
 * @property string $slug
 */
class Slug extends CmsModel
{
    use Listify,
        ListifyConstructorTrait,
        OnlyActive,
        PositionOrdered,
        Rememberable;

    protected $cmsModule = 1;

    protected $fillable = [
        'ref_module_id',
        'entry_id',
        'language_id',
        'slug',
    ];

}
