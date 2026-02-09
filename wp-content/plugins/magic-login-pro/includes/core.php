<?php
/**
 * Core plugin functionality.
 *
 * @package MagicLogin
 */

namespace MagicLogin\Core;

use MagicLogin\Updater;
use const MagicLogin\Constants\UPDATE_ENDPOINT;
use function MagicLogin\Utils\get_license_key;
use function MagicLogin\Utils\is_magic_login_settings_screen;
use \WP_Error as WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	add_action( 'init', __NAMESPACE__ . '\\i18n' );
	add_action( 'init', __NAMESPACE__ . '\\init' );
	add_action( 'init', __NAMESPACE__ . '\\setup_updater' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_scripts' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\admin_styles' );
	add_action( 'init', __NAMESPACE__ . '\\register_translation_strings' );

	/**
	 * Fire an action after the magic login core is loaded.
	 *
	 * @hook magic_login_pro_loaded
	 */
	do_action( 'magic_login_pro_loaded' );
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'magic-login' );
	load_textdomain( 'magic-login', WP_LANG_DIR . '/magic-login/magic-login-' . $locale . '.mo' );
	load_plugin_textdomain( 'magic-login', false, plugin_basename( MAGIC_LOGIN_PRO_PATH ) . '/languages/' );
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init() {
	/**
	 * Fire an action after the magic login core is initialized.
	 *
	 * @hook magic_login_pro_init
	 */
	do_action( 'magic_login_pro_init' );
}

/**
 * Setup custom updater
 */
function setup_updater() {
	// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
	$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
	if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
		return;
	}

	// setup the updater
	$updater = new Updater(
		UPDATE_ENDPOINT,
		MAGIC_LOGIN_PRO_PLUGIN_FILE,
		array(
			'version'      => MAGIC_LOGIN_PRO_VERSION,  // current version number
			'license_key'  => get_license_key(),  // license key (used get_option above to retrieve from DB)
			'license_url'  => home_url(), // license domain
			'download_tag' => 'magic-login-pro', // download tag slug
			'beta'         => false,
		)
	);
}

/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts() {
	return [ 'admin', 'frontend', 'shared', 'shortcode', 'block-editor' ];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script  Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|WP_Error URL
 */
function script_url( $script, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in MagicLogin script loader.' );
	}

	return MAGIC_LOGIN_PRO_URL . "dist/js/{$script}.js";

}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context    Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string URL
 */
function style_url( $stylesheet, $context ) {

	if ( ! in_array( $context, get_enqueue_contexts(), true ) ) {
		return new WP_Error( 'invalid_enqueue_context', 'Invalid $context specified in MagicLogin stylesheet loader.' );
	}

	return MAGIC_LOGIN_PRO_URL . "dist/css/{$stylesheet}.css";

}


/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts() {

	if ( ! is_magic_login_settings_screen() ) {
		return;
	}

	wp_enqueue_script(
		'magic_login_admin',
		script_url( 'admin', 'admin' ),
		[ 'jquery' ],
		MAGIC_LOGIN_PRO_VERSION,
		true
	);
}

/**
 * Enqueue styles for admin.
 *
 * @return void
 */
function admin_styles() {

	if ( ! is_magic_login_settings_screen() ) {
		return;
	}

	wp_enqueue_style(
		'magic_login_admin',
		style_url( 'admin-style', 'admin' ),
		[],
		MAGIC_LOGIN_PRO_VERSION
	);

}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate() {
	flush_rewrite_rules();
}

/**
 * Register default translation strings.
 * Technically doing nothing other than registering the default strings for language catalog.
 *
 * @return void
 * @since 2.6
 */
function register_translation_strings() {
	/* translators: Do not translate USERNAME, SITENAME, EXPIRES, EXPIRES_WITH_INTERVAL, MAGIC_LINK, SITENAME, SITEURL: those are placeholders. */
	$email_text = __(
		'Hi {{USERNAME}},

Click and confirm that you want to log in to {{SITENAME}}. This link will expire in {{EXPIRES_WITH_INTERVAL}} and can only be used once:

<a href="{{MAGIC_LINK}}" target="_blank" rel="noreferrer noopener">Log In</a>

Need the link? {{MAGIC_LINK}}


You can safely ignore and delete this email if you do not want to log in.

Regards,
All at {{SITENAME}}
{{SITEURL}}',
		'magic-login'
	);

	// translators: default email_subject message
	__( 'Log in to {{SITENAME}}', 'magic-login' );

	// translators: default email_subject message for registration
	esc_html__( 'Welcome to {{SITENAME}}', 'magic-login' );

	// translators: default registration message
	$email_text = __(
		'Hi there,
<br><br>
Thank you for signing up to {{SITENAME}}! We are excited to have you on board.
<br>
To get started, simply use the magic link below to log in:
<br><br>
<a href="{{MAGIC_LINK}}" target="_blank" rel="noreferrer noopener">Click here to log in</a>
<br><br>
If the button above does not work, you can also copy and paste the following URL into your browser:
<br>
{{MAGIC_LINK}}
<br><br>
We hope you enjoy your experience with us. If you have any questions or need assistance, feel free to reach out.
<br><br>
Regards,<br>
All at {{SITENAME}}<br>
{{SITEURL}}',
		'magic-login'
	);

	// translators: default sms login message
	esc_html__( 'Here is your login code: {{MAGIC_LOGIN_CODE}}. It will expire in {{EXPIRES_WITH_INTERVAL}}.', 'magic-login' );

	// registration message for SMS
	esc_html__( 'Welcome {{FULL_NAME}}! Your account on {{SITENAME}} has been created. You can login here: {{MAGIC_LINK}}.', 'magic-login' );
}
