<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;
use WPAdminify\Inc\Admin\Options\Productivity;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}
/**
 * WPAdminify
 * Sidebar Widgets
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class Sidebar_Widgets extends AdminSettingsModel
{
    public $widget_list;
    public $disable_gutenberg;

    public function __construct()
    {
        $this->widget_list       = (array) AdminSettings::get_instance()->get('remove_widgets')['sidebar_widgets_list'];


        // Get and disable the sidebar widgets.
        add_action('widgets_init', [$this, 'jltma_remove_default_widgets'], 99);
    }

    /**
     * Render all registered widgets
     *
     * @return void
     */
    public static function render_sidebar_checkboxes()
    {
        return get_option('sidebar_widgets', []);
    }


    /**
     * Generate Sidebar Widgets on Checkbox format
     *
     * @return void
     */
    public static function jltwp_adminify_get_default_widgets()
    {
        global $wp_widget_factory;

        $widgets = [];

        // $default_widgets = [
        // 'WP_Widget_Pages',
        // 'WP_Widget_Calendar',
        // 'WP_Widget_Archives',
        // 'WP_Widget_Links',
        // 'WP_Widget_Media_Audio',
        // 'WP_Widget_Media_Image',
        // 'WP_Widget_Media_Video',
        // 'WP_Widget_Media_Gallery',
        // 'WP_Widget_Meta',
        // 'WP_Widget_Search',
        // 'WP_Widget_Text',
        // 'WP_Widget_Categories',
        // 'WP_Widget_Recent_Posts',
        // 'WP_Widget_Recent_Comments',
        // 'WP_Widget_RSS',
        // 'WP_Widget_Tag_Cloud',
        // 'WP_Nav_Menu_Widget',
        // 'WP_Widget_Custom_HTML'
        // ];

        /**
         * Array of known widgets that won't work in the builder.
         *
         * @see jltwp_adminify_get_wp_widgets_exclude
         */
        $exclude = apply_filters(
            'jltwp_adminify_get_wp_widgets_exclude',
            [
                'WP_Widget_Media_Audio',
                'WP_Widget_Media_Image',
                'WP_Widget_Media_Video',
                'WP_Widget_Media_Gallery',
                'WP_Widget_Text',
                'WP_Widget_Custom_HTML',
            ]
        );

        foreach ($wp_widget_factory->widgets as $class => $widget) {
            if (in_array($class, $exclude)) {
                continue;
            }
            $widgets[$class] = $widget->name;
        }

        ksort($widgets);
        return $widgets;
    }



    /**
     * Remove Sidebar Widgets.
     *
     * Gets the list of disabled sidebar widgets and disables
     * them for you in WordPress.
     *
     * @since 1.0.0
     */
    public function jltma_remove_default_widgets()
    {
        $widgets = $this->widget_list;
        $_widgets = $this->jltwp_adminify_get_default_widgets();

        update_option('sidebar_widgets', $_widgets);

        if (!empty($widgets)) {
            foreach ($widgets as $widget_class) {
                unregister_widget($widget_class);
            }
        }
    }
}
