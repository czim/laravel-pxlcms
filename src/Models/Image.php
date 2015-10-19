<?php
namespace Czim\PxlCms\Models;

use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because images uses 'position' column
 *
 * @property string $file
 * @property string $caption
 * @property string $extension
 * @property-read string $url
 * @property-read array  $resizes
 */
class Image extends CmsModel
{
    use Listify,
        Rememberable;

    protected $table = 'cms_m_images';

    public $timestamps = false;

    /**
     * The full URL to the image asset
     * @var string
     */
    public $url;

    /**
     * List of resizes for the image (if loaded through a model's magic property)
     *
     * @var array
     */
    public $resizes = [];


    protected $fillable = [
        'file',
        'caption',
        'extension',
    ];

    protected $dates = [
        'uploaded',
    ];

    protected $hidden = [
        'field_id',
        'entry_id',
    ];

    protected $appends = [
        'url',
        'resizes',
    ];

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initListify();
    }

    /*
     * Accessors for Appends
     */

    public function getUrlAttribute()
    {
        return $this->url;
    }

    public function getResizesAttribute()
    {
        return $this->resizes;
    }

}
