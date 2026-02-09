<?php
/**
 * Security functionality
 *
 * @package MagicLogin
 */

namespace MagicLogin\Security;

// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment
use const MagicLogin\Constants\DISABLE_USER_META;
use function MagicLogin\Utils\get_client_ip;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	add_filter( 'magic_login_pre_send_login_link', __NAMESPACE__ . '\\check_brute_force', 5 );
	add_filter( 'magic_login_pre_send_login_link', __NAMESPACE__ . '\\maybe_throttle_request', 7 );
	add_filter( 'magic_login_pre_send_login_link', __NAMESPACE__ . '\\maybe_check_domain', 10, 2 );
	add_filter( 'magic_login_pre_send_login_link', __NAMESPACE__ . '\\maybe_disabled_for_user', 12, 2 );
	add_filter( 'magic_login_pre_code_login', __NAMESPACE__ . '\\maybe_disabled_for_user', 12, 2 );
	add_filter( 'magic_login_pre_process_code_login_request', __NAMESPACE__ . '\\check_brute_force', 5 );
	add_filter( 'magic_login_pre_process_code_login_request', __NAMESPACE__ . '\\maybe_throttle_request', 7 );
	add_action( 'magic_login_send_login_sms', __NAMESPACE__ . '\\set_throttle_observer' );
	add_action( 'magic_login_send_login_link', __NAMESPACE__ . '\\set_throttle_observer' );
	add_action( 'magic_login_invalid_code', __NAMESPACE__ . '\\brute_force_observer' );
	add_action( 'magic_login_invalid_user', __NAMESPACE__ . '\\brute_force_observer' );
	add_action( 'magic_login_invalid_token', __NAMESPACE__ . '\\brute_force_observer' );
	add_action( 'magic_login_invalid_ip', __NAMESPACE__ . '\\brute_force_observer' );
	add_action( 'magic_login_handle_login_request', __NAMESPACE__ . '\\maybe_block_brute_force' );
	add_action( 'magic_login_before_login', __NAMESPACE__ . '\\maybe_ip_check', 10, 2 );
	add_filter( 'magic_login_add_auto_login_link', __NAMESPACE__ . '\\maybe_disable_auto_login_link_for_user', 10, 3 );
	add_filter( 'magic_login_replace_magic_link_in_wp_mail', __NAMESPACE__ . '\\maybe_disable_auto_login_link_for_user', 10, 3 );
}

/**
 * Domain restriction checks
 *
 * @param null   $null default value for shortcircut
 * @param object $user \WP_User object
 *
 * @return \WP_Error|null
 */
function maybe_check_domain( $null, $user ) {
	if ( ! is_null( $null ) ) {
		return $null;
	}

	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_domain_restriction'] ) {
		return null;
	}

	if ( ! is_a( $user, '\WP_User' ) ) {
		return null;
	}

	$allowed_domains = $settings['allowed_domains'];
	$allowed_domains = preg_split( '/[\r\n]+/', $allowed_domains, - 1, PREG_SPLIT_NO_EMPTY );

	/**
	 * Filter allowed domains for login
	 *
	 * @hook   magic_login_allowed_domains
	 *
	 * @param  array $allowed_domains Allowed domains
	 *
	 * @return array Allowed domains
	 * @since  2.5
	 */
	$allowed_domains = apply_filters( 'magic_login_allowed_domains', $allowed_domains );

	if ( ! empty( $allowed_domains ) ) {
		$email_info      = explode( '@', $user->user_email );
		$domain          = strtolower( array_pop( $email_info ) ); // Normalize to lowercase
		$allowed_domains = array_map( 'strtolower', $allowed_domains ); // Normalize allowed domains to lowercase

		if ( ! in_array( $domain, $allowed_domains, true ) ) {
			return new \WP_Error( 'magic_login_disallowed_domain', esc_html__( 'Magic login is not allowed for your domain!', 'magic-login' ) );
		}
	}

	return null;
}

/**
 * Maybe throttle login request
 *
 * @param \WP_Error|null $result current result, null default
 *
 * @return \WP_Error|null $result Result of filtered value, null if not throttled
 */
function maybe_throttle_request( $result ) {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_login_throttling'] ) {
		return $result;
	}

	$ip_hash       = sha1( get_client_ip() );
	$transient_key = 'magic_login_throttle_' . $ip_hash;
	$value         = get_site_transient( $transient_key );

	if ( ! is_array( $value ) ) {
		return $result;
	}

	if ( isset( $value['count'] ) ) {
		if ( absint( $value['count'] ) >= $settings['login_throttling_limit'] ) {
			return new \WP_Error( 'magic_login_throttling_limit', esc_html__( 'You have created too many login requests! Please check your emails including your spam box to see one of the login links that we have already sent.', 'magic-login' ) );
		}
	}

	return $result;
}

/**
 * Observe login link requests for throttling
 */
function set_throttle_observer() {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_login_throttling'] ) {
		return;
	}

	$ttl           = absint( $settings['login_throttling_time'] ) * MINUTE_IN_SECONDS;
	$ip_hash       = sha1( get_client_ip() );
	$transient_key = 'magic_login_throttle_' . $ip_hash;

	$value = get_site_transient( $transient_key );
	if ( ! is_array( $value ) ) {
		$value = [
			'created_at' => time(),
			'count'      => 1,
		];
		set_site_transient( $transient_key, $value, $ttl );

		return;
	}

	if ( isset( $value['created_at'] ) ) {
		$ttl = $ttl - ( time() - absint( $value['created_at'] ) );
	}

	$value['count'] ++; // increase

	set_site_transient( $transient_key, $value, $ttl );
}

/**
 * Check brute force request
 *
 * @param \WP_Error|null $result current result, null default
 *
 * @return \WP_Error|null $result Result of filtered value, null if not throttled
 */
function check_brute_force( $result ) {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_brute_force_protection'] ) {
		return $result;
	}

	$ip_hash       = sha1( get_client_ip() );
	$transient_key = 'magic_login_bf_block_' . $ip_hash;
	$value         = get_site_transient( $transient_key );

	if ( false !== $value ) {
		$err_msg = esc_html__( 'Too many failed login attempts. Please try again later.', 'magic-login' );

		if ( isset( $value['blocked_at'] ) ) {
			$time_diff = human_time_diff( time(), ( absint( $value['blocked_at'] ) + $settings['brute_force_bantime'] * MINUTE_IN_SECONDS ) );
			$err_msg   = sprintf( esc_html__( 'Too many failed login attempts. Please try again in %s.', 'magic-login' ), $time_diff );
		}

		return new \WP_Error( 'magic_login_brute_force_block', $err_msg );
	}

	return $result;
}

/**
 * Observe login link requests for throttling
 */
function brute_force_observer() {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_brute_force_protection'] ) {
		return;
	}

	$ttl           = absint( $settings['brute_force_login_time'] ) * MINUTE_IN_SECONDS;
	$ip_hash       = sha1( get_client_ip() );
	$transient_key = 'magic_login_bf_' . $ip_hash;

	$value = get_site_transient( $transient_key );
	if ( ! is_array( $value ) ) {
		$value = [
			'created_at' => time(),
			'count'      => 1,
		];
		set_site_transient( $transient_key, $value, $ttl );

		return;
	}

	if ( isset( $value['created_at'] ) ) {
		$ttl = $ttl - ( time() - absint( $value['created_at'] ) );
	}

	$value['count'] ++; // increase

	set_site_transient( $transient_key, $value, $ttl );

	if ( absint( $value['count'] ) >= absint( $settings['brute_force_login_attempt'] ) ) {
		$block_key  = 'magic_login_bf_block_' . $ip_hash;
		$block_info = [
			'blocked_at' => time(),
			'ip_hash'    => $ip_hash,
		];
		set_site_transient( $block_key, $block_info, $settings['brute_force_bantime'] * MINUTE_IN_SECONDS );
	}
}


/**
 * Maybe block brute force requests
 */
function maybe_block_brute_force() {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_brute_force_protection'] ) {
		return;
	}

	$ip_hash       = sha1( get_client_ip() );
	$transient_key = 'magic_login_bf_block_' . $ip_hash;
	if ( false !== get_site_transient( $transient_key ) ) {
		$error = sprintf( __( 'Too many failed login attempts. <a href="%s">Try signing in instead</a>?', 'magic-login' ), wp_login_url() );
		wp_die( wp_kses_post( $error ) );
	}
}


/**
 * Check client requested/login IP address.
 * Block login if it mismatch
 *
 * @param object $user       User object
 * @param array  $token_info token details
 */
function maybe_ip_check( $user, $token_info ) {
	$settings = \MagicLogin\Utils\get_settings();
	if ( ! $settings['enable_ip_check'] ) {
		return;
	}

	if ( $token_info && isset( $token_info['ip_hash'] ) && 'cli' !== $token_info['ip_hash'] ) {
		$ip_hash = sha1( get_client_ip() );
		if ( $ip_hash !== $token_info['ip_hash'] ) {
			/**
			 * Fires when the IP address is invalid
			 *
			 * @hook  magic_login_invalid_ip
			 */
			do_action( 'magic_login_invalid_ip' );
			// dont sniff data
			$error = esc_html__( 'Unauthorized request!', 'magic-login' );
			wp_die( $error ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}

/**
 * User magic login check
 *
 * @param null   $null default value for shortcircut
 * @param object $user \WP_User object
 *
 * @return \WP_Error|null
 * @since 1.4
 */
function maybe_disabled_for_user( $null, $user ) {
	if ( ! is_a( $user, '\WP_User' ) ) {
		return $null;
	}

	$disabled = (bool) get_user_meta( $user->ID, DISABLE_USER_META, true );

	if ( $disabled ) {
		return new \WP_Error( 'magic_login_disabled_user', esc_html__( 'You cannot login via magic login!', 'magic-login' ) );
	}

	return $null;
}

/**
 * Dont integrate auto login links with the users who disabled magic login
 *
 * @param bool     $status true by default
 * @param array    $atts   wp_mail arguments
 * @param \WP_User $user   user object
 *
 * @return false|mixed
 * @since 1.6
 */
function maybe_disable_auto_login_link_for_user( $status, $atts, $user ) {
	if ( ! is_a( $user, '\WP_User' ) ) {
		return $status;
	}

	$disabled = (bool) get_user_meta( $user->ID, DISABLE_USER_META, true );
	if ( $disabled ) {
		return false;
	}

	return $status;
}
