<?php
namespace Czim\PxlCms\Models;

use Watson\Rememberable\Rememberable;

class Resize extends CmsModel
{
    use Rememberable;

    protected $table = 'cms_field_options_resizes';

    public $timestamps = false;

    protected $fillable = [
        'prefix',
        'width',
        'height',
        'make_grayscale',
        'watermark',
        'watermark_image',
        'watermark_left',
        'watermark_top',
        'corners',
        'corners_name',
        'no_cropping',
        'background_color',
        'trim',
    ];

    protected $hidden = [
        'field_id',
    ];

    protected $casts = [
        'make_grayscale' => 'boolean',
        'watermark'      => 'boolean',
        'no_cropping'    => 'boolean',
        'trim'           => 'boolean',
    ];

}
