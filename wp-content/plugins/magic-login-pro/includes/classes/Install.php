<?php
/**
 * Installation functionalities
 *
 * @package MagicLogin
 */

namespace MagicLogin;

use const MagicLogin\Constants\DB_VERSION_OPTION_NAME;
use const MagicLogin\Constants\SETTING_OPTION;

/**
 * Class Install
 */
class Install {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'check_version' ], 5 );
	}

	/**
	 * Return an instance of the current class
	 *
	 * @since 2.1
	 */
	public static function setup() {
		static $instance = false;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Check DB version and run the updater is required.
	 */
	public function check_version() {
		if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			return;
		}

		if ( version_compare( get_option( DB_VERSION_OPTION_NAME ), MAGIC_LOGIN_PRO_DB_VERSION, '<' ) ) {
			$this->install();
			/**
			 * Fires after plugin update.
			 *
			 * @hook  magic_login_pro_updated
			 * @since 2.1
			 */
			do_action( 'magic_login_pro_updated' );
		}
	}

	/**
	 * Perform Installation
	 */
	public function install() {
		if ( ! is_blog_installed() ) {
			return;
		}

		$lock_key = 'magic_login_installing';
		// Check if we are not already running
		if ( $this->has_lock( $lock_key ) ) {
			return;
		}

		// lets set the transient now.
		$this->set_lock( $lock_key );

		if ( MAGIC_LOGIN_IS_NETWORK ) {
			$this->maybe_upgrade_network_wide();
		} else {
			$this->maybe_upgrade();
		}

		$this->remove_lock( $lock_key );
	}

	/**
	 * Upgrade routine for network wide activation
	 */
	public function maybe_upgrade_network_wide() {
		if ( version_compare( get_site_option( DB_VERSION_OPTION_NAME ), MAGIC_LOGIN_PRO_DB_VERSION, '<' ) ) {
			$this->upgrade_21( true );
			$this->upgrade_25( true );
			update_site_option( DB_VERSION_OPTION_NAME, MAGIC_LOGIN_PRO_DB_VERSION );
		}
	}

	/**
	 * Upgrade routine
	 */
	public function maybe_upgrade() {
		if ( version_compare( get_option( DB_VERSION_OPTION_NAME ), MAGIC_LOGIN_PRO_DB_VERSION, '<' ) ) {
			$this->upgrade_21();
			$this->upgrade_25();
			update_option( DB_VERSION_OPTION_NAME, MAGIC_LOGIN_PRO_DB_VERSION, false );
		}
	}


	/**
	 * Check if a lock exists of the upgrade routine
	 *
	 * @param string $lock_name transient name
	 *
	 * @return bool
	 */
	private function has_lock( $lock_name ) {
		if ( MAGIC_LOGIN_IS_NETWORK ) {
			if ( 'yes' === get_site_transient( $lock_name ) ) {
				return true;
			}

			return false;
		}

		if ( 'yes' === get_transient( $lock_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Set the lock
	 *
	 * @param string $lock_name transient name for the lock
	 *
	 * @return bool
	 */
	private function set_lock( $lock_name ) {
		if ( MAGIC_LOGIN_IS_NETWORK ) {
			return set_site_transient( $lock_name, 'yes', MINUTE_IN_SECONDS );
		}

		return set_transient( $lock_name, 'yes', MINUTE_IN_SECONDS );
	}

	/**
	 * Remove lock
	 *
	 * @param string $lock_name transient name for the lock
	 *
	 * @return bool
	 */
	private function remove_lock( $lock_name ) {
		if ( MAGIC_LOGIN_IS_NETWORK ) {
			return delete_site_transient( $lock_name );
		}

		return delete_transient( $lock_name );
	}


	/**
	 * Version 2.1 is shipped with `enforce_redirection_rules` option, which is default for new installations
	 * However, keep the existing logic for the old installations update the setting for the existing installations
	 *
	 * @param bool $network_wide Whether to upgrade network wide or not
	 *
	 * @return void
	 */
	public function upgrade_21( $network_wide = false ) {
		$current_version = $network_wide ? get_site_option( DB_VERSION_OPTION_NAME ) : get_option( DB_VERSION_OPTION_NAME );
		if ( ! version_compare( $current_version, '2.1', '<' ) ) {
			return;
		}

		if ( $network_wide ) {
			$db_option = get_site_option( SETTING_OPTION );
		} else {
			$db_option = get_option( SETTING_OPTION );
		}

		$settings = \MagicLogin\Utils\get_settings();

		if ( false !== $db_option && ! empty( $settings['enable_login_redirection'] ) ) {
			$settings['enforce_redirection_rules'] = false;

			if ( $network_wide ) {
				update_site_option( SETTING_OPTION, $settings );
			} else {
				update_option( SETTING_OPTION, $settings );
			}
		}
	}

	/**
	 * Upgrade routine for version 2.5
	 *
	 * @param bool $network_wide Whether to upgrade network wide or not
	 *
	 * @return void
	 */
	public function upgrade_25( $network_wide = false ) {
		$current_version = $network_wide ? get_site_option( DB_VERSION_OPTION_NAME ) : get_option( DB_VERSION_OPTION_NAME );
		if ( ! version_compare( $current_version, '2.5', '<' ) ) {
			return;
		}

		// Call flush_rewrite_rules to refresh permalinks
		flush_rewrite_rules();

		// Update the database version to 2.5
		if ( $network_wide ) {
			update_site_option( DB_VERSION_OPTION_NAME, '2.5' );
		} else {
			update_option( DB_VERSION_OPTION_NAME, '2.5' );
		}
	}

}

