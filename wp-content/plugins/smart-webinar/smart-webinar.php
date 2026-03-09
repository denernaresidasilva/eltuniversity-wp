<?php
/**
 * Plugin Name: Smart Webinar Automation
 * Plugin URI:  https://eltuniversity.com
 * Description: Plataforma completa de webinar evergreen e live com automação comportamental e integração WhatsApp.
 * Version:     1.0.0
 * Author:      ELT University
 * Author URI:  https://eltuniversity.com
 * Text Domain: smart-webinar
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SMART_WEBINAR_VERSION', '1.0.0' );
define( 'SMART_WEBINAR_PATH',    plugin_dir_path( __FILE__ ) );
define( 'SMART_WEBINAR_URL',     plugin_dir_url( __FILE__ ) );
define( 'SMART_WEBINAR_FILE',    __FILE__ );

// Autoload classes
spl_autoload_register( function ( $class ) {
    $prefix = 'SmartWebinar\\';
    $len    = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        return;
    }
    $relative = substr( $class, $len );
    $file     = SMART_WEBINAR_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';
    if ( file_exists( $file ) ) {
        require $file;
    }
} );

require_once SMART_WEBINAR_PATH . 'includes/Installer.php';
register_activation_hook(   __FILE__, [ 'SmartWebinar\\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'SmartWebinar\\Installer', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'SmartWebinar\\Loader', 'init' ] );
