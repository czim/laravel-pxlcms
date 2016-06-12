<?php
namespace Generated\Models;

use Czim\PxlCms\Models\CmsModel;
use Watson\Rememberable\Rememberable;

/**
 * Class PageTranslation
 *
 * @property integer $id
 * @property string $title
 * @property string $name
 * @property string $content
 * @property string $seo_title
 * @property string $seo_description
 */
class PageTranslation extends CmsModel
{
    use Rememberable;

    protected $cmsModule = 22;

    protected $fillable = [
        'title',
        'name',
        'content',
        'seo_title',
        'seo_description',
    ];

}
