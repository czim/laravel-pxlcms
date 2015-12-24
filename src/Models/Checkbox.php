<?php
namespace Czim\PxlCms\Models;

use Watson\Rememberable\Rememberable;

/**
 * @property string $choice
 * @property int    $field_id
 * @property int    $entry_id
 */
class Checkbox extends CmsModel
{
    use Rememberable;

    protected $table = 'cms_m_checkboxes';

    public $timestamps = false;

    protected $fillable = [
        'field_id',
        'choice',
    ];

    protected $hidden = [
        'field_id',
        'entry_id',
    ];

}
