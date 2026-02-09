<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Libs\Addons;

if (!class_exists('Addons_Plugins')) {
    /**
     * Addons Plugins class
     *
     * Jewel Theme <support@jeweltheme.com>
     */
    class Addons_Plugins extends Addons
    {

        private $transient_key = 'wp_adminify_addon_plugins_data';

        /**
         * Constructor method
         */
        public function __construct()
        {
            $this->menu_order = 99; // for submenu order value should be more than 10 .
            parent::__construct($this->menu_order);
            add_action('admin_enqueue_scripts', [$this, 'add_addons_thickbox']);
        }

        public function add_addons_thickbox () {
            add_thickbox();
        }

        /**
         * Menu list
         */
        public function menu_items()
        {
            return array(
                array(
                    'key'   => 'all',
                    'label' => 'All',
                ),
                array(
                    'key'   => 'featured', // key should be used as category to the plugin list.
                    'label' => 'Featured Item',
                ),
                array(
                    'key'   => 'popular',
                    'label' => 'Popular',
                ),
                array(
                    'key'   => 'favorites',
                    'label' => 'Favorites',
                ),
                array(
                    'key'   => 'recommended',
                    'label' => 'Recommended',
                ),
            );
        }

        /**
         * Get data from github
         *
         * @return void
         */
        public function get_adminify_plugins_lists()
        {

            // Return plugins data from cache if found
            $plugins_data = get_transient($this->transient_key);
            if (!empty($plugins_data)) return $plugins_data;

            // URL of the raw file in the GitHub repository
            $file_url = 'https://raw.githubusercontent.com/jeweltheme/adminify-addons-assets/main/adminify-plugins.json';

            // Make the request to fetch the file contents
            $response = wp_remote_get($file_url);


            // Check if the request was successful
            if (!is_wp_error($response) && $response['response']['code'] === 200) {
                // Get the body of the response (file contents)
                $file_contents = wp_remote_retrieve_body($response);

                // Decode the JSON data into an array
                $data_array = json_decode($file_contents, true);

                // Check if JSON decoding was successful
                if (!empty($data_array)) {
                    // Now you have the data in an array format, you can use it as needed
                    $plugins_data = [];

                    foreach ($data_array as $plugin_data) {

                        $plugins_data[ $plugin_data['slug'] ] = $plugin_data;

                        if ( str_contains( $plugin_data['download_link'], 'downloads.wordpress.org' ) ) {

                            require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

                            $plugin_info = \plugins_api( 'plugin_information', array( 'slug' => $plugin_data['slug'] ) );
                            $plugins_data[ $plugin_data['slug'] ]['version'] = $plugin_info->version;

                        } else {

                            if ( ! isset( $plugin_data['version'] ) ) {
                                unset( $plugins_data[ $plugin_data['slug'] ] );
                                continue;
                            }

                            $download_link = add_query_arg( 'version', $plugin_data['version'], $plugin_data['download_link'] );
                            $plugins_data[ $plugin_data['slug'] ]['download_link'] = $download_link;

                        }

                    }

                    // Get plugins data from API and cache it
                    $status = set_transient($this->transient_key, $plugins_data, DAY_IN_SECONDS / 2);
                    if (!$status) return [];
                    return $plugins_data;
                } else {
                    // JSON decoding failed
                    echo esc_html__('Error while data retrieval.', 'adminify');
                }
            } else {
                // Request failed
                echo esc_html__('Failed to retrieve file from Source.', 'adminify');
            }
        }


        /**
         * Plugins List
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function plugins_list()
        {
            return $this->get_adminify_plugins_lists();
        }

        /**
         * Admin submenu
         */
        public function admin_menu()
        {
            $submenu_position = apply_filters('jltwp_adminify_submenu_position', 50);
            add_submenu_page(
                'wp-adminify-settings',       // Ex. wp-adminify-settings /  edit.php?post_type=page .
                __('Addons', 'adminify'),
                sprintf( '<span class="adminify-addons-text">%s</span>', __( 'Addons', 'adminify' ) ),
                'manage_options',
                'wp-adminify-addons-plugins',
                array($this, 'render_addons_plugins'),
                $submenu_position
            );
        }
    }
}
