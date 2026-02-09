<?php
/**
 * Spam Protection functionality.
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use function MagicLogin\Utils\get_decrypted_value;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Class SpamProtection
 */
class SpamProtection {

	/**
	 * MagicLogin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Spam protection service.
	 *
	 * @var object
	 */
	private $service;

	/**
	 * Return an instance of the current class
	 *
	 * @since
	 */
	public static function setup() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = \MagicLogin\Utils\get_settings();

		if ( ! $this->settings['spam_protection']['enable_login'] && ! $this->settings['spam_protection']['enable_registration'] ) {
			return;
		}

		$this->initialize_service();

		if ( $this->settings['spam_protection']['enable_login'] ) {
			add_action( 'magic_login_form', [ $this, 'add_spam_protection' ] );
			add_action( 'magic_login_code_form', [ $this, 'add_spam_protection' ] );
			add_filter( 'magic_login_pre_process_login_request', [ $this, 'verify_login_request' ] );
			add_filter( 'magic_login_pre_process_code_login_request', [ $this, 'verify_login_request' ] );
		}

		if ( $this->settings['spam_protection']['enable_registration'] ) {
			add_action( 'magic_login_registration_before_process', [ $this, 'verify_registration_request' ], 10, 5 );
			add_action( 'magic_login_registration_form_before_submit', [ $this, 'add_spam_protection' ] );
		}

		// Hook to enqueue scripts
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'login_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Initialize the spam protection service.
	 */
	private function initialize_service() {
		$service_name = $this->settings['spam_protection']['service'];

		if ( 'recaptcha' === $service_name ) {
			$type        = $this->settings['recaptcha']['type'];
			$site_key    = $this->settings['recaptcha'][ $type ]['site_key'];
			$site_secret = get_decrypted_value( $this->settings['recaptcha'][ $type ]['secret_key'] );
			if ( $site_key && $site_secret ) {
				$this->service = new ReCaptchaService( $site_key, $site_secret, $type );
			}
		} elseif ( 'cf_turnstile' === $service_name ) {
			$site_key    = $this->settings['cf_turnstile']['site_key'];
			$site_secret = get_decrypted_value( $this->settings['cf_turnstile']['secret_key'] );
			if ( $site_key && $site_secret ) {
				$this->service = new CloudflareTurnstileService( $site_key, $site_secret );
			}
		}
	}

	/**
	 * Add spam protection to the form.
	 */
	public function add_spam_protection() {
		if ( empty( $this->service ) ) {
			return;
		}

		echo '<div class="magic-login-captcha-wrapper">';
		echo $this->service->render(); // phpcs:ignore
		echo '</div>';
	}

	/**
	 * Enqueue the required scripts.
	 */
	public function enqueue_scripts() {
		if ( ! isset( $this->service ) ) {
			return;
		}

		$script_url    = $this->service->get_script_url();
		$inline_script = $this->service->get_inline_script();

		if ( $script_url ) {
			wp_enqueue_script( 'magic-login-spam-protection', $script_url, [], null, true ); // phpcs:ignore
		}

		if ( $inline_script && ( ! $this->settings['enable_ajax'] || 'login_enqueue_scripts' === current_filter() ) ) {
			wp_add_inline_script( 'magic-login-spam-protection', $inline_script );
		}

	}

	/**
	 * Verify the login request.
	 *
	 * @param mixed $result null or WP_Error.
	 *
	 * @return mixed|\WP_Error|null
	 */
	public function verify_login_request( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( empty( $this->service ) ) {
			return;
		}

		$token = $this->get_client_token();

		if ( ! $this->service->verify( $token ) ) {
			return $this->get_verification_error();
		}

		return null;
	}

	/**
	 * Verify the registration request.
	 *
	 * @param bool   $is_ajax    Whether the request is an AJAX request.
	 * @param string $first_name First name.
	 * @param string $last_name  Last name.
	 * @param string $email      Email.
	 * @param string $tos        TOS.
	 *
	 * @return void
	 */
	public function verify_registration_request( $is_ajax, $first_name, $last_name, $email, $tos ) {

		if ( empty( $this->service ) ) {
			return;
		}

		$token = $this->get_client_token();

		if ( ! $this->service->verify( $token ) ) {
			$error_message = $this->get_verification_error_message();

			if ( $is_ajax ) {
				wp_send_json_error( $error_message );
			} else {
				// Redirect with error message
				$args['magic-registration'] = 'error';
				$args['message']            = rawurlencode( $error_message );

				if ( ! empty( $first_name ) ) {
					$args['first_name'] = rawurlencode( $first_name );
				}
				if ( ! empty( $last_name ) ) {
					$args['last_name'] = rawurlencode( $last_name );
				}

				if ( ! empty( $email ) ) {
					$args['email'] = rawurlencode( $email );
				}

				if ( ! empty( $tos ) ) {
					$args['tos'] = rawurlencode( $tos );
				}

				wp_safe_redirect( add_query_arg( $args, wp_get_referer() ) );
				exit;
			}
		}
	}

	/**
	 * Get the client token for verification.
	 *
	 * @return string
	 */
	public function get_client_token() {
		if ( isset( $_POST['g-recaptcha-response'] ) ) {
			return wp_unslash( $_POST['g-recaptcha-response'] ); // phpcs:ignore
		}

		if ( isset( $_POST['magic-login-cf-turnstile-response'] ) ) {
			return wp_unslash( $_POST['magic-login-cf-turnstile-response'] ); // phpcs:ignore
		}
	}

	/**
	 * Get the verification error message.
	 *
	 * @return string
	 */
	private function get_verification_error_message() {
		switch ( $this->settings['spam_protection']['service'] ) {
			case 'recaptcha':
				return esc_html__( 'ReCaptcha verification failed. Please try again.', 'magic-login' );
			case 'cf_turnstile':
				return esc_html__( 'Turnstile verification failed. Please try again.', 'magic-login' );
			default:
				return esc_html__( 'Verification failed. Please try again.', 'magic-login' );
		}
	}

	/**
	 * Get the verification error.
	 *
	 * @return \WP_Error
	 */
	private function get_verification_error() {
		return new \WP_Error( 'magic_login_verification_failed', $this->get_verification_error_message() );
	}

}
