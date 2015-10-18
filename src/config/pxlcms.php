<?php

return [
    
    /*
    |--------------------------------------------------------------------------
    | File / Webserver Paths for CMS Content
    |--------------------------------------------------------------------------
    |
    | Paths to, for instance, CMS file and image uploads, relative to the
    | Laravel application root directory.
    |
    */

    // todo: implement/use this
    'paths' => [

        // Relative path to images from laravel/server root
        'images' => 'public/cms_images',

        // Relative path to file uploads from laravel/server root
        'files'  => 'public/cms_files',
    ],


    /*
    |--------------------------------------------------------------------------
    | CMS-specific Table Names
    |--------------------------------------------------------------------------
    |
    | CMS Content database table names for commonly used CMS entities.
    |
    */

    'tables' => [

        // Prefix string for module tables
        'prefix' => 'cms_',

        // Postfix string for multilingual tables
        'translation_postfix' => '_ml',

        // Meta-data for cms, used for CMS section grouping, etc
        'meta' => [

            'modules'     => 'cms_modules',
            'menu'        => 'cms_menu',
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


    /*
    |--------------------------------------------------------------------------
    | Relationships and References
    |--------------------------------------------------------------------------
    |
    | Configuration of how the CmsModel should handle relationships and what
    | data structure it may expect to find in the CMS database.
    |
    */

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
        // cms_m_images
        'images' => [
            'keys' => [
                'field' => 'field_id',
                'entry' => 'entry_id',
            ]
        ],
        // cms_m_files
        'files' => [
            'keys' => [
                'field' => 'field_id',
                'entry' => 'entry_id',
            ]
        ],
        // cms_m_checkboxes
        'checkboxes' => [
            'keys' => [
                'field' => 'field_id',
                'entry' => 'entry_id',
            ]
        ],
        // cms_categories
        'categories' => [
            'keys' => [
                'module'   => 'module_id',
                // The keyname on the model that refers to the categories record
                'category' => 'e_category_id',
            ]
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Translatable / Multilingual Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration of translatable models and other 'ML' CMS database content.
    |
    */

    'translatable' => [

        // Translation table key to indicate locale (ml language id)
        'locale_key' => 'language_id',

        // Translation foreign key to translated belongsTo parent
        'translation_foreign_key' => 'entry_id',

        // The column for the locale 'code' in cms_languages
        'locale_code_column' => 'code',

        // The postfix for the translation table -- what to add to a module table to get the translation table
        'translation_table_postfix' => '_ml',
    ],


    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Configuration of caching as applied to standard CMS models and relations.
    | By default, the Rememberable Eloquent trait is used for caching.
    |
    */

    // Cache configuration for standard model / cms relations -- time in minutes (Rememberable)
    // Set to 0 to disable caching
    'cache' => [

        // Resizes for Images (looked up for images by fieldId)
        'resizes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    |
    | Configuration of (global) scopes for models (and perhaps other classes).
    |
    */

    'scopes' => [

        'only_active' => [
            'column' => 'e_active',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Automatic Code Generation
    |--------------------------------------------------------------------------
    |
    | Configuration of generator for models and other code files
    |
    */
    
    'generator' => [

        // Which namespace to prepend for generated content
        'namespace' => [
            'models'       => 'App\\Models\\Generated',   // todo reset to without Generated
            'requests'     => 'App\\Http\\Requests',
            'repositories' => 'App\\Repositories',
        ],

        'aesthetics' => [
            // Whether to sort use import statements by their length (false sorts them alphabetically)
            'sort_imports_by_string_length' => false,
        ],


        // The FQN's for the standard CMS models for special relationships (and CMS categories)
        'standard_models' => [
            'category' => 'Czim\\PxlCms\\Models\\Category',
            'checkbox' => 'Czim\\PxlCms\\Models\\Checkbox',
            'file'     => 'Czim\\PxlCms\\Models\\File',
            'image'    => 'Czim\\PxlCms\\Models\\Image',

            // Name to (attempt to) use for a model's relation to the CMS category model.
            // The first non-conflicting name will be used. Note that this relationship is always singular
            'category_relation_names' => [
                'category', 'cmsCategory', 'pxlCmsCategory',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Model Generation
        |--------------------------------------------------------------------------
        |
        | Settings affecting automatic generation of Eloquent-based Model files.
        |
        */

        // Model-generation-specific settings
        'models' => [

            // FQN for base adapter model for generated models to extend
            'extend_model' => "Czim\\PxlCms\\Models\\CmsModel",

            // FQN for (optional) traits to import/use (whether they are used is not determined by this)
            'traits' => [
                'listify_fqn'             => "Lookitsatravis\\Listify\\Listify",
                'listify_constructor_fqn' => "Czim\\PxlCms\\Models\\ListifyConstructorTrait",
                'translatable_fqn'        => "Czim\\PxlCms\\Translatable\\Translatable",
                'rememberable_fqn'        => "Watson\\Rememberable\\Rememberable",

                'scope_active_fqn'        => "Czim\\PxlCms\\Models\\Scopes\\OnlyActive",
                'scope_position_fqn'      => "Czim\\PxlCms\\Models\\Scopes\\PositionOrder",
            ],

            // How to handle default/global scopes for models
            'scopes' => [

                // Available modes for each scope are:
                //
                //  'global'        for a global scope that may be ignored with ::withInactive() (scope trait)
                //  'method'        for adding a scope public method scopeActive() to each model
                //  null / false    do nothing, don't add scopes, all records returned by default

                // Scope for the e_active flag, only return active records
                'only_active'           => 'method',
                // If using 'method', the scope method name that will be used ('scope' is prefixed automatically)
                'only_active_method'    => 'active',

                // Scope order by e_position (the listify column)
                // todo: implement this
                'position_order'        => 'method',
                'position_order_method' => 'ordered',
            ],

            // The model name prefix settings help keep things organised for multi-menu, multi-group cmses
            // everything enabled would result in a classname like: "MenuGroupSectionModule"
            // the prefixes can be independently applied ("MenuModule", "MenuGroupModule", etc)
            //
            // Note that duplicate model names are resolved by prefixing first section, then group,
            // then menu names until this results in unique names. This behavior will occur even if
            // any of the prefixes are disabled here.
            //
            // Note that you should be careful when overriding model/module names, since duplicate name
            // checks are NOT done for forced names!
            'model_name' => [

                // Always prefix the section names to the model names (level above modules)
                'prefix_section_to_model_names' => false,
                // Always prefix the group names to the model names (higher level)
                'prefix_group_to_model_names' => false,
                // Always prefix the menu names to the model names (highest level)
                'prefix_menu_to_model_names' => false,

                // Singularize the names of all models (using str_singular)
                'singularize_model_names' => true,
            ],


            // Pluralize the names of reversed relationships if they are hasMany
            'pluralize_reversed_relationship_names' => true,
            // Same, but for self-referencing relationships
            'pluralize_reversed_relationship_names_for_self_reference' => true,

            // If used, simplify namespaces of standard models through use statements
            'include_namespace_of_standard_models' => true,

            // If a (reverse) relationship's name is already taken by an attribute
            // in the model, add this to prevent duplicate names
            'relationship_fallback_postfix' => 'Reference',

            // If a (reverse) relationship is self-referencing (on the model), the
            // relationship name gets this postfixed to prevent duplicate names
            'relationship_reverse_postfix'  => 'Reverse',

            // Postfix string for translation model
            'translation_model_postfix' => 'Translation',

            // Whether to allow overriding the current locale for a translated standard model
            // relation (such as images/files) through a parameter on the relations method
            'allow_locale_override_on_translated_model_relation' => true,

            // Singularize relationship names for hasOne and belongsTo relationships that have only 1 possible match
            // This is not used, since it would break the database dependency!
            //'singularize_single_relationships' => true,

            // Whether to add foreign key attribute names to the $hidden property
            'hide_foreign_key_attributes' => true,

            // Whether to use rememberable trait on models generated
            'enable_rememberable_cache' => true,

            // Whether to enable laravel timestamps for models with created_at and update_at
            "enable_timestamps_on_models_with_suitable_attributes" => true,

            // The date property type (or FQN) to use for ide-helper tags referring to date fields
            'date_property_fqn' => '\\Carbon\\Carbon',

            // If adding hidden attributes for a model, always add these attributes to hide aswell
            'default_hidden_fields' => [
                'e_active',
                'e_position',
                'e_category_id',
                'e_user_id',
            ],

            // Settings for ide-helper content to add to models
            'ide_helper' => [

                // Whether to add (id-helper data to) a docblock for the model
                'add_docblock' => true,

                // Whether to add @property tags for the magic attribute properties of the model
                'tag_attribute_properties' => true,

                // Whether to add @property-read tags for the model's relationships
                'tag_relationship_magic_properties' => true,

                // Whether to add @method static tags for whereProperty($value) type methods
                // this can get quite spammy for models with many attributes
                'tag_magic_where_methods_for_attributes' => false,

                // todo: make and use a tag exception toggle for whereCategory special relation tags
            ],
        ],


        /*
        |--------------------------------------------------------------------------
        | Ignoring CMS content while analyzing generating
        |--------------------------------------------------------------------------
        |
        | ** This is not currently supported **
        | Just a placeholder here as a reminder that this might be a good idea.
        |
        */

        'ignore' => [

            // Indicate modules by their number: cms_m##_<some_name>
            'modules' => [
            ],

        ],

        /*
        |--------------------------------------------------------------------------
        | Overriding Automatically Generated Content
        |--------------------------------------------------------------------------
        |
        | If you want to override specific properties or output generated by
        | this package for a given model, you can define the specifics here.
        |
        */

        'override' => [

            // Force some values for models generated to overrule the data analysis
            'models' => [

                // Key the array by the module ID that the model is based on.

                // Example:
                // You can use any or all of these settings per model
                "0" => [

                    // Force a custom name for the model
                    "name" => "CustomName",

                    // Force listify trait on or off
                    "listify" => true,

                    // Configuration for model attributes
                    "attributes" => [

                        // Set specific attribute names (snake_case notation) to not be fillable
                        // This amends the normal fillable list, so anything not listed here (and normally
                        // made fillable) will be fillable.
                        "fillable-remove" => [],
                        // Set specific attribute names (snake_case notation) to be fillable (full list)
                        // This overrides "remove-fillable"
                        "fillable" => [],
                        // Set to true to make no attribute fillable
                        "fillable-empty" => false,

                        // Add attribute names (snake_case notation) to overrule hidden fields with
                        // Don't forget e_position, e_active, etc, if you use this
                        "hidden" => [],
                        // Set this to true if you don't want to hide any fields for the model
                        "hidden-empty" => false,

                        // Add attribute names (snake_case notation) with the type to cast to
                        "casts" => [
                            // "some_attribute" => "boolean",
                        ],
                        // Add attribute names (snake_case notation) for which NOT to add casts
                        "casts-remove" => [],
                    ],



                    // Configuration per relationship of the model (references)
                    // Use camelCase notation for the names
                    // todo: implement this
                    "relationships" => [


                        "yourRelationName" => [
                            // Force a different name for the relation
                            // Use camelCase notation, do not use a conflicting/existing attribute name
                            'name' => "overrideName",
                            // Do not create reverse relationships for this relation
                            "prevent_reverse" => true,
                        ]

                    ]
                ],

            ],
        ],

    ],

];
