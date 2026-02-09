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

class Performance_Pro  extends AdminSettingsModel
{
    public function __construct()
    {
        add_filter('adminify_settings/performance', [$this, 'performance_pro_settings'], 9999, 2);
    }

    public function performance_pro_settings($fields, $class)
    {
        // disable_embeds
        $index                   = array_search('disable_embeds', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Disable Embeds', 'adminify');
        unset($fields[$index]['class']);


        //heartbeat_api
        $index                   = array_search('heartbeat_api', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Control Heartbeat API', 'adminify');
        unset($fields[$index]['class']);

        //backend
        $backend_index = array_search('backend', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$backend_index]['class']);

        //on_post_create
        $on_post_create_index = array_search('on_post_create', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$on_post_create_index]['class']);

        //on_frontend
        $on_frontend_index = array_search('on_frontend', array_column($fields[$index]['fields'], 'id'));
        unset($fields[$index]['fields'][$on_frontend_index]['class']);

        //revisions
        // $index                   = array_search('revisions', array_column($fields, 'id'));
        // $fields[$index]['title'] = __('Control Revisions', 'adminify');

        // revisions_enable
        // $revisions_enable_index = array_search('revisions_enable', array_column($fields[$index]['fields'], 'id'));
        // $fields[$index]['fields'][$revisions_enable_index]['class'] = 'adminify-p-0';

        // limit
        // $limit_index = array_search('limit', array_column($fields[$index]['fields'], 'id'));
        // unset($fields[$index]['fields'][$limit_index]['class']);

        // post_types
        // $post_types_index = array_search('post_types', array_column($fields[$index]['fields'], 'id'));
        // unset($fields[$index]['fields'][$post_types_index]['class']);

        return $fields;
    }

}
