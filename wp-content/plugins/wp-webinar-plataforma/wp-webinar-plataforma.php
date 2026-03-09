<?php
/**
 * Plugin Name: Plataforma de Webinars
 * Plugin URI:  https://github.com/denernaresidasilva/eltuniversity-wp
 * Description: Plataforma completa de webinars com transmissão ao vivo, evergreen, chat, automações e analytics.
 * Version:     1.0.0
 * Author:      ELT University
 * Text Domain: wp-webinar-plataforma
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WP_WEBINAR_VERSION',     '1.0.0' );
define( 'WP_WEBINAR_FILE',        __FILE__ );
define( 'WP_WEBINAR_DIR',         plugin_dir_path( __FILE__ ) );
define( 'WP_WEBINAR_URL',         plugin_dir_url( __FILE__ ) );
define( 'WP_WEBINAR_BASENAME',    plugin_basename( __FILE__ ) );
define( 'WP_WEBINAR_DB_VERSION',  '1.0.0' );

require_once WP_WEBINAR_DIR . 'includes/Installer.php';
require_once WP_WEBINAR_DIR . 'includes/Loader.php';

register_activation_hook( __FILE__, [ 'WebinarPlataforma\\Installer', 'install' ] );

WebinarPlataforma\Loader::init();
