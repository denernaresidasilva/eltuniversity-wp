<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @package WPAdminify
 * White Label Settings
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class White_Label_Pro  extends AdminSettingsModel
{
    public function __construct()
    {
        add_filter('adminify_settings/wp_white_label', [$this, 'wp_white_label_settings'], 9999, 2);

        // Apply Agency and Upper
        if (jltwp_adminify()->is_plan('agency')) {
            add_filter('adminify_settings/adminify_white_label', [$this, 'adminify_white_label_settings'], 9999, 2);
        }
    }

    public function wp_white_label_settings($fields, $class ){

        //admin_bar_cleanup
        $index                                    = array_search('admin_bar_cleanup', array_column($fields, 'id'));
        $fields[$index]['options']['updates']     = __('Updates', 'adminify');
        $fields[$index]['options']['new_content'] = __('"New" Button', 'adminify');

        return $fields;

    }

    public function adminify_white_label_settings($fields, $class)
    {
        // adminify_whl_sub_heading
        $index = array_search('adminify_whl_sub_heading', array_column($fields, 'id'));
        $fields[$index]['class'] = 'adminify-mt-10';

        $support_url = '';
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            $support_url = 'https://wpadminify.com/contact/';
        } else {
            $support_url = 'https://wordpress.org/support/plugin/adminify/#new-topic-0';
        }

        $fields[$index]['content'] = Utils::adminfiy_help_urls(
            sprintf(__('<span>"WP Adminify" Branding</span>', 'adminify')) ,
            'https://wpadminify.com/docs/adminify/white-label/rebrand-wp-adminify-plugin',
            'https://www.youtube.com/watch?v=zDK_MwIcTpc',
            'https://www.facebook.com/groups/jeweltheme',
            $support_url
        );

        // Option Settings
        global $wpdb;
        $option_value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $this->prefix ) );

        $settings = maybe_unserialize( $option_value );
        $adminify_white_label_enable = !empty( $settings['white_label']['adminify']['plugin_option'] ) ? $settings['white_label']['adminify']['plugin_option'] : false;

        // Remove unnecessary classes
        foreach($fields as $key => $value){
            if($value['id'] == 'adminify_whl_sub_heading' || ( $value['id'] == 'plugin_option' && !empty($adminify_white_label_enable) ) ) {
                continue;
            }

            $index = array_search($value['id'], array_column($fields, 'id'));
            if($value['id'] == 'plugin_option'){
                $fields[$index]['class'] = 'adminify-full-width-field adminify-hightlight-field adminify-one-col adminify-mt-6';
            } else {
                $fields[$index]['class'] = 'adminify-white-label';
            }
        }
        return $fields;
    }
}
