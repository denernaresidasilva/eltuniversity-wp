<?php
/**
 * Settings page
 *
 * @package MagicLogin\Admin
 */

use function MagicLogin\Admin\Dashboard\maybe_display_license_message;
use function MagicLogin\Utils\get_license_info;
use function MagicLogin\Utils\get_license_status_message;
use function MagicLogin\Utils\get_ttl_with_interval;
use function MagicLogin\Utils\get_doc_url;
use function MagicLogin\Utils\delete_all_tokens;
use function MagicLogin\Utils\get_allowed_intervals;
use function MagicLogin\Utils\get_license_key;
use function MagicLogin\Utils\mask_string;
use const MagicLogin\Constants\LICENSE_ENDPOINT;
use const MagicLogin\Constants\LICENSE_INFO_TRANSIENT;
use const MagicLogin\Constants\LICENSE_KEY_OPTION;
use const MagicLogin\Constants\SETTING_OPTION;


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$settings     = \MagicLogin\Utils\get_settings();
$license_info = get_license_info();

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found
// phpcs:disable Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed
// phpcs:disable WordPress.WP.I18n.MissingTranslatorsComment

?>



<?php maybe_display_license_message(); ?>

<form method="post" action="" enctype="multipart/form-data" id="magic-login-settings-form">
	<?php wp_nonce_field( 'magic_login_settings', 'magic_login_settings' ); ?>
	<section class="sui-row-with-sidenav">

		<!-- TAB: Regular -->
		<div class="sui-box" data-tab="basic-options">

			<div class="sui-box-header">
				<h2 class="sui-box-title">
					<?php esc_html_e( 'Settings', 'magic-login' ); ?>
				</h2>
				<div class="sui-actions-right sui-hidden-important" style="display: none;">
					<button type="submit" class="sui-button sui-button-blue" id="magic-login-save-settings-top" data-msg="">
						<i class="sui-icon-save" aria-hidden="true"></i>
						<?php esc_html_e( 'Update settings', 'magic-login' ); ?>
					</button>
				</div>
			</div>

			<div class="sui-box-body sui-upsell-items">

				<div class="sui-tabs sui-padding-right sui-padding-left">
					<div role="tablist" class="sui-tabs-menu magic-login-main-tab-nav" style="border-top:none;">
						<button type="button" role="tab" id="login__tab" class="sui-tab-item magic-login-main-tab-item active" aria-controls="login_content" aria-selected="true">
							<?php esc_html_e( 'Login', 'magic-login' ); ?>
						</button>

						<button type="button" role="tab" id="registration__tab" class="sui-tab-item magic-login-main-tab-item" aria-controls="registration__content" aria-selected="false" tabindex="-1">
							<?php esc_html_e( 'Registration', 'magic-login' ); ?>
						</button>

						<button type="button" role="tab" id="spam_protection__tab" class="sui-tab-item magic-login-main-tab-item" aria-controls="spam_protection__content" aria-selected="false" tabindex="-1">
							<?php esc_html_e( 'Spam Protection', 'magic-login' ); ?>
						</button>
						<button type="button" role="tab" id="sms__tab" class="sui-tab-item magic-login-main-tab-item" aria-controls="sms__content" aria-selected="false" tabindex="-1">
							<?php esc_html_e( 'SMS', 'magic-login' ); ?>
						</button>
						<button type="button" role="tab" id="tools__tab" class="sui-tab-item magic-login-main-tab-item" aria-controls="tools__content" aria-selected="false" tabindex="-1">
							<?php esc_html_e( 'Tools', 'magic-login' ); ?>
						</button>
						<?php if ( ! defined( 'MAGIC_LOGIN_LICENSE_KEY' ) ) : ?>
						<button type="button" role="tab" id="license__tab" class="sui-tab-item magic-login-main-tab-item" aria-controls="license__content" aria-selected="false" tabindex="-1">
							<?php esc_html_e( 'License', 'magic-login' ); ?>
						</button>
						<?php endif; ?>
					</div>

					<div class="sui-tabs-content">
						<?php require __DIR__ . '/tabs/login.php'; ?>
						<?php require __DIR__ . '/tabs/registration.php'; ?>
						<?php require __DIR__ . '/tabs/spam-protection.php'; ?>
						<?php require __DIR__ . '/tabs/sms.php'; ?>
						<?php require __DIR__ . '/tabs/tools.php'; ?>
						<?php if ( ! defined( 'MAGIC_LOGIN_LICENSE_KEY' ) ) : ?>
							<?php require __DIR__ . '/tabs/license.php'; ?>
						<?php endif; ?>
					</div>

				</div>

				<div class="sui-box-footer">
					<div class="sui-actions-left">
						<button type="submit" class="sui-button sui-button-blue" id="magic-login-save-settings" data-msg="">
							<i class="sui-icon-save" aria-hidden="true"></i>
							<?php esc_html_e( 'Update settings', 'magic-login' ); ?>
						</button>
					</div>
				</div>

			</div>
	</section>

</form>
