<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package WPAdminify
 * Productivity Pro
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class Productivity_Pro  extends AdminSettingsModel
{
    public function __construct()
    {
        add_filter('adminify_settings/productivity', [$this, 'productivity_pro_settings'], 9999, 2);
    }

    public function productivity_pro_settings($fields, $class)
    {
        // other_notices
        $index                                             = array_search('other_notices', array_column($fields, 'id'));
        $fields[$index]['title']                           = __('Other Notices', 'adminify');
        $fields[$index]['options']        = [
            'welcome_panel'        => __('Remove Welcome Panel', 'adminify'),
            'php_nag'              => __('Remove "PHP Update Required" Notice', 'adminify'),
            'core_update_notice'   => __('Hide Core Update Notice', 'adminify'),
            'plugin_update_notice' => __('Hide Plugin Update Notice', 'adminify'),
            'theme_update_notice'  => __('Hide Theme Update Notice', 'adminify'),
            'site_health'          => __('Disable Site Health checks', 'adminify'),
        ];


        //hide_notices
        $index                   = array_search('hide_notices', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Hide "Admin Notices"?', 'adminify');
        unset($fields[$index]['class']);

        //hide_notices_non_admin
        $index                   = array_search('hide_notices_non_admin', array_column($fields, 'id'));
        $fields[$index]['label'] = __('Also, Hide for Non-Admin Users?', 'adminify');
        // unset($fields[$index]['class']);

        // admin_pages
        $index                   = array_search('admin_pages', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Admin Pages', 'adminify');
        unset($fields[$index]['class']);

        // screen_help_tab
        $index                                                       = array_search('screen_help_tab', array_column($fields, 'id'));
        $fields[$index]['title']                                     = __('Screen Options and Help Tab', 'adminify');

        // enable_for_screen
        $enable_for_screen_index    = array_search('enable_for_screen', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$enable_for_screen_index]['class'] = 'adminify-pl-0 adminify-pt-0';

        $screen_help_data_index                                     = array_search('screen_help_data', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$screen_help_data_index]['options'] = [
            'hide_screen_options' => __('Hide Screen Options', 'adminify'),
            'hide_help_tab'       => __('Hide Help Tab', 'adminify')
        ];


        // pto_taxonomies
        $index                                                                 = array_search('post_types_order', array_column($fields, 'id'));
        $pto_taxonomies_index                                                  = array_search('pto_taxonomies', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$pto_taxonomies_index]['title']              = __('Sortable Taxonomies', 'adminify');


        //taxonomies
        $index                                                    = array_search('post_duplicator', array_column($fields, 'id'));
        $pto_taxonomies_index                                     = array_search('taxonomies', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$pto_taxonomies_index]['title'] = __('Enable for Taxonomies', 'adminify');

        // media_ininite_scroll
        $index                                                       = array_search('media_attachments', array_column($fields, 'id'));
        // $media_attachments_index                                     = array_search('media_ininite_scroll', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$media_attachments_index]['title'] = __('Infinite Scroll for Media Library?', 'adminify');
        // $fields[$index]['fields'][$media_attachments_index]['class'] = 'adminify-pl-0 adminify-pt-0';

        //allowed_upload_files
        // $media_attachments_index                                       = array_search('allowed_upload_files', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$media_attachments_index]['title']   = __('Allowed Files Uploads', 'adminify');
        // $fields[$index]['fields'][$media_attachments_index]['options'] = [
        //     'svg'  => __('SVG Files', 'adminify'),
        //     'avif' => __('AVIF Files', 'adminify'),
        //     'ico'  => __('ICO Files', 'adminify'),
        //     'webp' => __('WEBP Files', 'adminify'),
        // ];


        //convert_to_webp
        // $media_attachments_index                                     = array_search('convert_to_webp', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$media_attachments_index]['title'] = __('Convert to WEBP', 'adminify');

        // //featured_to_post
        // $media_attachments_index                                     = array_search('featured_to_post', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$media_attachments_index]['title'] = __('Link Featured Images to Post', 'adminify');

        // //media_lowercase
        // $media_attachments_index                                     = array_search('media_lowercase', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$media_attachments_index]['title'] = __('Lowercase Filenames for Uploads', 'adminify');


        // custom_admin_columns
        $index                                                                                   = array_search('custom_admin_columns', array_column($fields, 'id'));
        $custom_admin_columns_index                                                              = array_search('columns_data', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$custom_admin_columns_index]['options']['last_login_column']   = __('Show "Last Login" Column for Users', 'adminify');
        $fields[$index]['fields'][$custom_admin_columns_index]['options']['post_page_id_column'] = __('Show Post/Page ID Column. Display "ID" column for post and page table lists', 'adminify');
        $fields[$index]['fields'][$custom_admin_columns_index]['options']['post_thumb_column']   = __('Show Post Thumbnails Column', 'adminify');

        return $fields;
    }
}
