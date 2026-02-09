<?php

namespace WPAdminify\Inc\Admin\Frames;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Modules\MenuEditor\MenuEditorOptions;
use WPAdminify\Inc\Admin\AdminSettings;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}
/**
 * WP Adminify
 * Admin Class
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

if (!class_exists('Admin')) {
    class Admin
    {

        public $options;
        public $menu_settings;

        /**
         * Constructor for the class.
         *
         * Initializes the hooks for the admin class.
         *
         * @return void
         */
        public function __construct()
        {
            $this->options = (array) AdminSettings::get_instance()->get();
            $this->menu_settings = (new MenuEditorOptions())->get();
            $this->init_hooks();
        }

        /**
         * Initializes the hooks for the admin class.
         *
         * This function adds the `load_scripts` method of the current object as a callback
         * to the `admin_enqueue_scripts` action hook. It also adds the `page_attribute`
         * method of the current object as a callback to the `language_attributes` filter
         * hook.
         *
         * @return void
         */
        private function init_hooks()
        {
            if ( wp_doing_ajax() ) return;
            add_action('admin_init', [$this, 'load_template']);
            add_action('admin_enqueue_scripts', [$this, 'load_scripts']);
            add_filter("language_attributes", [$this, "page_attribute"]);
            add_filter('parent_file', array($this, 'capture_wp_menu'), 999999999);

            add_action('wp_before_admin_bar_render', [$this, 'get_admin_bar_menu_list'], 999999);
        }

        /**
         * Enqueue the admin stylesheet and script.
         *
         * @return void
         */
        public function load_scripts()
        {
            wp_enqueue_style('frame-adminify--admin', WP_ADMINIFY_ASSETS . 'admin/css/admin' . Utils::assets_ext('.css'), [], WP_ADMINIFY_VER);
            wp_enqueue_script('frame-adminify--admin', WP_ADMINIFY_ASSETS . 'admin/js/frame' . Utils::assets_ext('.js'), ['jquery', 'react'], WP_ADMINIFY_VER, true);

            $localize_array_data = [
                'ajax_url'  => admin_url('admin-ajax.php'),
                'security_nonce' => wp_create_nonce('adminify_nonce')
            ];

            wp_localize_script( 'frame-adminify--admin', 'WPAdminify', $localize_array_data );
            wp_localize_script( 'frame-adminify--admin', 'WPAdminifyFrameNotAllowedURLs', self::get_not_allowed_urls() );
        }

        public static function get_not_allowed_urls() {

            $not_allowed_urls = [
                '/wp-admin/customize.php',
                '/wp-admin/site-editor.php',
                [
                    'url' => '*',
                    'query_params' => ['fl_builder', 'fl_builder_ui']
                ],
                [
                    'url' => '*',
                    'query_params' => ['action' => 'elementor']
                ],
                [
                    'url' => '*',
                    'query_params' => ['action' => 'architect'] // Added for thrive page builder
                ],
                [
                    'url' => '*',
                    'query_params' => ['bricks' => 'true']
                ],
                [
                    'url' => '*',
                    'query_params' => ['ct_builder' => 'true', 'oxygen_iframe!']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'latepoint'] // added for latepoint
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'sbi-setup']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'sbtt']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'cff-setup']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'rank-math-registration']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'googlesitekit-splash']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'cff-feed-builder']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'sbi-feed-builder']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'sbr']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'ctf-feed-builder']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'sby-feed-builder']
                ],
                [
                    'url' => '*',
                    'query_params' => ['page' => 'suretriggers']
                ],
                // [
                //     'url' => '/wp-admin/post-new.php',
                //     'post_type' => ['post', 'page']
                // ],
                // [
                //     'url' => '/wp-admin/post.php',
                //     'post_type' => ['post', 'page']
                // ]
            ];

            return apply_filters( 'adminify_frame_not_allowed_urls', $not_allowed_urls );
        }

        /**
         * Adds the 'frame-adminify-app="true"' attribute to the given attribute string.
         *
         * @param string $attr The original attribute string.
         * @return string The modified attribute string.
         */
        public function page_attribute($attr)
        {
            $attrs = [$attr];
            $attrs[] = 'frame-adminify-app="true"';
            return implode(' ', $attrs);
        }

        /**
         * Loads the admin template.
         *
         * @return void
         */
        public function load_template()
        {
            adminify_load_template('Templates.php');
        }

        public function capture_wp_menu($parent_file)
        {
            global $menu, $submenu;

            $menu_build = jltwp_adminify_build_menu($menu, $submenu, $this->menu_settings);

            $this->enqueue_menu($menu_build);

            return $parent_file;
        }

        public function enqueue_menu($menu)
        {
            $outputter = function () use ($menu) {

                $current_user        = wp_get_current_user();
                $light_dark_mode = $this->options['light_dark_mode'];
                if( !isset($light_dark_mode['admin_ui_mode']) ) {
                    $default_light_dark_mode = [
                        'admin_ui_mode' => 'light',
                        'admin_ui_logo_type' => 'image_logo',
                        'admin_ui_light_mode' => [
                            'admin_ui_light_logo_text' => 'WP Adminify',
                            'admin_ui_light_logo_text_typo' => array_fill_keys(
                                ['font-family', 'font-weight', 'font-style', 'font-size', 'letter-spacing', 'color', 'type', 'unit'], ''
                            ) + ['unit' => 'px'],
                            'admin_ui_light_logo' => array_fill_keys(['url', 'id', 'width', 'height', 'thumbnail', 'alt', 'title', 'description'], ''),
                            'light_logo_size' => ['width' => 120, 'height' => 32, 'unit' => 'px'],
                            'mini_admin_ui_light_logo' => array_fill_keys(['url', 'id', 'width', 'height', 'thumbnail', 'alt', 'title', 'description'], ''),
                        ],
                        'admin_ui_dark_mode' => [
                            'admin_ui_dark_logo_text' => 'WP Adminify',
                            'admin_ui_dark_logo_text_typo' => array_fill_keys(
                                ['font-family', 'font-weight', 'font-style', 'font-size', 'letter-spacing', 'color', 'type', 'unit'], ''
                            ) + ['unit' => 'px'],
                            'admin_ui_dark_logo' => array_fill_keys(['url', 'id', 'width', 'height', 'thumbnail', 'alt', 'title', 'description'], ''),
                            'dark_logo_size' => ['width' => 150, 'height' => 45, 'unit' => 'px'],
                            'mini_admin_ui_dark_logo' => array_fill_keys(['url', 'id', 'width', 'height', 'thumbnail', 'alt', 'title', 'description'], ''),
                            'schedule_dark_mode' => array_fill_keys(['enable_schedule_dark_mode', 'schedule_dark_mode_type', 'schedule_dark_mode_start_time', 'schedule_dark_mode_end_time'], ''),
                        ],
                    ];
                    $light_dark_mode = array_merge($default_light_dark_mode, $light_dark_mode);
                }else{
                    $light_dark_mode['admin_ui_mode'] = empty(get_user_meta(get_current_user_id(), 'color_mode', true)) ? $light_dark_mode['admin_ui_mode'] : get_user_meta(get_current_user_id(), 'color_mode', true);
                }
                $menu_settings = [
                    'menu'                     => $menu,
                    'admin_bar_dark_light_btn' => $this->options['admin_bar_dark_light_btn'],
                    'light_dark_mode'          => $light_dark_mode ,
                    'menu_layout_settings'     => $this->options['menu_layout_settings'],
                    'user_info' => [
                        'img'          => get_avatar($current_user->user_email, 72, '', ''),
                        'display_name' => esc_html($current_user->display_name),
                        'profile_url' => esc_url(admin_url('profile.php')),
                        'username' => esc_html($current_user->user_login),
                        'email'        => wp_kses_post(is_email($current_user->user_email)),
                    ],
                    'image_path' => WP_ADMINIFY_ASSETS_IMAGE,
                    'is_pro'      => (class_exists('\\WPAdminify\\Pro\\Adminify_Pro') && !empty(\WPAdminify\Pro\Adminify_Pro::is_premium())) ? true : false
                ];

                $data = "var frame_adminify_menu=" . json_encode($menu_settings);
                wp_print_inline_script_tag($data, ["id" => "frame_adminify_menu"]);
            };

            add_action("admin_footer", $outputter, 0);
            add_action("wp_footer", $outputter, 0);
        }


        public function get_admin_bar_menu_list()
        {
            global $wp_admin_bar;
            $admin_bar_data = $this->nodes_to_array($wp_admin_bar->get_nodes());
            $admin_bar_data_nested = $this->format_to_nested($admin_bar_data);

            $admin_bar_menu_data = [];

            // Admin Bar Exits
            if ( Utils::is_plugin_active('admin-bar/admin-bar.php') || Utils::is_plugin_active('admin-bar-pro/admin-bar-pro.php') ) {
                $admin_bar_items                  = get_option('_jltadminbar_settings');
                if( empty($admin_bar_items) ) return;

                $existing_admin_bar               = !empty($admin_bar_items['existing_admin_bar']) ? $admin_bar_items['existing_admin_bar'] : '';
                $saved_admin_bar                  = !empty($admin_bar_items['saved_admin_bar']) ? $admin_bar_items['saved_admin_bar'] : [];

                $parsed_admin_bar_backend         = empty($saved_admin_bar) ? $existing_admin_bar : $this->parse_menu_items($saved_admin_bar, $existing_admin_bar, 'backend');
                $nested_admin_bar                 = $this->format_to_nested($parsed_admin_bar_backend);
                $formated_admin_menu              = $this->associative_to_index_array($nested_admin_bar);

                $remove_admin_menu = [];
                foreach ($formated_admin_menu as $key => $value) {
                    if($value['id'] === 'wp-logo' || $value['id'] === 'site-name'|| $value['id'] === 'updates'|| $value['id'] === 'menu-toggle'|| $value['id'] === 'comments') {
                        continue;
                    }
                    $remove_admin_menu[$key] = $value;
                }

                $admin_bar_menu_data = $this->nodes_to_array_for_admin_bar($remove_admin_menu);
            }

            unset($admin_bar_data_nested['wp-logo']);
            unset($admin_bar_data_nested['site-name']);
            unset($admin_bar_data_nested['updates']);
            unset($admin_bar_data_nested['menu-toggle']);
            unset($admin_bar_data_nested['comments']);

            $admin_bar_data_nested = jlt_adminify_replaceAmpersandInHref($admin_bar_data_nested);
            $outputter = function () use ($admin_bar_data_nested, $admin_bar_menu_data) {
                $extra_data = [
                    'data'                => !empty($admin_bar_menu_data) ? $admin_bar_menu_data : array_values($admin_bar_data_nested),
                    'logout_url'          => wp_logout_url(home_url('/')),
                    // 'site_url'            => site_url('/'),
                    'site_url'            => home_url(),
                    'admin_url'           => admin_url(),
                    'search'              => $this->options['admin_bar_search'],
                    'notification'        => $this->options['admin_bar_notif'],
                    'light_dark_switcher' => $this->options['admin_bar_dark_light_btn'],
                ];
                $data = "var adminify_admin_bar_data=" . json_encode($extra_data);
                wp_print_inline_script_tag($data, ["id" => "adminify_admin_bar_data"]);
            };

            add_action("admin_footer", $outputter, 0);
            add_action("wp_footer", $outputter, 0);
            return $admin_bar_data;
        }

        public function adminify_adminbar_localize_script(){
            echo $this->get_admin_bar_menu_list();
        }

        /**
         * Turn admin bar items object to array
         *
         * @param array $nodes The admin bar menu.
         * @return array Array in expected format.
         */
        public function nodes_to_array($nodes)
        {
            $admin_bar_array = array();
            foreach ($nodes as $node_id => $node) {
                $admin_bar_array[$node_id] = array(
                    'icon'                   => $this->add_default_icon($node->id),
                    // 'icon_default'           => $this->add_default_icon($node->id),
                    'id'                     => $node->id,
                    // 'id_default'             => $node->id,
                    'title'                  => $node->title,
                    // 'title_default'          => $node->title,
                    'parent'                 => $node->parent,
                    // 'parent_default'         => $node->parent,
                    'href'                   => $node->href,
                    // 'href_default'           => $node->href,
                    'group'                  => $node->group,
                    // 'group_default'          => $node->group,
                    'meta'                   => $node->meta,
                    // 'meta_default'           => $node->meta,
                    'submenu'                => array(),
                    // 'hidden_for'             => '',
                    // 'newly_created'          => 0,
                    'menu_level'             => 0,
                    'menu_status'            => true,
                );
            }
            return $admin_bar_array;
        }

        /**
         * Turn admin bar items object to array
         *
         * @param array $nodes The admin bar menu.
         * @return array Array in expected format.
         */
        public function nodes_to_array_for_admin_bar($nodes)
        {
            $admin_bar_array = [];
            foreach ($nodes as $node_id => $node) {
                $admin_bar_array[$node_id] = array(
                    'icon'                   => !empty($node['icon']) ? $node['icon'] : $node['icon_default'],
                    // 'icon_default'           => $this->add_default_icon($node->id),
                    'id'                     => !empty($node['id']) ? $node['id'] : $node['id_default'],
                    // 'id_default'             => $node->id,
                    'title'                  => !empty($node['title']) ? $node['title'] : $node['title_default'],
                    // 'title_default'          => $node->title,
                    'parent'                 => !empty($node['parent']) ? $node['parent'] : $node['parent_default'],
                    // 'parent_default'         => $node->parent,
                    'href'                   => !empty($node['href']) ? $node['href'] : $node['href_default'],
                    // 'href_default'           => $node->href,
                    'group'                  => !empty($node['group']) ? $node['group'] : $node['group_default'],
                    // 'group_default'          => $node->group,
                    'meta'                   => !empty($node['meta']) ? $node['meta'] : $node['meta_default'],
                    // 'meta_default'           => $node->meta,
                    'submenu'                => $this->nodes_to_array_for_admin_bar($node['submenu']),
                    // 'hidden_for'             => '',
                    // 'newly_created'          => 0,
                    'menu_level'             => 0,
                    'menu_status'            => !empty( $node['menu_status'] ) ? true : false,
                );
            }

            return array_values($admin_bar_array);
        }

        /**
         * Default Icon
         */
        public function add_default_icon($menu_id)
        {
            $icon_class = '';

            if ('wp-logo' === $menu_id) {
                $icon_class = 'dashicons dashicons-wordpress';
            } else if ('my-sites' === $menu_id) {
                $icon_class = 'dashicons dashicons-admin-multisite';
            } else if ('site-name' === $menu_id) {
                $icon_class = 'dashicons dashicons-admin-home';
            } else if ('site-name-frontend' === $menu_id) {
                $icon_class = 'dashicons dashicons-dashboard';
            } else if ('customize' === $menu_id) {
                $icon_class = 'dashicons dashicons-admin-customizer';
            } else if ('updates' === $menu_id) {
                $icon_class = 'dashicons dashicons-update';
            } else if ('comments' === $menu_id) {
                $icon_class = 'dashicons dashicons-admin-comments';
            } else if ('new-content' === $menu_id) {
                $icon_class = 'dashicons dashicons-plus';
            } else if ('edit' === $menu_id) {
                $icon_class = 'dashicons dashicons-edit';
            } else if ('site-editor' === $menu_id) {
                $icon_class = 'dashicons dashicons-admin-appearance';
            }
            return $icon_class;
        }


        public function format_to_nested($flat_array)
        {

            if (isset($flat_array['menu-toggle'])) {
                unset($flat_array['menu-toggle']);
            }

            $nested_array = [];

            // Third, get the parent menu items.
            foreach ($flat_array as $menu_id => $menu) {

                if( !empty($menu['hidden_for']) ) {
                    $disable_for = [];

                    foreach($menu['hidden_for'] as $hidden_key => $hidden_user){
                        $disable_for[$hidden_key] = strtolower($hidden_user['value']);

                    }

                    if (\WPAdminify\Inc\Utils::restrict_for($disable_for)) {
                        unset($flat_array[$menu_id]);
                        continue;
                    }
                }

                if ( isset($menu['parent']) && $menu['parent'] && !isset($flat_array[$menu['parent']]) ) {
                    unset($flat_array[$menu_id]);
                    continue;
                }

                if (!isset($menu['parent']) || !$menu['parent'] || !isset($flat_array[$menu['parent']])) {
                    $nested_array[$menu_id] = $menu;

                    $additional = array(
                        'title_encoded'         => isset($menu['title']) ? htmlentities2($menu['title']) : '',
                        'title_clean'           => isset($menu['title']) ? wp_strip_all_tags($menu['title']) : '',
                        'title_encoded_default' => isset($menu['title_default']) ? htmlentities2($menu['title_default']) : '',
                        'title_clean_default'   => isset($menu['title_default']) ? wp_strip_all_tags($menu['title_default']) : '',
                        'submenu'               => array(),
                        'menu_level'            => 0
                    );

                    $nested_array[$menu_id] = array_merge($nested_array[$menu_id], $additional);
                }
            }

            // Fourth, remove collected parent array from $flat_array.
            foreach ($nested_array as $key => $value) {
                if (isset($flat_array[$key])) {
                    unset($flat_array[$key]);
                }
            }

            // Fifth, get the 1st level submenu items.
            foreach ($flat_array as $menu_id => $menu) {
                if (isset($nested_array[$menu['parent']])) {
                    $nested_array[$menu['parent']]['submenu'][$menu['id']] = $menu;

                    $additional = array(
                        'title_encoded'         => isset($menu['title']) ? htmlentities2($menu['title']) : '',
                        'title_clean'           => isset($menu['title']) ? wp_strip_all_tags($menu['title']) : '',
                        'title_encoded_default' => isset($menu['title_default']) ? htmlentities2($menu['title_default']) : '',
                        'title_clean_default'   => isset($menu['title_default']) ? wp_strip_all_tags($menu['title_default']) : '',
                        'submenu'               => array(),
                        'menu_level'            => 1
                    );

                    $nested_array[$menu['parent']]['submenu'][$menu['id']] = array_merge(
                        $nested_array[$menu['parent']]['submenu'][$menu['id']],
                        $additional
                    );

                    unset($flat_array[$menu_id]);
                }
            }

            // Sixth, get the 2nd level submenu items.
            if (!empty($flat_array)) {
                // Loop over flat_array.
                foreach ($flat_array as $menu_id => $menu) {
                    // Loop over nested_array.
                    foreach ($nested_array as $parent_id => $parent_array) {
                        $submenu_lv2_found = false;

                        if (!empty($parent_array['submenu'])) {
                            // Loop over parent array's submenu.
                            foreach ($parent_array['submenu'] as $submenu_lv1_id => $submenu_lv1_array) {
                                if ($menu['parent'] === $submenu_lv1_id) {
                                    if (!isset($nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'])) {
                                        $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'] = array();
                                    }

                                    $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$menu_id] = $menu;

                                    $additional = array(
                                        'title_encoded'         => isset($menu['title']) ? htmlentities2($menu['title']) : '',
                                        'title_clean'           => isset($menu['title']) ? wp_strip_all_tags($menu['title']) : '',
                                        'title_encoded_default' => isset($menu['title_default']) ? htmlentities2($menu['title_default']) : '',
                                        'title_clean_default'   => isset($menu['title_default']) ? wp_strip_all_tags($menu['title_default']) : '',
                                        'submenu'               => array(),
                                        'menu_level'            => 2
                                    );

                                    $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$menu_id] = array_merge(
                                        $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$menu_id],
                                        $additional
                                    );

                                    unset($flat_array[$menu_id]);
                                    $submenu_lv2_found = true;
                                    break;
                                }
                            }
                        }

                        if ($submenu_lv2_found) {
                            break;
                        }
                    }
                }
            }

            // Seventh, get the 3rd level submenu items.
            if (!empty($flat_array)) {
                // Loop over flat_array.
                foreach ($flat_array as $menu_id => $menu) {
                    // Loop over nested_array.
                    foreach ($nested_array as $parent_id => $parent_array) {
                        $submenu_lv3_found = false;

                        if (!empty($parent_array['submenu'])) {
                            // Loop over parent array's submenu.
                            foreach ($parent_array['submenu'] as $submenu_lv1_id => $submenu_lv1_array) {
                                if (!empty($submenu_lv1_array['submenu'])) {
                                    // Loop over submenu level 1's submenu.
                                    foreach ($submenu_lv1_array['submenu'] as $submenu_lv2_id => $submenu_lv2_array) {
                                        if ($menu['parent'] === $submenu_lv2_id) {
                                            if (!isset($nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$submenu_lv2_id]['submenu'])) {
                                                $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$submenu_lv2_id]['submenu'] = array();
                                            }

                                            $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$submenu_lv2_id]['submenu'][$menu_id] = $menu;

                                            $additional = array(
                                                'title_encoded'         => isset($menu['title']) ? htmlentities2($menu['title']) : '',
                                                'title_clean'           => isset($menu['title']) ? wp_strip_all_tags($menu['title']) : '',
                                                'title_encoded_default' => isset($menu['title_default']) ? htmlentities2($menu['title_default']) : '',
                                                'title_clean_default'   => isset($menu['title_default']) ? wp_strip_all_tags($menu['title_default']) : '',
                                                'submenu'               => array(),
                                                'menu_level'            => 3
                                            );

                                            $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$submenu_lv2_id]['submenu'][$menu_id] = array_merge(
                                                $nested_array[$parent_id]['submenu'][$submenu_lv1_id]['submenu'][$submenu_lv2_id]['submenu'][$menu_id],
                                                $additional
                                            );

                                            unset($flat_array[$menu_id]);
                                            $submenu_lv3_found = true;
                                            break;
                                        }
                                    }
                                }

                                if ($submenu_lv3_found) {
                                    break;
                                }
                            }
                        }

                        if ($submenu_lv3_found) {
                            break;
                        }
                    }
                }
            }
            return $nested_array;
        }



        public function is_network_active()
        {
            if (!function_exists('is_plugin_active_for_network') || !function_exists('is_plugin_active')) {
                require_once ABSPATH . '/wp-admin/includes/plugin.php';
            }

            return (is_plugin_active_for_network('adminify/adminify.php') ? true : false);
        }

        public function parse_menu_items($args, $default, $request_from = 'backend')
        {
            // $instance     = new self();
            $parsed_array = [];
            // $ms_helper    = new Multisite_Helper();
            $mulsite_site = $this->is_network_active();

            // parse menu
            if (true) {
                if ($request_from == 'backend') {
                    $new_array    = [];
                    foreach ($default as $key => $menu) {
                        if (array_search($key, array_keys($args)) && $key == $args[$key]['id']) {
                            // $new_array[$key] = $args[$key];
                        } else {
                            if (($mulsite_site) && ($key == 'my-sites')) {
                                $new_array[$key] = $args[$key];
                                unset($args[$key]);
                            } else {
                                $new_array[$key] = $menu;
                            }
                        }
                    }

                    $new_args = [];
                    foreach ($args as $key => $menu) {
                        if (str_contains($key, 'custom-menu-')) {
                            $new_args[$key] = $menu;
                        }
                        if (!array_key_exists($key, $default)) {
                            continue;
                        }
                        $new_args[$key] = $args[$key];
                    }

                    $pos  = array_search('comments', array_keys($new_array), true);
                    // $pos += 1;

                    unset($new_array['top-secondary']);

                    // $new_array = array_slice($new_array, 0, $pos, true) + $new_args + array_slice($new_array, $pos, count($new_array) - 1, true);
                    $new_array = $new_args + array_slice($new_array, $pos, count($new_array), true);

                    return $new_array;
                }
            }
        }

        public static function associative_to_index_array($asoc_array)
        {

            $new_array = [];
            $i = 0;

            foreach ($asoc_array as $key  =>  $value) {
                if (isset($value['submenu']) && count($value['submenu']) > 0) {
                    $j = 0;
                    $sub_1 = [];
                    foreach ($value['submenu'] as $key1  =>  $value1) {

                        if (isset($value1['submenu']) &&  count($value1['submenu']) > 0) {
                            $k = 0;
                            $sub_2 = [];
                            foreach ($value1['submenu'] as $key2  =>  $value2) {
                                if (isset($value2['submenu']) &&  count($value2['submenu']) > 0) {
                                    $l = 0;
                                    $sub_3 = [];
                                    foreach ($value2['submenu'] as $key => $value3) {
                                        if (isset($value3['submenu']) &&  count($value3['submenu']) > 0) {
                                            $sub_3[$l] = $value3;
                                            $sub_3[$l]['submenu'] = array_values($value3['submenu']);
                                        } else {
                                            $sub_3[$l] = $value3;
                                        }
                                        $l++;
                                    }
                                    $sub_2[$k] = $value2;
                                    $sub_2[$k]['submenu'] = $sub_3;
                                } else {
                                    $sub_2[$k] = $value2;
                                }
                                $k++;
                            }

                            $sub_1[$j] = $value1;
                            $sub_1[$j]['submenu'] = $sub_2;
                        } else {
                            $sub_1[$j] = $value1;
                        }
                        // $new_array[$i]['submenu'][$j] = $value1;
                        $j++;
                    }
                    $new_array[$i] = $value;
                    $new_array[$i]['submenu'] = $sub_1;
                } else {
                    $new_array[$i] = $value;
                }
                $i++;
            }
            return $new_array;
        }


    }
}
