<?php
/**
 * Easy Digital Downloads (EDD) Integration
 *
 * @package MagicLogin
 */

namespace MagicLogin\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * EDD Compatibility
 */
class EDD {

	/**
	 * Initialize EDD integration.
	 *
	 * @return void
	 */
	public static function setup() {
		if ( ! defined( 'EDD_VERSION' ) ) {
			return; // Exit if EDD is not active
		}

		add_action( 'init', [ __CLASS__, 'maybe_add_hooks' ] );
	}

	/**
	 * Conditionally add hooks based on settings.
	 *
	 * @return void
	 */
	public static function maybe_add_hooks() {
		if ( is_user_logged_in() ) {
			return;
		}

		$settings = \MagicLogin\Utils\get_settings();

		if ( $settings['enable_edd_checkout'] ) {
			$position = $settings['edd_checkout_position'] ?? 'before';
			add_action( $position, [ __CLASS__, 'render_magic_login_form' ] );
		}

		if ( $settings['enable_edd_login'] ) {
			add_filter( 'the_content', [ __CLASS__, 'inject_magic_login_into_edd_login_page' ] );
		}
	}

	/**
	 * Render Magic Login form on EDD page.
	 *
	 * @return void
	 * @since 2.4
	 */
	public static function render_magic_login_form() {
		/**
		 * Filter the title of the Magic Login form.
		 *
		 * @hook magic_login_edd_title
		 *
		 * @param string $title The title of the form.
		 *
		 * @return string The modified title.
		 */
		$title = apply_filters( 'magic_login_edd_title', esc_html__( 'Quick Login with Magic Link', 'magic-login' ) );

		/**
		 * Filter the shortcode for the Magic Login form.
		 *
		 * @hook magic_login_edd_shortcode
		 *
		 * @param string $shortcode The shortcode to be executed.
		 *
		 * @return string The modified shortcode.
		 */
		$shortcode = apply_filters( 'magic_login_edd_shortcode', '[magic_login_form]' );
		?>
		<div class="edd-magic-login-wrapper edd-alert edd-alert-info">
			<p class="edd-magic-login-title">
				<?php echo esc_html( $title ); ?>
			</p>
			<div class="edd-magic-login-content">
				<?php echo do_shortcode( $shortcode ); ?>
			</div>
		</div>

		<?php
	}

	/**
	 * Check if the current page is the EDD login page or login redirect page.
	 *
	 * @return bool
	 * @since 2.4
	 */
	private static function is_edd_login_related_page() {
		$edd_login_page_id          = edd_get_option( 'login_page', false );
		$edd_login_redirect_page_id = edd_get_option( 'login_redirect_page', false );

		return ( ! empty( $edd_login_page_id ) && is_page( $edd_login_page_id ) ) || ( ! empty( $edd_login_redirect_page_id ) && is_page( $edd_login_redirect_page_id ) );
	}

	/**
	 * Inject Magic Login form into the EDD login page.
	 *
	 * @param string $content The original page content.
	 *
	 * @return string Updated content with Magic Login form.
	 * @since 2.4
	 */
	public static function inject_magic_login_into_edd_login_page( $content ) {
		if ( self::is_edd_login_related_page() ) {
			$settings = \MagicLogin\Utils\get_settings();
			ob_start();
			self::render_magic_login_form();
			$magic_login_form = ob_get_clean();

			if ( 'before' === $settings['edd_login_position'] ) {
				$content = $magic_login_form . $content;
			} else {
				$content = $content . $magic_login_form;
			}
		}

		return $content;
	}

}
