<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;

use WPAdminify\Inc\Utils;
// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}
/**
 * WPAdminify
 * Dashboard Widgets
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */
class Remove_DashboardWidgets extends AdminSettingsModel
{
    public $widget_list;

    public function __construct()
    {
        $this->widget_list = (array) AdminSettings::get_instance()->get('remove_widgets')['dashboard_widgets_list'];
        // $this->restrict_for = (array) AdminSettings::get_instance()->get('dashboard_widgets_user_roles');

        // Dashboard widgets by WP Adminify
        add_action('admin_init', [$this, 'jltwp_adminify_add_hooks']);

        // add_action('admin_enqueue_scripts', [$this, 'dashboard_styles'], 100);
    }

    public function jltwp_adminify_add_hooks()
    {
        add_action('wp_dashboard_setup', [$this, 'disable_dashboard_widgets'], 100);
        // add_action('wp_network_dashboard_setup', [$this, 'disable_dashboard_widgets'], 100);
    }

    public static function get_dashboard_widgets_checkbox()
    {
        $widgets = (new self())->get_default_dashboard_widgets();
        $flat_widgets = [];
        foreach ($widgets as $context => $priority) {
            foreach ($priority as $data) {
                foreach ($data as $id => $widget) {
                    if (!$widget) {
                        continue;
                    }
                    $key = $id . '|' . $context;
                    $widget['title'] = (isset($widget['title']) ? $widget['title'] : '');
                    $flat_widgets[$key] = wp_strip_all_tags($widget['title']);
                }
            }
        }
        $widgets = wp_list_sort( $flat_widgets, [ 'title_stripped' => 'ASC' ], null, true );
        return $widgets;
    }

    public static function render_dashboard_checkboxes()
    {
        (new self())->get_default_dashboard_widgets();
        $dom = new \DOMDocument();
        $widgets = get_option('dashboard_widgets', []);
        $new_list = [];
        foreach ($widgets as $key => $value) {
            if ($value != $value) {
                libxml_use_internal_errors(true);
                $dom->loadHTML($value);
                $value = $dom->getElementsByTagName('span')->item(0)->nodeValue;
            }
            $new_list[$key] = $value;
        }
        return $new_list;
    }

    /**
     * Get the default dashboard widgets.
     *
     * @return array Sidebar widgets.
     */
    public function get_default_dashboard_widgets()
    {
        global $wp_meta_boxes;
        $screen = (is_network_admin() ? 'dashboard-network' : 'dashboard');
        $current_screen = get_current_screen();
        if (!isset($wp_meta_boxes[$screen]) || !is_array($wp_meta_boxes[$screen])) {
            require_once ABSPATH . '/wp-admin/includes/dashboard.php';
            set_current_screen($screen);
            wp_dashboard_setup();
            if (is_callable(['Antispam_Bee', 'add_dashboard_chart'])) {
                \Antispam_Bee::add_dashboard_chart();
            }
        }
        if (isset($wp_meta_boxes[$screen][0])) {
            unset($wp_meta_boxes[$screen][0]);
        }
        $widgets = [];
        if (isset($wp_meta_boxes[$screen])) {
            $widgets = $wp_meta_boxes[$screen];
        }
        set_current_screen($current_screen);
        /**
         * Filters the available dashboard widgets.
         *
         * @param array $widgets The globally available dashboard widgets.
         */
        return apply_filters('wp_adminify_dashboard_widgets', $widgets);
    }

    public function disable_dashboard_widgets()
    {
        $widgets = $this->widget_list;
        $_widgets = self::get_dashboard_widgets_checkbox();
        update_option('dashboard_widgets', $_widgets);
        if (!$widgets) {
            return;
        }
        foreach ($widgets as $meta_box) {
            $widget_data = explode('|', $meta_box);
            $widget_id = $widget_data[0];
            $widget_pos = '';
            if (!empty($widget_data[1])) {
                $widget_pos = $widget_data[1];
            }
            if ('dashboard_welcome_panel' === $widget_id) {
                remove_action('welcome_panel', 'wp_welcome_panel');
                continue;
            }
            if ('try_gutenberg_panel' === $widget_id) {
                remove_action('try_gutenberg_panel', 'wp_try_gutenberg_panel');
                continue;
            }
            if ('dashboard_browser_nag' === $widget_id || 'dashboard_php_nag' === $widget_id) {
                continue;
            }
            /**
             * Docs: https://developer.wordpress.org/reference/functions/remove_meta_box/
             * Example: remove_meta_box('dashboard_right_now', 'dashboard', 'normal');   // Right Now
             */
            remove_meta_box($widget_id, get_current_screen()->base, $widget_pos);
        }
    }
}
