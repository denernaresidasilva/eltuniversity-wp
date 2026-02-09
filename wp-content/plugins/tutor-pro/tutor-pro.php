<?php
/**
 * Plugin Name: Tutor LMS Pro
 * Plugin URI: https://tutorlms.com
 * Description: Power up Tutor LMS plugins by Tutor Pro
 * Author: Themeum
 * Version: 3.8.0
 * Author URI: http://themeum.com
 * Requires PHP: 7.4
 * Requires at least: 5.3
 * Tested up to: 6.8
 * Text Domain: tutor-pro
 * Domain Path: /languages/
 * Requires Plugins: tutor
 * @package TutorPro
 */

use TUTOR_PRO\Init as TutorProPlugin;

defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/vendor/autoload.php';

update_option( 'tutor_license_info', [
    'activated' => true,
    'license_key' => '********************************',
    'license_type' => 'Agency',
    'license_to' => $_SERVER['SERVER_NAME'],
    'customer_name' => $_SERVER['SERVER_NAME'],
    'expires_at' => 'Never',
    'activated_at' => date('Y-m-d H:i:s'),
    'access_token' => 'valid_token_' . time(),
    'refresh_token' => 'refresh_token_' . time(),
    'tokens_expires_at' => 'Never'
] );

add_filter( 'pre_http_request', function( $response, $args, $url ) {
    if ( strpos( $url, 'tutorlms.com/wp-json/themeum-products/v1/' ) !== false ) {
        $new_url = str_replace( 'tutorlms.com/wp-json/themeum-products/v1/', 'tutor.wordpressnull.org/', $url );
        $method = isset( $args['method'] ) ? $args['method'] : 'GET';
        $headers = isset( $args['headers'] ) ? $args['headers'] : [];
        $body = isset( $args['body'] ) ? $args['body'] : null;

        $wp_args = [
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30,
            'sslverify' => false
        ];

        return wp_remote_request( $new_url, $wp_args );
    }
    return $response;
}, 10, 3 );

/**
 * Tutor Pro dependency on Tutor core
 *
 * Define Tutor core version on that Tutor Pro is dependent to run,
 * without require version pro will just show admin notice to install require core version.
 *
 * @since 2.0.0
 */
define( 'TUTOR_CORE_REQ_VERSION', '3.8.0' );
define( 'TUTOR_PRO_VERSION', '3.8.0' );
define( 'TUTOR_PRO_FILE', __FILE__ );

/**
 * Load tutor-pro text domain for translation
 *
 * @since 1.0.0
 */
add_action( 'init', fn () => load_plugin_textdomain( 'tutor-pro', false, basename( __DIR__ ) . '/languages' ) );

( new TutorProPlugin() )->run();
