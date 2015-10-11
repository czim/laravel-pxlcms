<?php
namespace Czim\PxlCms\Generator;

class FieldType
{
    const TYPE_INPUT     = 1;
    const TYPE_DROPDOWN  = 2;
    const TYPE_LABEL     = 12;
    const TYPE_COLORCODE = 15;

    const TYPE_TEXT            = 4;
    const TYPE_TEXT_HTML_FLEX  = 5;
    const TYPE_TEXT_HTML_RAW   = 14;
    const TYPE_TEXT_HTML_FCK   = 18;
    const TYPE_TEXT_HTML_ALOHA = 33;
    const TYPE_TEXT_LONG       = 34;

    const TYPE_BOOLEAN = 19;
    const TYPE_INTEGER = 20;
    const TYPE_NUMERIC = 13;
    const TYPE_FLOAT   = 24;
    const TYPE_DATE    = 29;

    const TYPE_REFERENCE            = 16;
    const TYPE_REFERENCE_MANY       = 17;
    const TYPE_REFERENCE_NEGATIVE   = 25;
    const TYPE_REFERENCE_AUTOSORT   = 26;
    const TYPE_REFERENCE_CHECKBOXES = 27;
    const TYPE_REFERENCE_CROSS      = 28;   // ?

    const TYPE_SLIDER   = 30; // ?
    const TYPE_RANGE    = 31; // ?
    const TYPE_LOCATION = 32; // ?

    const TYPE_IMAGE       = 6;
    const TYPE_IMAGE_MULTI = 7;
    const TYPE_FILE        = 8;
    const TYPE_CHECKBOX    = 10; // NOT a boolean!

    const TYPE_CUSTOM_HIDDEN = 21;
    const TYPE_CUSTOM        = 22;



    protected $types = [
        1  => "Input",
        2  => "Dropdown",
        4  => "Text",
        5  => "HTML Text (Flex)",
        6  => "Image",                      // hasOne to Image model on FK entry_id (with language (0 if not ML) and from field id scope)
        7  => "Multi-Image (Gallery)",      // hasMany to Image model on FK entry_id (with language (0 if not ML) and from field id scope)      [find example]
        8  => "File upload",                // hasOne to CmsFile model on FK entry_id (with language (0 if not ML) and from field id scope)     [find example]
        10 => "Checkbox",                   // hasMany to Checkbox model on FK entry_id (with field id scope)                                   [find example] + do we need this?
        12 => "Label (Admin editable)",
        13 => "Numeric",
        14 => "Raw HTML source",
        15 => "Colorcode",
        16 => "Reference",                  // hasOne + belongsTo (One)
        17 => "Reference (1:N)",            // belongsToMany with pivot cms_m_reference between from_entry_id, to_entry_id (with from_field_id scope, ordered by position)
        18 => "HTML Text (FCK)",
        19 => "Boolean",
        20 => "Numeric (integer)",
        21 => "Custom Hidden Input",
        22 => "Custom Input",
        24 => "Float (10,6) [Lat/Lng]",
        25 => "Reference (negative)",       // hasOne reversed [?]
        26 => "Reference (1:N Autosort)",   // belongsToMany with additional sort order? [find example, test]
        27 => "Reference (1:N Checkboxes)", // ?
        28 => "Cross Reference",
        29 => "Date",
        30 => "Slider",
        31 => "Range",
        32 => "Location",
        33 => "HTML Text (Aloha)",
        34 => "Longtext",
    ];


    /**
     * @param int $id
     * @return string|null
     */
    public function getFriendlyNameForId($id)
    {
        return array_get($this->types, (string) $id);
    }

}
