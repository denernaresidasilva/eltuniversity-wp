<?php
/**
 * Modals for the settings page
 *
 * @package MagicLogin\Admin
 */

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div class="sui-modal sui-modal-lg">
	<div role="dialog"
		 id="magic-login-sms-test"
		 class="sui-modal-content"
		 aria-live="polite"
		 aria-modal="true"
		 aria-labelledby="magic-login-sms-test-title"
		 aria-describedby="magic-login-sms-test-desc"
	>

		<div class="sui-box">

			<button class="sui-screen-reader-text" data-modal-close=""><?php esc_html_e( 'Close', 'magic-login' ); ?></button>

			<div class="sui-box-header">

				<h3 id="magic-login-sms-test-title" class="sui-box-title"><?php esc_html_e( 'Send a Test SMS', 'magic-login' ); ?></h3>

				<button class="sui-button-icon sui-button-float--right" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this modal', 'magic-login' ); ?></span>
				</button>

			</div>

			<div id="magic-login-sms-test-form-wrapper" class="sui-box-body">
				<form id="sms-test-form" method="post">
					<?php wp_nonce_field( 'magic_login_send_test_sms_nonce', 'magic_login_send_test_sms_nonce' ); ?>
					<div class="sui-form-field">
						<label for="magic-login-sms-test-phone" class="sui-label"><?php esc_html_e( 'Phone Number', 'magic-login' ); ?></label>
						<input type="text" id="magic-login-sms-test-phone" name="magic_login_sms_test_phone" class="sui-form-control" placeholder="+1234567890" required>
					</div>
					<div class="sui-form-field">
						<label for="magic-login-sms-test-message" class="sui-label"><?php esc_html_e( 'SMS Content', 'magic-login' ); ?></label>
						<textarea id="magic-login-sms-test-message" name="magic_login_sms_test_message" class="sui-form-control" required></textarea>
					</div>
					<div class="sui-form-field">
						<button type="submit" class="sui-button sui-button-blue"><?php esc_html_e( 'Send Test SMS', 'magic-login' ); ?></button>
					</div>
				</form>
				<div id="sms-response-message" class="sui-form-field"></div>
			</div>
		</div>

	</div>

</div>
