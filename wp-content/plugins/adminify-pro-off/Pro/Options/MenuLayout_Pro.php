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

class MenuLayout_Pro  extends AdminSettingsModel{

    public function __construct()
    {
        add_filter('adminify_settings/admin_menu/settings', [$this, 'admin_menu_pro_settings'], 9999, 2);
        add_filter('adminify_settings/admin_menu/styles', [$this, 'admin_menu_pro_styles'], 9999, 2);
    }

    public function admin_menu_pro_settings($fields, $class)
    {
        // user_info
        $index = array_search('user_info_fields', array_column($fields, 'id'));
        $fields[$index]['title'] = __('User Info', 'adminify');

        // enable_user_info
        $enable_user_info_index = array_search('enable_user_info', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$enable_user_info_index]['class'] = 'adminify-pt-0 adminify-pl-0 !adminify-flex';

        // user_info_content
        $user_info_content_index = array_search('user_info_content', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$user_info_content_index]['class']);

        // user_info_avatar
        $user_info_avatar_index = array_search('user_info_avatar', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$user_info_avatar_index]['class']);



        // horz_menu_type
        $index = array_search('horz_menu_type', array_column($fields, 'id'));
        $fields[$index]['title'] = __( 'Menu Item Style', 'adminify' );
        unset($fields[$index]['class']);

        // show_bloglink
        // $index = array_search('show_bloglink', array_column($fields, 'id'));
        // $fields[$index]['title'] = __( 'Show Blog Link', 'adminify' );

        // horz_dropdown_icon
        $index = array_search('horz_dropdown_icon', array_column($fields, 'id'));
        $fields[$index]['title'] = __( 'Dropdown Toggle Icon', 'adminify' );
        unset($fields[$index]['class']);

        // horz_toplinks
        $index = array_search('horz_toplinks', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Top Menu Links', 'adminify');
        unset($fields[$index]['class']);

        // horz_bubble_icon_hide
        $index = array_search('horz_bubble_icon_hide', array_column($fields, 'id'));
        $fields[$index]['title'] = __( 'Bubble Icon', 'adminify' );
        unset($fields[$index]['class']);

        // horz_long_menu_break
        // $index = array_search('horz_long_menu_break', array_column($fields, 'id'));
        // $fields[$index]['title'] = __( 'Break Long Lists', 'adminify' );
        // unset($fields[$index]['class']);

        // horizontal_menu_notice
        $index = array_search('horizontal_menu_notice', array_column($fields, 'id'));
        unset($fields[$index]);



        // $index = array_search('accordion_menu_notice', array_column($fields,
        //     'id'
        // ));
        // unset($fields[$index]);

        // $index = array_search('toggle_menu_notice', array_column($fields, 'id'));
        // unset($fields[$index]);


        return $fields;
    }

    public function admin_menu_pro_styles($fields, $class)
    {
        // horz_menu_parent_padding
        $index                          = array_search('menu_styles', array_column($fields, 'id'));
        $horz_menu_parent_padding_index = array_search('horz_menu_parent_padding', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$horz_menu_parent_padding_index]['title'] = __('Parent Menu Horizontal Padding', 'adminify');
        unset($fields[$index]['fields'][$horz_menu_parent_padding_index]['class']);


        $index          = array_search('user_info_style', array_column($fields, 'id'));
        $fields[$index]['title'] = __('User Info Colors', 'adminify');
        unset($fields[$index]['class']);

        return $fields;
    }
}
