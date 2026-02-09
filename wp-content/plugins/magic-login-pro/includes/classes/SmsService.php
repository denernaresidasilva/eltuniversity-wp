<?php
/**
 * SMS service
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use MagicLogin\Services\SmsProviderFactory;
use MagicLogin\Utils;
use function MagicLogin\Utils\sanitize_phone_number;
use const MagicLogin\Constants\PHONE_NUMBER_META;


/**
 * SMS service
 */
class SmsService {

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Constructor
	 *
	 * @param array|null $settings Optional settings (fallback to global sms settings if null).
	 */
	public function __construct( array $settings = null ) {
		$this->settings = $settings ?? Utils\get_settings()['sms'];
	}


	/**
	 * Send SMS
	 *
	 * @param string $to      Phone number
	 * @param string $message Message
	 *
	 * @return bool
	 */
	public function send( string $to, string $message ): bool {
		try {
			if ( defined( 'MAGIC_LOGIN_LOG_SMS' ) && MAGIC_LOGIN_LOG_SMS ) {
				error_log( "Sending SMS to $to: $message" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return true;
			}

			$provider = SmsProviderFactory::create( $this->settings['provider'], $this->settings );

			return $provider->send_sms( $to, $message );
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return false;
		}
	}

	/**
	 * Check if SMS login is enabled
	 *
	 * @return bool
	 * @since 2.4
	 */
	public static function is_sms_login_enabled() {
		if ( defined( 'MAGIC_LOGIN_USERNAME_ONLY' ) && MAGIC_LOGIN_USERNAME_ONLY ) {
			return false;
		}

		$settings = Utils\get_settings();

		return ! empty( $settings['sms']['enable'] );
	}

	/**
	 * Check if the phone number is already taken
	 *
	 * @param string $phone_number Phone number
	 *
	 * @return bool
	 * @since 2.4
	 */
	public static function is_phone_number_taken( $phone_number ) {
		$phone_number = sanitize_phone_number( $phone_number );

		$existing_user = get_users(
			[
				'meta_key'   => PHONE_NUMBER_META, // phpcs:ignore
				'meta_value' => $phone_number, // phpcs:ignore
				'number'     => 1,
			]
		);

		return ! empty( $existing_user );
	}


	/**
	 * Get user by phone number
	 *
	 * @param string $phone_number Phone number
	 *
	 * @return mixed|null
	 * @since 2.4
	 */
	public static function get_user_by_phone_number( $phone_number ) {
		/**
		 * Filters the arguments used to get the user by phone number
		 *
		 * @hook magic_login_get_user_by_phone_number_args
		 * @param array  $args         Arguments
		 *                             - meta_key: Phone number meta key
		 *                             - meta_value: Phone number
		 *                             - number: Number of users to return
		 * @param string $phone_number Phone number
		 *
		 * @return array
		 * @since 2.4
		 */
		$args = apply_filters(
			'magic_login_get_user_by_phone_number_args',
			[
			'meta_key'   => PHONE_NUMBER_META, // phpcs:ignore
			'meta_value' => sanitize_phone_number( $phone_number ), // phpcs:ignore
			'number'     => 1,
			],
			$phone_number
		);

		$users = get_users( $args );

		return ! empty( $users ) ? $users[0] : null;
	}



	/**
	 * Get the user phone number
	 *
	 * @param int $user_id User ID
	 *
	 * @return mixed|null
	 * @since 2.4
	 */
	public static function get_user_phone_number( $user_id ) {
		$phone_number = get_user_meta( $user_id, PHONE_NUMBER_META, true );
		$phone_number = apply_filters( 'magic_login_user_phone_number', $phone_number, $user_id );

		return $phone_number;
	}

	/**
	 * Get SMS login message
	 *
	 * @return string
	 * @since 2.4
	 */
	public static function get_sms_login_message() {
		$setting = Utils\get_settings();
		$message = __( $setting['sms']['login_message'], 'magic-login' ); // phpcs:ignore

		/**
		 * Filter the SMS login message
		 *
		 * @hook  magic_login_sms_login_message
		 *
		 * @param string $message SMS login message
		 *
		 * @return string Altered SMS login message
		 * @since 2.4
		 */
		return apply_filters( 'magic_login_sms_login_message', $message );
	}

	/**
	 * Get placeholders by user
	 *
	 * @param int    $user_id User ID
	 * @param string $context Context
	 *
	 * @return array
	 * @since 2.4
	 */
	public static function get_placeholders_by_user( $user_id, $context = 'sms' ) {
		global $magic_login_token, $magic_login_link;
		$user         = get_user_by( 'ID', $user_id );
		$login_link   = Utils\create_login_link( $user, $context );
		$placeholders = \MagicLogin\Utils\get_email_placeholders_by_user( $user, $login_link );

		/**
		 * Filter the SMS placeholders
		 *
		 * @hook  magic_login_sms_placeholders
		 *
		 * @param array $placeholders Placeholders
		 * @param int   $user_id      User ID
		 *
		 * @return array Altered placeholders
		 * @since 2.4
		 */
		return apply_filters( 'magic_login_sms_placeholders', $placeholders, $user_id );
	}

	/**
	 * Send login SMS
	 *
	 * @param int $user_id User ID
	 *
	 * @return bool
	 * @since 2.4
	 */
	public static function send_login_sms( $user_id ) {
		$phone_number = self::get_user_phone_number( $user_id );
		if ( ! $phone_number ) {
			return false;
		}
		$message = self::get_sms_login_message();
		// check if the message contains MAGIC_LOGIN_CODE
		$context = 'sms';
		if ( false !== strpos( $message, '{{MAGIC_LOGIN_CODE}}' ) ) {
			$context = 'sms_code';
		}

		$placeholders  = self::get_placeholders_by_user( $user_id, $context );
		$login_message = str_replace( array_keys( $placeholders ), array_values( $placeholders ), $message );
		$service       = new SmsService();

		/**
		 * Send login SMS
		 *
		 * @hook  magic_login_send_login_sms
		 *
		 * @param int $user_id User ID
		 *
		 * @since 2.4
		 */
		do_action( 'magic_login_send_login_sms', $user_id );

		return $service->send( $phone_number, $login_message );
	}

}
