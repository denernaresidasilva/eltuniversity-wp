<?php
/**
 * Registration functionality
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use function MagicLogin\Core\style_url;
use function MagicLogin\Utils\create_login_link;
use function MagicLogin\Utils\get_email_placeholders_by_user;
use function MagicLogin\Utils\sanitize_phone_number;
use const MagicLogin\Constants\PHONE_NUMBER_META;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

/**
 * Registration class
 */
class Registration {
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
		$settings = \MagicLogin\Utils\get_settings();
		if ( $settings['registration']['enable'] ) {
			add_action( 'wp', [ $this, 'handle_registration_form' ] );
			add_action( 'wp_ajax_nopriv_magic_login_register_user', [ $this, 'ajax_handle_registration_form' ] );
			add_shortcode( 'magic_login_registration_form', [ $this, 'registration_form' ] );
			add_action( 'magic_login_registration_after_user_create', [ $this, 'send_registration_email' ], 10, 2 );
			add_action( 'magic_login_registration_after_user_create', [ $this, 'save_phone_number' ], 10, 2 );
			add_action( 'magic_login_registration_after_user_create', [ $this, 'send_registration_sms' ], 99, 2 );
		}

		if ( $settings['sms']['wp_registration'] ) {
			// Add phone number field to the WordPress registration form.
			add_action( 'register_form', [ $this, 'add_phone_field_to_wp_registration' ] );

			if ( $settings['sms']['wp_require_phone'] ) {
				add_filter( 'registration_errors', [ $this, 'validate_phone_field_on_wp_registration' ], 10, 3 );
			}

			// Save the phone number when the user registers.
			add_action( 'user_register', [ $this, 'save_phone_field_on_wp_registration' ] );
			// Send registration SMS when user registers via standard WordPress registration
			add_action( 'user_register', [ $this, 'send_registration_sms' ], 99, 2 );
		}

	}


	/**
	 * Registration form shortcode
	 *
	 * @param array $atts Shortcode Attributes
	 *
	 * @return false|string
	 */
	public static function registration_form( $atts ) {
		$settings = \MagicLogin\Utils\get_settings();

		$atts = shortcode_atts(
			[
				'show_name'          => $settings['registration']['show_name_field'],
				'require_name'       => $settings['registration']['require_name_field'],
				'show_terms'         => $settings['registration']['show_terms_field'],
				'require_terms'      => $settings['registration']['require_terms_field'],
				'registration_terms' => $settings['registration']['terms'],
				'show_phone'         => $settings['sms']['magic_registration'],
				'require_phone'      => $settings['sms']['magic_registration_require_phone'],
				'button_text'        => esc_html__( 'Register and send login link', 'magic-login' ),
				'email'              => '',
				'info_message'       => esc_html__( 'Continue with registration, and you will get the login link once registration is completed.', 'magic-login' ),
				'success_message'    => esc_html__( 'Registration successful! Please check your inbox for the login link.', 'magic-login' ),
				'hide_logged_in'     => true,
			],
			$atts,
			'magic_login_registration_form'
		);

		$bool_fields = [ 'show_name', 'require_name', 'show_terms', 'require_terms', 'hide_logged_in', 'require_first_name', 'require_last_name', 'show_phone', 'require_phone' ];

		foreach ( $bool_fields as $field ) {
			if ( isset( $atts[ $field ] ) ) {
				$atts[ $field ] = filter_var( $atts[ $field ], FILTER_VALIDATE_BOOLEAN );
			}
		}

		/**
		 * Filter registration form attributes
		 *
		 * @hook  magic_login_registration_form_shortcode_atts
		 *
		 * @param array $atts Shortcode attributes
		 *
		 * @return array $atts Altered shortcode attributes
		 * @since 2.2
		 */
		$atts = apply_filters( 'magic_login_registration_form_shortcode_atts', $atts );

		// Prepare validation rules
		$validation_rules = [
			'require_first_name' => $atts['show_name'] && $atts['require_name'],
			'require_last_name'  => $atts['show_name'] && $atts['require_name'],
			'require_terms'      => $atts['show_terms'] && $atts['require_terms'],
			'require_phone'      => $atts['show_phone'] && $atts['require_phone'],
		];

		if ( isset( $atts['require_first_name'] ) ) {
			$validation_rules['require_first_name'] = $atts['require_first_name'];
		}

		if ( isset( $atts['require_last_name'] ) ) {
			$validation_rules['require_last_name'] = $atts['require_last_name'];
		}

		/**
		 * Filter validation rules for registration form
		 *
		 * @hook  magic_login_registration_form_validation_rules
		 *
		 * @param array $validation_rules Validation rules
		 *
		 * @since 2.2
		 */
		$validation_rules = apply_filters( 'magic_login_registration_form_validation_rules', $validation_rules );

		// Generate a hash of the validation rules
		$secret_key      = wp_salt();
		$validation_hash = hash_hmac( 'sha256', wp_json_encode( $validation_rules ), $secret_key );

		$hide_logged_in = filter_var( $atts['hide_logged_in'], FILTER_VALIDATE_BOOLEAN );

		if ( is_user_logged_in() && $hide_logged_in && ! is_preview() ) {
			return;
		}

		wp_enqueue_style(
			'magic_login_shortcode',
			style_url( 'shortcode-style', 'shortcode' ),
			[],
			MAGIC_LOGIN_PRO_VERSION
		);

		if ( $settings['enable_ajax'] ) {
			wp_enqueue_script( 'magic-login-frontend', MAGIC_LOGIN_PRO_URL . 'dist/js/frontend.js', [ 'jquery' ], MAGIC_LOGIN_PRO_VERSION, true );
		}

		ob_start();

		$first_name          = isset( $_GET['first_name'] ) ? sanitize_text_field( wp_unslash( $_GET['first_name'] ) ) : ''; // phpcs:ignore
		$last_name           = isset( $_GET['last_name'] ) ? sanitize_text_field( wp_unslash( $_GET['last_name'] ) ) : ''; // phpcs:ignore
		$email               = isset( $_GET['email'] ) ? sanitize_email( wp_unslash( $_GET['email'] ) ) : $atts['email'];  // phpcs:ignore
		$phone               = isset( $_GET[ PHONE_NUMBER_META ] ) ? sanitize_text_field( wp_unslash( $_GET[ PHONE_NUMBER_META ] ) ) : '';  // phpcs:ignore
		$registration_status = isset( $_GET['magic-registration'] ) ? sanitize_text_field( wp_unslash( $_GET['magic-registration'] ) ) : ''; // phpcs:ignore

		// when registration is successful don't show the form
		if ( 'success' === $registration_status && isset( $_GET['message'] ) ) { // phpcs:ignore
			echo '<div id="magic-login-register"><div class="registration_result"><div class="success">' . esc_html( sanitize_text_field( wp_unslash( $_GET['message'] ) ) ) . '</div></div></div>'; // phpcs:ignore

			return ob_get_clean();
		}

		?>
		<div id="magic-login-register">
			<div class="registration_result">
				<?php
				if ( 'error' === $registration_status && isset( $_GET['message'] ) ) : // phpcs:ignore
					echo '<div class="error">' . esc_html( sanitize_text_field( wp_unslash( $_GET['message'] ) ) ) . '</div>'; // phpcs:ignore
				elseif ( ! empty( $atts['info_message'] ) ) :
					echo '<div class="info">' . esc_html( sanitize_text_field( wp_unslash( $atts['info_message'] ) ) ) . '</div>'; // phpcs:ignore
				endif;
				?>
			</div>

			<form
				id="magic_login_registration_form"
				action=""
				method="post"
				data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
				data-ajax-spinner="<?php echo esc_url( get_admin_url() . 'images/spinner.gif' ); ?>"
				data-ajax-sending-msg="<?php esc_attr_e( 'Processing your registration, please wait...', 'magic-login' ); ?>"
				data-spam-protection-msg="<?php esc_attr_e( 'Please verify that you are not a robot.', 'magic-login' ); ?>"
			>
				<?php
				/**
				 * Fires at the start of the registration form
				 *
				 * @hook  magic_login_registration_form_start
				 * @since 2.2
				 */
				do_action( 'magic_login_registration_form_start' );
				?>

				<?php if ( filter_var( $atts['show_name'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
					<div class="form-group">
						<p>
							<label for="first_name"><?php esc_html_e( 'First Name', 'magic-login' ); ?></label>
							<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" <?php echo( ! empty( $validation_rules['require_first_name'] ) ? 'required' : '' ); ?> >
						</p>
						<p>
							<label for="last_name"><?php esc_html_e( 'Last Name', 'magic-login' ); ?></label>
							<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" <?php echo( ! empty( $validation_rules['require_last_name'] ) ? 'required' : '' ); ?> >
						</p>
					</div>
				<?php endif; ?>
				<p>
					<label for="email"><?php esc_html_e( 'Email', 'magic-login' ); ?></label>
					<input type="email" name="email" id="email" value="<?php echo esc_attr( $email ); ?>" required>
				</p>

				<?php if ( filter_var( $atts['show_phone'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
					<p>
						<label for="<?php echo esc_attr( PHONE_NUMBER_META ); ?>">
							<?php esc_html_e( 'Phone Number', 'magic-login' ); ?>
						</label>
						<input type="text" name="<?php echo esc_attr( PHONE_NUMBER_META ); ?>" id="<?php echo esc_attr( PHONE_NUMBER_META ); ?>" value="<?php echo esc_attr( $phone ); ?>" <?php echo( ! empty( $validation_rules['require_phone'] ) ? 'required' : '' ); ?> >
					</p>
				<?php endif; ?>

				<?php
				/**
				 * Fires in the middle of the registration form
				 *
				 * @hook  magic_login_registration_form_middle
				 * @since 2.2
				 */
				do_action( 'magic_login_registration_form_middle' );
				?>
				<?php if ( filter_var( $atts['show_terms'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
					<div class="checkbox-group magic-login-registration-terms">
						<input type="checkbox" name="registration_terms" id="registration_terms" <?php echo( ! empty( $validation_rules['require_terms'] ) ? 'required' : '' ); ?> >
						<label for="registration_terms"><?php echo wp_kses_post( $atts['registration_terms'] ); ?></label>
					</div>
				<?php endif; ?>
				<?php
				/**
				 * Fires before the submit button in the registration form
				 *
				 * @hook  magic_login_registration_form_before_submit
				 * @since 2.2
				 */
				do_action( 'magic_login_registration_form_before_submit' );
				?>
				<p>
					<input type="hidden" name="action" value="magic_login_register_user">
					<input type="hidden" name="success_message" value="<?php echo esc_attr( $atts['success_message'] ); ?>">
					<input type="hidden" name="security" value="<?php echo esc_attr( wp_create_nonce( 'ajax-registration-nonce' ) ); ?>">
					<input type="hidden" name="validation_rules" value="<?php echo esc_attr( base64_encode( wp_json_encode( $validation_rules ) ) ); // phpcs:ignore ?>">
					<input type="hidden" name="validation_hash" value="<?php echo esc_attr( $validation_hash ); ?>">
					<input type="submit" name="submit_registration" value="<?php echo esc_attr( $atts['button_text'] ); ?>" class="magic-login-submit">
				</p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Handle registration form submission
	 *
	 * @return void
	 */
	public function handle_registration_form() {
		if ( isset( $_POST['action'] ) && 'magic_login_register_user' === $_POST['action'] ) {
			check_admin_referer( 'ajax-registration-nonce', 'security' );
			$this->process_registration_form( false );
		}
	}

	/**
	 * Handle registration form submission via AJAX
	 *
	 * @return void
	 */
	public function ajax_handle_registration_form() {
		check_ajax_referer( 'ajax-registration-nonce', 'security' );
		$this->process_registration_form( true );
	}

	/**
	 * Process registration form
	 *
	 * @param bool $is_ajax Whether the form is submitted via AJAX
	 *
	 * @return void
	 */
	public function process_registration_form( $is_ajax = false ) {
		// Get validation rules and hash from the form submission
		$validation_rules = isset( $_POST['validation_rules'] ) ? json_decode( base64_decode( sanitize_text_field( wp_unslash( $_POST['validation_rules'] ) ) ), true ) : ''; // phpcs:ignore
		$validation_hash  = isset( $_POST['validation_hash'] ) ? sanitize_text_field( wp_unslash( $_POST['validation_hash'] ) ) : '';

		// Generate the hash again using the same secret key
		$secret_key    = wp_salt();
		$expected_hash = hash_hmac( 'sha256', wp_json_encode( $validation_rules ), $secret_key );

		// Verify the hash
		if ( $validation_hash !== $expected_hash ) {
			$this->handle_registration_error(
				esc_html__( 'Validation failed: invalid rules.', 'magic-login' ),
				$is_ajax
			);
		}

		$user_data = [
			'first_name'      => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'       => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'email'           => isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '',
			PHONE_NUMBER_META => isset( $_POST[ PHONE_NUMBER_META ] )
				? sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) )
				: '', // phpcs:ignore
		];

		$terms = isset( $_POST['registration_terms'] );

		/**
		 * Fires before processing the registration form
		 *
		 * @hook  magic_login_registration_before_process
		 *
		 * @param bool   $is_ajax    Is the request AJAX?
		 * @param string $first_name First name
		 * @param string $last_name  Last name
		 * @param string $email      Email address
		 * @param bool   $terms      Terms accepted
		 * @param string $phone      Phone number
		 */
		do_action( 'magic_login_registration_before_process', $is_ajax, $user_data['first_name'], $user_data['last_name'], $user_data['email'], $terms, $user_data[ PHONE_NUMBER_META ] );

		// Validation Checks
		$validation_checks = [
			'require_terms'      => [
				'value'   => ! $terms,
				'message' => esc_html__( 'You must accept the Terms of Service.', 'magic-login' ),
			],
			'require_first_name' => [
				'value'   => empty( $user_data['first_name'] ),
				'message' => esc_html__( 'You must enter your first name.', 'magic-login' ),
			],
			'require_last_name'  => [
				'value'   => empty( $user_data['last_name'] ),
				'message' => esc_html__( 'You must enter your last name.', 'magic-login' ),
			],
			'require_phone'      => [
				'value'   => empty( $user_data[ PHONE_NUMBER_META ] ),
				'message' => esc_html__( 'You must enter your phone number.', 'magic-login' ),
			],
		];

		foreach ( $validation_checks as $rule => $check ) {
			if ( ! empty( $validation_rules[ $rule ] ) && $check['value'] ) {
				$this->handle_registration_error( $check['message'], $is_ajax, $user_data );
			}
		}

		// Validate phone number format (E.164)
		if ( ! empty( $user_data[ PHONE_NUMBER_META ] ) && ! preg_match( '/^\+?[1-9]\d{1,14}$/', $user_data[ PHONE_NUMBER_META ] ) ) {
			$this->handle_registration_error(
				esc_html__( 'Please enter a valid phone number.', 'magic-login' ),
				$is_ajax,
				$user_data
			);
		}

		// Check if phone number is already registered
		if ( ! empty( $user_data[ PHONE_NUMBER_META ] ) && SmsService::is_phone_number_taken( $user_data[ PHONE_NUMBER_META ] ) ) {
			$this->handle_registration_error(
				esc_html__( 'Unable to process your request. Please try a different phone number.', 'magic-login' ),
				$is_ajax,
				$user_data
			);
		}

		// Email validation
		if ( ! is_email( $user_data['email'] ) || email_exists( $user_data['email'] ) ) {
			$this->handle_registration_error(
				esc_html__( 'Invalid email address or email already exists.', 'magic-login' ),
				$is_ajax,
				$user_data
			);
		}

		// Register user
		$userdata = [
			'first_name' => $user_data['first_name'],
			'last_name'  => $user_data['last_name'],
			'user_email' => $user_data['email'],
			'user_login' => $user_data['email'],
			'user_pass'  => wp_generate_password(),
		];

		$user_id = self::register_user( $userdata );

		if ( ! is_wp_error( $user_id ) ) {
			$success_message = esc_html__( 'Registration successful! Please check your inbox for the login link.', 'magic-login' );
			if ( isset( $_POST['success_message'] ) ) {
				$success_message = sanitize_text_field( wp_unslash( $_POST['success_message'] ) );
			}

			$settings = \MagicLogin\Utils\get_settings();

			$redirect_url = $settings['registration']['redirection_url'];

			/**
			 * Filter registration redirection URL
			 *
			 * @hook  magic_login_registration_redirect_url
			 *
			 * @param string $redirect_url Redirection URL
			 * @param int    $user_id      User ID
			 * @param array  $userdata     User data
			 *
			 * @since 2.5
			 */
			$redirect_url    = apply_filters( 'magic_login_registration_redirect_url', $redirect_url, $user_id, $userdata );
			$redirect_url    = wp_validate_redirect( $redirect_url, site_url() );
			$should_redirect = $settings['registration']['enable_redirection'] && ! empty( $redirect_url );

			/**
			 * Filter registration success message
			 *
			 * @hook  magic_login_registration_success_message
			 *
			 * @param string $success_message Success message
			 * @param int    $user_id         User ID
			 * @param array  $userdata        User data
			 *
			 * @return string
			 * @since 2.2
			 */
			$success_message = apply_filters( 'magic_login_registration_success_message', $success_message, $user_id, $userdata );

			if ( $is_ajax ) {
				if ( $should_redirect ) {
					wp_send_json_success(
						array(
							'redirect_url'    => $redirect_url,
							'success_message' => $success_message,
						)
					);
					exit;
				}

				wp_send_json_success( $success_message );
			} else {
				$redirect_url = $should_redirect ? $redirect_url : wp_get_referer();

				wp_safe_redirect(
					add_query_arg(
						array(
							'magic-registration' => 'success',
							'message'            => rawurlencode( $success_message ),
						),
						$redirect_url
					)
				);
				exit;
			}
		} else {
			$this->handle_registration_error(
				esc_html__( 'Registration failed:', 'magic-login' ) . ' ' . $user_id->get_error_message(),
				$is_ajax,
				$user_data
			);
		}
	}

	/**
	 * Register a new user
	 *
	 * @param array $userdata User data
	 *
	 * @return int|\WP_Error
	 */
	public static function register_user( $userdata ) {
		$settings = \MagicLogin\Utils\get_settings();

		// Check if domain restriction is enabled 👀
		if ( ! empty( $settings['registration']['enable_domain_restriction'] ) ) {
			$allowed_domains = array_map( 'trim', explode( "\n", $settings['registration']['allowed_domains'] ) );

			/**
			 * Filter allowed domains for registration
			 *
			 * @hook   magic_login_registration_allowed_domains
			 *
			 * @param  array $allowed_domains Allowed domains
			 *
			 * @return array $allowed_domains Allowed domains
			 * @since  2.4
			 */
			$allowed_domains = apply_filters( 'magic_login_registration_allowed_domains', $allowed_domains );

			// Normalize allowed domains to lowercase
			$allowed_domains = array_map( 'strtolower', $allowed_domains );

			if ( ! self::is_domain_allowed( $userdata['user_email'], $allowed_domains ) ) {
				return new \WP_Error(
					'registration_domain_restricted',
					esc_html__( 'Registration is restricted to specific email domains.', 'magic-login' )
				);
			}
		}

		/**
		 * Fires before user creation
		 *
		 * @hook   magic_login_registration_before_user_create
		 *
		 * @param  array $userdata User data.
		 *
		 * @since  2.2
		 */
		do_action( 'magic_login_registration_before_user_create', $userdata );

		/**
		 * Filter user data before registration
		 *
		 * @hook   magic_login_registration_user_data
		 *
		 * @param  array $userdata User data
		 *
		 * @return array User data
		 * @since  2.2
		 */
		$userdata = apply_filters( 'magic_login_registration_user_data', $userdata );

		$user_id = wp_insert_user( $userdata );

		if ( ! is_wp_error( $user_id ) ) {
			$user = get_user_by( 'id', $user_id );

			if ( ! empty( $settings['registration']['role'] ) ) {
				$user->set_role( $settings['registration']['role'] );
			} else {
				$user->set_role( get_option( 'default_role' ) );
			}

			clean_user_cache( $user_id );

			/**
			 * Fires after user creation
			 *
			 * @hook   magic_login_registration_after_user_create
			 *
			 * @param  int $user_id User ID.
			 * @param  array $userdata User data.
			 *
			 * @since  2.2
			 */
			do_action( 'magic_login_registration_after_user_create', $user_id, $userdata );
		}

		return $user_id;
	}

	/**
	 * Send registration email
	 *
	 * @param int   $user_id  User ID
	 * @param array $userdata User data
	 *
	 * @return void
	 */
	public function send_registration_email( $user_id, $userdata ) {
		$settings = \MagicLogin\Utils\get_settings();

		/**
		 * Filter whether to send registration email
		 *
		 * @hook   magic_login_registration_send_email
		 *
		 * @param bool  $send_email Whether to send registration email.
		 * @param int   $user_id    User ID.
		 * @param array $userdata   User data.
		 *
		 * @return bool Whether to send registration email.
		 * @since  2.2
		 */
		$should_send_email = apply_filters( 'magic_login_registration_send_email', $settings['registration']['send_email'], $user_id, $userdata );

		if ( ! $should_send_email ) {
			return;
		}

		if ( empty( $settings['registration']['email_content'] ) ) {
			return;
		}

		/**
		 * Filter registration email subject and content
		 *
		 * @hook  magic_login_registration_email_subject
		 *
		 * @param string $subject Email subject
		 * @param int $user_id User ID
		 * @param array $userdata User data
		 *
		 * @since 2.2
		 */
		$subject = apply_filters( 'magic_login_registration_email_subject', __( $settings['registration']['email_subject'], 'magic-login' ), $user_id, $userdata ); // phpcs:ignore

		/**
		 * Filter registration email content
		 *
		 * @hook  magic_login_registration_email_content
		 *
		 * @param string $content  Email content
		 * @param int    $user_id  User ID
		 * @param array  $userdata User data
		 *
		 * @since 2.2
		 */
		$content = apply_filters( 'magic_login_registration_email_content', __( $settings['registration']['email_content'], 'magic-login' ), $user_id, $userdata ); // phpcs:ignore

		$user       = get_user_by( 'id', $user_id );
		$login_link = create_login_link( $user );

		$placeholders = get_email_placeholders_by_user( $user, $login_link );

		$email_subject = str_replace( array_keys( $placeholders ), $placeholders, $subject );
		$email_content = str_replace( array_keys( $placeholders ), $placeholders, $content );

		add_filter( 'magic_login_add_auto_login_link', '__return_false' ); // Disable auto login link in email

		/**
		 * Filter email headers
		 *
		 * @hook  magic_login_email_headers
		 *
		 * @param array $headers Email headers
		 */
		$headers = apply_filters( 'magic_login_email_headers', array( 'Content-Type: text/html; charset=UTF-8' ) );

		wp_mail(
			$userdata['user_email'],
			$email_subject,
			$email_content,
			$headers
		);
	}

	/**
	 * Send registration SMS
	 *
	 * @param int   $user_id  User ID
	 * @param array $userdata User data
	 *
	 * @return void
	 */
	public function send_registration_sms( $user_id, $userdata ) {
		global $magic_login_sent_registration_sms;

		if ( $magic_login_sent_registration_sms ) {
			return; // Prevent sending SMS multiple times
		}

		$settings = \MagicLogin\Utils\get_settings();

		/**
		 * Filter whether to send registration SMS
		 *
		 * @hook   magic_login_registration_send_sms
		 *
		 * @param bool  $send_sms Whether to send registration SMS.
		 * @param int   $user_id  User ID.
		 * @param array $userdata User data.
		 *
		 * @return bool Whether to send registration SMS.
		 * @since  2.6
		 */
		$should_send_sms = apply_filters(
			'magic_login_registration_send_sms',
			$settings['sms']['send_registration_message'],
			$user_id,
			$userdata
		);

		if ( ! $should_send_sms ) {
			return;
		}

		// Get user's phone number
		$phone_number = get_user_meta( $user_id, PHONE_NUMBER_META, true );

		if ( empty( $phone_number ) ) {
			return;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}

		// Get message template from settings
		$message = __( $settings['sms']['registration_message'], 'magic-login' ); // phpcs:ignore

		$login_link = create_login_link( $user );

		$placeholders = get_email_placeholders_by_user( $user, $login_link );

		$sms_content = str_replace( array_keys( $placeholders ), $placeholders, $message );

		// Send SMS
		$sms_service = new SmsService();
		$sms_service->send( $phone_number, $sms_content );
		$magic_login_sent_registration_sms = true; // Mark SMS as sent to prevent duplicates
	}

	/**
	 * Add phone number field to the WordPress registration form.
	 *
	 * @return void
	 * @since 2.4
	 */
	public function add_phone_field_to_wp_registration() {
		?>
		<p>
			<label for="<?php echo esc_attr( PHONE_NUMBER_META ); ?>">
				<?php esc_html_e( 'Phone Number', 'magic-login' ); ?>
			</label>
			<input type="text"
				   name="<?php echo esc_attr( PHONE_NUMBER_META ); ?>"
				   id="<?php echo esc_attr( PHONE_NUMBER_META ); ?>"
				   class="input"
				   value="<?php echo isset( $_POST[ PHONE_NUMBER_META ] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) ) : ''; ?>"
			/>
		</p>
		<?php
	}

	/**
	 * Validate the phone number field when a new user registers.
	 *
	 * @param \WP_Error $errors               WP_Error object.
	 * @param string    $sanitized_user_login The username after it has been sanitized.
	 * @param string    $user_email           The user email address.
	 *
	 * @return \WP_Error
	 * @since 2.4
	 */
	public function validate_phone_field_on_wp_registration( $errors, $sanitized_user_login, $user_email ) {
		$settings = \MagicLogin\Utils\get_settings();
		if ( $settings['wp_require_phone'] && empty( $_POST[ PHONE_NUMBER_META ] ) ) {
			$errors->add( 'phone_number_error', __( '<strong>ERROR</strong>: A phone number is required.', 'magic-login' ) );

			return $errors;
		}

		$phone_number = sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) );

		if ( ! preg_match( '/^\+?[1-9]\d{1,14}$/', $phone_number ) ) {
			$errors->add( 'phone_number_error', __( '<strong>ERROR</strong>: Invalid phone number format.', 'magic-login' ) );
		} elseif ( SmsService::is_phone_number_taken( $phone_number ) ) {
			// DO NOT EXPOSE REGISTRATION STATUS to avoid enumeration attacks
			$errors->add( 'phone_number_error', __( '<strong>ERROR</strong>: Unable to process your request. Please try a different phone number.', 'magic-login' ) );
		}

		return $errors;
	}

	/**
	 * Save the phone number field when a new user registers.
	 *
	 * @param int $user_id The ID of the new user.
	 *
	 * @since 2.4
	 */
	public function save_phone_field_on_wp_registration( $user_id ) {
		if ( ! empty( $_POST[ PHONE_NUMBER_META ] ) ) {
			$phone_number = sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) );
			update_user_meta( $user_id, PHONE_NUMBER_META, $phone_number );
		}
	}

	/**
	 * Handle registration errors by either returning an AJAX response or redirecting.
	 *
	 * @param string $error_message Error message to display.
	 * @param bool   $is_ajax Whether the request is an AJAX request.
	 * @param array  $user_data User data for redirection query args.
	 */
	private function handle_registration_error( $error_message, $is_ajax, $user_data = [] ) {
		if ( $is_ajax ) {
			wp_send_json_error( $error_message );
		} else {
			wp_safe_redirect(
				add_query_arg(
					array_merge(
						[
							'magic-registration' => 'error',
							'message'            => rawurlencode( $error_message ),
						],
						array_map( 'rawurlencode', $user_data ) // Encode user data safely
					),
					wp_get_referer()
				)
			);
			exit;
		}
	}


	/**
	 * Save phone number after user creation.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $userdata User data.
	 */
	public function save_phone_number( $user_id, $userdata ) {
		if ( ! empty( $_POST[ PHONE_NUMBER_META ] ) ) {
			$phone_number = sanitize_phone_number( sanitize_text_field( wp_unslash( $_POST[ PHONE_NUMBER_META ] ) ) );
			update_user_meta( $user_id, PHONE_NUMBER_META, $phone_number );
		}
	}

	/**
	 * Check if the email domain is allowed for registration.
	 *
	 * @param string $email           User's email address.
	 * @param array  $allowed_domains List of allowed domains.
	 *
	 * @return bool
	 */
	private static function is_domain_allowed( $email, $allowed_domains ) {
		if ( empty( $allowed_domains ) ) {
			return true; // No restriction if the allowed domains list is empty.
		}

		$email_domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

		$is_allowed = in_array( $email_domain, array_map( 'strtolower', $allowed_domains ), true );

		/**
		 * Filter to override domain restriction logic dynamically.
		 *
		 * @hook  magic_login_registration_is_domain_allowed
		 *
		 * @param bool   $is_allowed      Whether the email domain is allowed.
		 * @param string $email           The user's email address.
		 * @param array  $allowed_domains List of allowed domains.
		 *
		 * @since 2.4
		 */
		$is_allowed = apply_filters( 'magic_login_registration_is_domain_allowed', $is_allowed, $email, $allowed_domains );

		return $is_allowed;
	}



}
