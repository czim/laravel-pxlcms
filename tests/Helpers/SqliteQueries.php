<?php
namespace Czim\PxlCms\Test\Helpers;

class SqliteQueries implements QueriesInterface
{

    /**
     * Returns queries to set up base CMS structure
     *
     * @return string[]
     */
    public function getCreateQueries()
    {
        return [
            'CREATE TABLE `cms_auth_codes` (
                `id` integer primary key autoincrement,
                `code` varchar(16) DEFAULT NULL,
                `enabled` INT DEFAULT \'1\'
            )',
            'CREATE TABLE `cms_categories` (
              `id` integer primary key autoincrement,
              `module_id` integer NOT NULL,
              `parent_category_id` integer NOT NULL,
              `name` text NOT NULL,
              `description` text NOT NULL,
              `depth` tinyint(3) NOT NULL,
              `position` integer NOT NULL
            )',
            'CREATE TABLE `cms_field_options_choices` (
              `id` integer primary key autoincrement,
              `field_id` integer NOT NULL,
              `choice` text NOT NULL,
              `position` tinyint(3) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_field_options_resizes` (
              `id` integer primary key autoincrement,
              `field_id` integer NOT NULL,
              `prefix` text NOT NULL,
              `width` smallint(5) NOT NULL,
              `height` smallint(5) NOT NULL,
              `make_grayscale` tinyint(1) NOT NULL DEFAULT \'0\',
              `watermark` tinyint(1) NOT NULL DEFAULT \'0\',
              `watermark_image` text NOT NULL,
              `watermark_left` tinyint(3) NOT NULL DEFAULT \'0\',
              `watermark_top` tinyint(3) NOT NULL DEFAULT \'0\',
              `corners` tinyint(1) NOT NULL DEFAULT \'0\',
              `corners_name` text NOT NULL,
              `no_cropping` tinyint(1) NOT NULL DEFAULT \'0\',
              `background_color` varchar(6),
              `trim` tinyint(1) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_field_types` (
              `id` integer primary key autoincrement,
              `name` text NOT NULL,
              `form_element` text NOT NULL,
              `description` text,
              `db_field` text NOT NULL,
              `uses_choices` tinyint(1) NOT NULL DEFAULT \'0\',
              `uses_resizes` tinyint(1) NOT NULL DEFAULT \'0\',
              `uses_massupload` tinyint(1) NOT NULL DEFAULT \'0\',
              `multi_value` tinyint(1) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_fields` (
              `id` integer primary key autoincrement,
              `module_id` integer NOT NULL,
              `field_type_id` integer NOT NULL,
              `indexed` tinyint(1) NOT NULL DEFAULT \'0\',
              `name` text NOT NULL,
              `display_name` text NOT NULL,
              `identifier` tinyint(1) NOT NULL DEFAULT \'0\',
              `position` smallint(5) NOT NULL,
              `value_count` tinyint(3) NOT NULL DEFAULT \'1\',
              `refers_to_module` smallint(5) DEFAULT NULL,
              `help_text` mediumtext,
              `render_x` smallint(5) NOT NULL,
              `render_y` smallint(5) NOT NULL,
              `render_dx` smallint(5) NOT NULL,
              `render_dy` smallint(5) NOT NULL,
              `multilingual` tinyint(1) NOT NULL DEFAULT \'0\',
              `tab_id` smallint(5) NOT NULL,
              `custom_html` text NOT NULL,
              `default` varchar(255) DEFAULT NULL,
              `options` text NOT NULL
            )',
            'CREATE TABLE `cms_groups` (
              `id` integer primary key autoincrement,
              `menu_id` integer NOT NULL,
              `name` text NOT NULL,
              `position` smallint(5) NOT NULL
            )',
            'CREATE TABLE `cms_languages` (
              `id` integer primary key autoincrement,
              `code` varchar(5),
              `language` text,
              `local` text,
              `common` tinyint(1) NOT NULL,
              `available` tinyint(1) NOT NULL DEFAULT \'0\',
              `default` tinyint(1) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_m_checkboxes` (
              `id` integer primary key autoincrement,
              `entry_id` int(10) NOT NULL,
              `field_id` int(10) NOT NULL,
              `choice` text NOT NULL
            )',
            'CREATE TABLE `cms_m_files` (
              `id` integer primary key autoincrement,
              `entry_id` int(10) NOT NULL,
              `field_id` int(10) NOT NULL,
              `language_id` int(10) NOT NULL,
              `file` text NOT NULL,
              `extension` varchar(5) NOT NULL,
              `uploaded` int(11) NOT NULL,
              `position` int(10) NOT NULL
            )',
            'CREATE TABLE `cms_m_images` (
              `id` integer primary key autoincrement,
              `entry_id` int(10) NOT NULL,
              `field_id` int(10) NOT NULL,
              `language_id` int(10) NOT NULL,
              `file` text NOT NULL,
              `caption` text NOT NULL,
              `extension` varchar(5) NOT NULL,
              `uploaded` int(11) NOT NULL,
              `position` int(10) NOT NULL
            )',
            'CREATE TABLE `cms_m_references` (
              `from_field_id` integer NOT NULL,
              `from_entry_id` integer NOT NULL,
              `to_entry_id` int(10) NOT NULL,
              `position` tinyint(3) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_m_thumbs` (
              `id` integer primary key autoincrement,
              `image_id` int(10) NOT NULL,
              `resize_id` integer DEFAULT NULL,
              `filename` text NOT NULL
            )',

            'CREATE TABLE `cms_menu` (
              `id` integer primary key autoincrement,
              `name` text NOT NULL,
              `icon` text NOT NULL,
              `position` smallint(5) NOT NULL
            )',
            'CREATE TABLE `cms_modules` (
              `id` integer primary key autoincrement,
              `section_id` integer NOT NULL,
              `name` text NOT NULL,
              `introduction` mediumtext,
              `position` smallint(5) NOT NULL,
              `max_entries` integer NOT NULL DEFAULT \'0\',
              `client_cat_control` tinyint(1) NOT NULL DEFAULT \'0\',
              `max_cat_depth` tinyint(3) NOT NULL DEFAULT \'0\',
              `sort_entries_manually` tinyint(1) NOT NULL DEFAULT \'1\',
              `sort_entries_by` text NOT NULL,
              `is_custom` tinyint(1) NOT NULL DEFAULT \'0\',
              `custom_path` text NOT NULL,
              `admin_only` tinyint(1) NOT NULL DEFAULT \'0\',
              `icon_image` text NOT NULL,
              `allow_create` tinyint(1) NOT NULL DEFAULT \'1\',
              `allow_update` tinyint(1) NOT NULL DEFAULT \'1\',
              `allow_delete` tinyint(1) NOT NULL DEFAULT \'1\',
              `xml_access` tinyint(1) NOT NULL DEFAULT \'1\',
              `custom_rendering` tinyint(1) NOT NULL DEFAULT \'0\',
              `view_own_entries_only` tinyint(1) NOT NULL DEFAULT \'0\',
              `searchable` tinyint(1) NOT NULL DEFAULT \'0\',
              `allow_column_sorting` tinyint(1) NOT NULL DEFAULT \'0\',
              `simulate_categories_for` integer NOT NULL,
              `hide_from_menu` tinyint(1) NOT NULL DEFAULT \'0\',
              `csv_export` tinyint(1) NOT NULL DEFAULT \'0\',
              `related_items_filter` tinyint(1) NOT NULL DEFAULT \'0\',
              `search_referenced_identifiers` tinyint(1) NOT NULL DEFAULT \'0\',
              `override_table_name` text
            )',
            'CREATE TABLE `cms_sections` (
              `id` integer primary key autoincrement,
              `group_id` mediumint(8) NOT NULL,
              `name` tinytext,
              `position` smallint(5) NOT NULL
            )',
            'CREATE TABLE `cms_tabs` (
              `id` integer primary key autoincrement,
              `name` varchar(50) NOT NULL,
              `module_id` integer NOT NULL,
              `position` smallint(5) NOT NULL
            )',
            'CREATE TABLE `cms_user_languages` (
              `id` integer primary key autoincrement,
              `user_id` integer NOT NULL,
              `language` integer NOT NULL
            )',
            'CREATE TABLE `cms_user_rights` (
              `user_id` integer primary key,
              `module_id` integer NOT NULL,
              `create` tinyint(1) NOT NULL DEFAULT \'0\',
              `read` tinyint(1) NOT NULL DEFAULT \'0\',
              `update` tinyint(1) NOT NULL DEFAULT \'0\',
              `delete` tinyint(1) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `cms_users` (
              `id` integer primary key autoincrement,
              `username` text,
              `password` varchar(32),
              `fullname` text,
              `email` text,
              `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
              `user_manager` tinyint(1) NOT NULL DEFAULT \'0\',
              `created_by` integer NOT NULL,
              `last_login` int(10) NOT NULL,
              `ref_filter_module_id` int(10) DEFAULT NULL,
              `ref_filter_entry_id` int(10) DEFAULT NULL,
              `auth_code_id` int(11) NOT NULL DEFAULT \'0\'
            )',
            'CREATE TABLE `sessions` (
              `id` varchar(40) NOT NULL DEFAULT \'\',
              `last_modified` timestamp NOT NULL,
              `data` text
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
              `id` integer primary key autoincrement,
              `ref_module_id` text,
              `entry_id` text,
              `language_id` int(11) DEFAULT NULL,
              `slug` text,
              `e_active` tinyint(1) NOT NULL DEFAULT \'1\',
              `e_position` int(11) NOT NULL,
              `e_category_id` integer DEFAULT NULL,
              `e_user_id` smallint(5) NOT NULL
            )',

            'CREATE TABLE `cms_m22_pages` (
              `id` integer primary key autoincrement,
              `e_active` tinyint(1) NOT NULL DEFAULT \'1\',
              `e_position` int(11) NOT NULL,
              `e_category_id` mediumint(8) DEFAULT NULL,
              `e_user_id` smallint(5) NOT NULL
            )',

            'CREATE TABLE `cms_m22_pages_ml` (
              `id` integer primary key autoincrement,
              `entry_id` int(10) NOT NULL,
              `language_id` mediumint(8) NOT NULL,
              `title` tinytext,
              `name` tinytext,
              `content` text,
              `seo_title` tinytext,
              `seo_description` tinytext not null
            )',
        ];
    }

    /**
     * Returns queries to fill basic CMS content
     *
     * @return string[]
     */
    public function getBasicCmsContentQueries()
    {
        return [
            'INSERT INTO `cms_field_types` (`id`, `name`, `form_element`, `description`, `db_field`, `uses_choices`, `uses_resizes`, `uses_massupload`, `multi_value`)
            VALUES
                (1, \'Input\', \'text\', \'One line text entry.\', \'TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (2, \'Dropdown\', \'select\', \'A dropdown box with multiple choices of which 1 may be selected (a html SELECT element).\', \'TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 1, 0, 0, 0),
                (10, \'Checkbox\', \'checkbox\', \'A series of checkboxes of which multiple may be checked.\', \'#REF\', 1, 0, 0, 1),
                (4, \'Text\', \'textarea\', \'A text input box for larger amounts of text (the html TEXTAREA element).\', \'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (5, \'HTML Text (Flex)\', \'htmltext\', \'A rich (html) text input element (very basic).\', \'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (6, \'Image\', \'image\', \'An image upload element.\', \'#REF\', 0, 1, 0, 1),
                (7, \'Multi-Image (Gallery)\', \'image_multi\', \'A multi-image upload element. Allows uploading multiple images at once.\', \'#REF\', 0, 1, 1, 1),
                (8, \'File upload\', \'file\', \'A file upload element.\', \'#REF\', 0, 0, 0, 1),
                (12, \'Label (Admin editable)\', \'label\', \'A simple one-line text input element, which appears as a header to normal users.\', \'TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (13, \'Numeric\', \'numeric\', \'A numeric input field (supports two decimals).\', \'DOUBLE(16,2) NOT NULL\', 0, 0, 0, 0),
                (14, \'Raw HTML source\', \'htmlsource\', \'A large textarea for raw HTML storing. Only required in specific and semi-custom implementations.\', \'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (15, \'Colorcode\', \'color\', \'A colorpicker, values are stored as HTML hexcodes (for example: #ff00aa).\', \'VARCHAR(7) NOT NULL \', 0, 0, 0, 0),
                (16, \'Reference\', \'reference\', \'A reference to another entry (usually in another module). Refer to the manual for detailed information.\', \'INT(11) UNSIGNED NOT NULL\', 0, 0, 0, 0),
                (17, \'Reference (1:N)\', \'reference_multi\', \'Multiple references to another entry (usually in another module). Refer to the manual for detailed information.\', \'#REF\', 0, 0, 0, 1),
                (18, \'HTML Text (FCK)\', \'htmltext_fck\', \'A rich (html) text input element, powered by FCKEditor.\', \'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (19, \'Boolean\', \'boolean\', \'A simple YES / NO selection.\', \'BOOL NOT NULL\', 0, 0, 0, 0),
                (20, \'Numeric (integer)\', \'numeric\', \'A numeric input field (only integers).\', \'INT(10) NOT NULL\', 0, 0, 0, 0),
                (21, \'Custom Hidden Input\', \'custom_text\', \'Hidden Input with custom html\', \'TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (22, \'Custom Input\', \'custom_input\', \'Input with custom html\', \'TINYTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (24, \'Float (10,6) [Lat/Lng]\', \'numeric\', \'A numeric input field with six decimals. Specifically included for Lat/Lng points.\', \'FLOAT(10,6) NOT NULL\', 0, 0, 0, 0),
                (25, \'Reference (negative)\', \'reference\', \'Reference to large or negative index.\', \'BIGINT(20) NOT NULL\', 0, 0, 0, 0),
                (26, \'Reference (1:N Autosort)\', \'reference_multi\', \'Multiple references to another entry (usually in another module). Refer to the manual for detailed information. Will sort based on referenced module.\', \'#REF\', 0, 0, 0, 1),
                (27, \'Reference (1:N Checkboxes)\', \'reference_multi\', \'Multi reference rendered with checkboxes\', \'#REF\', 0, 0, 0, 1),
                (28, \'Cross Reference\', \'reference_multi\', \'Automatically creates a reference the other way. So if you create a reference from A to B, the B to A reference will also be made. Only for self references.\', \'#REF\', 0, 0, 0, 1),
                (29, \'Date\', \'date\', \'A date field\', \'INT(13)\', 0, 0, 0, 0),
                (30, \'Slider\', \'range\', \'Select a value between a min and max value\', \'INT NOT NULL DEFAULT 0\', 0, 0, 0, 0),
                (31, \'Range\', \'range\', \'Select a range between a min and max value\', \'TEXT NULL DEFAULT NULL\', 0, 0, 0, 0),
                (32, \'Location\', \'location\', \'Laat de gebruiker een locatie kiezen via google maps\', \'TEXT NULL DEFAULT NULL\', 0, 0, 0, 0),
                (33, \'HTML Text (Aloha)\', \'htmltext_aloha\', \'A rich (html) text input element, powered by Aloha Editor.\', \'TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0),
                (34, \'Longtext\', \'textarea\', \'Text area, with longtext storage (very long values).\', \'LONGTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL\', 0, 0, 0, 0)
            ',
            'INSERT INTO `cms_languages` (`id`, `code`, `language`, `local`, `common`, `available`, `default`)
            VALUES
                (33, \'de\', \'German\', \'Deutsch\', 1, 0, 0),
                (38, \'en\', \'English\', \'English\', 1, 1, 0),
                (48, \'fr\', \'French\', \'Français; langue française\', 1, 0, 0),
                (94, \'li\', \'Limburgish\', \'Limburgs\', 0, 0, 0),
                (116, \'nl\', \'Dutch\', \'Nederlands\', 1, 1, 1);
            ',

        ];
    }

    /**
     * Returns queries to fill module-related (specific) data
     *
     * @return string[]
     */
    public function getModuleContentQueries()
    {
        return [

            'INSERT INTO `cms_menu` (`id`, `name`, `icon`, `position`)
            VALUES
                (2, \'PxlCms Test\', \'home.gif\', 1);
            ',
            'INSERT INTO `cms_groups` (`id`, `menu_id`, `name`, `position`)
            VALUES
                (1, 2, \'Content\', 1),
                (2, 2, \'Maintenance Data\', 0);
            ',
            "INSERT INTO `cms_sections` (`id`, `group_id`, `name`, `position`)
            VALUES
                (9, 1, 'Pages', 0),
                (7, 2, 'Stuff', 1);
            ",

            /*
             * Modules
             */

            "INSERT INTO `cms_modules` (`id`, `section_id`, `name`, `introduction`, `position`, `max_entries`, `client_cat_control`, `max_cat_depth`, `sort_entries_manually`, `sort_entries_by`, `is_custom`, `custom_path`, `admin_only`, `icon_image`, `allow_create`, `allow_update`, `allow_delete`, `xml_access`, `custom_rendering`, `view_own_entries_only`, `searchable`, `allow_column_sorting`, `simulate_categories_for`, `hide_from_menu`, `csv_export`, `related_items_filter`, `search_referenced_identifiers`, `override_table_name`)
            VALUES
            (1, 7, 'Slugs', '', 0, 0, 0, 0, 1, '', 0, '', 0, 'pi.gif', 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, ''),
            (22, 9, 'Pages', '', 6, 0, 0, 0, 1, '', 0, '', 0, 'book.gif', 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, NULL)",

            /*
             * Fields
             */

            // pages

            "INSERT INTO `cms_fields` (`id`, `module_id`, `field_type_id`, `indexed`, `name`, `display_name`, `identifier`, `position`, `value_count`, `refers_to_module`, `help_text`, `render_x`, `render_y`, `render_dx`, `render_dy`, `multilingual`, `tab_id`, `custom_html`, `default`, `options`)
            VALUES
                (104, 22, 1, 0, 'Seo Description', '', 0, 5, 1, NULL, '', 0, 4500, 570, 120, 1, 2, '', NULL, ''),
                (103, 22, 1, 0, 'Seo Title', '', 0, 4, 1, NULL, '', 0, 4350, 570, 120, 1, 2, '', NULL, ''),
                (102, 22, 10, 0, 'Show In Menu', '', 0, 3, 0, NULL, '', 0, 4200, 570, 120, 0, 2, '', NULL, ''),
                (101, 22, 18, 0, 'Content', '', 0, 2, 1, NULL, '', 0, 4050, 570, 120, 1, 1, '', NULL, ''),
                (100, 22, 1, 0, 'Name', '', 0, 1, 1, NULL, '', 0, 3900, 570, 120, 1, 1, '', NULL, ''),
                (99, 22, 1, 0, 'Title', '', 1, 0, 1, NULL, '', 0, 3750, 570, 120, 1, 1, '', NULL, '');
            ",

        ];
    }

}
