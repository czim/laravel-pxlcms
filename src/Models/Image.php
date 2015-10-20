<?php
namespace Czim\PxlCms\Models;

use Czim\PxlCms\Helpers\Paths;
use Illuminate\Database\Eloquent\Model;
use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because images uses 'position' column
 *
 * @property string $file
 * @property string $caption
 * @property string $extension
 * @property-read string $url
 * @property-read string $localPath
 * @property-read array  $resizes
 */
class Image extends Model
{
    use Listify,
        Rememberable;

    protected $table = 'cms_m_images';

    public $timestamps = false;

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
     * Accessors / Mutators for Appends
     */

    public function getUrlAttribute()
    {
        return Paths::images($this->file);
    }

    public function setUrlAttribute()
    {
        return null;
    }

    public function getLocalPathAttribute()
    {
        return Paths::uploadsInternal($this->file);
    }

    public function setLocalPathAttribute()
    {
        return null;
    }

    public function getResizesAttribute()
    {
        return $this->resizes;
    }

}
