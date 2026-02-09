<?php
/**
 * Redirection functionality
 *
 * @package MagicLogin
 */

namespace MagicLogin\Redirection;

use \WP_Error as WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$settings = \MagicLogin\Utils\get_settings();
	$priority = ( $settings['enable_login_redirection'] && $settings['enforce_redirection_rules'] ) ? 9999 : 10;

	add_filter( 'magic_login_redirect', __NAMESPACE__ . '\\maybe_login_redirect', $priority, 2 );
	add_filter( 'login_redirect', __NAMESPACE__ . '\\maybe_wp_login_redirect', $priority, 3 );
}


/**
 * Get magic link redirect url after login
 *
 * @param string   $redirect_url default redirection url
 * @param \WP_User $user         User object
 *
 * @return mixed
 */
function maybe_login_redirect( $redirect_url, $user ) {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_login_redirection'] ) {
		return $redirect_url;
	}

	$user_redirect_url = get_user_redirect_url( $user );

	if ( ! empty( $user_redirect_url ) ) {
		$redirect_url = $user_redirect_url;
	}

	return $redirect_url;
}

/**
 * Maybe apply redirection to normal WP login
 *
 * @param string           $redirect_to           The redirect destination URL.
 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
 *
 * @return mixed
 */
function maybe_wp_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( is_wp_error( $user ) ) {
		return $redirect_to;
	}

	$settings = \MagicLogin\Utils\get_settings();

	if ( ! $settings['enable_login_redirection'] ) {
		return $redirect_to;
	}

	if ( ! $settings['enable_wp_login_redirection'] ) {
		return $redirect_to;
	}

	$user_redirect_url = get_user_redirect_url( $user );

	if ( ! empty( $user_redirect_url ) ) {
		$redirect_to = $user_redirect_url;
	}

	return $redirect_to;
}

/**
 * Calculate redirect url for given user
 *
 * @param \WP_User $user object
 *
 * @return string
 */
function get_user_redirect_url( $user ) {
	$redirect_to = '';
	$settings    = \MagicLogin\Utils\get_settings();
	if ( ! empty( $settings['default_redirection_url'] ) ) {
		$redirect_to = $settings['default_redirection_url'];
	}

	if ( $settings['enable_role_based_redirection'] ) {
		foreach ( $settings['role_based_redirection_rules'] as $role => $target_url ) {
			if ( empty( $target_url ) ) {
				continue;
			}

			if ( in_array( $role, $user->roles, true ) ) {
				$redirect_to = $target_url;
			}
		}
	}

	return $redirect_to;
}
