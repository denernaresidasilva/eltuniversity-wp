<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
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

class Security_Pro  extends AdminSettingsModel
{

    public function __construct()
    {
        add_filter('adminify_settings/security', [$this, 'security_settings'], 9999, 2);
    }

    public function security_settings($fields, $class)
    {
        // redirect_urls_fields
        $index                   = array_search('redirect_urls_fields', array_column($fields, 'id'));
        $fields[$index]['title'] = __('Redirect URLs', 'adminify');
        unset($fields[$index]['class']);
        $redirect_urls_options                   = array_search('redirect_urls_options', array_column($fields, 'id'));
        $fields[$redirect_urls_options]['class'] = 'adminify-pt-0 adminify-tabs-content';

        // apply_for
        $index                                                                         = array_search('disable_comments', array_column($fields, 'id'));
        $apply_for_index                                                               = array_search('apply_for', array_column($fields[$index]['fields'], 'id'));
        $fields[$index]['fields'][$apply_for_index]['options']['hide_existing']        = __('Hide Existing Comments from Frontend', 'adminify');
        $fields[$index]['fields'][$apply_for_index]['options']['replace_comment_link'] = __('Comments Content disable auto linking, display comments links as plain text, replace Comment Links to JavaScript?', 'adminify');
        $fields[$index]['fields'][$apply_for_index]['options']['replace_author_link']  = __('Remove Link from comment "Author Name" & replace to JavaScript?', 'adminify');

        // post_types
        $post_types_index                                       = array_search('post_types', array_column($fields[$index]['fields'], 'id'));


        //disable_automatic_emails
        // $index                   = array_search('disable_automatic_emails', array_column($fields, 'id'));
        // $fields[$index]['title'] = __('Disable Automatic Updates Emails', 'adminify');
        // unset($fields[$index]['class']);

        //disable_language_switcher_login
        // $index                   = array_search('disable_language_switcher_login', array_column($fields, 'id'));
        // $fields[$index]['title'] = __('Disable Login Screen Language Switcher', 'adminify');
        // unset($fields[$index]['class']);


        return $fields;
    }
}
