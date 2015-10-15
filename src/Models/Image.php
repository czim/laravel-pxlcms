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

    /**
     * @var int|null    which field this is associated with, if any
     */
    public $associatedFieldId;


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

    public function toArray()
    {
        $array = parent::toArray();

        $array['field_id'] = $this->associatedFieldId;

        return $array;
    }

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initListify();
    }

    /**
     * Associate this image with a given fieldId
     * This makes sense when the image is retrieved for a field/relation of a module/model
     * that may have resizes associated with it, for instance.
     *
     * @param int $fieldId
     */
    public function associateWithFieldId($fieldId)
    {
        $this->associatedFieldId = $fieldId;
    }

}
