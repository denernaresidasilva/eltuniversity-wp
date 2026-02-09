<?php

namespace WPAdminify\Libs;

// No, Direct access Sir !!!
if (!defined('ABSPATH')) {
    exit;
}

/*
 * Addons global class
 */

if (!class_exists('Addons')) {

    /**
     * Addons Class
     *
     * Jewel Theme <support@jeweltheme.com>
     */
    class Addons
    {
        public $menu_items = [];
        public $plugins_list = [];
        public $sub_menu;
        public $menu_order;

        public $server_url = 'https://coupon.wpadminify.com/';


        /**
         * Constructor method
         *
         * @param integer $menu_order .
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function __construct($menu_order = 70)
        {
            $this->menu_order   = $menu_order;
            $this->menu_items   = $this->menu_items();
            $this->plugins_list = $this->plugins_list();

            $this->includes();

            add_action('admin_menu', array($this, 'admin_menu'), 1000);
            // add_action('network_admin_menu', array($this, 'admin_menu'), $this->menu_order);
            add_action('wp_ajax_jltwp_adminify_addons_upgrade_plugin', array($this, 'jltwp_adminify_addons_upgrade_plugin'));
            add_action('wp_ajax_jltwp_adminify_addons_activate_plugin', array($this, 'jltwp_adminify_addons_activate_plugin'));
            add_action('plugins_loaded', array($this, 'maybe_replace_addons_path'), 1000); // 1000 is important
            add_action( 'rest_api_init', array( $this , 'jltwp_adminify_addons_rest_routes') );
        }

        public function jltwp_adminify_addons_rest_routes() {
            register_rest_route('adminify/v1', '/get-addons-list', array(
                'methods'             => 'GET',
                'callback'            => [$this, 'jltwp_adminify_get_addons_plugins_list'],
                // 'permission_callback' => [$this, 'adminify_is_admin_user'],
                'permission_callback' => '__return_true',
            ));

            register_rest_route('adminify/v1', '/install-addons', array(
                'methods'             => 'POST',
                'callback'            => [$this, 'jltwp_adminify_install_addons'],
                'permission_callback' => [$this, 'adminify_verify_nonce_and_permissions'],
            ));
        }

        public function adminify_is_admin_user() {
            return current_user_can('manage_options');
        }

        public function adminify_verify_nonce_and_permissions() {
            // Check user
            if ( ! current_user_can('manage_options') ) {
                return new WP_Error('forbidden', 'You are not allowed to do this.', array('status' => 403));
            }

            // Check nonce from header
            $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
            if ( ! wp_verify_nonce($nonce, 'wp_rest') ) {
                return new WP_Error('rest_cookie_invalid_nonce', __('Invalid nonce.'), array('status' => 403));
            }
            if ( is_multisite() && ! is_super_admin() ) {
                return new WP_Error('not_allowed', 'Super admin only on multisite.', array('status' => 403));
            }

            return true;
        }


        public function jltwp_adminify_get_addons_plugins_list() {
            $plugins = $this->plugins_list;
            unset($plugins['master-addons']);
            $all_plugins = get_plugins();
            $active_plugins = get_option('active_plugins');
            foreach( $plugins as $slug => $plugin){
                foreach ($all_plugins as $plugin_file => $plugin_data) {
                    if (strpos($plugin_file, $slug) !== false) {
                        $plugins[$slug]["status"] = 'installed';

                        if (in_array($plugin_file, $active_plugins)) {
                            $plugins[$slug]["status"] = 'activated';
                        }
                        break;
                    }
                }
                if( !isset($plugins[$slug]["status"])) $plugins[$slug]["status"] = 'not-installed';
    
            }

            return rest_ensure_response($plugins);
            
        }


        public function jltwp_adminify_install_addons( $request ) {
            $addons = $request->get_param('addons'); 
            if ( empty($addons) || !is_array($addons) ) {
                return new WP_Error('no_addons', 'No addons were selected.', array('status' => 400));
            }

            $plugins_list = $this->jltwp_adminify_get_addons_plugins_list()->data;
            foreach( $addons as $key => $plugin ){
                if($plugins_list[$plugin]['status'] == "activated") continue;
                if($plugins_list[$plugin]['status'] == "installed") {
                    $this->jltwp_adminify_activate_plugin_by_slug($plugin);
                    continue;
                }
                $params = [
                    'request_type' => 'rest',
                    'plugin' => $plugins_list[$plugin]['download_link'],
                ];

                $this->jltwp_adminify_addons_upgrade_plugin($params);
            }
            
            return rest_ensure_response(['message' => 'Addons installed', 'addons' => $addons]);
        }

        function jltwp_adminify_activate_plugin_by_slug($slug) {
            $plugin_path = WP_PLUGIN_DIR . '/' . $slug;

            if (!is_dir($plugin_path)) {
                return;
            }

            $plugin_files = glob("$plugin_path/*.php");
            if (!$plugin_files || empty($plugin_files)) {
                return;
            }

            $main_plugin_file = basename($plugin_files[0]);
            $plugin_relative_path = $slug . '/' . $main_plugin_file;

            if (is_plugin_active($plugin_relative_path)) {
                return;
            }

            activate_plugin($plugin_relative_path);
        }

        public function maybe_replace_addons_path() {

            $addons = [
                'sidebar-generator/adminify-sidebar-generator.php' => 'adminify-sidebar-generator/adminify-sidebar-generator.php'
            ];

            foreach ($addons as $old_plugin => $new_plugin) {

                $old_plugin_path = WP_PLUGIN_DIR . '/' . $old_plugin;
                $new_plugin_path = WP_PLUGIN_DIR . '/' . $new_plugin;

                // Both files exist, delete the old one
                if ( file_exists($old_plugin_path) && file_exists($new_plugin_path) ) {
                    unlink(dirname($old_plugin_path));
                    continue;
                }

                // If the old file exists and the new file doesn't exist, rename the old file to the new file
                if ( file_exists($old_plugin_path) && !file_exists($new_plugin_path) ) {

                    // check if the old plugin is active
                    include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

                    $is_active = is_plugin_active( $old_plugin );

                    if ( $is_active ) {
                        // Deactivate the old plugin
                        deactivate_plugins( $old_plugin );
                        // Rename the old plugin to the new plugin
                        rename( dirname($old_plugin_path), dirname($new_plugin_path) );

                        if ( file_exists($new_plugin_path) ) {
                            // Clear the plugin cache
                            wp_cache_delete( 'plugins', 'plugins' );

                            // Activate the new plugin
                            activate_plugin( $new_plugin );
                        }

                    } else {
                        // Rename the old plugin to the new plugin
                        rename( dirname($old_plugin_path), dirname($new_plugin_path) );
                    }
                }

            }

        }

        /**
         * Includes
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function includes()
        {
            // if (!function_exists('install_plugin_install_status')) {
            // require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
            require_once(ABSPATH . '/wp-load.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/misc.php');
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
            require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
            // }
        }

        /**
         * Menu Items
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function menu_items()
        {
            return array();
        }

        /**
         * Plugins list
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function plugins_list()
        {
            return array();
        }

        /**
         * Admin submenu
         */
        public function admin_menu()
        {
        }

        /**
         * Render addons plugins body
         */
        public function render_addons_plugins()
        {
?>
            <div class='wp-adminify-addons-wrapper'>
                <?php $this->header(); ?>
                <?php $this->body(); ?>
            </div>
        <?php
        }


        /**
         * Addons License Header Check
         *
         * @return void
         */

        public function jltwp_adminify_addons_check()
        {

            $license = jltwp_adminify()->_get_license();

            if (!is_object($license) || !$license->is_valid() || !$license->is_active()) return;

            if ( $this->is_eligible_for_coupon() ) {
                // Get the coupon
                $coupon = $this->maybe_create_and_get_coupon();
                if (!empty($coupon) && !empty($coupon['code'])) {
                    echo sprintf(
                        __('<h3>Coupon Code: <strong style="color: red">%s</strong> Redeem this coupon code to get free access to all our premium addons (Except Admin Bar Editor, RoleMaster Suite and Master Addons). Learn how to <a href="https://wpadminify.com/redeem-addons-using-coupon-code/" target="_blank">redeem coupon code?</a></h3> ', 'adminify'),
                        esc_attr($coupon['code'])
                    );
                }
            }

            echo '<style>
			#fs_addons .fs-cards-list{ display: flex; }
			#fs_addons .fs-cards-list .fs-card .fs-inner .fs-cta .button{
				top: 112px;
				right: 12px;
				line-height: 26px !important;
				border-radius: 3px !important;
			}</style>';
        }

        public function is_eligible_for_coupon() {

            $is_eligible = get_option('wp_adminify_addon__is_eligible_for_coupon', null);

            if ( $is_eligible !== null ) return wp_validate_boolean($is_eligible);
            $args = [
                'license' => base64_encode(json_encode(jltwp_adminify()->_get_license())),
                'action' => 'check_eligibility'
            ];

            $request_uri = add_query_arg($args, $this->server_url);

            $response = wp_remote_get($request_uri);

            if (!is_wp_error($response) && $response['response']['code'] === 200) {
                $file_contents = wp_remote_retrieve_body($response);
                $is_eligible = json_decode($file_contents, true);
                update_option('wp_adminify_addon__is_eligible_for_coupon', wp_validate_boolean($is_eligible));
                return $is_eligible;
            }

            return false;
        }

        public function maybe_delete_corrupted_coupon(){
            $coupon_delete_check = get_option('wp_adminify_addon__coupon_is_deleted', false);
            if($coupon_delete_check != true){
                delete_option('wp_adminify_addon__coupon');
                update_option('wp_adminify_addon__coupon_is_deleted', true);
            }
        }

        public function maybe_create_and_get_coupon()
        {
            $this->maybe_delete_corrupted_coupon();
            $coupon = get_option('wp_adminify_addon__coupon');

            if (!empty($coupon)) return $coupon;

            // communicate hit hserver get coupon
            $args = [
                'license' => base64_encode(json_encode(jltwp_adminify()->_get_license())),
                'action' => 'get_coupon'
            ];

            $response = wp_remote_get(add_query_arg($args, $this->server_url));

            if (!is_wp_error($response) && $response['response']['code'] === 200) {

                $file_contents = wp_remote_retrieve_body($response);
                $response_data = json_decode($file_contents, true);

                if (!empty($response_data) && is_array($response_data) && !empty($response_data['id']) && !empty($response_data['code']) ) {
                    $coupon = [
                        'id'   => $response_data['id'],
                        'code' => $response_data['code']
                    ];
                    update_option('wp_adminify_addon__coupon', $coupon);
                }
            }

            return $coupon;
        }


        /**
         * Header
         */
        public function header()
        {
        ?>
            <div class='wp-adminify-addons-header'>
                <div class='wp-adminify-addons-title'>
                    <h2>
                        <?php echo esc_html__('Add Ons for WP Adminify', 'adminify'); ?>
                    </h2>
                    <?php $this->jltwp_adminify_addons_check(); ?>
                </div>
                <div class='wp-adminify-addons-menu'>
                    <div class="wp-filter">
                        <ul class="filter-links">
                            <?php
                            $i = 0;

                            foreach ($this->menu_items as $menu) {
                                $class = str_replace(' ', '-', strtolower($menu['key']));
                            ?>
                                <li class="plugin-install-<?php echo esc_attr($class); ?>">
                                    <a href="#" class="<?php echo esc_attr(0 === $i ? 'current' : ''); ?>" data-type="<?php echo esc_attr($menu['key']); ?>"><?php echo esc_html($menu['label']); ?></a>
                                </li>
                            <?php
                                ++$i;
                            }
                            ?>
                        </ul>

                        <form class="search-form wp-adminify-search-plugins mr-0" method="get">
                            <input type="hidden" name="tab" value="search">
                            <label class="screen-reader-text" for="search-plugins">
                                <?php echo esc_html__('Search Plugins', 'adminify'); ?>
                            </label>
                            <input type="search" name="s" id="search-plugins" value="" class="wp-filter-search" placeholder="<?php echo esc_html__('Search plugins...', 'adminify'); ?>">
                            <input type="submit" id="search-submit" class="button hide-if-js" value="<?php echo esc_html__('Search Plugins', 'adminify'); ?>">
                        </form>
                    </div>
                </div>
            </div>
        <?php
        }

        /**
         * Body
         */
        public function body()
        {
        ?>
            <div class="wp-list-table widefat plugin-install">
                <div id="the-list">
                    <?php
                    $this->plugins();
                    ?>
                </div>
            </div>
            <?php
        }

        /**
         * Body
         */
        public function plugins()
        {

            foreach ($this->plugins_list as $key => $plugin) {
                $install_status = \install_plugin_install_status($plugin);
                $classes        = implode(' ', $plugin['type']);

                $more_details = self_admin_url(
                    'plugin-install.php?tab=plugin-information&amp;plugin=' . esc_attr($plugin['slug']) .
                        '&amp;TB_iframe=true&amp;width=600&amp;height=550'
                );

                ?>
                    <div class="plugin-card plugin-card-<?php echo esc_attr($key); ?> <?php echo esc_attr($classes); ?>">
                        <div class="plugin-card-top">
                            <div class="name column-name">
                                <h3>
                                    <a href="<?php echo esc_url($more_details); ?>" class="thickbox open-plugin-details-modal">
                                        <?php echo esc_html($plugin['name']); ?>
                                        <img src="<?php echo esc_url($plugin['icon']); ?>" class="plugin-icon" alt="">
                                    </a>
                                </h3>
                            </div>
                            <div class="desc column-description">
                                <p><?php echo wp_kses_post($plugin['short_description']); ?></p>
                            </div>
                            <!-- Hover Popup -->
                             <?php if( !empty($plugin['pricing_url']) || !empty($plugin['view_details']) ) { ?>
                                <div class="adminify-plugin-details">
                                    <?php if( !empty($plugin['view_details']) ) { ?>
                                        <a href="<?php echo esc_url($plugin['view_details']); ?>" target="_blank" class="adminify-view-details"><?php echo esc_html__('View Details', 'adminify'); ?></a>
                                    <?php } ?>

                                    <?php if( !empty($plugin['pricing_url']) ) { ?>
                                        <a href="<?php echo esc_url($plugin['pricing_url']); ?>" target="_blank"><?php echo esc_html__('Buy Now', 'adminify'); ?></a>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="plugin-card-bottom">
                            <div class="column-downloaded">
                                <span class="plugin-status">
                                    <?php
                                    echo esc_html__('Status:', 'adminify');

                                    if ('install' === $install_status['status']) {
                                    ?>
                                        <span class="plugin-status-not-install" data-plugin-url="<?php echo esc_attr($plugin['download_link']); ?>"><?php echo esc_html__('No Installed', 'adminify'); ?></span>
                                        <?php
                                    } elseif ('update_available' === $install_status['status']) {
                                        if (is_plugin_active($install_status['file'])) {
                                        ?>
                                            <span class="plugin-status-active">
                                                <?php echo esc_html__('Active', 'adminify'); ?>
                                            </span>
                                        <?php
                                        } else {
                                        ?>
                                            <span class="plugin-status-inactive" data-plugin-file="<?php echo esc_attr(esc_attr($install_status['file'])); ?>">
                                                <?php echo esc_html__('Inactive', 'adminify'); ?>
                                            </span>
                                        <?php
                                        }
                                    } elseif (('latest_installed' === $install_status['status']) || ('newer_installed' === $install_status['status'])) {
                                        if (is_plugin_active($install_status['file'])) {
                                        ?>
                                            <span class="plugin-status-active">
                                                <?php echo esc_html__('Active', 'adminify'); ?>
                                            </span>
                                        <?php
                                        } elseif (current_user_can('activate_plugin', $install_status['file'])) {
                                        ?>
                                            <span class="plugin-status-inactive" data-plugin-file="<?php echo esc_attr($install_status['file']); ?>">
                                                <?php echo esc_html__('Inactive', 'adminify'); ?>
                                            </span>
                                        <?php
                                        } else {
                                        ?>
                                            <span class="plugin-status-inactive" data-plugin-file="<?php echo esc_attr($install_status['file']); ?>">
                                                <?php echo esc_html__('Inactive', 'adminify'); ?>
                                            </span>
                                    <?php
                                        }
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="column-compatibility">
                                <ul class="plugin-action-buttons">
                                    <?php
                                    if ('install' === $install_status['status']) {
                                    ?>
                                        <li>
                                            <button class="install-now adminify-btn adminify-btn-outline-primary" data-install-url="<?php echo esc_attr($plugin['download_link']); ?>">
                                                <?php echo esc_html__('Install Now', 'adminify'); ?>
                                            </button>
                                        </li>
                                    <?php
                                    } elseif ('update_available' === $install_status['status']) {
                                    ?>
                                        <li class="mr-0">
                                            <button class="update-now button" data-plugin="<?php echo esc_attr($install_status['file']); ?>" data-slug="<?php echo esc_attr($plugin['slug']); ?>" data-update-url="<?php echo esc_attr($install_status['url']); ?>">
                                                <?php echo esc_html__('Update Now', 'adminify'); ?>
                                            </button>
                                        </li>
                                        <?php
                                    } elseif (('latest_installed' === $install_status['status']) || ('newer_installed' === $install_status['status'])) {
                                        if (is_plugin_active($install_status['file'])) {
                                        ?>
                                            <li class="mr-0">
                                                <button type="button" class="adminify-btn adminify-btn-success" disabled="disabled">
                                                    <?php echo esc_html__('Activated', 'adminify'); ?>
                                                </button>
                                            </li>
                                        <?php
                                        } elseif (current_user_can('activate_plugin', $install_status['file'])) {
                                        ?>
                                            <button class="button activate-now" data-plugin-file="<?php echo esc_attr($install_status['file']); ?>">
                                                <?php echo esc_html__('Activate Now', 'adminify'); ?>
                                            </button>
                                        <?php
                                        } else {
                                        ?>
                                            <li class="mr-0">
                                                <button type="button" class="button button-disabled" disabled="disabled">
                                                    <?php echo esc_html__('Installed', 'adminify'); ?>
                                                </button>
                                            </li>
                                    <?php
                                        }
                                    }
                                    ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php
            }
        }

        /**
         * Activate Plugins
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function jltwp_adminify_addons_activate_plugin()
        {
            if (empty($_POST['plugin'])) {
                return;
            }
            try {
                $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

                if (!wp_verify_nonce($nonce, 'jltwp_adminify_addons_nonce')) {
                    wp_send_json_error(array('mess' => __('Nonce is invalid', 'adminify')));
                }

                // if ((is_multisite() && !is_network_admin()) || !current_user_can('install_plugins')) {
                //     wp_send_json_error(array('mess' => __('Invalid access', 'adminify')));
                // }

                $plugin = sanitize_text_field(wp_unslash($_POST['plugin']));
                $plugin_links = array_values(wp_list_pluck($this->plugins_list, 'slug'));

                if (!in_array(dirname($plugin), $plugin_links)) {
                    wp_send_json_error(array('mess' => __('Invalid plugin', 'adminify')));
                }

                $result = activate_plugin($plugin);

                if (is_wp_error($result)) {
                    wp_send_json_error(
                        array(
                            'mess' => $result->get_error_message(),
                        )
                    );
                }
                wp_send_json_success(
                    array(
                        'mess' => __('Activate success', 'adminify'),
                    )
                );
            } catch (\Exception $ex) {
                wp_send_json_error(
                    array(
                        'mess' => __('Error exception.', 'adminify'),
                        array(
                            'error' => $ex,
                        ),
                    )
                );
            } catch (\Error $ex) {
                wp_send_json_error(
                    array(
                        'mess' => __('Error.', 'adminify'),
                        array(
                            'error' => $ex,
                        ),
                    )
                );
            }
        }

        public function get_the_plugin_slug( $plugin ) {

            // If the plugin is like myplugin/myplugin.php
            if ( ! filter_var($plugin, FILTER_VALIDATE_URL) ) {
                return dirname($plugin);
            }

            // If the plugin is from wordpress.org
            if ( false !== strpos( $plugin, 'https://downloads.wordpress.org/plugin/' ) ) {
                $plugin = str_replace( 'https://downloads.wordpress.org/plugin/', '', $plugin );
                return str_replace( '.zip', '', $plugin );
            }

            // If the plugin is from local store
            $plugin = explode( 'plugin_slug=', $plugin );
            $plugin = explode( '&', $plugin[1] );
            return $plugin[0];
        }

        /**
         * Upgrade Plugins required Libraries
         *
         * @author Jewel Theme <support@jeweltheme.com>
         */
        public function jltwp_adminify_addons_upgrade_plugin( $params = null )
        {
            if ($params == null && empty($_POST['plugin'])) {
                return;
            }

            try {
                require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
                require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';

                if($params == null){
                    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    
                    if (!wp_verify_nonce($nonce, 'jltwp_adminify_addons_nonce')) {
                        wp_send_json_error(array('mess' => __('Nonce is invalid', 'adminify')));
                    }
                    $plugin = sanitize_text_field(wp_unslash($_POST['plugin']));
                }else{
                    $plugin =  $params['plugin'];
                }

                // if ((is_multisite() && !is_network_admin()) || !current_user_can('install_plugins')) {
                //     wp_send_json_error(array('mess' => __('Invalid access', 'adminify')));
                // }
                
                $plugin_slug = $this->get_the_plugin_slug( $plugin );

                if ( ! array_key_exists( $plugin_slug, $this->plugins_list) ) {
                    wp_send_json_error(array('mess' => __('Invalid plugin', 'adminify')));
                }
                
                if($params == null){
                    $type     = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : 'install';
                }else{
                    $type     = 'install';
                }
                $skin     = new \WP_Ajax_Upgrader_Skin();
                $upgrader = new \Plugin_Upgrader($skin);

                if ('install' === $type) {

                    $result = $upgrader->install($plugin);
                    if ($params == null){
                        if (empty($result) || empty($upgrader->result)) {
                            wp_send_json_error(
                                array(
                                    'mess' => 'Something is wrong',
                                )
                            );
                        }

                        if (is_wp_error($result)) {
                            wp_send_json_error(
                                array(
                                    'mess' => $result->get_error_message(),
                                )
                            );
                        }
                    }else{
                        if(empty($result) || empty($upgrader->result)){
                            return;
                        }
                    }

                    $plugins = get_plugins('/' . $upgrader->result['destination_name']);
                    $plugin_data = end($plugins);
                    $plugin_data['slug'] = $upgrader->result['destination_name'];
                    $plugin_data['version'] = $plugin_data['Version'];

                    if (!empty($plugin_data) && !is_wp_error($plugin_data)) {

                        $install_status = \install_plugin_install_status($plugin_data);

                        $active_plugin  = activate_plugin($install_status['file']);
                        
                        if ($params == null){
                            if (is_wp_error($active_plugin)) {
                                wp_send_json_error(
                                    array(
                                        'mess' => $active_plugin->get_error_message(),
                                    )
                                );
                            } else {
                                wp_send_json_success(
                                    array(
                                        'mess' => __('Install success', 'adminify'),
                                    )
                                );
                            }
                        }
                    } else {
                        if ($params == null){
                            wp_send_json_error(
                                array(
                                    'mess' => 'Error',
                                )
                            );

                        }
                    }
                } else {

                    $is_active = is_plugin_active($plugin);
                    $result = $upgrader->upgrade($plugin);

                    if ($params == null){
                        if ( empty($result) || is_wp_error($result) ) {
                            wp_send_json_error(
                                array(
                                    'mess' => is_wp_error($result) ? $result->get_error_message() : __('Couldn\'t upgrade', 'adminify')
                                )
                            );
                        }
                    }

                    $active_status = activate_plugin($plugin);

                    if ($params == null){
                        if ( empty($active_status) || is_wp_error($active_status) ) {
                            wp_send_json_error(
                                array(
                                    'mess' => is_wp_error($result) ? $result->get_error_message() : __('Activation Failed', 'adminify')
                                )
                            );
                        }

                        wp_send_json_success(
                            array(
                                'mess'   => __('Update success', 'adminify'),
                                'active' => true,
                            )
                        );
                    }
                }
                
            } catch (\Exception $ex) {
                if ($params == null){
                    wp_send_json_error(
                        array(
                            'mess' => __('Error exception.', 'adminify'),
                            array(
                                'error' => $ex,
                            ),
                        )
                    );
                }
            }
        }
        
    }
}
