<?php

namespace WPAdminify\Inc\Admin;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;
use WPAdminify\Inc\Admin\Options\Customize;
use WPAdminify\Inc\Admin\Options\Productivity;
use WPAdminify\Inc\Admin\Options\CustomCSSJS;
use WPAdminify\Inc\Admin\Options\Performance;
use WPAdminify\Inc\Admin\Options\MenuLayout;
use WPAdminify\Inc\Admin\Options\Security;
use WPAdminify\Inc\Admin\Options\White_Label;
if ( !defined( 'ABSPATH' ) ) {
    die;
}
// Cannot access directly.
if ( !class_exists( 'AdminSettings' ) ) {
    class AdminSettings extends AdminSettingsModel {
        // AdminSettings cannot be extended by creating instances
        public static $instance = null;

        public $defaults = [];

        private $message = [];

        public function __construct() {
            // this should be first so the default values get stored
            $this->jltwp_adminify_options();
            parent::__construct( (array) get_option( $this->prefix ) );
            add_action( 'network_admin_menu', [$this, 'network_panel'] );
        }

        public function network_panel() {
            add_menu_page(
                $this->get_plugin_menu_label(),
                $this->get_plugin_menu_label(),
                'manage_options',
                'wp-adminify-settings',
                [$this, 'network_panel_display'],
                WP_ADMINIFY_ASSETS_IMAGE . 'logos/menu-icon.svg',
                30
            );
        }

        public function get_bloginfo( $blog_id, $fields = [] ) {
            switch_to_blog( $blog_id );
            $_fields = [];
            foreach ( $fields as $field ) {
                $_fields[$field] = get_bloginfo( $field );
            }
            restore_current_blog();
            return $_fields;
        }

        public function get_sites() {
            $sites = get_sites();
            foreach ( $sites as $site ) {
                $info = $this->get_bloginfo( $site->blog_id, ['name'] );
                $site->name = $info['name'];
            }
            return $sites;
        }

        public function get_sites_option_empty() {
            return sprintf( __( '<option value="%1$s">%2$s</option>', 'adminify' ), 0, __( 'Select', 'adminify' ) );
        }

        public function get_sites_option( $sites = [], $add_empty_slot = false ) {
            if ( empty( $sites ) ) {
                $sites = $this->get_sites();
            }
            $_sites = [];
            if ( $add_empty_slot ) {
                $_sites[] = $this->get_sites_option_empty();
            }
            foreach ( $sites as $site ) {
                $_sites[] = sprintf( __( '<option value="%1$s">%2$s</option>', 'adminify' ), $site->blog_id, $site->name );
            }
            return implode( '', $_sites );
        }

        public function maybe_display_message() {
            if ( empty( $this->message ) ) {
                return;
            }
            $classes = 'adminify-status adminify-status--' . esc_attr( $this->message['type'] );
            echo '<div class="' . esc_attr( $classes ) . '"><p>' . esc_html__( wp_kses_post( $this->message['message'] ), 'adminify' );
        }

        public function network_panel_display() {
            $multisite_settings = sprintf(
                wp_kses_post( '<div class="%1$s"><h2>%2$s</h2> <a href="%3$s" target="_blank">%4$s</a></div>', 'adminify' ),
                Utils::upgrade_pro_class(),
                esc_html__( 'Network Settings', 'adminify' ),
                esc_url( 'https://wpadminify.com/pricing' ),
                Utils::adminify_upgrade_pro( 'Please Upgrade or Activate License' )
            );
            // Initialize the multisite_settings variable
            $multisite_settings = apply_filters( 'adminify/admin_settings/network', $multisite_settings );
            // Apply the filter
            echo $multisite_settings;
        }

        public function option_modules() {
            $extra_data_merge = [];
            $clonable_data = [
                '_wpadminify'                                 => __( 'WP Adminify Options', 'adminify' ),
                '_adminify_admin_columns_adminify_admin_page' => __( 'Admin Page Columns Data', 'adminify' ),
            ];
            // Activity Logs Active
            if ( Utils::is_plugin_active( 'adminify-activity-logs/adminify-activity-logs.php' ) ) {
                $extra_data_merge['adminify_activity_logs'] = __( 'Activity Logs Data', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Quick Circle Menu Active
            if ( Utils::is_plugin_active( 'adminify-quick-circle-menu/adminify-quick-circle-menu.php' ) ) {
                $extra_data_merge['_wpadminify_quick_circle_menu'] = __( 'Quick Circle Menu', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Google Pagespeed Active
            if ( Utils::is_plugin_active( 'adminify-google-pagespeed/adminify-google-pagespeed.php' ) ) {
                $extra_data_merge['adminify_page_speed'] = __( 'Google Pagespeed', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Login Customizer Active
            if ( Utils::is_plugin_active( 'loginfy/loginfy.php' ) ) {
                $extra_data_merge['jltwp_adminify_login'] = __( 'Loginify Data', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Header Footer Scripts Active
            if ( Utils::is_plugin_active( 'adminify-sidebar-generator/adminify-sidebar-generator.php' ) ) {
                $extra_data_merge['_wp_adminify_sidebar_settings'] = __( 'Sidebar Generator', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Sidebar Generator Active
            if ( Utils::is_plugin_active( 'adminify-header-footer-scripts/adminify-header-footer-scripts.php' ) ) {
                $extra_data_merge['_wpadminify_custom_js_css'] = __( 'Custom JS/CSS', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            // Admin Columns Active
            if ( Utils::is_plugin_active( 'adminify-admin-columns/adminify-admin-columns.php' ) ) {
                $extra_data_merge['_adminify_admin_columns_page'] = __( 'Admin Columns Page Data', 'adminify' );
                $extra_data_merge['_adminify_admin_columns_post'] = __( 'Admin Columns Post Data', 'adminify' );
                $clonable_data = array_merge( $clonable_data, $extra_data_merge );
            }
            return (array) apply_filters( 'adminify_clone_blog_option_modules', $clonable_data );
        }

        public function get_pagespeed_data( $copy_from ) {
            switch_to_blog( $copy_from );
            global $wpdb;
            $table_name = $wpdb->prefix . 'adminify_page_speed';
            $histories = $wpdb->get_results( "SELECT * FROM {$table_name}", ARRAY_A );
            restore_current_blog();
            return $histories;
        }

        public function clone_pagespeed_data( $histories, $copy_to ) {
            switch_to_blog( $copy_to );
            global $wpdb;
            $table_name = $wpdb->prefix . 'adminify_page_speed';
            foreach ( $histories as $history ) {
                unset($history['id']);
                $wpdb->insert( "{$table_name}", $history, [
                    'url'           => '%s',
                    'score_desktop' => '%d',
                    'score_mobile'  => '%d',
                    'data_desktop'  => '%s',
                    'data_mobile'   => '%s',
                    'screenshot'    => '%s',
                    'time'          => '%s',
                ] );
            }
            restore_current_blog();
        }

        public function get_admin_columns_options( $copy_from ) {
            $options = [];
            switch_to_blog( $copy_from );
            $args = [
                'public' => true,
            ];
            $types = get_post_types( $args );
            unset($types['attachment']);
            restore_current_blog();
            foreach ( $types as $type ) {
                $options[] = '_adminify_admin_columns_meta_' . esc_attr( $type );
            }
            return $options;
        }

        public static function get_instance() {
            if ( !is_null( self::$instance ) ) {
                return self::$instance;
            }
            self::$instance = new self();
            return self::$instance;
        }

        protected function get_defaults() {
            return $this->defaults;
        }

        public static function get_pro_label() {
            $is_pro = "";
            $is_pro = WP_ADMINIFY;
            return $is_pro;
        }

        public function get_plugin_menu_icon() {
            $menu_icon = WP_ADMINIFY_ASSETS_IMAGE . 'logos/menu-icon-light.svg';
            $saved_data = get_option( $this->prefix );
            if ( isset( $saved_data['white_label']['adminify']['menu_icon'] ) && !empty( $saved_data['white_label']['adminify']['menu_icon']['url'] ) ) {
                $menu_icon = $saved_data['white_label']['adminify']['menu_icon']['url'];
            }
            return $menu_icon;
        }

        public function get_plugin_menu_label() {
            $plugin_menu_label = self::get_pro_label();
            $saved_data = get_option( $this->prefix );
            if ( isset( $saved_data['white_label']['adminify']['menu_label'] ) && !empty( $saved_data['white_label']['adminify']['menu_label'] ) ) {
                $plugin_menu_label = $saved_data['white_label']['adminify']['menu_label'];
            }
            return $plugin_menu_label;
        }

        public static function support_url() {
            $support_url = '';
            $support_url = 'https://wordpress.org/support/plugin/adminify/#new-topic-0';
            return $support_url;
        }

        public function jltwp_adminify_options() {
            if ( !class_exists( 'ADMINIFY' ) ) {
                return;
            }
            $submenu_position = apply_filters( 'jltwp_adminify_submenu_position', 30 );
            $saved_data = get_option( $this->prefix );
            $global_admin_ui_mode = ( empty( $saved_data['light_dark_mode']['admin_ui_mode'] ) ? 'light' : sanitize_text_field( $saved_data['light_dark_mode']['admin_ui_mode'] ) );
            $admin_ui_mode = ( empty( get_user_meta( get_current_user_id(), 'color_mode', true ) ) ? $global_admin_ui_mode : get_user_meta( get_current_user_id(), 'color_mode', true ) );
            $light_logo_image_url = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-light.svg';
            $dark_logo_image_url = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-dark.svg';
            $plugin_author_name = WP_ADMINIFY_AUTHOR;
            // WP Adminify Options
            \ADMINIFY::createOptions( $this->prefix, [
                'framework_title'         => '<img class="wp-adminify-settings-logo adminify-settings-light-logo" src=' . esc_url( $light_logo_image_url ) . '><img class="wp-adminify-settings-logo adminify-settings-dark-logo" src=' . esc_url( $dark_logo_image_url ) . '>' . ' <small>by ' . esc_html( $plugin_author_name ) . '</small>',
                'framework_class'         => '',
                'menu_title'              => $this->get_plugin_menu_label(),
                'menu_slug'               => 'wp-adminify-settings',
                'menu_capability'         => 'manage_options',
                'menu_icon'               => $this->get_plugin_menu_icon(),
                'menu_position'           => 30,
                'menu_hidden'             => false,
                'menu_parent'             => 'admin.php?page=wp-adminify-settings',
                'show_bar_menu'           => true,
                'show_sub_menu'           => false,
                'show_in_network'         => false,
                'show_in_customizer'      => false,
                'show_search'             => false,
                'show_reset_all'          => true,
                'show_reset_section'      => true,
                'show_footer'             => true,
                'show_all_options'        => false,
                'show_form_warning'       => true,
                'sticky_header'           => false,
                'save_defaults'           => false,
                'ajax_save'               => true,
                'admin_bar_menu_icon'     => '',
                'admin_bar_menu_priority' => 80,
                'footer_text'             => ' ',
                'footer_after'            => ' ',
                'footer_credit'           => ' ',
                'database'                => 'options',
                'transient_time'          => 0,
                'contextual_help'         => [],
                'contextual_help_sidebar' => '',
                'enqueue_webfont'         => true,
                'async_webfont'           => false,
                'output_css'              => true,
                'nav'                     => 'normal',
                'has_nav'                 => false,
                'theme'                   => 'dark',
                'class'                   => 'wp-adminify-settings',
                'defaults'                => [],
            ] );
            $this->defaults = array_merge( $this->defaults, ( new Customize() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new MenuLayout() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new Productivity() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new Security() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new Performance() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new CustomCSSJS() )->get_defaults() );
            $this->defaults = array_merge( $this->defaults, ( new White_Label() )->get_defaults() );
            // Fix Missing keys on save
            add_filter( "adminify_{$this->prefix}_save", function ( $data ) {
                $data = $this->fix_missing_keys( $this->defaults, $data );
                return $data;
            } );
            // Backup Settings
            \ADMINIFY::createSection( $this->prefix, [
                'title'  => __( 'Backup', 'adminify' ),
                'icon'   => 'fas fa-database',
                'fields' => [[
                    'type'    => 'subheading',
                    'content' => Utils::adminfiy_help_urls(
                        __( 'Backup Config Settings', 'adminify' ),
                        'https://wpadminify.com/docs/adminify/export-import/backup',
                        'https://www.youtube.com/playlist?list=PLqpMw0NsHXV-EKj9Xm1DMGa6FGniHHly8',
                        'https://www.facebook.com/groups/jeweltheme',
                        self::support_url()
                    ),
                ], [
                    'type'  => 'backup',
                    'class' => 'adminify-block',
                ]],
            ] );
        }

        function fix_missing_keys( $defaults, $data ) {
            if ( is_array( $defaults ) ) {
                foreach ( $defaults as $key => $value ) {
                    if ( is_array( $value ) ) {
                        if ( !isset( $data[$key] ) || gettype( $data[$key] ) !== 'array' ) {
                            $data[$key] = [];
                        }
                        $data[$key] = $this->fix_missing_keys( $value, $data[$key] );
                    }
                }
            }
            return $data;
        }

        public function get_prefix() {
            return $this->prefix;
        }

    }

}