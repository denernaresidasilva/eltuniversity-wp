<?php
/**
 * Rest API for Magic Login
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;
use function MagicLogin\Utils\sanitize_phone_number;

/**
 * Class API
 */
class API {
	/**
	 * MagicLogin settings.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->settings = \MagicLogin\Utils\get_settings();

		if ( ! empty( $this->settings['enable_rest_api'] ) && $this->settings['enable_rest_api'] ) {
			add_action( 'rest_api_init', [ $this, 'rest_api_init' ] );
		}
	}

	/**
	 * Return an instance of the current class.
	 *
	 * @return self
	 */
	public static function setup() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Initialize the REST API routes.
	 */
	public function rest_api_init() {
		register_rest_route(
			'magic-login/v1',
			'/token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_api_request' ],
					'args'                => array(
						'user'        => array(
							'required' => true,
						),
						'redirect_to' => array(
							'required' => false,
						),
						'send'        => array(
							'required' => false,
						),
						'qr'          => array(
							'required' => false,
							'type'     => 'boolean',
						),
						'qr_img'      => array(
							'required' => false,
							'type'     => 'boolean',
						),
					),
					'permission_callback' => [ $this, 'permission_callback' ],
				),
			)
		);
	}

	/**
	 * Handle the API request.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function handle_api_request( WP_REST_Request $request ) {
		$user_param = $request->get_param( 'user' );
		$user       = $this->get_user_by_param( $user_param );

		if ( is_wp_error( $user ) ) {
			return new WP_REST_Response(
				[
					'code'    => $user->get_error_code(),
					'message' => $user->get_error_message(),
				],
				422
			);
		}

		if ( ! empty( $request->get_param( 'redirect_to' ) ) ) {
			$_POST['redirect_to'] = $request->get_param( 'redirect_to' );
		}

		$login_url = \MagicLogin\Utils\create_login_link( $user );
		$mail_sent = false;

		if ( ! empty( $request->get_param( 'send' ) ) ) {
			$mail_sent = LoginManager::send_login_link( $user, $login_url );
		}

		$response = [
			'link'      => $login_url,
			'mail_sent' => $mail_sent,
		];

		if ( $request->get_param( 'qr' ) ) {
			$response['qr'] = \MagicLogin\QR::get_image_src( $login_url );
		}

		if ( $request->get_param( 'qr_img' ) ) {
			$response['qr_img'] = \MagicLogin\QR::get_img_tag( $login_url );
		}

		return new WP_REST_Response( $response, 200 );
	}

	/**
	 * Permission callback for the API request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool|WP_Error
	 */
	public function permission_callback( WP_REST_Request $request ) {
		$current_user = wp_get_current_user();

		if ( empty( $current_user ) || 0 === $current_user->ID ) {
			return false;
		}

		if ( is_wp_error( $current_user ) ) {
			return false;
		}

		$user_param = $request['user'];
		$user       = $this->get_user_by_param( $user_param );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'missing_user', esc_html__( 'No account matches the given user.', 'magic-login' ), array( 'status' => 422 ) );
		}

		return current_user_can( 'edit_user', $user->ID );
	}

	/**
	 * Get user by login or email, respecting MAGIC_LOGIN_USERNAME_ONLY definition.
	 *
	 * @param string $user_param User login or email.
	 *
	 * @return \WP_User|WP_Error
	 */
	private function get_user_by_param( $user_param ) {
		if ( is_numeric( $user_param ) ) {
			$user = get_user_by( 'id', $user_param );
		} else {
			$user = get_user_by( 'login', $user_param );
		}

		if ( ! $user && ( ! defined( 'MAGIC_LOGIN_USERNAME_ONLY' ) || false === MAGIC_LOGIN_USERNAME_ONLY ) ) {
			if ( strpos( $user_param, '@' ) !== false ) {
				$user = get_user_by( 'email', $user_param );
			}
		}

		if ( SmsService::is_sms_login_enabled() ) {
			// Check if the input is a phone number
			if ( ! $user && preg_match( '/^\+?[1-9]\d{1,14}$/', $user_param ) ) {
				$phone_number = sanitize_phone_number( $user_param );
				$user         = SmsService::get_user_by_phone_number( $phone_number );
			}
		}

		if ( ! $user ) {
			return new WP_Error( 'missing_user', esc_html__( 'No account matches the given user.', 'magic-login' ) );
		}

		return $user;
	}
}
