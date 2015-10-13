<?php
namespace Czim\PxlCms\Models;

use Watson\Rememberable\Rememberable;

class Checkbox extends CmsModel
{
    use Rememberable;

    protected $table = 'cms_m_checkboxes';

    public $timestamps = false;

    protected $fillable = [
        'choice',
    ];

    protected $hidden = [
        'field_id',
        'entry_id',
    ];

}
