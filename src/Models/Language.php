<?php
namespace Czim\PxlCms\Models;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

/**
 * Can use Listify out of the box because images uses 'position' column
 *
 * @property string  $code
 * @property string  $language
 * @property string  $local
 * @property boolean $common
 * @property boolean $available
 * @property boolean $default
 */
class Language extends Model
{
    use Rememberable;

    protected $table = 'cms_languages';

    public $timestamps = false;

    protected $fillable = [
        'code',
        'language',
        'local',
        'common',
        'available',
        'default',
    ];

    protected $casts = [
        'common'    => 'boolean',
        'available' => 'boolean',
        'default'   => 'boolean',
    ];

}
