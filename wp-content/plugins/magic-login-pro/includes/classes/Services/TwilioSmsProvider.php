<?php
/**
 * Twilio SMS provider
 *
 * @package MagicLogin\Services
 */

namespace MagicLogin\Services;

use MagicLogin\Dependencies\Twilio\Rest\Client;
use function MagicLogin\Utils\get_decrypted_value;
use function MagicLogin\Utils\sanitize_phone_number;

/**
 * Twilio SMS provider
 */
class TwilioSmsProvider implements SmsProviderInterface {

	/**
	 * Twilio client
	 *
	 * @var Client
	 */
	protected $client;

	/**
	 * From number
	 *
	 * @var string
	 */
	protected $from;

	/**
	 * Configure the SMS provider
	 *
	 * @param array $settings Settings
	 *
	 * @return void
	 */
	public function configure( array $settings ): void {
		$this->client = new Client( $settings['account_sid'], get_decrypted_value( $settings['auth_token'] ) );
		$this->from   = $settings['from'];
	}

	/**
	 * Send SMS
	 *
	 * @param string $to      Phone number
	 * @param string $message Message
	 *
	 * @return bool
	 */
	public function send_sms( string $to, string $message ): bool {
		try {

			/**
			 * Filter Twilio SMS options
			 *
			 * @hook  magic_login_twilio_sms_options
			 *
			 * @param array $options Twilio SMS options
			 *
			 * @return array altered Twilio SMS options
			 * @since 2.4.2
			 */
			$options = apply_filters(
				'magic_login_twilio_sms_options',
				[
					'from' => $this->from,
					'body' => $message,
				]
			);

			$this->client->messages->create( $to, $options );

			return true;
		} catch ( \Exception $e ) {
			error_log( $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

			return false;
		}
	}
}
