<?php
/**
 * SMS provider factory
 *
 * @package MagicLogin\Services
 */

namespace MagicLogin\Services;

use InvalidArgumentException;

/**
 * SMS provider factory
 */
class SmsProviderFactory {

	/**
	 * Create SMS provider
	 *
	 * @param string $provider Provider
	 * @param array  $settings Settings
	 *
	 * @return SmsProviderInterface
	 * @throws InvalidArgumentException If provider is not supported
	 */
	public static function create( string $provider, array $settings ): SmsProviderInterface {

		/**
		 * Filter SMS providers
		 *
		 * @hook  magic_login_sms_providers
		 *
		 * @param array $providers Providers
		 *
		 * @return array Array of SMS providers.
		 * @since 2.4
		 */
		$providers = apply_filters(
			'magic_login_sms_providers',
			[
				'twilio' => TwilioSmsProvider::class,
			]
		);

		if ( ! isset( $providers[ $provider ] ) ) {
			throw new InvalidArgumentException( "Unsupported SMS provider: $provider" );
		}

		$provider_instance = new $providers[ $provider ]();
		$provider_instance->configure( $settings[ $provider ] );

		return $provider_instance;
	}

}
