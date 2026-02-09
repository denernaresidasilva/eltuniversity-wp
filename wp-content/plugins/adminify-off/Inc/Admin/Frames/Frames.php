<?php

namespace WPAdminify\Inc\Admin\Frames;

use WPAdminify\Inc\Utils;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}
/**
 * WP Adminify
 * Frames Class
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

if (!class_exists('Frames')) {
	class Frames
	{
        public function __construct()
        {
            $this->init_hooks();
        }

        private function init_hooks()
        {
            add_filter("language_attributes", [$this, "page_attribute"]);
            add_action('admin_enqueue_scripts', [$this, 'load_scripts']);

            // Reload the page after plugin activation/deactivation
            if ( isset( $_GET['activate'] ) || isset( $_GET['activate-multi'] ) || isset( $_GET['deactivate'] ) || isset( $_GET['deactivate-multi'] ) ) {
                self::custom_plugin_change_reload();
            }
        }
        
        static function custom_plugin_change_reload($actual_link = null) {
            // if (shortcode_exists('ssa_booking')) {
            //     return;
            // } Commented out to avoid issues with shortcode existence check as this plugin using iframe inside it.

            if (!is_null($actual_link)) {
                echo "<script type='text/javascript'>
                    parent.location.replace('$actual_link');
                </script>";
                return; 
            }else{
                echo '<script type="text/javascript">
                    parent.location.reload();
                </script>';
            }
        }

        public function load_scripts()
        {
            wp_enqueue_style('frame-adminify--frame', WP_ADMINIFY_ASSETS . 'admin/css/frame' . Utils::assets_ext('.css'), [], WP_ADMINIFY_VER);
        }

        public function page_attribute($attr)
        {
            $attrs = [$attr];
            $attrs[] = 'frame-adminify-iframe="true"';
            return implode(' ', $attrs);
        }
    }

}
