<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;


if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

if (!class_exists('Performance')) {
    class Performance extends AdminSettingsModel
    {

        public $defaults = [];

        public function __construct()
        {
            $this->optimization_settings();
        }

        protected function get_defaults()
        {
            return [
                'performance'       => [
                    'performance_enable' => true,
                    'performance_data'   => [
                        'version_strings',
                        'emoji',
                    ]
                ],
                'disable_embeds'    => false,   // Gutenberg
                // Gutenberg
                'disable_gutenberg' => [
                    'disable_gutenberg_enable' => false,
                    'disable_for'              => []
                ],
                'heartbeat_api'     => [
                    'enabled'               => false,
                    'backend'               => 'default',
                    'backend_modify'        => 60,
                    'on_post_create'        => 'default',
                    'on_post_create_modify' => 15,
                    'on_frontend'           => 'default',
                    'on_frontend_modify'    => 60
                ],
                'adminify_assets'   => [],
                'revisions'         => [
                    'revisions_enable' => false,
                    'limit'            => 30,
                    'post_types'       => '',
                ]
            ];
        }

        /**
         * Gutenberg Settings
         */

        public function gutenberg_settings(&$fields)
        {
            // Disable Gutenberg
            $disable_gutenberg_for = [
                'block_editor'             => __('Remove Backend Gutenberg Block Editor & Scripts for Entire Site. i.e. - Post/Page/Custom Post Types', 'adminify'),
                'remove_gutenberg_scripts' => __('Remove Frontend Gutenberg Styles & Scripts', 'adminify'),
                // 'scripts'                  => __('Remove Gutenberg Scripts', 'adminify'),
                // 'sidebar_widgets' => __('Disable Gutenberg for Sidebar Widgets', 'adminify'),
            ];


            $disable_gutenberg_fields = [
                [
                    'id'         => 'disable_gutenberg_enable',
                    'type'       => 'switcher',
                    'class'      => 'adminify-pl-0 adminify-pt-0',
                    'title'      => __('', 'adminify'),
                    'text_on'    => __('Show', 'adminify'),
                    'text_off'   => __('Hide', 'adminify'),
                    'text_width' => 80,
                    // 'default'    => $this->get_default_field('disable_gutenberg')['disable_gutenberg_enable'],
                ],
                [
                    'id'         => 'disable_for',
                    'type'       => 'checkbox',
                    'title'      => __('', 'adminify'),
                    'class'      => 'adminify-one-col',
                    'options'    => $disable_gutenberg_for,
                    // 'default'    => $this->get_default_field('disable_gutenberg')['disable_for'],
                    'dependency' => ['disable_gutenberg_enable', '==', 'true', true],
                ]
            ];

            $fields[] = [
                'id'       => 'disable_gutenberg',
                'type'     => 'fieldset',
                'title'    => __('Disable Gutenberg for', 'adminify'),
                'subtitle' => __('Cleanup Gutenberg Form and Gutenberg Template', 'adminify'),
                'fields'   => $disable_gutenberg_fields,
                'default'  => $this->get_default_field('disable_gutenberg'),
            ];
        }


        /**
         * Admin Notices: Settings
         */
        public function performances(&$fields)
        {
            $performance_data = [
                'dashicons'                => __('Remove Dashicons from front-end for Public Visitors', 'adminify'),
                'version_strings'          => __('Remove Versions from Styles/Scripts', 'adminify'),
                'gravatar_query_strings'   => __('Remove Gravatar Query Strings', 'adminify'),
                'emoji'                    => __('Remove All Emoji styles and scripts from head section', 'adminify'),
                'jquery_migrate_front'     => __('Remove front end jQuery Migrate script.', 'adminify'),
                'jquery_migrate_back'      => __('Remove back end jQuery Migrate script. Note: It might break functionality', 'adminify'),
                'defer_parsing_js_footer'  => __('Enable Defer Parsing JS to Footer', 'adminify'),
                'cache_gzip_compression'   => __('Enable Browser Cache Expires & GZIP Compression', 'adminify'),
            ];

            $performance_fields = [
                [
                    'id'         => 'performance_enable',
                    'type'       => 'switcher',
                    'class'      => 'adminify-pl-0 adminify-pt-0',
                    'title'      => __('', 'adminify'),
                    'text_on'    => __('Show', 'adminify'),
                    'text_off'   => __('Hide', 'adminify'),
                    'text_width' => 80
                ],
                [
                    'id'         => 'performance_data',
                    'type'       => 'checkbox',
                    'title'      => __('', 'adminify'),
                    'class'      => 'adminify-one-col',
                    'options'    => $performance_data,
                    'dependency' => ['performance_enable', '==', 'true', true],
                ]
            ];

            $fields[] = array(
                'id'       => 'performance',
                'type'     => 'fieldset',
                'title'    => __('Performance Enhancements', 'adminify'),
                'subtitle' => __('Enhance Performances', 'adminify'),
                'fields'   => $performance_fields,
                'default'  => $this->get_default_field('performance'),
            );
        }




        /**
         * Security: Disable All Embeds
         *
         * @param [type] $security_feed
         *
         * @return void
         */
        public function disable_embeds(&$disable_embeds)
        {
            $disable_embeds[] = [
				'id'      => 'performance_subheading',
                'type'    => 'subheading',
                'content' => Utils::adminfiy_help_urls(
                    '<span></span>',
                    'https://wpadminify.com/docs/adminify/performance/disable-embeds',
                    'https://www.youtube.com/watch?v=xALg4WWZ_bE',
                    'https://www.facebook.com/groups/jeweltheme',
                    \WPAdminify\Inc\Admin\AdminSettings::support_url()
                ),
			];

            $disable_embeds[] = [
                'id'         => 'disable_embeds',
                'type'       => 'switcher',
                'class'      => 'adminify-pro-fieldset adminify-pro-notice',
                'title'      => sprintf(__('Disable Embeds %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
                'subtitle'   => __('Disable all Embeds from everywhere like - REST API, Head Tag, Tinymce Editor, Remote results etc.', 'adminify'),
                'text_on'    => __('Yes', 'adminify'),
                'text_off'   => __('No', 'adminify'),
                'text_width' => 80,
                'default'    => $this->get_default_field('disable_embeds'),
            ];
        }

        public function control_heartbit_api_settings(&$fields){
            $fields[] = [
                'id'         => 'enabled',
                'type'       => 'switcher',
                'class'      => 'adminify-pt-0 adminify-pl-0',
                'title'      => __('', 'adminify'),
                'text_on'    => __('Show', 'adminify'),
                'text_off'   => __('Hide', 'adminify'),
                'text_width' => 80,
                'default'    => $this->get_default_field('heartbeat_api')['enabled'],
            ];
            $fields[] = [
                'id'       => 'backend',
                'type'     => 'radio',
                'inline'   => true,
                'class'    => Utils::upgrade_pro_class(),
                'title'    => __('WordPress Dashboard', 'adminify'),
                'subtitle' => __('Backend Post Types', 'adminify'),
                'options'    => [
                    'default' => __('Default', 'adminify'),
                    'modify'  => __('Modify', 'adminify'),
                    'disable' => __('Disable', 'adminify'),
                ],
                'default' => $this->get_default_field('heartbeat_api')['backend'],
                'dependency' => ['enabled', '==', 'true', 'true'],
            ];
            $fields[] = [
                'id'       => 'backend_modify',
                'type'     => 'select',
                'title'    => __('Set interval to once every', 'adminify'),
                'subtitle' => __('Default: 1 Minute', 'adminify'),
                'options'  => array(
                    '15'  => __('15 seconds', 'adminify'),
                    '30'  => __('30 seconds', 'adminify'),
                    '60'  => __('1 Minute', 'adminify'),
                    '120' => __('2 Minute', 'adminify'),
                    '180' => __('3 Minute', 'adminify'),
                    '300' => __('5 Minute', 'adminify'),
                    '600' => __('10 Minute', 'adminify'),
                ),
                'default' => $this->get_default_field('heartbeat_api')['backend_modify'],
                'dependency' => ['enabled|backend', '==|==', 'true|modify', 'true'],
            ];

            $fields[] = [
                'id'       => 'on_post_create',
                'type'     => 'radio',
                'class'      => Utils::upgrade_pro_class(),
                'inline'   => true,
                'title'    => __('Create Post Editor', 'adminify'),
                'subtitle' => __('On post creation and edit screens', 'adminify'),
                'options'  => [
                    'default' => __('Default', 'adminify'),
                    'modify'  => __('Modify', 'adminify'),
                    'disable' => __('Disable', 'adminify'),
                ],
                'default' => $this->get_default_field('heartbeat_api')['on_post_create'],
                'dependency' => ['enabled', '==', 'true', 'true'],
            ];

            $fields[] = [
                'id'       => 'on_post_create_modify',
                'type'     => 'select',
                'title'    => __('Set interval to once every', 'adminify'),
                'subtitle' => __('Default: 15 seconds', 'adminify'),
                'options'  => array(
                    '15'  => __('15 seconds', 'adminify'),
                    '30'  => __('30 seconds', 'adminify'),
                    '60'  => __('1 Minute', 'adminify'),
                    '120' => __('2 Minute', 'adminify'),
                    '180' => __('3 Minute', 'adminify'),
                    '300' => __('5 Minute', 'adminify'),
                    '600' => __('10 Minute', 'adminify'),
                ),
                'default'    => $this->get_default_field('heartbeat_api')['on_post_create_modify'],
                'dependency' => ['enabled|on_post_create', '==|==', 'true|modify', 'true'],
            ];

            $fields[] = [
                'id'       => 'on_frontend',
                'type'     => 'radio',
                'class'    => Utils::upgrade_pro_class(),
                'inline'   => true,
                'title'    => __('Frontend', 'adminify'),
                'subtitle' => __('Frontend Heartbits', 'adminify'),
                'options'  => [
                    'default' => __('Default', 'adminify'),
                    'modify'  => __('Modify', 'adminify'),
                    'disable' => __('Disable', 'adminify'),
                ],
                'default'    => $this->get_default_field('heartbeat_api')['on_frontend'],
                'dependency' => ['enabled', '==', 'true', 'true'],
            ];

            $fields[] = [
                'id'       => 'on_frontend_modify',
                'type'     => 'select',
                'title'    => __('Set interval to once every', 'adminify'),
                'subtitle' => __('Default: 1 Minute', 'adminify'),
                'options'  => array(
                    '15'  => __('15 seconds', 'adminify'),
                    '30'  => __('30 seconds', 'adminify'),
                    '60'  => __('1 Minute', 'adminify'),
                    '120' => __('2 Minute', 'adminify'),
                    '180' => __('3 Minute', 'adminify'),
                    '300' => __('5 Minute', 'adminify'),
                    '600' => __('10 Minute', 'adminify'),
                ),
                'default'    => $this->get_default_field('heartbeat_api')['on_frontend_modify'],
                'dependency' => ['enabled|on_frontend', '==|==', 'true|modify', 'true'],
            ];
        }


        /**
         * Control Heartbit API
         */
        public function control_heartbit_api(&$fields){
            $heartbeat_settings      = [];
            $this->control_heartbit_api_settings($heartbeat_settings);

            $fields[] = array(
                'id'       => 'heartbeat_api',
                'type'     => 'fieldset',
                'class'    => 'adminify-nopadding',
                'title'    => sprintf(__('Control Heartbeat API %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
                'class'    => 'adminify-pro-fieldset',
                'subtitle' => __('Modify the interval of the WordPress Heartbeat API or disable it on admin pages, post creation/edit screens, and the frontend to reduce CPU load on the server', 'adminify'),
                'fields'   => $heartbeat_settings
            );
        }


        /**
         * Revisions Settings
         *
         * @return void
         */
        public function control_revisions_settings( &$revisions_settings ){
            $revisions_settings[] = [
                'id'         => 'revisions_enable',
                'type'       => 'switcher',
                'class'      => 'adminify-p-0 adminify-pro-feature',
                'title'      => __('', 'adminify'),
                'text_on'    => __('Show', 'adminify'),
                'text_off'   => __('Hide', 'adminify'),
                'text_width' => 80,

                'default'    => $this->get_default_field('revisions')['revisions_enable'],
            ];
            $revisions_settings[] = [
                'id'         => 'limit',
                'type'       => 'number',
                'title'      => __( 'Limit of Revisions', 'adminify' ),
                'subtitle'   => __('Number of Revisions to show', 'adminify'),
                'class'      => 'adminify-pro-feature adminify-pro-notice',
                'default'    => $this->get_default_field('revisions')['limit'],
                'dependency' => ['revisions_enable', '==', 'true', 'true'],
            ];

            $revisions_settings[] = [
                'id'         => 'post_types',
                'type'       => 'checkbox',
                'title'      => __('Apply for Post Types', 'adminify'),
                'class'      => 'adminify-pro-feature adminify-pro-notice',
                'subtitle'   => __('Disable all Embeds from everywhere like - REST API, Head Tag, Tinymce Editor, Remote results etc.', 'adminify'),
                'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
                'default'    => $this->get_default_field('revisions')['post_types'],
                'dependency' => ['revisions_enable', '==', 'true', 'true'],
            ];

        }

        /**
         * Control Revisions
         */
        public function control_revisions(&$fields){
            $revisions_settings      = [];
            $this->control_revisions_settings($revisions_settings);

            $fields[] = array(
                'id'       => 'revisions',
                'type'     => 'fieldset',
                'title'    => sprintf(__('Control Revisions %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
                'subtitle' => __('Limit the number of revisions kept for post types supporting revisions to prevent database bloat.', 'adminify'),
                'fields'   => $revisions_settings,
                'default'  => $this->get_default_field('revisions'),
            );
        }


        public function optimization_settings()
        {
            if (!class_exists('ADMINIFY')) {
                return;
            }

			$fields = [];

			$this->disable_embeds( $fields );
			$this->control_heartbit_api( $fields );
			// $this->control_revisions( $fields );
			$this->performances( $fields );
            $this->gutenberg_settings($fields);


            $fields = apply_filters('adminify_settings/performance', $fields, $this);

            // Optimization Section
            \ADMINIFY::createSection(
                $this->prefix,
                [
                    'title'  => __('Performance', 'adminify'),
                    'id'     => 'performance',
                    'icon'   => 'fas fa-chart-bar',
                    'fields' => $fields,
                ]
            );
        }
    }
}
