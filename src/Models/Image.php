<?php
namespace Czim\PxlCms\Models;

use Lookitsatravis\Listify\Listify;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because images uses 'position' column
 */
class Image extends CmsModel
{
    use Listify,
        Rememberable;

    protected $table = 'cms_m_images';

    public $timestamps = false;

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

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initListify();
    }

}
