<?php
namespace Czim\PxlCms\Models;

use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because files uses 'position' column
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

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initListify();
    }

}
