<?php
/**
 * Third-Party Integrations Loader
 * Loads external plugin compatibility.
 *
 * @package MagicLogin
 */

namespace MagicLogin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Integration Loader.
 */
class Integrations {

	/**
	 * List of supported third-party integrations.
	 *
	 * @var array
	 */
	private static $integrations
		= [
			'\MagicLogin\Integrations\WooCommerce',
			'\MagicLogin\Integrations\FluentCRM',
			'\MagicLogin\Integrations\EDD',
			'\MagicLogin\Integrations\Elementor',
		];

	/**
	 * Initialize integrations.
	 *
	 * @return void
	 */
	public static function setup() {
		foreach ( self::$integrations as $integration ) {
			if ( class_exists( $integration ) && method_exists( $integration, 'setup' ) ) {
				call_user_func( [ $integration, 'setup' ] );
			}
		}
	}
}
