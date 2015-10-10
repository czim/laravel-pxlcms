<?php
namespace Czim\PxlCms\Models;

use Illuminate\Database\Eloquent\Model;
use Watson\Rememberable\Rememberable;

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
