<?php
/**
 * Uninstall Magic Login
 * Deletes all plugin related data and configurations
 *
 * @package MagicLogin
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// don't perform uninstallation routine if free version is active
if ( defined( 'MAGIC_LOGIN_PLUGIN_FILE' ) ) {
	return;
}

require_once 'plugin.php';

// delete plugin settings
delete_option( \MagicLogin\Constants\SETTING_OPTION );
delete_option( \MagicLogin\Constants\DB_VERSION_OPTION_NAME );
delete_site_option( \MagicLogin\Constants\SETTING_OPTION );
delete_site_option( \MagicLogin\Constants\DB_VERSION_OPTION_NAME );

// clean-up tokens
MagicLogin\Utils\delete_all_tokens();

// clean-up scheduled cron
wp_clear_scheduled_hook( \MagicLogin\Constants\CRON_HOOK_NAME );
