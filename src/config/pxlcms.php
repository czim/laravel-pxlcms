<?php

return [

    // ------------------------------------------------------------------------------
    //      Caching
    // ------------------------------------------------------------------------------

    'rememberable' => [

        // time to live for cache, in minutes
        'default-ttl'   => 10,

        // time to live for cache, in minutes for locales/languages model
        'languages-ttl' => 60,
    ],
    
    // ------------------------------------------------------------------------------
    //      CMS-specific table names
    // ------------------------------------------------------------------------------

    'tables' => [

        // prefix for module tables
        'prefix' => 'cms_',

        // meta-data for cms, used for CMS section grouping, etc
        'meta' => [

            'modules'     => 'cms_modules',
            'groups'      => 'cms_groups',
            'sections'    => 'cms_sections',
            'fields'      => 'cms_fields',
            'field_types' => 'cms_field_types',
            'tabs'        => 'cms_tabs',
            'users'       => 'cms_users',

            'field_options_choices' => 'cms_field_options_choices',
            'field_options_resizes' => 'cms_field_options_resizes',
        ],

        // CMS data table relevant for front-end too

        'languages'  => 'cms_languages',
        'categories' => 'cms_categories',
        'files'      => 'cms_m_files',
        'images'     => 'cms_m_images',
        'references' => 'cms_m_references',
        'slugs'      => 'cms_slugs',

    ],


    // ------------------------------------------------------------------------------
    //      Relationships / references
    // ------------------------------------------------------------------------------

    'relations' => [

        // cms_m_references columns
        'references' => [
            'keys' => [
                'field'    => 'from_field_id',
                'from'     => 'from_entry_id',
                'to'       => 'to_entry_id',
                'position' => 'position',
            ]
        ],
    ],

    // ------------------------------------------------------------------------------
    //      Translatable / ML
    // ------------------------------------------------------------------------------

    'translatable' => [

        // translation table key to indicate locale (ml language id)
        'locale_key' => 'language_id',

        // translation foreign key to translated belongsTo parent
        'translation_foreign_key' => 'entry_id',

        // the column for the locale 'code' in cms_languages
        'locale_code_column' => 'code',

        // the postfix for the translation table -- what to add to a module table to get the translation table
        'translation_table_postfix' => '_ml',
    ],
    
    // ------------------------------------------------------------------------------
    //      Automatic code generation from CMS db content
    // ------------------------------------------------------------------------------
    
    'generator' => [

        // which namespace to prepend for generated content
        'namespace' => [
            'models'   => 'App\\Models\\Generated\\',   // todo reset to without Generated
            'requests' => 'App\\Http\\Requests',
        ],

        /*
         * Which CMS content to ignore when generating
         */

        'ignore' => [

            // indicate modules by their number: cms_m##_<some_name>
            'modules' => [
            ],

        ],

        'custom' => [

            // indicate table names that should be treated as modules
            // but without translations, of course
            // (does nothing yet)
            'modules' => [

            ],
        ]
    ],

];
