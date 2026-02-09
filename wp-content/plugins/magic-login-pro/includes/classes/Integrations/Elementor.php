<?php
/**
 * Elementor Integration
 *
 * @package MagicLogin
 * @since 2.6
 */

namespace MagicLogin\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Elementor Integration Class
 */
class Elementor {

	/**
	 * Initialize Elementor integration.
	 *
	 * @return void
	 * @since 2.6
	 */
	public static function setup() {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_action( 'elementor/widgets/register', [ __CLASS__, 'register_widgets' ] );
		add_action( 'elementor/elements/categories_registered', [ __CLASS__, 'add_elementor_widget_categories' ] );
		add_action( 'elementor/frontend/after_register_styles', [ __CLASS__, 'register_styles' ] );
		add_action( 'elementor/editor/after_register_styles', [ __CLASS__, 'register_styles' ] );
	}

	/**
	 * Register Magic Login widgets.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 *
	 * @return void
	 */
	public static function register_widgets( $widgets_manager ) {
		// Register Login Widget
		require_once MAGIC_LOGIN_PRO_PATH . 'includes/classes/Integrations/Elementor/LoginWidget.php';
		$widgets_manager->register( new \MagicLogin\Integrations\Elementor\LoginWidget() );

		// Register Registration Widget
		require_once MAGIC_LOGIN_PRO_PATH . 'includes/classes/Integrations/Elementor/RegistrationWidget.php';
		$widgets_manager->register( new \MagicLogin\Integrations\Elementor\RegistrationWidget() );
	}

	/**
	 * Add Magic Login widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 *
	 * @return void
	 */
	public static function add_elementor_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'magic-login',
			[
				'title' => esc_html__( 'Magic Login', 'magic-login' ),
				'icon'  => 'fa fa-magic',
			]
		);
	}

	/**
	 * Register styles for Elementor widgets.
	 *
	 * @return void
	 */
	public static function register_styles() {
		wp_register_style(
			'magic-login-elementor-widget',
			MAGIC_LOGIN_PRO_URL . 'dist/css/elementor-style.css',
			[],
			MAGIC_LOGIN_PRO_VERSION
		);
	}
}
