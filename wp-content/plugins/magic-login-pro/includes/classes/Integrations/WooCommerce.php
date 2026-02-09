<?php
/**
 * WooCommerce Compatibility
 *
 * @package MagicLogin
 */

namespace MagicLogin\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * WooCommerce Compatibility
 */
class WooCommerce {

	/**
	 * Whether scripts have been enqueued.
	 *
	 * @var bool
	 */
	private static $enqueue_scripts = false;

	/**
	 * Initialize WooCommerce compatibility.
	 *
	 * @return void
	 */
	public static function setup() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_action( 'woocommerce_before_template_part', [ __CLASS__, 'maybe_add_magic_login_to_woocommerce_login_form' ], 10, 2 );
		add_action( 'woocommerce_after_template_part', [ __CLASS__, 'maybe_add_magic_login_to_woocommerce_login_form' ], 10, 2 );
		add_action( 'wp_footer', [ __CLASS__, 'maybe_add_woo_scripts' ] );

		// My Account Page Integration
		add_action( 'wp', [ __CLASS__, 'maybe_add_magic_login_to_my_account_page' ] );
	}

	/**
	 * Add magic login form to WooCommerce login form.
	 *
	 * @param string $template_name Template name.
	 * @param string $template_path Template path.
	 */
	public static function maybe_add_magic_login_to_woocommerce_login_form( $template_name, $template_path ) {
		if ( 'global/form-login.php' !== $template_name ) {
			return;
		}

		$settings = \MagicLogin\Utils\get_settings();

		if ( ! $settings['enable_woo_integration'] ) {
			return;
		}

		// Prevent adding the form multiple times.
		if ( did_action( 'magic_login_before_woocommerce_login_form' ) ) {
			return;
		}

		$hook = current_action();

		if ( false === stripos( $hook, $settings['woo_position'] ) ) {
			return;
		}

		self::$enqueue_scripts = true;

		/**
		 * Filter the title of the Magic Login form on WooCommerce login form.
		 *
		 * @hook magic_login_woo_title
		 *
		 * @param bool $hide_form Whether to hide the form. Default is true.
		 *
		 * @return bool Altered value.
		 */
		$hide_form = apply_filters( 'magic_login_hide_on_woocommerce_login_form', true );
		/**
		 * Filter the shortcode content of the Magic Login form on WooCommerce login form.
		 *
		 * @hook magic_login_shortcode_content_on_woocommerce_login_form
		 *
		 * @param string $shortcode_content Shortcode content.
		 *
		 * @return string Altered shortcode content.
		 */
		$shortcode_content = apply_filters( 'magic_login_shortcode_content_on_woocommerce_login_form', '[magic_login_form submit_class="woocommerce-button"]' );
		?>

		<div id="magic-login-woo-wrapper" class="<?php echo esc_attr( "magic-login-$hook" ); ?>" <?php echo ( $hide_form ) ? 'style="display:none;"' : ''; ?>>
			<?php if ( false !== stripos( $settings['woo_position'], 'after' ) ) : ?>
				<span class="magic-login-woo-or-separator"></span>
			<?php endif; ?>
			<?php
			/**
			 * Fires before the WooCommerce login form.
			 *
			 * @hook  magic_login_before_woocommerce_login_form
			 * @since 2.4
			 */
			do_action( 'magic_login_before_woocommerce_login_form' );

			echo do_shortcode( $shortcode_content );

			/**
			 * Fires after the WooCommerce login form.
			 *
			 * @hook  magic_login_after_woocommerce_login_form
			 * @since 2.4
			 */
			do_action( 'magic_login_after_woocommerce_login_form' );
			?>
			<?php if ( false !== stripos( $settings['woo_position'], 'before' ) ) : ?>
				<span class="magic-login-woo-or-separator"></span>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Add Magic Login to WooCommerce My Account Page.
	 *
	 * @return void
	 */
	public static function maybe_add_magic_login_to_my_account_page() {
		$settings = \MagicLogin\Utils\get_settings();

		if ( ! isset( $settings['enable_woo_customer_login'] ) || ! $settings['enable_woo_customer_login'] ) {
			return;
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() && ! is_user_logged_in() ) {
			$position = $settings['woo_customer_login_position'] ?? 'before';

			if ( 'before' === $position ) {
				add_action( 'woocommerce_before_customer_login_form', [ __CLASS__, 'render_magic_login_form' ] );
			} elseif ( 'after' === $position ) {
				add_action( 'woocommerce_after_customer_login_form', [ __CLASS__, 'render_magic_login_form' ] );
			}
		}
	}

	/**
	 * Render Magic Login Form for My Account Page.
	 *
	 * @return void
	 */
	public static function render_magic_login_form() {
		/**
		 * Filter the title of the Magic Login form on WooCommerce My Account page.
		 *
		 * @hook magic_login_woo_customer_login_title
		 *
		 * @param string $title The title of the form.
		 *
		 * @return string The modified title.
		 */
		$title = apply_filters( 'magic_login_woo_customer_login_title', esc_html__( 'Quick Login with Magic Link', 'magic-login' ) );
		/**
		 * Filter the shortcode for the Magic Login form on WooCommerce My Account page.
		 *
		 * @hook magic_login_woo_customer_login_shortcode
		 *
		 * @param string $shortcode The shortcode to be executed.
		 *
		 * @return string The modified shortcode.
		 */
		$shortcode = apply_filters( 'magic_login_woo_customer_login_shortcode', '[magic_login_form submit_class="woocommerce-button"]' );
		?>
		<div class="magic-login-woo-account">
			<h3 class="woocommerce-form-title">
				<?php echo esc_html( $title ); ?>
			</h3>
			<div class="magic-login-content">
				<?php echo do_shortcode( $shortcode ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Maybe add scripts for WooCommerce.
	 *
	 * @return void
	 */
	public static function maybe_add_woo_scripts() {
		if ( ! self::$enqueue_scripts ) {
			return;
		}
		self::add_scripts();
		self::add_style();
	}

	/**
	 * Add JavaScript for WooCommerce Login Page.
	 *
	 * @return void
	 */
	private static function add_scripts() {
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				document.body.addEventListener('click', function(e) {
					if (e.target.matches('.showlogin')) {
						e.preventDefault();
						var div = document.getElementById('magic-login-woo-wrapper');
						if (div.style.display === 'none') {
							div.style.display = 'block';
							document.dispatchEvent(new CustomEvent('magic-login:woocommerce:show'));
						} else {
							div.style.display = 'none';
							document.dispatchEvent(new CustomEvent('magic-login:woocommerce:hide'));
						}
					}
				});
			});
		</script>
		<?php
	}

	/**
	 * Add inline styles for WooCommerce Login Page.
	 *
	 * @return void
	 */
	private static function add_style() {
		?>
		<style>
			.magic-login-woo-or-separator {
				display: block;
				text-align: center;
				position: relative;
				margin: 10px auto;
				width: 100%;
			}

			.magic-login-woo-or-separator:before {
				content: "<?php esc_html_e( 'or', 'magic-login' ); ?>";
				background-color: #fff;
				font-size: 13px;
				color: #9b9b9b;
				display: inline-block;
				width: 62px;
				position: relative;
				z-index: 1;
			}

			.magic-login-woo-or-separator:after {
				content: "";
				width: 100%;
				position: absolute;
				left: 0;
				top: 50%;
				height: 1px;
				margin-top: -0.5px;
				background-color: #d8d8d8;
			}

			.magic-login-woo-account {
				padding: 20px;
				margin-bottom: 20px;
				background: #f7f7f7;
				border: 1px solid #ddd;
				border-radius: 5px;
				text-align: center;
			}

			.woocommerce-form-title {
				font-size: 18px;
				font-weight: 600;
				color: #333;
				margin-bottom: 15px;
			}

			.magic-login-content input[type="text"],
			.magic-login-content input[type="email"] {
				width: 100%;
				padding: 10px;
				border: 1px solid #d1d1d1;
				border-radius: 3px;
				font-size: 14px;
				margin-bottom: 10px;
				background: #fff;
				box-shadow: none;
			}

			.magic-login-content button,
			.magic-login-content input[type="submit"] {
				background: #444;
				color: #fff;
				border: none;
				padding: 12px 20px;
				border-radius: 3px;
				font-size: 14px;
				width: 100%;
				cursor: pointer;
				transition: all 0.2s ease;
			}

			.magic-login-content button:hover,
			.magic-login-content input[type="submit"]:hover {
				background: #222;
			}
		</style>
		<?php
	}
}
