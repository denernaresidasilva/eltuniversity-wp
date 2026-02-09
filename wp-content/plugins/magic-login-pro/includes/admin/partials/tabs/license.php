<?php
/**
 * License Tab
 *
 * @package   MagicLoginPro
 */

use function MagicLogin\Admin\Dashboard\maybe_display_license_message;
use function MagicLogin\Utils\get_license_info;
use function MagicLogin\Utils\get_license_status_message;
use function MagicLogin\Utils\get_ttl_with_interval;
use function MagicLogin\Utils\get_doc_url;

use function MagicLogin\Utils\get_allowed_intervals;
use function MagicLogin\Utils\get_license_key;
use function MagicLogin\Utils\mask_string;

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<div role="tabpanel" tabindex="0" id="license__content" class="sui-tab-content magic-login-main-tab-content" aria-labelledby="license__tab">
	<!-- License Key -->

		<div class="sui-box-settings-row">
			<div class="sui-box-settings-col-1">
				<span class="sui-settings-label" id="license_key_label"><?php esc_html_e( 'License Key', 'magic-login' ); ?></span>
			</div>

			<div class="sui-box-settings-col-2">
				<div class="sui-form-field">
					<input
						name="license_key"
						id="license_key"
						class="sui-form-control sui-field-has-suffix"
						aria-labelledby="license_key_label"
						type="text"
						value="<?php echo esc_attr( mask_string( get_license_key(), 5 ) ); ?>"
					/>
					<span class="sui-field-suffix">
						<?php if ( false !== $license_info && 'valid' === $license_info['license_status'] ) : ?>
							<input type="submit" class="sui-button" name="magic_login_license_deactivate" id="magic-login-save-settings-license-deactivate" value="<?php esc_attr_e( 'Deactivate ', 'magic-login' ); ?>" />
						<?php else : ?>
							<input type="submit" class="sui-button" name="magic_login_license_activate" id="magic-login-save-settings-license-activate" value="<?php esc_attr_e( 'Activate ', 'magic-login' ); ?>" />
						<?php endif; ?>
					</span>
					<span class="sui-description"><?php echo esc_html( get_license_status_message() ); ?></span>
				</div>
			</div>
		</div>
</div>
