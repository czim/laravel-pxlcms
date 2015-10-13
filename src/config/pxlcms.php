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

        // postfix for multilingual tables
        'translation_postfix' => '_ml',

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
        'checkboxes' => 'cms_m_checkboxes',
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

        // whether to sort use import statements by their length (false sorts them alphabetically)
        'sort_imports_by_string_length' => true,

        // the FQN's for the standard CMS models for special relationships
        'standard_models' => [
            'image'    => 'Czim\\PxlCms\\Models\\Image',
            'checkbox' => 'Czim\\PxlCms\\Models\\Checkbox',
            'file'     => 'Czim\\PxlCms\\Models\\File',
        ],

        // if used, simplify namespaces of standard models through use statements
        'include_namespace_of_standard_models' => true,

        // if a (reverse) relationship's name is already taken by an attribute
        // in the model, add this to prevent duplicate names
        'relationship_fallback_postfix' => 'Reference',

        // if a (reverse) relationship is self-referencing (on the model), the
        // relationship name gets this postfixed to prevent duplicate names
        'relationship_reverse_postfix'  => 'Reverse',

        // postfix for translation model
        'translation_model_postfix' => 'Translation',

        // singularize the names of models (using str_singular)
        'singularize_model_names' => true,

        // singularize relationship names for hasOne and belongsTo relationships that have only 1 possible match
        // not used, since it would break the database dependency!
        //'singularize_single_relationships' => true,

        // whether to add foreign key attribute names to the $hidden property
        'hide_foreign_key_attributes' => true,

        // whether to use rememberable trait on models generated
        'enable_rememberable_cache' => true,

        // if adding hidden attributes for a model, always add these attributes to hide aswell
        'default_hidden_fields' => [
            'e_active',
            'e_position',
            'e_category_id',
            'e_user_id',
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

            // custom field names (set this per app)
            // to override poorly chosen (singular vs. plural f.i.) references,
            // etc.
            'fields' => [

            ],
        ],

    ],

];
