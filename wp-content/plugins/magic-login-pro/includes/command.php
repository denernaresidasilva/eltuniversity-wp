<?php
/**
 *  CLI command of the plugin
 *
 * @package MagicLogin
 */

namespace MagicLogin\CLI;

use function MagicLogin\Login\send_login_link;
use function MagicLogin\Utils\create_login_link;
use \WP_CLI_Command as WP_CLI_Command;
use \WP_CLI as WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * CLI Commands for Magic Login
 */
class Command extends WP_CLI_Command {


	/**
	 * Generate a magic link for any user.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : ID, email address, or user login for the user.
	 *
	 * [--count=<count>]
	 * : Generate a specified number of login tokens.
	 * ---
	 * default: 1
	 * ---
	 *
	 * [--redirect-to=<url>]
	 * : Target redirection URL upon login
	 *
	 * [--send]
	 * : Send login email to the user
	 *
	 * [--qr]
	 * : Output QR code image URL.
	 *
	 * [--qr-img]
	 * : Output QR code as an HTML <img> tag.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate two magic login URLs.
	 *     $ wp magic-login create testuser --count=2
	 *     http://wpdev.test/wp-login.php?user_id=2&token=ebe62e3&magic-login=1
	 *     http://wpdev.test/wp-login.php?user_id=2&token=eb41c77&magic-login=1
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function create( $args, $assoc_args ) {
		$fetcher = new \WP_CLI\Fetchers\User();
		$user    = $fetcher->get_check( $args[0] );

		$count = isset( $assoc_args['count'] ) ? absint( $assoc_args['count'] ) : 1;

		if ( ! empty( $assoc_args['redirect-to'] ) ) {
			$_POST['redirect_to'] = $assoc_args['redirect-to'];
		}

		for ( $i = 0; $i < $count; $i ++ ) {
			$login_url = create_login_link( $user );
			WP_CLI::log( $login_url );

			if ( ! empty( $assoc_args['qr'] ) ) {
				$qr_url = \MagicLogin\QR::get_image_src( $login_url );
				WP_CLI::log( 'QR: ' . $qr_url );
			}

			if ( ! empty( $assoc_args['qr-img'] ) ) {
				$qr_img_tag = \MagicLogin\QR::get_img_tag( $login_url );
				WP_CLI::log( 'QR IMG: ' . $qr_img_tag );
			}
		}

		$sent = false;
		if ( ! empty( $assoc_args['send'] ) ) {
			WP_CLI::log( esc_html__( 'Sending email to user', 'magic-login' ) );
			$sent = send_login_link( $user, $login_url );
			if ( $sent ) {
				WP_CLI::success( esc_html__( 'The email has been successfully sent.', 'magic-login' ) );
			} else {
				WP_CLI::error( esc_html__( 'The email could not be sent', 'magic-login' ) );
			}
		}

	}

	/**
	 * Bulk generation of magic login.
	 *
	 * ## OPTIONS
	 *
	 * [--redirect-to=<url>]
	 * : Target redirection URL upon login
	 *
	 * [--role=<role>]
	 * : Only generate links for the users with a certain role.
	 *
	 * [--send]
	 * : Send login email to the user
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * [--qr]
	 * : Output QR code image URL in results.
	 *
	 * [--qr-img]
	 * : Output QR code as an HTML <img> tag in results.
	 *
	 * ## EXAMPLES
	 *
	 *     # Generate bulk login URLs.
	 *     $ wp magic-login bulk-create
	 *
	 * @subcommand bulk-create
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function bulk_create( $args, $assoc_args ) {
		global $wpdb;
		global $wp_actions;

		if ( ! empty( $assoc_args['role'] ) && 'none' === $assoc_args['role'] ) {
			$norole_user_ids = wp_get_users_with_no_role();

			if ( ! empty( $norole_user_ids ) ) {
				$assoc_args['include'] = $norole_user_ids;
				unset( $assoc_args['role'] );
			}
		}

		$users = get_users( $assoc_args );

		if ( ! empty( $assoc_args['redirect-to'] ) ) {
			$_POST['redirect_to'] = $assoc_args['redirect-to'];
		}

		$rows = [];
		foreach ( $users as $user ) {
			$sent       = false;
			$login_url  = create_login_link( $user );
			$qr_url     = '';
			$qr_img_tag = '';

			if ( ! empty( $assoc_args['send'] ) ) {
				$sent = send_login_link( $user, $login_url );
				unset( $wp_actions['magic_login_send_login_link'] ); // skip did_action check
			}

			if ( ! empty( $assoc_args['qr'] ) ) {
				$qr_url = \MagicLogin\QR::get_image_src( $login_url );
			}

			if ( ! empty( $assoc_args['qr-img'] ) ) {
				$qr_img_tag = \MagicLogin\QR::get_img_tag( $login_url );
			}

			$columns = [ 'email', 'url' ];

			if ( ! empty( $assoc_args['qr'] ) ) {
				$columns[] = 'qr';
			}

			if ( ! empty( $assoc_args['qr-img'] ) ) {
				$columns[] = 'qr_img';
			}

			$columns[] = 'sent';

			$rows[] = [
				'email'  => $user->user_email,
				'url'    => $login_url,
				'qr'     => $qr_url,
				'qr_img' => $qr_img_tag,
				'sent'   => $sent ? esc_html__( 'Yes', 'magic-login' ) : esc_html__( 'No', 'magic-login' ),
			];
		}

		\WP_CLI\Utils\format_items( $assoc_args['format'], $rows, $columns );
	}


	/**
	 * Export plugin settings to a file.
	 * ## OPTIONS
	 * [--file=<file>]
	 * : Path to save the export file. Default: magic-login-settings.json
	 * [--include-sensitive]
	 * : Include decrypted API credentials.
	 * [--include-license]
	 * : Include license key in export.
	 * ## EXAMPLES
	 *     wp magic-login export-settings --file=magic.json --include-sensitive --include-license
	 *
	 * @subcommand export-settings
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function export_settings( $args, $assoc_args ) {
		$file              = $assoc_args['file'] ?? 'magic-login-settings.json';
		$include_sensitive = isset( $assoc_args['include-sensitive'] );
		$include_license   = isset( $assoc_args['include-license'] );

		$export = \MagicLogin\Tools::generate_export_settings( $include_sensitive, $include_license );

		// Try to write to file
		$result = file_put_contents( $file, wp_json_encode( $export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); // phpcs:ignore

		if ( false === $result ) {
			\WP_CLI::error( esc_html__( 'Could not write export file.', 'magic-login' ) );
		}

		\WP_CLI::success(
			sprintf(
			/* translators: %s: export file name */
				esc_html__( 'Settings exported to %s', 'magic-login' ),
				$file
			)
		);
	}


	/**
	 * Import plugin settings from a file.
	 * ## OPTIONS
	 * [--file=<file>]
	 * : Path to the JSON settings file.
	 * [--activate-license]
	 * : Activate license key if it's in the file.
	 * ## EXAMPLES
	 *     wp magic-login import-settings --file=magic.json --activate-license
	 *
	 * @subcommand import-settings
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function import_settings( $args, $assoc_args ) {
		$file = $assoc_args['file'] ?? '';

		if ( empty( $file ) || ! file_exists( $file ) ) {
			\WP_CLI::error( esc_html__( 'Settings file not found.', 'magic-login' ) );
		}

		$content  = file_get_contents( $file ); // phpcs:ignore
		$imported = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE || empty( $imported ) ) {
			\WP_CLI::error( esc_html__( 'Invalid or malformed JSON file.', 'magic-login' ) );
		}

		$activate_license = isset( $assoc_args['activate-license'] );

		$success = \MagicLogin\Tools::process_import_settings( $imported, $activate_license );

		if ( $success ) {
			\WP_CLI::success( esc_html__( 'Settings imported successfully.', 'magic-login' ) );
		} else {
			\WP_CLI::error( esc_html__( 'Settings could not be imported.', 'magic-login' ) );
		}
	}

	/**
	 * Reset plugin data.
	 * ## OPTIONS
	 * <what>
	 * : What to reset. Options:
	 *   - settings
	 *   - license
	 *   - all-tokens
	 * ## EXAMPLES
	 *     wp magic-login reset settings
	 *     wp magic-login reset all-tokens
	 *
	 * @param array $args       Positional CLI args.
	 * @param array $assoc_args Associative CLI args.
	 */
	public function reset( $args, $assoc_args ) {
		$target = $args[0] ?? '';

		switch ( $target ) {
			case 'settings':
				\MagicLogin\Tools::reset_settings();
				\WP_CLI::success( esc_html__( 'Settings have been reset.', 'magic-login' ) );
				break;

			case 'license':
				\MagicLogin\Tools::reset_license();
				\WP_CLI::success( esc_html__( 'License has been reset.', 'magic-login' ) );
				break;

			case 'all-tokens':
				\MagicLogin\Utils\delete_all_tokens();
				\WP_CLI::success( esc_html__( 'All magic login tokens have been deleted.', 'magic-login' ) );
				break;

			default:
				\WP_CLI::error( esc_html__( 'Invalid reset target. Use "settings", "license", or "all-tokens".', 'magic-login' ) );
		}
	}



}
