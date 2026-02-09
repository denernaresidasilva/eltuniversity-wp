<?php
/**
 * Frontend Reset Password - Reset Form
 *
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Read eye toggle setting (defaults to enabled if unset)
$design_options = get_option( 'somfrp_design_settings' );
$somfrp_enable_eye = ( ! isset( $design_options['somfrp_enable_eye_toggle'] ) || $design_options['somfrp_enable_eye_toggle'] === 'on' );

?>
<div id="password-lost-form-wrap">

	<?php if ( ! empty( $errors ) ) : ?>
		<?php if ( is_array( $errors ) ) : ?>
			<?php foreach ( $errors as $error ) : ?>
				<p class="som-password-sent-message som-password-error-message">
					<span><?php echo esc_html( $error ); ?></span>
				</p>
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>

	<form id="resetpasswordform" method="post" class="account-page-form som-pass-strength-form">
		<fieldset>
			<legend><?php echo $form_title; ?></legend>

			<div class="somfrp-lost-pass-form-text">
				<?php echo $reset_text_output; ?>
			</div>

			<div>
				<div>
					<label for="som_new_user_pass"><?php _e( 'New Password', 'frontend-reset-password' ); ?></label>

					<div class="somfrp-password-wrapper" style="position:relative;">
						<input
							name="som_new_user_pass"
							id="som_new_user_pass"
							class="disblock som-password-input som-pass-strength-input"
							type="password"
							pattern="<?php echo get_password_pattern(); ?>"
							required
							autocomplete="new-password"
							aria-label="<?php esc_attr_e( 'New Password', 'frontend-reset-password' ); ?>"
						/>

						<?php if ( $somfrp_enable_eye ) : ?>
							<button
								type="button"
								class="somfrp-eye-toggle"
								aria-label="<?php esc_attr_e( 'Toggle password visibility', 'frontend-reset-password' ); ?>"
								title="<?php esc_attr_e( 'Show/Hide password', 'frontend-reset-password' ); ?>"
								data-target="#som_new_user_pass"
							>

							<svg class="somfrp-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/></svg>
							<svg class="somfrp-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/></svg>

							</button>
						<?php endif; ?>
					</div>

					<?php do_action( 'som_after_change_new_pass_input' ); ?>
				</div>

				<div class="somfrp-password-wrapper">
					<label for="som_new_user_pass_again"><?php _e( 'Re-enter Password', 'frontend-reset-password' ); ?></label>

					<div class="somfrp-password-wrapper" style="position:relative;">
						<input
							name="som_new_user_pass_again"
							id="som_new_user_pass_again"
							class="disblock som-password-input"
							type="password"
							pattern="<?php echo get_password_pattern(); ?>"
							required
							autocomplete="new-password"
							aria-label="<?php esc_attr_e( 'Re-enter Password', 'frontend-reset-password' ); ?>"
						/>

						<?php if ( $somfrp_enable_eye ) : ?>
							<button
								type="button"
								class="somfrp-eye-toggle"
								aria-label="<?php esc_attr_e( 'Toggle password visibility', 'frontend-reset-password' ); ?>"
								title="<?php esc_attr_e( 'Show/Hide password', 'frontend-reset-password' ); ?>"
								data-target="#som_new_user_pass_again"
							>

							<svg class="somfrp-eye" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 96C239.2 96 174.5 132.8 127.4 176.6C80.6 220.1 49.3 272 34.4 307.7C31.1 315.6 31.1 324.4 34.4 332.3C49.3 368 80.6 420 127.4 463.4C174.5 507.1 239.2 544 320 544C400.8 544 465.5 507.2 512.6 463.4C559.4 419.9 590.7 368 605.6 332.3C608.9 324.4 608.9 315.6 605.6 307.7C590.7 272 559.4 220 512.6 176.6C465.5 132.9 400.8 96 320 96zM176 320C176 240.5 240.5 176 320 176C399.5 176 464 240.5 464 320C464 399.5 399.5 464 320 464C240.5 464 176 399.5 176 320zM320 256C320 291.3 291.3 320 256 320C244.5 320 233.7 317 224.3 311.6C223.3 322.5 224.2 333.7 227.2 344.8C240.9 396 293.6 426.4 344.8 412.7C396 399 426.4 346.3 412.7 295.1C400.5 249.4 357.2 220.3 311.6 224.3C316.9 233.6 320 244.4 320 256z"/></svg>
							<svg class="somfrp-eye-off" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M73 39.1C63.6 29.7 48.4 29.7 39.1 39.1C29.8 48.5 29.7 63.7 39 73.1L567 601.1C576.4 610.5 591.6 610.5 600.9 601.1C610.2 591.7 610.3 576.5 600.9 567.2L504.5 470.8C507.2 468.4 509.9 466 512.5 463.6C559.3 420.1 590.6 368.2 605.5 332.5C608.8 324.6 608.8 315.8 605.5 307.9C590.6 272.2 559.3 220.2 512.5 176.8C465.4 133.1 400.7 96.2 319.9 96.2C263.1 96.2 214.3 114.4 173.9 140.4L73 39.1zM236.5 202.7C260 185.9 288.9 176 320 176C399.5 176 464 240.5 464 320C464 351.1 454.1 379.9 437.3 403.5L402.6 368.8C415.3 347.4 419.6 321.1 412.7 295.1C399 243.9 346.3 213.5 295.1 227.2C286.5 229.5 278.4 232.9 271.1 237.2L236.4 202.5zM357.3 459.1C345.4 462.3 332.9 464 320 464C240.5 464 176 399.5 176 320C176 307.1 177.7 294.6 180.9 282.7L101.4 203.2C68.8 240 46.4 279 34.5 307.7C31.2 315.6 31.2 324.4 34.5 332.3C49.4 368 80.7 420 127.5 463.4C174.6 507.1 239.3 544 320.1 544C357.4 544 391.3 536.1 421.6 523.4L357.4 459.2z"/></svg>

							</button>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div class="lostpassword-submit">
				<?php wp_nonce_field( 'somfrp_reset_pass', 'somfrp_nonce' ); ?>
				<input type="hidden" name="submitted" id="submitted" value="true">
				<input type="hidden" name="somfrp_action" id="somfrp_post_action" value="somfrp_reset_pass">
				<button type="submit" id="reset-pass-submit" name="reset-pass-submit" class="button big-btn">
					<?php echo $button_text; ?>
				</button>
			</div>

		</fieldset>
	</form>

	<?php
	// Pass length for live requirement checks
	$min_length = isset( $sec_options['somfrp_pass_length'] ) ? absint( $sec_options['somfrp_pass_length'] ) : 0;
	?>

	<script>
		// Live password requirement checks for the first password field
		document.addEventListener('DOMContentLoaded', function () {
			var passwordInput = document.getElementById('som_new_user_pass');
			if (!passwordInput) return;

			var minLength = <?php echo (int) $min_length; ?>;

			passwordInput.addEventListener('input', function () {
				var val = passwordInput.value;

				var hasLower   = /[a-z]/.test(val);
				var hasUpper   = /[A-Z]/.test(val);
				var hasNumber  = /[0-9]/.test(val);
				var hasSpecial = /[!@#$%^&*_=+]/.test(val);
				var hasLength  = val.length >= minLength;

				updateRequirement('require-lower', hasLower);
				updateRequirement('require-upper', hasUpper);
				updateRequirement('require-number', hasNumber);
				updateRequirement('require-special', hasSpecial);
				updateRequirement('require-length', hasLength);
			});

			function updateRequirement(id, isValid) {
				var el = document.getElementById(id);
				if (el) {
					el.classList.remove('valid', 'invalid');
					el.classList.add(isValid ? 'valid' : 'invalid');
				}
			}
		});

		// Eye toggle logic (works for both fields when enabled)
		document.addEventListener('DOMContentLoaded', function () {
			document.body.addEventListener('click', function (e) {
				var btn = e.target.closest('.somfrp-eye-toggle');
				if (!btn) return;

				var targetSel = btn.getAttribute('data-target');
				if (!targetSel) return;

				var input = document.querySelector(targetSel);
				if (!input) return;

				var isPassword = input.type === 'password';
				input.type = isPassword ? 'text' : 'password';

				btn.classList.toggle('dashicons-visibility', !isPassword);
				btn.classList.toggle('dashicons-hidden', isPassword);
			}, false);
		});
	</script>

</div>
