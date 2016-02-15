<?php
namespace Czim\PxlCms\Test\Helpers;

class Queries extends SqliteQueries implements QueriesInterface
{

    public function getCreateQueries()
    {
        return [
            'CREATE TABLE `cms_auth_codes` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `code` varchar(16) DEFAULT NULL,
                `enabled` tinyint(1) DEFAULT \'1\',
                PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_categories` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `module_id` mediumint(8) unsigned NOT NULL,
              `parent_category_id` mediumint(8) unsigned NOT NULL,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `description` text COLLATE utf8_unicode_ci NOT NULL,
              `depth` tinyint(3) unsigned NOT NULL,
              `position` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              KEY `parent_category_id` (`parent_category_id`),
              KEY `module_id` (`module_id`)
            )',
            'CREATE TABLE `cms_field_options_choices` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` mediumint(8) unsigned NOT NULL,
              `choice` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `position` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id`),
              KEY `field_id` (`field_id`)
            )',
            'CREATE TABLE `cms_field_options_resizes` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `field_id` mediumint(8) unsigned NOT NULL,
              `prefix` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `width` smallint(5) unsigned NOT NULL,
              `height` smallint(5) unsigned NOT NULL,
              `make_grayscale` tinyint(1) NOT NULL DEFAULT \'0\',
              `watermark` tinyint(1) NOT NULL DEFAULT \'0\',
              `watermark_image` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `watermark_left` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              `watermark_top` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              `corners` tinyint(1) NOT NULL DEFAULT \'0\',
              `corners_name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `no_cropping` tinyint(1) NOT NULL DEFAULT \'0\',
              `background_color` varchar(6) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
              `trim` tinyint(1) NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id`),
              KEY `field_id` (`field_id`)
            )',
            'CREATE TABLE `cms_field_types` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `form_element` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `description` tinytext CHARACTER SET utf8 NOT NULL,
              `db_field` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `uses_choices` tinyint(1) NOT NULL DEFAULT \'0\',
              `uses_resizes` tinyint(1) NOT NULL DEFAULT \'0\',
              `uses_massupload` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
              `multi_value` tinyint(1) NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_fields` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `module_id` mediumint(8) unsigned NOT NULL,
              `field_type_id` mediumint(8) unsigned NOT NULL,
              `indexed` tinyint(1) NOT NULL DEFAULT \'0\',
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `display_name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `identifier` tinyint(1) NOT NULL DEFAULT \'0\',
              `position` smallint(5) unsigned NOT NULL,
              `value_count` tinyint(3) unsigned NOT NULL DEFAULT \'1\',
              `refers_to_module` smallint(5) unsigned DEFAULT NULL,
              `help_text` mediumtext CHARACTER SET utf8 NOT NULL,
              `render_x` smallint(5) unsigned NOT NULL,
              `render_y` smallint(5) unsigned NOT NULL,
              `render_dx` smallint(5) unsigned NOT NULL,
              `render_dy` smallint(5) unsigned NOT NULL,
              `multilingual` tinyint(1) NOT NULL DEFAULT \'0\',
              `tab_id` smallint(5) NOT NULL,
              `custom_html` text COLLATE utf8_unicode_ci NOT NULL,
              `default` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `options` text COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              KEY `module_id` (`module_id`),
              KEY `field_type_id` (`field_type_id`)
            )',
            'CREATE TABLE `cms_groups` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `menu_id` mediumint(8) unsigned NOT NULL,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `position` smallint(5) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_languages` (
              `id` mediumint(8) unsigned NOT NULL,
              `code` varchar(5) COLLATE latin1_general_ci NOT NULL,
              `language` tinytext CHARACTER SET utf8 NOT NULL,
              `local` tinytext CHARACTER SET utf8 NOT NULL,
              `common` tinyint(1) NOT NULL,
              `available` tinyint(1) NOT NULL DEFAULT \'0\',
              `default` tinyint(1) NOT NULL DEFAULT \'0\',
              UNIQUE KEY `id` (`id`)
            )',
            'CREATE TABLE `cms_m_checkboxes` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(10) unsigned NOT NULL,
              `field_id` int(10) unsigned NOT NULL,
              `choice` tinytext NOT NULL,
              PRIMARY KEY (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `field_id` (`field_id`)
            )',
            'CREATE TABLE `cms_m_files` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(10) unsigned NOT NULL,
              `field_id` int(10) unsigned NOT NULL,
              `language_id` int(10) unsigned NOT NULL,
              `file` tinytext NOT NULL,
              `extension` varchar(5) NOT NULL,
              `uploaded` int(11) unsigned NOT NULL,
              `position` int(10) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `field_id` (`field_id`)
            )',
            'CREATE TABLE `cms_m_images` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(10) unsigned NOT NULL,
              `field_id` int(10) unsigned NOT NULL,
              `language_id` int(10) unsigned NOT NULL,
              `file` tinytext NOT NULL,
              `caption` tinytext NOT NULL,
              `extension` varchar(5) NOT NULL,
              `uploaded` int(11) unsigned NOT NULL,
              `position` int(10) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `field_id` (`field_id`)
            )',
            'CREATE TABLE `cms_m_references` (
              `from_field_id` mediumint(8) unsigned NOT NULL,
              `from_entry_id` int(10) unsigned NOT NULL,
              `to_entry_id` int(10) unsigned NOT NULL,
              `position` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`from_field_id`,`from_entry_id`,`to_entry_id`)
            )',
            'CREATE TABLE `cms_m_thumbs` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `image_id` int(10) unsigned NOT NULL,
              `resize_id` mediumint(8) unsigned DEFAULT NULL,
              `filename` tinytext NOT NULL,
              PRIMARY KEY (`id`),
              KEY `image_id` (`image_id`),
              KEY `resize_id` (`resize_id`)
            )',

            'CREATE TABLE `cms_menu` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `icon` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `position` smallint(5) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_modules` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `section_id` mediumint(8) unsigned NOT NULL,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `introduction` mediumtext CHARACTER SET utf8 NOT NULL,
              `position` smallint(5) unsigned NOT NULL,
              `max_entries` mediumint(8) unsigned NOT NULL DEFAULT \'0\',
              `client_cat_control` tinyint(1) NOT NULL DEFAULT \'0\',
              `max_cat_depth` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
              `sort_entries_manually` tinyint(1) NOT NULL DEFAULT \'1\',
              `sort_entries_by` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `is_custom` tinyint(1) NOT NULL DEFAULT \'0\',
              `custom_path` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `admin_only` tinyint(1) NOT NULL DEFAULT \'0\',
              `icon_image` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `allow_create` tinyint(1) NOT NULL DEFAULT \'1\',
              `allow_update` tinyint(1) NOT NULL DEFAULT \'1\',
              `allow_delete` tinyint(1) NOT NULL DEFAULT \'1\',
              `xml_access` tinyint(1) NOT NULL DEFAULT \'1\',
              `custom_rendering` tinyint(1) NOT NULL DEFAULT \'0\',
              `view_own_entries_only` tinyint(1) NOT NULL DEFAULT \'0\',
              `searchable` tinyint(1) NOT NULL DEFAULT \'0\',
              `allow_column_sorting` tinyint(1) NOT NULL DEFAULT \'0\',
              `simulate_categories_for` mediumint(8) unsigned NOT NULL,
              `hide_from_menu` tinyint(1) NOT NULL DEFAULT \'0\',
              `csv_export` tinyint(1) NOT NULL DEFAULT \'0\',
              `related_items_filter` tinyint(1) NOT NULL DEFAULT \'0\',
              `search_referenced_identifiers` tinyint(1) NOT NULL DEFAULT \'0\',
              `override_table_name` tinytext CHARACTER SET utf8,
              PRIMARY KEY (`id`),
              KEY `section_id` (`section_id`)
            )',
            'CREATE TABLE `cms_sections` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `group_id` mediumint(8) unsigned NOT NULL,
              `name` tinytext COLLATE utf8_unicode_ci NOT NULL,
              `position` smallint(5) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_tabs` (
              `id` smallint(5) NOT NULL AUTO_INCREMENT,
              `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
              `module_id` mediumint(8) NOT NULL,
              `position` smallint(5) NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_user_languages` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `user_id` mediumint(8) unsigned NOT NULL,
              `language` mediumint(8) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_user_rights` (
              `user_id` mediumint(8) unsigned NOT NULL,
              `module_id` mediumint(8) unsigned NOT NULL,
              `create` tinyint(1) NOT NULL DEFAULT \'0\',
              `read` tinyint(1) NOT NULL DEFAULT \'0\',
              `update` tinyint(1) NOT NULL DEFAULT \'0\',
              `delete` tinyint(1) NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`user_id`,`module_id`)
            )',
            'CREATE TABLE `cms_users` (
              `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
              `username` tinytext COLLATE latin1_general_ci NOT NULL,
              `password` varchar(32) COLLATE latin1_general_ci NOT NULL,
              `fullname` tinytext CHARACTER SET utf8 NOT NULL,
              `email` tinytext COLLATE latin1_general_ci NOT NULL,
              `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
              `user_manager` tinyint(1) NOT NULL DEFAULT \'0\',
              `created_by` mediumint(8) unsigned NOT NULL,
              `last_login` int(10) unsigned NOT NULL,
              `ref_filter_module_id` int(10) unsigned DEFAULT NULL,
              `ref_filter_entry_id` int(10) unsigned DEFAULT NULL,
              `auth_code_id` int(11) unsigned NOT NULL DEFAULT \'0\',
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `sessions` (
              `id` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\',
              `last_modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              `data` text COLLATE utf8_unicode_ci,
              PRIMARY KEY (`id`)
            )',
        ];
    }

    /**
     * Return queries to create the module tables
     *
     * @return string[]
     */
    public function getCreateModuleQueries()
    {
        return [
            'CREATE TABLE `cms_m1_slugs` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `ref_module_id` tinytext CHARACTER SET utf8 NOT NULL,
              `entry_id` tinytext CHARACTER SET utf8 NOT NULL,
              `language_id` int(11) DEFAULT NULL,
              `slug` tinytext CHARACTER SET utf8 NOT NULL,
              `e_active` tinyint(1) NOT NULL DEFAULT \'1\',
              `e_position` int(11) unsigned NOT NULL,
              `e_category_id` mediumint(8) unsigned DEFAULT NULL,
              `e_user_id` smallint(5) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_m40_news` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `date` int(13) DEFAULT NULL,
              `author` tinytext CHARACTER SET utf8 NOT NULL,
              `e_active` tinyint(1) NOT NULL DEFAULT \'1\',
              `e_position` int(11) unsigned NOT NULL,
              `e_category_id` mediumint(8) unsigned DEFAULT NULL,
              `e_user_id` smallint(5) unsigned NOT NULL,
              PRIMARY KEY (`id`)
            )',
            'CREATE TABLE `cms_m40_news_ml` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(10) unsigned NOT NULL,
              `language_id` mediumint(8) unsigned NOT NULL,
              `name` tinytext CHARACTER SET utf8 NOT NULL,
              `content` text CHARACTER SET utf8 NOT NULL,
              PRIMARY KEY (`id`)
            )',

        ];
    }

}
