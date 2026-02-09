<?php
/**
 * Shortcode functionality
 *
 * @package MagicLogin
 */

namespace MagicLogin\Shortcode;

use MagicLogin\CodeLogin;
use MagicLogin\LoginManager;
use MagicLogin\QR;
use MagicLogin\SmsService;
use function MagicLogin\Core\style_url;
use function MagicLogin\Login\process_login_request;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
/**
 * Default setup routine
 *
 * @return void
 * @since 1.1
 */
function setup() {
	add_shortcode( 'magic_login_form', __NAMESPACE__ . '\\shortcode_login_form' );
	add_filter( 'magic_login_redirect', __NAMESPACE__ . '\\maybe_shortcode_redirect', 99, 2 );
	add_shortcode( 'magic_login_qr', __NAMESPACE__ . '\\shortcode_qr' );
}

/**
 * This form needs to be compatible with various themes as much as possible
 * not like the form in login.php which designed to fit on the standard login screen
 *
 * @param array $shortcode_atts Shortcode Attributes
 *
 * @return string|void
 */
function shortcode_login_form( $shortcode_atts ) {
	$atts = shortcode_atts(
		[
			'redirect_to'     => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], // phpcs:ignore
			'hide_logged_in'  => true,
			'error_message'   => '',
			'info_message'    => '',
			'success_message' => '',
			'label'           => '',
			'button_text'     => '',
			'class'           => '',
			'submit_class'    => '',
		],
		$shortcode_atts,
		'magic_login_form'
	);

	// accept on/off yes/no true/false
	$hide_logged_in = filter_var( $atts['hide_logged_in'], FILTER_VALIDATE_BOOLEAN );

	if ( is_user_logged_in() && $hide_logged_in && ! is_preview() ) {
		return;
	}

	ob_start();

	wp_enqueue_style(
		'magic_login_shortcode',
		style_url( 'shortcode-style', 'shortcode' ),
		[],
		MAGIC_LOGIN_PRO_VERSION
	);

	$settings = \MagicLogin\Utils\get_settings();

	if ( $settings['enable_ajax'] ) {
		wp_enqueue_script( 'magic-login-frontend', MAGIC_LOGIN_PRO_URL . 'dist/js/frontend.js', [ 'jquery' ], MAGIC_LOGIN_PRO_VERSION, true );
	}

	$add_redirection_field = empty( $settings['enable_login_redirection'] ) || empty( $settings['enforce_redirection_rules'] );

	/**
	 * Filter the form action URL for the login shortcode.
	 *
	 * @hook magic_login_shortcode_form_action
	 *
	 * @param string $form_action The form action URL. Default is empty.
	 *
	 * @return string The modified form action URL.
	 */
	$form_action = apply_filters( 'magic_login_shortcode_form_action', '' );

	$login_request = LoginManager::process_login_request( $atts );

	$button_text = ! empty( $atts['button_text'] ) ? $atts['button_text'] : esc_html__( 'Send me the link', 'magic-login' );
	?>
	<?php if ( $login_request['show_registration_form'] ) : ?>
		<?php
		$email = isset( $_POST['log'] ) && is_email( wp_unslash( $_POST['log'] ) ) ? sanitize_email( wp_unslash( $_POST['log'] ) ) : '';
		if ( 'auto' === $settings['registration']['mode'] && $settings['registration']['fallback_email_field'] ) {
			$shortcode = sprintf( '[magic_login_registration_form show_name="false" show_terms="false" email="%s"]', $email );
		} else {
			$shortcode = sprintf( '[magic_login_registration_form email="%s"]', $email );
		}
		echo do_shortcode( $shortcode );
		?>
	<?php else : ?>
		<div id="magic-login-shortcode">
			<div class="magic-login-form-header">
				<?php
				$login_errors = $login_request['errors'];

				// error messages
				if ( ! empty( $login_errors ) && is_wp_error( $login_errors ) && $login_errors->has_errors() ) {
					$error_messages = '';

					foreach ( $login_errors->get_error_codes() as $code ) {
						foreach ( $login_errors->get_error_messages( $code ) as $message ) {
							$error_messages .= $message . "<br />\n";
						}
					}

					if ( ! empty( $error_messages ) ) {
						printf( '<div id="login_error">%s</div>', wp_kses_post( $error_messages ) );
					}
				}

				// display info messages
				if ( ! empty( $login_request['info'] ) ) {
					echo wp_kses_post( $login_request['info'] );
				}
				?>

			</div>
			<?php if ( $login_request['code_login'] ) : ?>
				<?php CodeLogin::code_form(); ?>
			<?php elseif ( $login_request['show_form'] ) : ?>
				<form name="magicloginform"
					  class="magic-login-inline-login-form <?php echo esc_attr( $atts['class'] ); ?>"
					  id="magicloginform"
					  action="<?php echo esc_url( $form_action ); ?>"
					  method="post"
					  autocomplete="off"
					  data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					  data-ajax-spinner="<?php echo esc_url( get_admin_url() . 'images/spinner.gif' ); ?>"
					  data-ajax-sending-msg="<?php esc_attr_e( 'Sending...', 'magic-login' ); ?>"
					  data-spam-protection-msg="<?php esc_attr_e( 'Please verify that you are not a robot.', 'magic-login' ); ?>"
				>
					<?php if ( ! empty( $atts['label'] ) ) : ?>
						<label for="user_login"><?php echo wp_kses_post( $atts['label'] ); ?></label>
					<?php elseif ( defined( 'MAGIC_LOGIN_USERNAME_ONLY' ) && MAGIC_LOGIN_USERNAME_ONLY ) : ?>
						<label for="user_login"><?php esc_html_e( 'Username', 'magic-login' ); ?></label>
					<?php elseif ( SmsService::is_sms_login_enabled() ) : ?>
						<label for="user_login"><?php esc_html_e( 'Username, Email Address, or Phone Number', 'magic-login' ); ?></label>
					<?php else : ?>
						<label for="user_login"><?php esc_html_e( 'Username or Email Address', 'magic-login' ); ?></label>
					<?php endif; ?>
					<input type="text" name="log" id="user_login" class="input" value="" size="20" autocapitalize="off" autocomplete="username" required />
					<?php

					/**
					 * Fires following the 'email' field in the login form.
					 *
					 * @hook  magic_login_form
					 * @since 1.0
					 */
					do_action( 'magic_login_form' );

					?>


					<?php if ( $add_redirection_field ) : ?>
						<input type="hidden" name="redirect_to" value="<?php echo esc_url( $atts['redirect_to'] ); ?>" />
					<?php endif; ?>
					<input type="hidden" name="testcookie" value="1" />

					<?php if ( $settings['enable_ajax'] ) : ?>
						<input type="hidden" name="messages[info]" value="<?php echo esc_attr( $atts['info_message'] ); ?>" />
						<input type="hidden" name="messages[error]" value="<?php echo esc_attr( $atts['error_message'] ); ?>" />
						<input type="hidden" name="messages[success]" value="<?php echo esc_attr( $atts['success_message'] ); ?>" />
					<?php endif; ?>

					<input type="submit" name="wp-submit" id="wp-submit" class="magic-login-submit button button-primary button-large <?php echo esc_attr( $atts['submit_class'] ); ?>" value="<?php echo esc_attr( $button_text ); ?>" />
				</form>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
	return ob_get_clean();
}

/**
 * Get magic link redirect url after login
 *
 * @param string   $redirect_url default redirection url
 * @param \WP_User $user         User object
 *
 * @return mixed
 */
function maybe_shortcode_redirect( $redirect_url, $user ) {
	if ( isset( $_REQUEST['redirect_to'] ) && $_REQUEST['redirect_to'] ) { // phpcs:ignore
		$redirect_url = esc_url_raw( $_REQUEST['redirect_to'] ); // phpcs:ignore
	}

	return $redirect_url;
}

/**
 * Generate QR code for the given URL
 *
 * @param array $atts Shortcode attributes
 *
 * @return string
 * @since 2.5
 */
function shortcode_qr( $atts ) {
	$atts = shortcode_atts(
		[
			'url'   => '',
			'width' => '200',
			'scale' => 5,
		],
		$atts
	);

	if ( empty( $atts['url'] ) ) {
		return ''; // silently fail, no URL provided
	}

	$qr_data_uri = QR::generate_image( $atts['url'], (int) $atts['scale'] );

	return sprintf(
		'<img src="%s" width="%d" alt="' . esc_attr__( 'QR Code', 'magic-login' ) . '" />',
		esc_attr( $qr_data_uri ), // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl
		(int) $atts['width']
	);
}
