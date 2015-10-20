<?php
namespace Czim\PxlCms\Models;

use Watson\Rememberable\Rememberable;

/**
 * @property int     $field_id
 * @property string  $prefix
 * @property int     $width
 * @property int     $height
 * @property boolean $make_grayscale
 * @property string  $watermark_image
 * @property boolean $watermark
 * @property int     $watermark_left
 * @property int     $watermark_top
 * @property int     $corners
 * @property string  $corners_name
 * @property boolean $no_cropping
 * @property string  $background_color
 * @property boolean $trim
 */
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
