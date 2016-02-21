<?php
namespace Generated\Models;

use Czim\PxlCms\Models\CmsModel;
use Watson\Rememberable\Rememberable;

/**
 * @property string $name
 * @property string $content
 */
class NewsTranslation extends CmsModel
{
    use Rememberable;

    protected $cmsModule = 40;

    protected $fillable = [
        'name',
        'content',
    ];

}
