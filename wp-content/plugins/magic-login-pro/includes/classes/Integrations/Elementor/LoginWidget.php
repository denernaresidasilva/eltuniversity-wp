<?php
/**
 * Magic Login Elementor Widget
 *
 * @package MagicLogin
 */

namespace MagicLogin\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use MagicLogin\CodeLogin;
use MagicLogin\LoginManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

/**
 * Magic Login Widget Class
 */
class LoginWidget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'magic-login';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Magic Login Form', 'magic-login' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-lock-user';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'magic-login', 'general' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'magic', 'login', 'form', 'authentication', 'passwordless' ];
	}

	/**
	 * Get style dependencies.
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends() {
		return [ 'magic-login-elementor-widget' ];
	}

	/**
	 * Register widget controls.
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'title',
			[
				'label'   => esc_html__( 'Title', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Login with Email', 'magic-login' ),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'magic-login' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Please enter your username or email address. You will receive an email message to log in.', 'magic-login' ),
			]
		);

		$this->add_control(
			'login_label',
			[
				'label'   => esc_html__( 'Login Field Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Username or Email Address', 'magic-login' ),
			]
		);

		$this->add_control(
			'button_label',
			[
				'label'   => esc_html__( 'Button Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Send me the link', 'magic-login' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'settings_section',
			[
				'label' => esc_html__( 'Settings', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'redirect_to',
			[
				'label'       => esc_html__( 'Redirect To', 'magic-login' ),
				'type'        => Controls_Manager::URL,
				'placeholder' => esc_html__( 'https://your-link.com', 'magic-login' ),
				'description' => esc_html__( 'Leave empty to redirect to current page', 'magic-login' ),
			]
		);

		$this->add_control(
			'hide_logged_in',
			[
				'label'        => esc_html__( 'Hide for Logged In Users', 'magic-login' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'magic-login' ),
				'label_off'    => esc_html__( 'No', 'magic-login' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'hide_form_after_submit',
			[
				'label'        => esc_html__( 'Hide Form After Submit', 'magic-login' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'magic-login' ),
				'label_off'    => esc_html__( 'No', 'magic-login' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'cancel_redirection',
			[
				'label'        => esc_html__( 'Cancel Redirection', 'magic-login' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'magic-login' ),
				'label_off'    => esc_html__( 'No', 'magic-login' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		// Style controls
		$this->start_controls_section(
			'style_form',
			[
				'label' => esc_html__( 'Form', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'form_align',
			[
				'label'     => esc_html__( 'Alignment', 'magic-login' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left'   => [
						'title' => esc_html__( 'Left', 'magic-login' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'magic-login' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => esc_html__( 'Right', 'magic-login' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .magic-login-elementor-widget' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'form_border',
				'selector' => '{{WRAPPER}} .magic-login-elementor-widget',
			]
		);

		$this->add_control(
			'form_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-elementor-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'form_padding',
			[
				'label'      => esc_html__( 'Padding', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-elementor-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'form_box_shadow',
				'selector' => '{{WRAPPER}} .magic-login-elementor-widget',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_title',
			[
				'label' => esc_html__( 'Title', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => esc_html__( 'Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-block-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .magic-login-block-title',
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-block-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_input',
			[
				'label' => esc_html__( 'Input Field', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_background',
			[
				'label'     => esc_html__( 'Background Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-elementor-widget input[type="text"]' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_color',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-elementor-widget input[type="text"]' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typography',
				'selector' => '{{WRAPPER}} .magic-login-elementor-widget input[type="text"]',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .magic-login-elementor-widget input[type="text"]',
			]
		);

		$this->add_control(
			'input_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-elementor-widget input[type="text"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_padding',
			[
				'label'      => esc_html__( 'Padding', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-elementor-widget input[type="text"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'input_margin',
			[
				'label'      => esc_html__( 'Margin', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-elementor-widget input[type="text"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_button',
			[
				'label' => esc_html__( 'Button', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'button_style_tabs' );

		$this->start_controls_tab(
			'button_normal',
			[
				'label' => esc_html__( 'Normal', 'magic-login' ),
			]
		);

		$this->add_control(
			'button_background',
			[
				'label'     => esc_html__( 'Background Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-submit' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-submit' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'button_hover',
			[
				'label' => esc_html__( 'Hover', 'magic-login' ),
			]
		);

		$this->add_control(
			'button_background_hover',
			[
				'label'     => esc_html__( 'Background Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-submit:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-submit:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'button_typography',
				'selector'  => '{{WRAPPER}} .magic-login-submit',
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .magic-login-submit',
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => esc_html__( 'Padding', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_margin',
			[
				'label'      => esc_html__( 'Margin', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-submit' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Prepare arguments similar to block attributes
		$args = [
			'title'               => $settings['title'],
			'description'         => $settings['description'],
			'loginLabel'          => $settings['login_label'],
			'buttonLabel'         => $settings['button_label'],
			'redirectTo'          => ! empty( $settings['redirect_to']['url'] ) ? $settings['redirect_to']['url'] : '',
			'hideLoggedIn'        => 'yes' === $settings['hide_logged_in'],
			'hideFormAfterSubmit' => 'yes' === $settings['hide_form_after_submit'],
			'cancelRedirection'   => 'yes' === $settings['cancel_redirection'],
			'className'           => 'magic-login-elementor-widget',
		];

		// Use the same rendering logic as the block
		echo $this->render_magic_login_form( $args ); // phpcs:ignore
	}

	/**
	 * Render the magic login form.
	 *
	 * @param array $args Form arguments.
	 * @return string
	 */
	private function render_magic_login_form( $args ) {
		$settings = \MagicLogin\Utils\get_settings();

		$add_redirection_field = empty( $settings['enable_login_redirection'] ) || empty( $settings['enforce_redirection_rules'] );

		if ( $settings['enable_ajax'] ) {
			wp_enqueue_script( 'magic-login-frontend', MAGIC_LOGIN_PRO_URL . 'dist/js/frontend.js', [ 'jquery' ], MAGIC_LOGIN_PRO_VERSION, true );
		}

		// Enqueue widget styles
		wp_enqueue_style(
			'magic-login-elementor-widget',
			MAGIC_LOGIN_PRO_URL . 'dist/css/elementor-style.css',
			[],
			MAGIC_LOGIN_PRO_VERSION
		);

		/**
		 * Filter the form action URL for the elementor widget.
		 *
		 * @hook magic_login_elementor_widget_form_action
		 *
		 * @param string $form_action The form action URL. Default is current page URL.
		 *
		 * @return string The modified form action URL.
		 */
		$form_action = apply_filters( 'magic_login_elementor_widget_form_action', '' );

		// If no custom form action is set, use the current page URL to ensure form processing
		if ( empty( $form_action ) ) {
			$form_action = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore
		}

		$class = 'magic-login-login-block magic-login-elementor-widget';

		if ( ! empty( $args['className'] ) ) {
			$class .= ' ' . esc_attr( $args['className'] );
		}

		if ( empty( $args['redirectTo'] ) ) {
			$args['redirectTo'] = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; // phpcs:ignore
		}

		if ( ! defined( 'REST_REQUEST' ) && ! is_admin() && is_user_logged_in() && $args['hideLoggedIn'] ) {
			return '';
		}

		// Force process login request early to ensure errors are captured
		// This is especially important for Elementor widgets that might render at different times
		ob_start();

		// Check if this is a form submission and process it directly
		$login_request = null;
		if ( isset( $_POST['log'] ) && isset( $_POST['wp-submit'] ) && isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			// Directly process the form submission to get the proper response
			$login_request = LoginManager::process_login_form_submission( $args );
		} else {
			// Normal processing for non-submissions
			$login_request = LoginManager::process_login_request();
		}

		if ( false === $login_request['show_form'] && ! $args['hideFormAfterSubmit'] ) {
			$login_request['show_form'] = true;
		}
		?>

		<?php if ( $login_request['show_registration_form'] ) : ?>
			<?php
			$email = isset( $_POST['log'] ) && is_email( wp_unslash( $_POST['log'] ) ) ? sanitize_email( wp_unslash( $_POST['log'] ) ) : '';
			if ( 'auto' === $settings['registration']['mode'] && $settings['registration']['fallback_email_field'] ) {
				$shortcode = sprintf( '[magic_login_registration_form show_name="false" show_terms="false" email="%s"]', $email );
			} else {
				$shortcode = sprintf( '[magic_login_registration_form email="%s"]', $email );
			}
			echo do_shortcode( $shortcode );
			?>
		<?php else : ?>

			<?php
			$error_messages = '';
			$login_errors   = $login_request['errors'];
			// error messages
			if ( ! empty( $login_errors ) && is_wp_error( $login_errors ) && $login_errors->has_errors() ) {
				foreach ( $login_errors->get_error_codes() as $code ) {
					foreach ( $login_errors->get_error_messages( $code ) as $message ) {
						$error_messages .= $message . "<br />\n";
					}
				}
			}
			?>

		<div id="magic-login-elementor-widget" class="<?php echo esc_attr( $class ); ?>">
			<?php if ( ! empty( $args['title'] ) ) : ?>
				<h2 class="magic-login-block-title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>
			<div class="magic-login-form-header">
				<?php
				if ( ! empty( $error_messages ) ) :
					printf( '<div class="magic_login_block_login_error">%s</div>', wp_kses_post( $error_messages ) );
				endif;
				?>

				<?php if ( $login_request['is_processed'] ) : ?>
					<?php echo wp_kses_post( $login_request['info'] ); ?>
				<?php endif; ?>

				<?php if ( ! $login_request['code_login'] && ! empty( $args['description'] ) && $login_request['show_form'] ) : ?>
					<p class="magic-login-block-description"><?php echo esc_html( $args['description'] ); ?></p>
				<?php endif; ?>
			</div>
			<?php if ( $login_request['code_login'] ) : ?>
				<?php CodeLogin::code_form(); ?>
			<?php elseif ( $login_request['show_form'] ) : ?>
				<form name="magicloginform"
					  class="block-login-form magic-login-elementor-form"
					  id="magicloginform-elementor-<?php echo esc_attr( get_the_ID() ); ?>"
					  action="<?php echo esc_url( $form_action ); ?>"
					  method="post" autocomplete="off"
					  data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					  data-ajax-spinner="<?php echo esc_url( get_admin_url() . 'images/spinner.gif' ); ?>"
					  data-ajax-sending-msg="<?php esc_attr_e( 'Sending...', 'magic-login' ); ?>"
					  data-spam-protection-msg="<?php esc_attr_e( 'Please verify that you are not a robot.', 'magic-login' ); ?>"
				>
					<div class="magicloginform-inner">
						<?php if ( ! empty( $args['loginLabel'] ) ) : ?>
							<label for="user_login_elementor_<?php echo esc_attr( get_the_ID() ); ?>"><?php echo esc_html( $args['loginLabel'] ); ?></label>
						<?php endif; ?>

						<input type="text" name="log" id="user_login_elementor_<?php echo esc_attr( get_the_ID() ); ?>" class="input" value="<?php echo isset( $_POST['log'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['log'] ) ) ) : ''; ?>" size="20" autocapitalize="off" autocomplete="username" required />
						<?php
						/**
						 * Fires following the 'log' field in the magic login form.
						 *
						 * @hook magic_login_form
						 */
						do_action( 'magic_login_form' );
						?>
						<?php if ( ! empty( $args['buttonLabel'] ) ) : ?>
							<input type="submit" name="wp-submit" id="wp-submit" class="magic-login-submit button button-primary button-large" value="<?php echo esc_attr( $args['buttonLabel'] ); ?>" />
						<?php endif; ?>

						<?php if ( ! empty( $args['redirectTo'] ) && ! $args['cancelRedirection'] && $add_redirection_field ) : ?>
							<input type="hidden" name="redirect_to" value="<?php echo esc_url( $args['redirectTo'] ); ?>" />
						<?php endif; ?>
						<input type="hidden" name="testcookie" value="1" />
						<input type="hidden" name="magic_login_source" value="elementor_widget" />
					</div>
				</form>
			<?php endif; ?>
		</div>

		<?php endif; ?>

		<?php
		return ob_get_clean();
	}

	/**
	 * Render widget output in the editor.
	 */
	protected function content_template() {
		?>
		<#
		var iconHTML = elementor.helpers.renderIcon( view, settings.selected_icon, { 'aria-hidden': true }, 'i' , 'object' );
		#>
		<div class="magic-login-login-block magic-login-elementor-widget">
			<# if ( settings.title ) { #>
				<h2 class="magic-login-block-title">{{{ settings.title }}}</h2>
			<# } #>
			<# if ( settings.description ) { #>
				<p class="magic-login-block-description">{{{ settings.description }}}</p>
			<# } #>
			<form class="block-login-form">
				<div class="magicloginform-inner">
					<# if ( settings.login_label ) { #>
						<label>{{{ settings.login_label }}}</label>
					<# } #>
					<input type="text" class="input" placeholder="<?php esc_attr_e( 'Username or Email', 'magic-login' ); ?>" />
					<# if ( settings.button_label ) { #>
						<input type="submit" class="magic-login-submit button button-primary button-large" value="{{{ settings.button_label }}}" />
					<# } #>
				</div>
			</form>
		</div>
		<?php
	}
}
