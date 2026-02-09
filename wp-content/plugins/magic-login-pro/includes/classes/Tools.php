<?php
/**
 * Tools
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use function MagicLogin\Utils\get_decrypted_value;
use function MagicLogin\Utils\get_license_key;
use const MagicLogin\Constants\DB_VERSION_OPTION_NAME;
use const MagicLogin\Constants\LICENSE_ENDPOINT;
use const MagicLogin\Constants\LICENSE_INFO_TRANSIENT;
use const MagicLogin\Constants\LICENSE_KEY_OPTION;
use const MagicLogin\Constants\SETTING_OPTION;

/**
 * Tools
 */
class Tools {

	/**
	 * Generate export settings.
	 *
	 * @param bool $include_sensitive Whether to include sensitive data (e.g. API credentials).
	 * @param bool $include_license   Whether to include the license key.
	 *
	 * @return mixed|null
	 */
	public static function generate_export_settings( $include_sensitive = false, $include_license = false ) {
		$settings = \MagicLogin\Utils\get_settings();

		if ( $include_sensitive ) {
			$settings['recaptcha']['v2_checkbox']['secret_key']  = get_decrypted_value( $settings['recaptcha']['v2_checkbox']['secret_key'] );
			$settings['recaptcha']['v2_invisible']['secret_key'] = get_decrypted_value( $settings['recaptcha']['v2_invisible']['secret_key'] );
			$settings['recaptcha']['v3']['secret_key']           = get_decrypted_value( $settings['recaptcha']['v3']['secret_key'] );
			$settings['cf_turnstile']['secret_key']              = get_decrypted_value( $settings['cf_turnstile']['secret_key'] );
			$settings['sms']['twilio']['auth_token']             = get_decrypted_value( $settings['sms']['twilio']['auth_token'] );
		} else {
			unset(
				$settings['recaptcha']['v2_checkbox']['secret_key'],
				$settings['recaptcha']['v2_invisible']['secret_key'],
				$settings['recaptcha']['v3']['secret_key'],
				$settings['cf_turnstile']['secret_key'],
				$settings['sms']['twilio']['auth_token']
			);
		}

		$export = [
			'site_url'       => home_url(),
			'plugin_version' => MAGIC_LOGIN_PRO_VERSION,
			'is_network'     => MAGIC_LOGIN_IS_NETWORK,
			'db_version'     => MAGIC_LOGIN_IS_NETWORK ? get_site_option( DB_VERSION_OPTION_NAME ) : get_option( DB_VERSION_OPTION_NAME ),
			'settings'       => $settings,
			'meta'           => [
				'sensitive_exported' => $include_sensitive,
				'license_included'   => $include_license,
				'pro'                => true,
			],
		];

		if ( $include_license ) {
			$export['license_key'] = get_license_key();
		}

		/**
		 * Filter the export settings.
		 *
		 * @param array $export The export settings array.
		 *
		 * @return array $export The modified export settings array.
		 * @since 2.5
		 * @hook  magic_login_export_settings
		 */
		return apply_filters( 'magic_login_export_settings', $export );
	}

	/**
	 * Process the import settings.
	 *
	 * @param array $imported         The imported settings array.
	 * @param bool  $activate_license Whether to activate the license or not.
	 *
	 * @return bool
	 */
	public static function process_import_settings( array $imported, $activate_license = false ) {
		/**
		 * Filter the import settings.
		 *
		 * @hook  magic_login_import_settings
		 *
		 * @param array $imported The import settings array.
		 *
		 * @return array $imported The modified import settings array.
		 * @since 2.5
		 */
		$imported = apply_filters( 'magic_login_import_settings', $imported );

		if ( empty( $imported['settings'] ) ) {
			return false;
		}

		$new_settings = array_replace_recursive( \MagicLogin\Utils\get_settings(), $imported['settings'] );

		// Encrypt sensitive values (if present)
		$encryption = new \MagicLogin\Encryption();

		if ( ! empty( $new_settings['recaptcha']['v2_checkbox']['secret_key'] ) ) {
			$new_settings['recaptcha']['v2_checkbox']['secret_key'] = $encryption->encrypt( $new_settings['recaptcha']['v2_checkbox']['secret_key'] );
		}

		if ( ! empty( $new_settings['recaptcha']['v2_invisible']['secret_key'] ) ) {
			$new_settings['recaptcha']['v2_invisible']['secret_key'] = $encryption->encrypt( $new_settings['recaptcha']['v2_invisible']['secret_key'] );
		}

		if ( ! empty( $new_settings['recaptcha']['v3']['secret_key'] ) ) {
			$new_settings['recaptcha']['v3']['secret_key'] = $encryption->encrypt( $new_settings['recaptcha']['v3']['secret_key'] );
		}

		if ( ! empty( $new_settings['cf_turnstile']['secret_key'] ) ) {
			$new_settings['cf_turnstile']['secret_key'] = $encryption->encrypt( $new_settings['cf_turnstile']['secret_key'] );
		}

		if ( ! empty( $new_settings['sms']['twilio']['auth_token'] ) ) {
			$new_settings['sms']['twilio']['auth_token'] = $encryption->encrypt( $new_settings['sms']['twilio']['auth_token'] );
		}

		if ( MAGIC_LOGIN_IS_NETWORK ) {
			update_site_option( SETTING_OPTION, $new_settings );
		} else {
			update_option( SETTING_OPTION, $new_settings );
		}

		if ( ! empty( $imported['license_key'] ) ) {
			update_option( LICENSE_KEY_OPTION, $imported['license_key'], false );

			if ( $activate_license ) {
				wp_remote_post(
					LICENSE_ENDPOINT,
					[
						'timeout'   => 15,
						'sslverify' => true,
						'body'      => [
							'action'      => 'activate',
							'license_key' => $imported['license_key'],
							'license_url' => home_url(),
						],
					]
				);
				delete_transient( LICENSE_INFO_TRANSIENT );
			}
		}

		if ( ! empty( $imported['db_version'] ) ) {
			if ( MAGIC_LOGIN_IS_NETWORK ) {
				update_site_option( DB_VERSION_OPTION_NAME, $imported['db_version'] );
			} else {
				update_option( DB_VERSION_OPTION_NAME, $imported['db_version'], false );
			}
		}

		/**
		 * Action after settings are imported.
		 *
		 * @param array $imported The imported settings array.
		 *
		 * @since 2.5
		 * @hook  magic_login_settings_imported
		 */
		do_action( 'magic_login_settings_imported', $imported );

		return true;
	}

	/**
	 * Reset settings and database version.
	 *
	 * @return void
	 */
	public static function reset_settings() {
		if ( MAGIC_LOGIN_IS_NETWORK ) {
			delete_site_option( SETTING_OPTION );
			delete_site_option( DB_VERSION_OPTION_NAME );
		} else {
			delete_option( SETTING_OPTION );
			delete_option( DB_VERSION_OPTION_NAME );
		}
	}

	/**
	 * Reset license key and info.
	 *
	 * @return void
	 */
	public static function reset_license() {
		delete_option( LICENSE_KEY_OPTION );
		delete_transient( LICENSE_INFO_TRANSIENT );
	}

}
