<?php
/**
 * Interface for SMS provider
 *
 * @package MagicLogin\Services
 */

namespace MagicLogin\Services;

interface SmsProviderInterface {
	/**
	 * Configure the SMS provider
	 *
	 * @param array $settings Settings
	 *
	 * @return void
	 */
	public function configure( array $settings ): void;

	/**
	 * Send SMS
	 *
	 * @param string $to Phone number
	 * @param string $message Message
	 *
	 * @return bool
	 */
	public function send_sms( string $to, string $message ): bool;
}
