<?php
namespace Czim\PxlCms\Models;

use Czim\PxlCms\Helpers\Paths;
use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because files uses 'position' column
 *
 * @property string $file
 * @property string $extension
 * @property \Carbon\Carbon $uploaded
 * @property-read string $url
 * @property-read string $localPath
 */
class File extends CmsModel
{
    use Listify,
        Rememberable;

    protected $table = 'cms_m_files';

    public $timestamps = false;


    protected $fillable = [
        'file',
        'extension',
    ];

    protected $dates = [
        'uploaded',
    ];

    protected $hidden = [
        'field_id',
        'entry_id',
        'language_id',
    ];

    protected $appends = [
        'url',
        'localPath',
    ];

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
        return Paths::uploads($this->file);
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

}
