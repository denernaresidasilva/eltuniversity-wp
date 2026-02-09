<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package WPAdminify
 * Quick Menu
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class Customize_Pro  extends AdminSettingsModel{

    public function __construct()
    {
        add_filter('adminify_settings/customize', [$this, 'customize_pro_settings'], 9999, 2);
        add_filter('adminify_settings/post_status', [$this, 'post_status_settings'], 9999, 2);
    }

    public function post_status_settings($fields, $class){

        $index = array_search('post_status_bg_colors', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Post Status Background Colors', 'adminify');
        unset($fields[$index]['class']);

        return $fields;
    }

    public function customize_pro_settings($fields, $class)
    {
        $index                   = array_search('admin_favicon_logo', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Admin Favicon', 'adminify');
        unset($fields[$index]['class']);

        //gutenberg_editor_logo
        $index                   = array_search('gutenberg_editor_logo', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Gutenberg Editor Logo', 'adminify');
        unset($fields[$index]['class']);

        // adminify_theme_custom_colors
        $index = array_search('adminify_theme_custom_colors', array_column($fields, 'id'));
        $fields[$index]['title'] =  __('Custom Color Preset', 'adminify');
        unset($fields[$index]['class']);


        // body_fields
        // remove: background_settings_notice
        $index = array_search('body_fields', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Custom Background', 'adminify');

        // adminify_custom_bg
        $adminify_custom_bg_index = array_search('adminify_custom_bg', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$adminify_custom_bg_index]['class'] = '!adminify-flex adminify-pt-0 adminify-pl-0';

        // admin_general_bg
        $admin_general_bg_index = array_search('admin_general_bg', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_general_bg_index]['class']);

        // admin_general_bg_gradient
        $admin_general_bg_gradient_index = array_search('admin_general_bg_gradient', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_general_bg_gradient_index]['class']);

        // admin_general_bg_image
        $admin_general_bg_image_index = array_search('admin_general_bg_image', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_general_bg_image_index]['class']);

        //admin_general_google_font
        $index = array_search('admin_general_google_font', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Body Font', 'adminify');
        unset($fields[$index]['class']);

        //admin_glass_effect
        // $index = array_search('admin_glass_effect', array_column($fields, 'id'));
        // $fields[$index] = [
        //     'id'         => 'admin_glass_effect',
        //     'type'       => 'switcher',
        //     'title'      => __('Glass Effect', 'adminify'),
        //     'default'    => $class->get_default_field('admin_glass_effect'),
        // ];

        // enable_schedule_dark_mode
        $index                     = array_search('light_dark_mode', array_column($fields, 'id'));
        $admin_ui_dark_mode        = array_search('admin_ui_dark_mode', array_column($fields[$index]['fields'], 'id'));
        $schedule_dark_mode        = array_search('schedule_dark_mode', array_column($fields[$index]['fields'][$admin_ui_dark_mode]['fields'], 'id'));
        $enable_schedule_dark_mode = array_search('enable_schedule_dark_mode', array_column($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'], 'id'));
        $fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'][$enable_schedule_dark_mode]['class'] = 'adminify-pt-0 adminify-pl-0';
        $fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['title'] = __('Schedule Dark Mode', 'adminify');

        // schedule_dark_mode_type
        $schedule_dark_mode_type_index = array_search('schedule_dark_mode_type', array_column($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'][$schedule_dark_mode_type_index]['class']);

        // schedule_dark_mode_start_time
        $schedule_dark_mode_start_time_index = array_search('schedule_dark_mode_start_time', array_column($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'][$schedule_dark_mode_start_time_index]['class']);
        
        // schedule_dark_mode_end_time
        $schedule_dark_mode_end_time_index = array_search('schedule_dark_mode_end_time', array_column($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'], 'id'));
        unset($fields[$index]['fields'][$admin_ui_dark_mode]['fields'][$schedule_dark_mode]['fields'][$schedule_dark_mode_end_time_index]['class']);

        return $fields;
    }


}
