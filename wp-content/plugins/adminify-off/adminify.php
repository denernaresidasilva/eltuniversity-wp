<?php

/**
 * Plugin Name: WP Adminify
 * Description: WP Adminify is a powerful plugin that modernizes and customizes your WordPress admin dashboard. It offers a clean, branded interface and advanced menu management features to enhance your admin user experience.
 * Plugin URI: https://wpadminify.com
 * Author: Jewel Theme
 * Version: 4.0.4.9
 * Author URI: https://wpadminify.com
 * License:     GPLv3 or later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: adminify
 * Domain Path: /languages
 *
 */
// No, Direct access Sir !!!
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$jltwp_adminify_plugin_data = get_file_data( __FILE__, array(
    'Version'     => 'Version',
    'Plugin Name' => 'Plugin Name',
    'Author'      => 'Author',
    'Description' => 'Description',
    'Plugin URI'  => 'Plugin URI',
), false );
if ( function_exists( 'jltwp_adminify' ) ) {
    jltwp_adminify()->set_basename( false, __FILE__ );
} elseif ( !function_exists( 'jltwp_adminify' ) ) {
    // Create a helper function for easy SDK access.
    function jltwp_adminify() {
        global $jltwp_adminify;
        if ( !isset( $jltwp_adminify ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_7829_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_7829_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once __DIR__ . '/Libs/freemius/start.php';
            $jltadminify_menu = array(
                'slug'        => 'wp-adminify-settings',
                'first-path'  => 'admin.php?page=wp-adminify-settings',
                'account'     => false,
                'network'     => false,
                'support'     => false,
                'contact'     => false,
                'affiliation' => false,
                'pricing'     => true,
                'addons'      => false,
            );
            // WP Adminify
            $jltwp_adminify = fs_dynamic_init( array(
                'id'                  => '7829',
                'slug'                => 'adminify',
                'premium_slug'        => 'adminify-pro',
                'type'                => 'plugin',
                'public_key'          => 'pk_a0ea61beae7126eb845f7e58a03e5',
                'premium_suffix'      => 'Premium',
                'affiliation'         => false,
                'has_addons'          => true,
                'has_paid_plans'      => true,
                'is_org_compliant'    => true,
                'parallel_activation' => array(
                    'enabled'                  => true,
                    'premium_version_basename' => 'adminify-pro/adminify.php',
                ),
                'menu'                => ( function_exists( 'get_menu_params__premium_only' ) ? get_menu_params__premium_only() : $jltadminify_menu ),
                'is_live'             => true,
                'is_premium'          => false,
            ) );
        }
        return $jltwp_adminify;
    }

    // Init Freemius.
    jltwp_adminify();
    jltwp_adminify()->add_filter( 'deactivate_on_activation', '__return_false' );
    // Signal that SDK was initiated.
    do_action( 'jltwp_adminify_loaded' );
}
if ( !defined( 'WP_ADMINIFY' ) ) {
    define( 'WP_ADMINIFY', $jltwp_adminify_plugin_data['Plugin Name'] );
}
if ( !defined( 'WP_ADMINIFY_VER' ) ) {
    define( 'WP_ADMINIFY_VER', $jltwp_adminify_plugin_data['Version'] );
}
if ( !defined( 'WP_ADMINIFY_FILE' ) ) {
    define( 'WP_ADMINIFY_FILE', __FILE__ );
}
if ( !defined( 'WP_ADMINIFY_SLUG' ) ) {
    define( 'WP_ADMINIFY_SLUG', dirname( plugin_basename( __FILE__ ) ) );
}
if ( !defined( 'WP_ADMINIFY_BASE' ) ) {
    define( 'WP_ADMINIFY_BASE', plugin_basename( __FILE__ ) );
}
if ( !defined( 'WP_ADMINIFY_PATH' ) ) {
    define( 'WP_ADMINIFY_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}
if ( !defined( 'WP_ADMINIFY_URL' ) ) {
    define( 'WP_ADMINIFY_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
}
if ( !defined( 'WP_ADMINIFY_ASSETS' ) ) {
    define( 'WP_ADMINIFY_ASSETS', WP_ADMINIFY_URL . 'assets/' );
}
if ( !defined( 'WP_ADMINIFY_ASSETS_IMAGE' ) ) {
    define( 'WP_ADMINIFY_ASSETS_IMAGE', WP_ADMINIFY_ASSETS . 'images/' );
}
if ( !defined( 'WP_ADMINIFY_ASSET_PATH' ) ) {
    define( 'WP_ADMINIFY_ASSET_PATH', wp_upload_dir()['basedir'] . '/wp-adminify' );
}
if ( !defined( 'WP_ADMINIFY_ASSET_URL' ) ) {
    define( 'WP_ADMINIFY_ASSET_URL', wp_upload_dir()['baseurl'] . '/wp-adminify' );
}
if ( !defined( 'WP_ADMINIFY_DESC' ) ) {
    define( 'WP_ADMINIFY_DESC', $jltwp_adminify_plugin_data['Description'] );
}
if ( !defined( 'WP_ADMINIFY_AUTHOR' ) ) {
    define( 'WP_ADMINIFY_AUTHOR', $jltwp_adminify_plugin_data['Author'] );
}
if ( !defined( 'WP_ADMINIFY_URI' ) ) {
    define( 'WP_ADMINIFY_URI', $jltwp_adminify_plugin_data['Plugin URI'] );
}
if ( !class_exists( '\\WPAdminify\\WP_Adminify' ) ) {
    // Autoload Files
    require_once __DIR__ . '/vendor/autoload.php';
    // Instantiate WP Adminify Class
    require_once __DIR__ . '/class-wp-adminify.php';
}
// Activation and Deactivation hooks
if ( class_exists( '\\WPAdminify\\WP_Adminify' ) ) {
    register_activation_hook( WP_ADMINIFY_FILE, array('\\WPAdminify\\WP_Adminify', 'jltwp_adminify_activation_hook') );
    register_deactivation_hook( WP_ADMINIFY_FILE, array('\\WPAdminify\\WP_Adminify', 'jltwp_adminify_deactivation_hook') );
}
if ( class_exists( '\\WPAdminify\\Inc\\Modules\\ActivityLogs\\Inc\\DB_Table' ) ) {
    register_activation_hook( WP_ADMINIFY_FILE, ['\\WPAdminify\\Inc\\Modules\\ActivityLogs\\Inc\\DB_Table', 'activation_hook'] );
    register_uninstall_hook( WP_ADMINIFY_FILE, ['\\WPAdminify\\Inc\\Modules\\ActivityLogs\\Inc\\DB_Table', 'deactivation_hook'] );
}