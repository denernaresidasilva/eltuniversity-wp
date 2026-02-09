<?php

namespace WPAdminify;

use WPAdminify\Libs\Featured;
use WPAdminify\Inc\Admin\Admin;
use WPAdminify\Inc\Classes\Assets;
use WPAdminify\Inc\Classes\Upgrade;
use WPAdminify\Inc\Classes\Feedback;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Classes\Pro_Upgrade;
use WPAdminify\Inc\Classes\Addons_Plugins;
use WPAdminify\Inc\Classes\Notifications\Notifications;
// No, Direct access Sir !!!
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !class_exists( 'WP_Adminify' ) ) {
    class WP_Adminify {
        const VERSION = WP_ADMINIFY_VER;

        private static $instance = null;

        public function __construct() {
            add_action( 'plugins_loaded', array($this, 'maybe_run_upgrades'), -100 );
            // This should run earlier
            add_action( 'plugins_loaded', array($this, 'maybe_loaded_addons'), -200 );
            // add_action('plugins_loaded', array($this, 'jltwp_adminify_plugins_loaded'), 999);
            add_filter( 'plugin_action_links_' . WP_ADMINIFY_BASE, array($this, 'plugin_action_links') );
            add_filter( 'network_admin_plugin_action_links_' . WP_ADMINIFY_BASE, array($this, 'plugin_action_links') );
            add_filter( 'admin_body_class', array($this, 'jltwp_adminify_body_class'), 99 );
            add_action( 'plugins_loaded', array($this, 'jltwp_is_plugin_row_meta') );
            add_action( 'plugins_loaded', array($this, 'jltwp_adminify_include_files') );
            $is_finished = get_option( 'jltwp_adminify_setup_wizard_ran' );
            if ( !empty( $is_finished ) || $is_finished != '1' ) {
                if ( apply_filters( 'jltwp_adminify_show_setup_wizard', true ) ) {
                    new \WPAdminify\Inc\Classes\Wizard\Setup_Wizard();
                    set_transient( '_adminify_activation_redirect', 1, 30 );
                }
            }
            jltwp_adminify()->add_filter( 'pricing_url', [$this, 'jltwp_adminify_pricing_url'] );
        }

        function jltwp_adminify_pricing_url( $pricing_url ) {
            $pricing_url = 'https://jeweltheme.com';
            return $pricing_url;
        }

        public function jltwp_is_plugin_row_meta() {
            add_filter(
                'plugin_row_meta',
                array($this, 'jltwp_adminify_plugin_row_meta'),
                10,
                2
            );
            add_filter(
                'network_admin_plugin_row_meta',
                array($this, 'jltwp_adminify_plugin_row_meta'),
                10,
                2
            );
        }

        /**
         * Add Body Class
         */
        public function jltwp_adminify_body_class( $classes ) {
            $classes .= ' wp-adminify ';
            $adminify_ui = AdminSettings::get_instance()->get( 'admin_ui' );
            if ( !empty( $adminify_ui ) ) {
                $classes .= ' adminify-ui';
            }
            if ( is_rtl() ) {
                $classes .= ' adminify-rtl ';
            }
            return $classes;
        }

        /**
         * Plugin action links
         *
         * @param   array $links
         *
         * @return array
         */
        public function plugin_action_links( $links ) {
            $links['settings'] = apply_filters( 'adminify_settings_link', sprintf( '<a class="adminify-plugin-settings" href="%1$s">%2$s</a>', admin_url( 'admin.php?page=wp-adminify-settings' ), __( 'Settings', 'adminify' ) ) );
            $links['pricing'] = apply_filters( 'adminify_upgrade_now_link', sprintf( '<a href="%1$s" class="adminify-upgrade-pro" target="_blank" style="color: orangered;font-weight: bold;">%2$s</a>', 'https://wpadminify.com/pricing', __( 'Upgrade Now', 'adminify' ) ) );
            return apply_filters( 'adminify_plugin_row_links', $links );
        }

        public function jltwp_adminify_plugin_row_meta( $plugin_meta, $plugin_file ) {
            if ( WP_ADMINIFY_BASE === $plugin_file ) {
                $row_meta = array(
                    'docs'       => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url_raw( 'https://wpadminify.com/kb' ), __( 'Docs', 'adminify' ) ),
                    'changelogs' => sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url_raw( 'https://wpadminify.com/changelogs/' ), __( 'Changelogs', 'adminify' ) ),
                    'tutorials'  => '<a href="https://www.youtube.com/playlist?list=PLqpMw0NsHXV-EKj9Xm1DMGa6FGniHHly8" aria-label="' . esc_attr( __( 'View WP Adminify Video Tutorials', 'adminify' ) ) . '" target="_blank">' . __( 'Video Tutorials', 'adminify' ) . '</a>',
                );
                $plugin_meta = array_merge( $plugin_meta, $row_meta );
            }
            return $plugin_meta;
        }

        public function jltwp_adminify_plugins_loaded() {
            self::jltwp_adminify_activation_hook();
        }

        /**
         * Addons Loaded Method
         *
         * @return void
         */
        public function maybe_loaded_addons() {
            do_action( 'jltwp_adminify_plugin_loaded', WP_Adminify::get_instance() );
        }

        public function maybe_run_upgrades() {
            if ( !is_admin() && !current_user_can( 'manage_options' ) ) {
                return;
            }
            $upgrade = new Upgrade();
            if ( $upgrade->if_updates_available() ) {
                $upgrade->run_updates();
            }
        }

        public function jltwp_adminify_include_files() {
            new Assets();
            new Admin();
            new Featured();
            new Feedback();
            new Notifications();
            new Pro_Upgrade();
            new Addons_Plugins();
        }

        public function jltwp_adminify_init() {
            // Backup for OLD Database
            $current_version = get_option( 'wp_adminify_version' );
            $is_backup = $old_data = get_option( '_wpadminify_backup' );
            if ( version_compare( $current_version, WP_ADMINIFY_VER, '<' ) && empty( $is_backup ) ) {
                $old_data = get_option( '_wpadminify' );
                update_option( '_wpadminify_backup', $old_data );
            }
            // Load Text Domain
            $this->jltwp_adminify_load_textdomain();
        }

        /**
         * Loads the text domain for localization.
         *
         * This function sets up the text domain for the WP Adminify plugin,
         * allowing it to load translation files for the specified locale.
         * It first attempts to load a custom translation file from the WordPress
         * languages directory and then loads the default translation file from
         * the plugin's languages directory.
         *
         * @return void
         */
        public function jltwp_adminify_load_textdomain() {
            add_action( 'init', function () {
                $domain = 'adminify';
                $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
                load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
                load_plugin_textdomain( $domain, false, dirname( WP_ADMINIFY_BASE ) . '/languages/' );
            } );
        }

        // Activation Hook
        public static function jltwp_adminify_activation_hook() {
            $current_adminify_version = get_option( 'wp_adminify_version', null );
            if ( get_option( 'jltwp_adminify_activation_time' ) === false ) {
                update_option( 'jltwp_adminify_activation_time', strtotime( 'now' ) );
            }
            if ( is_null( $current_adminify_version ) ) {
                update_option( 'wp_adminify_version', self::VERSION );
            }
            //database upgrade logic here
            $old_data = get_option( '_wpadminify' );
            update_option( '_wpadminify_backup', $old_data );
            //  Create term_order collumn in terms table, to support post type order
            global $wpdb;
            $check_term_order_column = $wpdb->query( "SHOW COLUMNS FROM {$wpdb->terms} LIKE 'term_order'" );
            if ( $check_term_order_column == 0 ) {
                $wpdb->query( "ALTER TABLE {$wpdb->terms} ADD term_order INT( 4 ) NULL DEFAULT '0'" );
            }
        }

        // Deactivation Hook
        public static function jltwp_adminify_deactivation_hook() {
            delete_option( 'jltwp_adminify_activation_time' );
            delete_option( 'jltwp_adminify_customizer_flush_url' );
        }

        /**
         * Returns the singleton instance of the class.
         */
        public static function get_instance() {
            if ( !isset( self::$instance ) && !self::$instance instanceof WP_Adminify ) {
                self::$instance = new WP_Adminify();
                self::$instance->jltwp_adminify_init();
            }
            return self::$instance;
        }

    }

    WP_Adminify::get_instance();
}