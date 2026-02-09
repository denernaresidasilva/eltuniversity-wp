<?php
/**
 * FluentCRM Integration
 *
 * @package MagicLogin
 */

namespace MagicLogin\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * FluentCRM Integration
 */
class FluentCRM {

	/**
	 * Initialize FluentCRM integration.
	 *
	 * @return void
	 */
	public static function setup() {
		if ( ! defined( 'FLUENTCRM' ) ) {
			return; // Exit if FluentCRM is not active
		}

		add_action( 'fluent_crm/after_init', [ __CLASS__, 'register_smart_code' ] );
	}

	/**
	 * Register Magic Login smart code for FluentCRM.
	 *
	 * @return void
	 */
	public static function register_smart_code() {
		FluentCrmApi( 'extender' )->addSmartCode(
			'magic_login',
			__( 'Magic Login', 'magic-login' ),
			[
				'magic_link'     => __( 'Magic Login Link', 'magic-login' ),
				'magic_login_qr' => __( 'Magic Login QR Code', 'magic-login' ),
			],
			[ __CLASS__, 'generate_magic_link' ]
		);
	}

	/**
	 * Generate Magic Login link for FluentCRM subscribers.
	 *
	 * @param string $code          Smart code key.
	 * @param string $value_key     The requested key (magic_link). // phpcs:ignore
	 * @param string $default_value Default return value if user not found.
	 * @param object $subscriber    FluentCRM subscriber object.
	 *
	 * @return string
	 */
	public static function generate_magic_link( $code, $value_key, $default_value, $subscriber ) {
		$allowed_keys = [ 'magic_link', 'magic_login_qr' ];

		if ( in_array( $value_key, $allowed_keys, true ) && ! empty( $subscriber->email ) ) {
			$user = get_user_by( 'email', $subscriber->email );
			if ( $user ) {
				/**
				 * Fires before creating Magic Login link for FluentCRM subscriber.
				 *
				 * @hook  magic_login_fluent_crm_before_create_login_link
				 *
				 * @param object $user  WP_User object.
				 * @param string $email User email.
				 *
				 * @since 2.4
				 */
				do_action( 'magic_login_fluent_crm_before_create_login_link', $user, $subscriber->email );
				$login_link = \MagicLogin\Utils\create_login_link( $user );

				/**
				 * Filter the Magic Login link for FluentCRM subscriber.
				 *
				 * @hook   magic_login_fluent_crm_create_login_link
				 *
				 * @param string $login_link Magic Login link.
				 * @param object $user       WP_User object.
				 * @param string $email      User email.
				 *
				 * @return string Magic Login link.
				 * @since  2.4
				 */
				$login_link = apply_filters( 'magic_login_fluent_crm_create_login_link', $login_link, $user, $subscriber->email );

				if ( 'magic_link' === $value_key ) {
					return $login_link;
				}

				if ( 'magic_login_qr' === $value_key ) {
					return \MagicLogin\QR::get_img_tag( $login_link );
				}

				return $login_link;
			}
		}

		return $default_value; // Return default if user not found
	}
}
