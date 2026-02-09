<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;


if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

if (!class_exists('CustomCSSJS')) {
    class CustomCSSJS extends AdminSettingsModel
    {

        public $defaults = [];

        public function __construct()
        {
            $this->developer_settings();
        }

        protected function get_defaults()
        {
            return [
                'devtools_tabs'   => [
                    'custom_css' => '',
                    'custom_js' => '',
                ]
            ];
        }



        /**
         * Post Status Columns
         */

        public function devtools_admin_area_custom_css_js_fields( &$fields ) {

            $fields[] = array(
                'id'       => 'custom_css',
                'type'     => 'code_editor',
                'title'    => __( 'Custom CSS', 'adminify' ),
                'subtitle' => __( 'Write your own <strong>Custom CSS</strong> for WordPress Admin Area.', 'adminify' ),
                'desc'     => __( 'Don\'t place &lt;style&gt;&lt;/style&gt; tag inside editor.', 'adminify' ),
                'settings' => array(
                    'theme' => 'monokai',
                    'mode'  => 'css',
                ),
                'sanitize' => false,
                'default'  => $this->get_default_field('devtools_tabs')['custom_css'],
            );

            $fields[] = array(
                'id'       => 'custom_js',
                'type'     => 'code_editor',
                'title'    => __( 'Custom JavaScript', 'adminify' ),
                'subtitle' => __('Write your own <strong>Custom Script</strong> for WordPress Admin Area.', 'adminify' ),
                'desc'     => __( 'Don\'t place &lt;script&gt;&lt;/script&gt; tag inside editor.', 'adminify' ),
                'settings' => array(
                    'theme' => 'dracula',
                    'mode'  => 'javascript',
                ),
                'sanitize' => false,
                'default'  => $this->get_default_field('devtools_tabs')['custom_js'],
            );
        }

        /**
         * Maybe Trigger Reset
         *
         * @param  [type] $data
         * @param  [type] $class
         *
         * @return void
         */
        public function maybe_trigger_reset( $data, $class ) {

            if ( !empty( $_POST['adminify_transient']['reset'] ) ) {

                delete_option( '_wpadminify_custom_js_css' );

            } else if ( !empty( $_POST['adminify_transient']['reset_section'] ) && isset( $_POST['adminify_transient']['section'] ) ) {

                $section = $_POST['adminify_transient']['section'];
                if ( $class->pre_sections[ $section - 2 ]['id'] === 'custom_css_js' ) {
                    delete_option( '_wpadminify_custom_js_css' );
                }

            }
        }


        /**
         * Tabs: Frontend & Backend
         *
         * @param  [type] $fields
         *
         * @return void
         */
        public function devtools_tabbed_settings( &$fields ){

            $backend_custom_scripts  = [];
            $frontend_custom_scripts = [];
            $this->devtools_admin_area_custom_css_js_fields( $backend_custom_scripts );

            if ( !Utils::is_plugin_active( 'adminify-header-footer-scripts/adminify-header-footer-scripts.php' ) ) {
                $frontend_custom_scripts[] = [
                    'type'       => 'notice',
                    'title'      => __(' ', 'adminify'),
                    'class'      => 'adminify-missing-plugins adminify-one-col',
                    'style'      => 'warning',
                    'content'    => Utils::missing_plugin_notice('Custom Header & Footer'),
                ];
            } else {

                add_action( "adminify__wpadminify_save_after", [ $this, 'maybe_trigger_reset' ], 10, 2 );

                if ( class_exists( '\WPAdminify\Modules\CustomHeaderFooter\Inc\CustomHeaderFooter\CustomHeaderFooterSettings' ) ) {
                    $customHeaderFooterSettings = new \WPAdminify\Modules\CustomHeaderFooter\Inc\CustomHeaderFooter\CustomHeaderFooterSettings();
                    $frontend_custom_scripts = $customHeaderFooterSettings->get_fields();
                }
            }

            $fields = [
                [
                    'id'    => 'devtools_tabs',
                    'type'  => 'tabbed',
                    'title' => '',
                    'tabs'  => [
                        [
                            'title'  => __( 'Admin Scripts', 'adminify' ),
                            'fields' => $backend_custom_scripts,
                        ],
                        [
                            'title'  => __( 'Frontend Scripts', 'adminify' ),
                            'fields' => $frontend_custom_scripts,
                        ],
                    ]
                ]
            ];
        }

        public function developer_settings()
        {
            if (!class_exists('ADMINIFY')) {
                return;
            }

            $fields = array();
            $this->devtools_tabbed_settings( $fields );

            // DevTools Section
            \ADMINIFY::createSection(
                $this->prefix,
                [
                    'title'  => __('Code Snippets', 'adminify'),
                    'id'     => 'custom_css_js',
                    'icon'   => 'fas fa-code',
                    'fields' => $fields,
                ]
            );
        }
    }
}
