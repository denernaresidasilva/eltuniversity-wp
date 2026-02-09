<?php
/**
 * Settings Page
 *
 * @package MagicLogin
 */

namespace MagicLogin\Admin\Dashboard;

use MagicLogin\Encryption;
use function MagicLogin\Utils\get_decrypted_value;
use function MagicLogin\Utils\is_masked_value;
use function MagicLogin\Utils\mask_string;
use function MagicLogin\Utils\sanitize_phone_number;
use const MagicLogin\Constants\DB_VERSION_OPTION_NAME;
use const MagicLogin\Constants\LICENSE_ENDPOINT;
use const MagicLogin\Constants\LICENSE_INFO_TRANSIENT;
use const MagicLogin\Constants\LICENSE_KEY_OPTION;
use const MagicLogin\Constants\SETTING_OPTION;
use function MagicLogin\Utils\delete_all_tokens;
use function MagicLogin\Utils\get_allowed_intervals;

use function MagicLogin\Utils\get_license_info;
use function MagicLogin\Utils\get_license_key;


// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	if ( MAGIC_LOGIN_IS_NETWORK ) {
		add_action( 'network_admin_menu', __NAMESPACE__ . '\\admin_menu' );
	} else {
		add_action( 'admin_menu', __NAMESPACE__ . '\\admin_menu' );
	}

	add_action( 'admin_init', __NAMESPACE__ . '\\save_settings' );
	add_filter( 'admin_body_class', __NAMESPACE__ . '\\add_sui_admin_body_class' );
	add_action( 'wp_ajax_send_test_sms', __NAMESPACE__ . '\\send_test_sms' );
}

/**
 * Add required class for shared UI
 *
 * @param string $classes css classes for admin area
 *
 * @return string
 * @see https://wpmudev.github.io/shared-ui/installation/
 */
function add_sui_admin_body_class( $classes ) {
	$classes .= ' sui-2-12-24 ';

	return $classes;
}


/**
 * Add menu item
 */
function admin_menu() {
	$parent = MAGIC_LOGIN_IS_NETWORK ? 'settings.php' : 'options-general.php';

	add_submenu_page(
		$parent,
		esc_html__( 'Magic Login Pro', 'magic-login' ),
		esc_html__( 'Magic Login Pro', 'magic-login' ),
		/**
		 * Filter the capability required to access the Magic Login Pro settings page.
		 *
		 * @hook magic_login_admin_menu_cap
		 *
		 * @param string $capability The capability required to access the settings page.
		 * @return string Altered value.
		 */
		apply_filters( 'magic_login_admin_menu_cap', 'manage_options' ),
		'magic-login',
		__NAMESPACE__ . '\settings_page'
	);
}

/**
 * Settings page
 */
function settings_page() {
	?>
	<?php if ( is_network_admin() ) : ?>
		<?php settings_errors(); ?>
	<?php endif; ?>

	<main class="sui-wrap">
		<?php include MAGIC_LOGIN_PRO_INC . 'admin/partials/header.php'; ?>
		<?php include MAGIC_LOGIN_PRO_INC . 'admin/partials/settings.php'; ?>
		<?php include MAGIC_LOGIN_PRO_INC . 'admin/partials/footer.php'; ?>
		<?php include MAGIC_LOGIN_PRO_INC . 'admin/partials/modals.php'; ?>
	</main>

	<?php
}

/**
 * Save settings
 */
function save_settings() {

	if ( ! is_user_logged_in() ) {
		return;
	}

	$nonce = filter_input( INPUT_POST, 'magic_login_settings', FILTER_SANITIZE_SPECIAL_CHARS );
	if ( wp_verify_nonce( $nonce, 'magic_login_settings' ) ) {

		// if it's export settings
		if ( isset( $_POST['magic_login_form_action'] ) && 'export_settings' === $_POST['magic_login_form_action'] ) {
			export_settings();
			exit;
		}

		// if it's import settings
		if ( isset( $_POST['magic_login_form_action'] ) && 'import_settings' === $_POST['magic_login_form_action'] ) {
			import_settings();

			return;
		}

		if ( isset( $_POST['magic_login_form_action'] ) && 'reset_tokens' === $_POST['magic_login_form_action'] ) {
			if ( false !== delete_all_tokens() ) {
				add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Tokens has been removed.', 'magic-login' ), 'success' );
			} else {
				add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Tokens could not be removed.', 'magic-login' ), 'error' );
			}

			return;
		}

		if ( isset( $_POST['magic_login_form_action'] ) && 'reset_license' === $_POST['magic_login_form_action'] ) {
			\MagicLogin\Tools::reset_license();
			add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'License has been reset.', 'magic-login' ), 'success' );

			return;
		}

		if ( isset( $_POST['magic_login_form_action'] ) && 'reset_settings' === $_POST['magic_login_form_action'] ) {

			\MagicLogin\Tools::reset_settings();
			add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Settings have been reset.', 'magic-login' ), 'success' );

			return;
		}

		$old_settings = \MagicLogin\Utils\get_settings();

		$settings                                  = [];
		$settings['is_default']                    = boolval( filter_input( INPUT_POST, 'is_default' ) );
		$settings['add_login_button']              = boolval( filter_input( INPUT_POST, 'add_login_button' ) );
		$settings['token_ttl']                     = absint( filter_input( INPUT_POST, 'token_ttl' ) );
		$settings['token_validity']                = absint( filter_input( INPUT_POST, 'token_validity' ) );
		$settings['auto_login_links']              = boolval( filter_input( INPUT_POST, 'auto_login_links' ) );
		$settings['enable_brute_force_protection'] = boolval( filter_input( INPUT_POST, 'enable_brute_force_protection' ) );
		$settings['brute_force_bantime']           = absint( filter_input( INPUT_POST, 'brute_force_bantime' ) );
		$settings['brute_force_login_attempt']     = absint( filter_input( INPUT_POST, 'brute_force_login_attempt' ) );
		$settings['brute_force_login_time']        = absint( filter_input( INPUT_POST, 'brute_force_login_time' ) );
		$settings['enable_login_throttling']       = boolval( filter_input( INPUT_POST, 'enable_login_throttling' ) );
		$settings['login_throttling_limit']        = absint( filter_input( INPUT_POST, 'login_throttling_limit' ) );
		$settings['login_throttling_time']         = absint( filter_input( INPUT_POST, 'login_throttling_time' ) );
		$settings['enable_ip_check']               = boolval( filter_input( INPUT_POST, 'enable_ip_check' ) );
		$settings['enable_domain_restriction']     = boolval( filter_input( INPUT_POST, 'enable_domain_restriction' ) );
		$settings['allowed_domains']               = sanitize_textarea_field( filter_input( INPUT_POST, 'allowed_domains' ) );
		$settings['email_subject']                 = sanitize_text_field( filter_input( INPUT_POST, 'email_subject' ) );
		$settings['enable_login_redirection']      = boolval( filter_input( INPUT_POST, 'enable_login_redirection' ) );
		$settings['default_redirection_url']       = esc_url_raw( filter_input( INPUT_POST, 'default_redirection_url' ) );
		$settings['enable_wp_login_redirection']   = boolval( filter_input( INPUT_POST, 'enable_wp_login_redirection' ) );
		$settings['enforce_redirection_rules']     = boolval( filter_input( INPUT_POST, 'enforce_redirection_rules' ) );
		$settings['enable_role_based_redirection'] = boolval( filter_input( INPUT_POST, 'enable_role_based_redirection' ) );
		$settings['enable_ajax']                   = boolval( filter_input( INPUT_POST, 'enable_ajax' ) );
		$settings['enable_woo_integration']        = boolval( filter_input( INPUT_POST, 'enable_woo_integration' ) );
		$settings['woo_position']                  = sanitize_text_field( filter_input( INPUT_POST, 'woo_position' ) );
		$settings['enable_woo_customer_login']     = boolval( filter_input( INPUT_POST, 'enable_woo_customer_login' ) );
		$settings['woo_customer_login_position']   = sanitize_text_field( filter_input( INPUT_POST, 'woo_customer_login_position' ) );
		$settings['enable_edd_checkout']           = boolval( filter_input( INPUT_POST, 'enable_edd_checkout' ) );
		$settings['edd_checkout_position']         = sanitize_text_field( filter_input( INPUT_POST, 'edd_checkout_position' ) );
		$settings['enable_edd_login']              = boolval( filter_input( INPUT_POST, 'enable_edd_login' ) );
		$settings['edd_login_position']            = sanitize_text_field( filter_input( INPUT_POST, 'edd_login_position' ) );
		$settings['enable_rest_api']               = boolval( filter_input( INPUT_POST, 'enable_rest_api' ) );

		// registration settings
		$settings['registration']['enable']                    = boolval( filter_input( INPUT_POST, 'registration_enable' ) );
		$settings['registration']['mode']                      = sanitize_text_field( filter_input( INPUT_POST, 'registration_mode' ) );
		$settings['registration']['fallback_email_field']      = boolval( filter_input( INPUT_POST, 'fallback_email_field' ) );
		$settings['registration']['show_name_field']           = boolval( filter_input( INPUT_POST, 'registration_show_name_field' ) );
		$settings['registration']['require_name_field']        = boolval( filter_input( INPUT_POST, 'registration_require_name_field' ) );
		$settings['registration']['show_terms_field']          = boolval( filter_input( INPUT_POST, 'registration_show_terms_field' ) );
		$settings['registration']['require_terms_field']       = boolval( filter_input( INPUT_POST, 'registration_require_terms_field' ) );
		$settings['registration']['send_email']                = boolval( filter_input( INPUT_POST, 'registration_send_email' ) );
		$settings['registration']['email_subject']             = sanitize_text_field( filter_input( INPUT_POST, 'registration_email_subject' ) );
		$settings['registration']['enable_domain_restriction'] = boolval( filter_input( INPUT_POST, 'enable_registration_domain_restriction' ) );
		$settings['registration']['allowed_domains']           = sanitize_textarea_field( filter_input( INPUT_POST, 'allowed_registration_domains' ) );
		$settings['registration']['role']                      = sanitize_textarea_field( filter_input( INPUT_POST, 'registration_role' ) );
		$settings['registration']['enable_redirection']        = boolval( filter_input( INPUT_POST, 'enable_registration_redirection' ) );
		$settings['registration']['redirection_url']           = esc_url_raw( filter_input( INPUT_POST, 'registration_redirection_url' ) );

		if ( current_user_can( 'unfiltered_html' ) ) {
			$settings['login_email']                   = filter_input( INPUT_POST, 'login_email' );
			$settings['registration']['terms']         = filter_input( INPUT_POST, 'registration_terms' );
			$settings['registration']['email_content'] = filter_input( INPUT_POST, 'registration_email_content' );
		} else {
			$settings['login_email']                   = wp_kses_post( filter_input( INPUT_POST, 'login_email' ) );
			$settings['registration']['terms']         = wp_kses_post( filter_input( INPUT_POST, 'registration_terms' ) );
			$settings['registration']['email_content'] = wp_kses_post( filter_input( INPUT_POST, 'registration_email_content' ) );
		}

		// spam protection settings
		$settings['spam_protection']['service']             = sanitize_text_field( filter_input( INPUT_POST, 'spam_protection_service' ) );
		$settings['spam_protection']['enable_login']        = boolval( filter_input( INPUT_POST, 'enable_spam_protection_login' ) );
		$settings['spam_protection']['enable_registration'] = sanitize_text_field( filter_input( INPUT_POST, 'enable_spam_protection_registration' ) );

		// recaptcha settings
		$settings['recaptcha']['type']                       = sanitize_text_field( filter_input( INPUT_POST, 'recaptcha_type' ) );
		$settings['recaptcha']['v2_checkbox']['site_key']    = sanitize_text_field( filter_input( INPUT_POST, 'v2_captcha_key' ) );
		$settings['recaptcha']['v2_checkbox']['secret_key']  = sanitize_text_field( filter_input( INPUT_POST, 'v2_captcha_secret' ) );
		$settings['recaptcha']['v2_invisible']['site_key']   = sanitize_text_field( filter_input( INPUT_POST, 'v2_invisible_captcha_key' ) );
		$settings['recaptcha']['v2_invisible']['secret_key'] = sanitize_text_field( filter_input( INPUT_POST, 'v2_invisible_captcha_secret' ) );
		$settings['recaptcha']['v3']['site_key']             = sanitize_text_field( filter_input( INPUT_POST, 'v3_captcha_key' ) );
		$settings['recaptcha']['v3']['secret_key']           = sanitize_text_field( filter_input( INPUT_POST, 'v3_captcha_secret' ) );

		// cf turnstile settings
		$settings['cf_turnstile']['site_key']   = sanitize_text_field( filter_input( INPUT_POST, 'cf_turnstile_key' ) );
		$settings['cf_turnstile']['secret_key'] = sanitize_text_field( filter_input( INPUT_POST, 'cf_turnstile_secret' ) );

		// sms settings
		$settings['sms']['enable']                           = boolval( filter_input( INPUT_POST, 'sms_enable' ) );
		$settings['sms']['twilio']['account_sid']            = sanitize_text_field( filter_input( INPUT_POST, 'twilio_account_sid' ) );
		$settings['sms']['twilio']['auth_token']             = sanitize_text_field( filter_input( INPUT_POST, 'twilio_auth_token' ) );
		$settings['sms']['twilio']['from']                   = sanitize_text_field( filter_input( INPUT_POST, 'twilio_from_number' ) );
		$settings['sms']['sms_sending_strategy']             = sanitize_text_field( filter_input( INPUT_POST, 'sms_sending_strategy' ) );
		$settings['sms']['login_message']                    = sanitize_textarea_field( filter_input( INPUT_POST, 'sms_login_message' ) );
		$settings['sms']['wp_registration']                  = boolval( filter_input( INPUT_POST, 'sms_wp_registration' ) );
		$settings['sms']['wp_require_phone']                 = boolval( filter_input( INPUT_POST, 'sms_wp_require_phone' ) );
		$settings['sms']['magic_registration']               = boolval( filter_input( INPUT_POST, 'sms_magic_registration' ) );
		$settings['sms']['magic_registration_require_phone'] = boolval( filter_input( INPUT_POST, 'sms_magic_registration_require_phone' ) );
		$settings['sms']['send_registration_message']        = boolval( filter_input( INPUT_POST, 'sms_send_registration_message' ) );
		$settings['sms']['registration_message']             = sanitize_textarea_field( filter_input( INPUT_POST, 'sms_registration_message' ) );

		if ( is_masked_value( $settings['recaptcha']['v2_checkbox']['secret_key'] ) ) {
			$settings['recaptcha']['v2_checkbox']['secret_key'] = get_decrypted_value( $old_settings['recaptcha']['v2_checkbox']['secret_key'] );
		}

		if ( is_masked_value( $settings['recaptcha']['v2_invisible']['secret_key'] ) ) {
			$settings['recaptcha']['v2_invisible']['secret_key'] = get_decrypted_value( $old_settings['recaptcha']['v2_invisible']['secret_key'] );
		}

		if ( is_masked_value( $settings['recaptcha']['v3']['secret_key'] ) ) {
			$settings['recaptcha']['v3']['secret_key'] = get_decrypted_value( $old_settings['recaptcha']['v3']['secret_key'] );
		}

		if ( is_masked_value( $settings['cf_turnstile']['secret_key'] ) ) {
			$settings['cf_turnstile']['secret_key'] = get_decrypted_value( $old_settings['cf_turnstile']['secret_key'] );
		}

		if ( is_masked_value( $settings['sms']['twilio']['auth_token'] ) ) {
			$settings['sms']['twilio']['auth_token'] = get_decrypted_value( $old_settings['sms']['twilio']['auth_token'] );
		}

		$encryption = new Encryption();

		$settings['recaptcha']['v2_checkbox']['secret_key']  = $encryption->encrypt( $settings['recaptcha']['v2_checkbox']['secret_key'] );
		$settings['recaptcha']['v2_invisible']['secret_key'] = $encryption->encrypt( $settings['recaptcha']['v2_invisible']['secret_key'] );
		$settings['recaptcha']['v3']['secret_key']           = $encryption->encrypt( $settings['recaptcha']['v3']['secret_key'] );
		$settings['cf_turnstile']['secret_key']              = $encryption->encrypt( $settings['cf_turnstile']['secret_key'] );
		$settings['sms']['twilio']['auth_token']             = $encryption->encrypt( $settings['sms']['twilio']['auth_token'] );

		// convert TTL in minute
		if ( isset( $_POST['token_ttl'] ) && $_POST['token_ttl'] > 0 && isset( $_POST['token_interval'] ) ) {
			switch ( $_POST['token_interval'] ) {
				case 'DAY':
					$settings['token_ttl'] = absint( $_POST['token_ttl'] ) * 1440;
					break;
				case 'HOUR':
					$settings['token_ttl'] = absint( $_POST['token_ttl'] ) * 60;
					break;
				case 'MINUTE':
				default:
					$settings['token_ttl'] = absint( $_POST['token_ttl'] ) * 1;
			}
		}

		$token_interval    = sanitize_text_field( filter_input( INPUT_POST, 'token_interval' ) );
		$allowed_intervals = get_allowed_intervals();

		if ( isset( $allowed_intervals[ $token_interval ] ) ) {
			$settings['token_interval'] = $token_interval;
		}

		$role_based_redirection_rules = [];
		if ( isset( $_POST['redirect_role'] ) ) {
			foreach ( $_POST['redirect_role'] as $role => $rule ) { // phpcs:ignore
				$role_based_redirection_rules[ $role ] = esc_url_raw( $rule ); // phpcs:ignore
			}
		}

		$settings['role_based_redirection_rules'] = $role_based_redirection_rules;

		if ( MAGIC_LOGIN_IS_NETWORK ) {
			update_site_option( SETTING_OPTION, $settings );
		} else {
			update_option( SETTING_OPTION, $settings );
		}

		add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Settings saved.', 'magic-login' ), 'success', true );

		$license_key = sanitize_text_field( filter_input( INPUT_POST, 'license_key' ) );
		if ( is_masked_value( $license_key ) ) {
			$license_key = get_license_key();
		}

		update_option( LICENSE_KEY_OPTION, $license_key, false );

		if ( isset( $_POST['magic_login_license_activate'] ) ) {
			wp_remote_post(
				LICENSE_ENDPOINT,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => [
						'action'      => 'activate',
						'license_key' => $license_key,
						'license_url' => home_url(),
					],
				)
			);
			delete_transient( LICENSE_INFO_TRANSIENT );
		} elseif ( isset( $_POST['magic_login_license_deactivate'] ) ) {
			wp_remote_post(
				LICENSE_ENDPOINT,
				array(
					'timeout'   => 15,
					'sslverify' => true,
					'body'      => [
						'action'      => 'deactivate',
						'license_key' => $license_key,
						'license_url' => home_url(),
					],
				)
			);
			delete_transient( LICENSE_INFO_TRANSIENT );
		}

		if ( empty( $license_key ) ) {
			delete_transient( LICENSE_INFO_TRANSIENT );
		}

		return;
	}

}


/**
 * Display license message
 */
function maybe_display_license_message() {
	$license_key = get_license_key();

	if ( empty( $license_key ) ) {
		?>
		<div class="sui-notice sui-notice-warning">
			<div class="sui-notice-content">
				<div class="sui-notice-message">
					<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
					<p><?php esc_html_e( 'Please activate your license on this domain. A valid license key is required for access to automatic updates and support.', 'magic-login' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}

/**
 * Send test SMS
 *
 * @return void
 * @since 2.4
 */
function send_test_sms() {
	check_ajax_referer( 'magic_login_send_test_sms_nonce', 'nonce' );

	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

	if ( empty( $phone ) || empty( $message ) ) {
		wp_send_json_error( __( 'Phone number and message are required.', 'magic-login' ) );
	}

	// Use your SMS service to send the SMS
	$sms_service = new \MagicLogin\SmsService();
	$result      = $sms_service->send( $phone, $message );

	if ( $result ) {
		wp_send_json_success( __( 'SMS sent successfully!', 'magic-login' ) );
	} else {
		wp_send_json_error( __( 'Failed to send SMS.', 'magic-login' ) );
	}
}

/**
 * Export settings
 *
 * @return void
 */
function export_settings() {
	$include_sensitive = ! empty( $_POST['export_include_sensitive'] );
	$include_license   = ! empty( $_POST['export_include_license'] );

	$export = \MagicLogin\Tools::generate_export_settings( $include_sensitive, $include_license );

	$filename = 'magic-login-settings-' . sanitize_title( wp_parse_url( home_url(), PHP_URL_HOST ) ) . '-' . gmdate( 'Y-m-d-H-i-s' ) . '.json';
	header( 'Content-Type: application/json' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	echo wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	exit;
}

/**
 * Import settings from JSON file.
 *
 * @return void
 */
function import_settings() {
	if ( empty( $_FILES['import_file']['tmp_name'] ) ) {
		add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'No file uploaded.', 'magic-login' ), 'error' );

		return;
	}

	$tmp_file = sanitize_text_field( wp_unslash( $_FILES['import_file']['tmp_name'] ) );

	$file     = file_get_contents( $tmp_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$imported = json_decode( $file, true );

	if ( json_last_error() !== JSON_ERROR_NONE || empty( $imported ) ) {
		add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Invalid or malformed JSON file.', 'magic-login' ), 'error' );

		return;
	}

	$activate = ! empty( $_POST['activate_imported_license'] );

	$success = \MagicLogin\Tools::process_import_settings( $imported, $activate );

	if ( $success ) {
		add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Settings imported successfully.', 'magic-login' ), 'success' );
	} else {
		add_settings_error( SETTING_OPTION, 'magic-login', esc_html__( 'Failed to import settings.', 'magic-login' ), 'error' );
	}
}


