<?php
namespace Czim\PxlCms\Models;

use Czim\PxlCms\Models\Scopes\PositionOrdered;
use Watson\Rememberable\Rememberable;

/**
 * This may be helpful for returning available options for Checkbox fields.
 * To do so, look up the field_id and make a query as follows:
 *
 *      FieldOptionChoice::where('field_id', 266)->remember(60)->get();
 *
 *
 * @property string $choice
 * @property int    $field_id
 * @property int    $position
 */
class FieldOptionChoice extends CmsModel
{
    const POSITION_COLUMN = 'position';

    use Rememberable,
        PositionOrdered;

    protected $table = 'cms_field_options_choices';

    public $timestamps = false;

    protected $fillable = [
        'choice',
        'position',
    ];

    protected $hidden = [
        'field_id',
        'entry_id',
    ];

}
