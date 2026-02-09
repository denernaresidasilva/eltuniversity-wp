<?php
/**
 * Magic Login Registration Elementor Widget
 *
 * @package MagicLogin
 */

namespace MagicLogin\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Magic Login Registration Widget Class
 */
class RegistrationWidget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'magic-login-registration';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'Magic Login Registration Form', 'magic-login' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-form-horizontal';
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
		return [ 'magic', 'login', 'registration', 'signup', 'form', 'user' ];
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
				'default' => esc_html__( 'Create Your Account', 'magic-login' ),
			]
		);

		$this->add_control(
			'description',
			[
				'label'   => esc_html__( 'Description', 'magic-login' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( 'Enter your details below to create a new account.', 'magic-login' ),
			]
		);

		$this->add_control(
			'email_label',
			[
				'label'   => esc_html__( 'Email Field Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Email Address', 'magic-login' ),
			]
		);

		$this->add_control(
			'first_name_label',
			[
				'label'   => esc_html__( 'First Name Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'First Name', 'magic-login' ),
			]
		);

		$this->add_control(
			'last_name_label',
			[
				'label'   => esc_html__( 'Last Name Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Last Name', 'magic-login' ),
			]
		);

		$this->add_control(
			'button_label',
			[
				'label'   => esc_html__( 'Button Label', 'magic-login' ),
				'type'    => Controls_Manager::TEXT,
				'default' => esc_html__( 'Create Account', 'magic-login' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'form_settings_section',
			[
				'label' => esc_html__( 'Form Settings', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'show_name_fields',
			[
				'label'        => esc_html__( 'Show Name Fields', 'magic-login' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'magic-login' ),
				'label_off'    => esc_html__( 'No', 'magic-login' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_terms',
			[
				'label'        => esc_html__( 'Show Terms Checkbox', 'magic-login' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'magic-login' ),
				'label_off'    => esc_html__( 'No', 'magic-login' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'terms_label',
			[
				'label'       => esc_html__( 'Terms Checkbox Label', 'magic-login' ),
				'type'        => Controls_Manager::WYSIWYG,
				'default'     => 'I agree to the <a href="#" target="_blank">terms and conditions</a>',
				'description' => esc_html__( 'You can use HTML tags like links. Example: I agree to the <a href="/privacy-policy">terms and conditions</a>', 'magic-login' ),
				'condition'   => [
					'show_terms' => 'yes',
				],
			]
		);

		$this->add_control(
			'pre_filled_email',
			[
				'label'       => esc_html__( 'Pre-fill Email', 'magic-login' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => esc_html__( 'user@example.com', 'magic-login' ),
				'description' => esc_html__( 'Pre-fill the email field with a specific email address', 'magic-login' ),
			]
		);

		$this->add_control(
			'redirect_to',
			[
				'label'       => esc_html__( 'Redirect After Registration', 'magic-login' ),
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
					'{{WRAPPER}} .magic-login-registration-elementor-widget' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'form_border',
				'selector' => '{{WRAPPER}} .magic-login-registration-elementor-widget',
			]
		);

		$this->add_control(
			'form_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-registration-elementor-widget' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-elementor-widget' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'form_box_shadow',
				'selector' => '{{WRAPPER}} .magic-login-registration-elementor-widget',
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
					'{{WRAPPER}} .magic-login-registration-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .magic-login-registration-title',
			]
		);

		$this->add_responsive_control(
			'title_margin',
			[
				'label'      => esc_html__( 'Margin', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-registration-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_input',
			[
				'label' => esc_html__( 'Input Fields', 'magic-login' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_background',
			[
				'label'     => esc_html__( 'Background Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'input_color',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'input_typography',
				'selector' => '{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'input_border',
				'selector' => '{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]',
			]
		);

		$this->add_control(
			'input_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-elementor-widget input[type="text"], {{WRAPPER}} .magic-login-registration-elementor-widget input[type="email"]' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-submit' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-registration-submit' => 'color: {{VALUE}};',
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
					'{{WRAPPER}} .magic-login-registration-submit:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_color_hover',
			[
				'label'     => esc_html__( 'Text Color', 'magic-login' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .magic-login-registration-submit:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'      => 'button_typography',
				'selector'  => '{{WRAPPER}} .magic-login-registration-submit',
				'separator' => 'before',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .magic-login-registration-submit',
			]
		);

		$this->add_control(
			'button_border_radius',
			[
				'label'      => esc_html__( 'Border Radius', 'magic-login' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .magic-login-registration-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .magic-login-registration-submit' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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

		// Prepare arguments for the registration form
		$args = [
			'title'            => $settings['title'],
			'description'      => $settings['description'],
			'email_label'      => $settings['email_label'],
			'first_name_label' => $settings['first_name_label'],
			'last_name_label'  => $settings['last_name_label'],
			'button_label'     => $settings['button_label'],
			'terms_label'      => $this->clean_terms_label( $settings['terms_label'] ),
			'show_name'        => 'yes' === $settings['show_name_fields'],
			'show_terms'       => 'yes' === $settings['show_terms'],
			'email'            => $settings['pre_filled_email'],
			'redirect_to'      => ! empty( $settings['redirect_to']['url'] ) ? $settings['redirect_to']['url'] : '',
			'hide_logged_in'   => ! empty( $settings['hide_logged_in'] ),
		];

		echo $this->render_registration_form( $args ); // phpcs:ignore
	}

	/**
	 * Render the registration form.
	 *
	 * @param array $args Form arguments.
	 * @return string
	 */
	private function render_registration_form( $args ) {
		// Check Elementor context more thoroughly
		$is_elementor_edit_mode        = \Elementor\Plugin::$instance->editor->is_edit_mode();
		$is_elementor_preview_mode     = isset( $_GET['elementor-preview'] ); // phpcs:ignore
		$is_elementor_frontend_preview = \Elementor\Plugin::$instance->preview->is_preview_mode();
		$is_elementor                  = $is_elementor_edit_mode || $is_elementor_preview_mode || $is_elementor_frontend_preview;

		// Check if the form should be hidden for logged-in users
		// IMPORTANT: Always show the form in Elementor edit/preview mode
		if ( ! $is_elementor && is_user_logged_in() && $args['hide_logged_in'] ) {
			return '';
		}

		// Enqueue widget styles
		wp_enqueue_style(
			'magic-login-elementor-widget',
			MAGIC_LOGIN_PRO_URL . 'dist/css/elementor-style.css',
			[],
			MAGIC_LOGIN_PRO_VERSION
		);

		// Set flag to prevent shortcode from loading its own styles
		add_filter( 'magic_login_load_registration_styles', '__return_false', 999 );

		// Use the magic login registration shortcode with our arguments
		$shortcode_args = [
			'email'              => $args['email'],
			'show_name'          => $args['show_name'] ? 'true' : 'false',
			'show_terms'         => $args['show_terms'] ? 'true' : 'false',
			'registration_terms' => $args['terms_label'], // Add terms label
			'redirect_to'        => $args['redirect_to'],
			'title'              => $args['title'],
			'description'        => $args['description'],
			'email_label'        => $args['email_label'],
			'first_name_label'   => $args['first_name_label'],
			'last_name_label'    => $args['last_name_label'],
			'button_label'       => $args['button_label'],
			'hide_logged_in'     => $args['hide_logged_in'] ? 'true' : 'false',
		];

		// Build shortcode attributes
		$attributes = [];
		foreach ( $shortcode_args as $key => $value ) {
			if ( ! empty( $value ) ) {
				// For registration_terms, we'll handle it differently to preserve HTML
				if ( 'registration_terms' === $key ) {
					// Use single quotes to wrap the attribute to avoid conflicts with double quotes in HTML
					$attributes[] = sprintf( "%s='%s'", $key, str_replace( "'", '&#39;', $value ) );
				} else {
					$attributes[] = sprintf( '%s="%s"', $key, esc_attr( $value ) );
				}
			}
		}

		$shortcode = sprintf( '[magic_login_registration_form %s]', implode( ' ', $attributes ) );

		ob_start();
		?>
		<div class="magic-login-registration-elementor-widget">
			<?php if ( ! empty( $args['title'] ) ) : ?>
				<h2 class="magic-login-registration-title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $args['description'] ) ) : ?>
				<p class="magic-login-registration-description"><?php echo esc_html( $args['description'] ); ?></p>
			<?php endif; ?>

			<?php
			// Use shortcode for the form functionality
			echo do_shortcode( $shortcode );
			?>
		</div>
		<?php

		// Remove the filter after shortcode execution
		remove_filter( 'magic_login_load_registration_styles', '__return_false', 999 );

		return ob_get_clean();
	}

	/**
	 * Clean terms label from WYSIWYG editor output.
	 * Removes wrapping <p> tags that WYSIWYG editors add automatically.
	 *
	 * @param string $terms_label The terms label from WYSIWYG editor.
	 * @return string Cleaned terms label.
	 */
	private function clean_terms_label( $terms_label ) {
		if ( empty( $terms_label ) ) {
			return $terms_label;
		}

		// Remove wrapping <p> tags and trim whitespace
		$cleaned = trim( $terms_label );
		$cleaned = preg_replace( '/^<p[^>]*>(.*)<\/p>$/s', '$1', $cleaned );

		return trim( $cleaned );
	}

	/**
	 * Render widget output in the editor.
	 */
	protected function content_template() {
		?>
		<style>
			/* Ensure styles are directly applied in the editor */
			.elementor-widget-magic-login-registration .magic-login-registration-elementor-widget {
				width: 100%;
			}
			.elementor-widget-magic-login-registration .magic-login-registration-form {
				margin-top: 15px;
			}
			.elementor-widget-magic-login-registration .magic-login-field {
				margin-bottom: 15px;
			}
			.elementor-widget-magic-login-registration .magic-login-field input[type="text"],
			.elementor-widget-magic-login-registration .magic-login-field input[type="email"] {
				width: 100%;
				padding: 8px 12px;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			.elementor-widget-magic-login-registration .magic-login-name-fields {
				display: flex;
				gap: 10px;
			}
			.elementor-widget-magic-login-registration .magic-login-name-fields .magic-login-field {
				flex: 1;
			}
			.elementor-widget-magic-login-registration .magic-login-registration-submit {
				background-color: #0073aa;
				color: #fff;
				padding: 10px 15px;
				border: none;
				border-radius: 4px;
				cursor: pointer;
			}
		</style>
		<div class="magic-login-registration-elementor-widget">
			<# if ( settings.title ) { #>
				<h2 class="magic-login-registration-title">{{{ settings.title }}}</h2>
			<# } #>

			<# if ( settings.description ) { #>
				<p class="magic-login-registration-description">{{{ settings.description }}}</p>
			<# } #>

			<form class="magic-login-registration-form">
				<# if ( 'yes' === settings.show_name_fields ) { #>
					<div class="magic-login-name-fields">
						<div class="magic-login-field">
							<# if ( settings.first_name_label ) { #>
								<label>{{{ settings.first_name_label }}}</label>
							<# } #>
							<input type="text" placeholder="<?php esc_attr_e( 'First Name', 'magic-login' ); ?>" />
						</div>
						<div class="magic-login-field">
							<# if ( settings.last_name_label ) { #>
								<label>{{{ settings.last_name_label }}}</label>
							<# } #>
							<input type="text" placeholder="<?php esc_attr_e( 'Last Name', 'magic-login' ); ?>" />
						</div>
					</div>
				<# } #>

				<div class="magic-login-field">
					<# if ( settings.email_label ) { #>
						<label>{{{ settings.email_label }}}</label>
					<# } #>
					<input type="email" placeholder="<?php esc_attr_e( 'Email Address', 'magic-login' ); ?>" value="{{{ settings.pre_filled_email }}}" />
				</div>

				<# if ( 'yes' === settings.show_terms ) { #>
					<div class="magic-login-field">
						<label>
							<input type="checkbox" />
							<# if ( settings.terms_label ) { #>
								{{{ settings.terms_label }}}
							<# } else { #>
								<?php esc_html_e( 'I agree to the terms and conditions', 'magic-login' ); ?>
							<# } #>
						</label>
					</div>
				<# } #>

				<# if ( settings.button_label ) { #>
					<input type="submit" class="magic-login-registration-submit button button-primary button-large" value="{{{ settings.button_label }}}" />
				<# } #>
			</form>
		</div>
		<?php
	}
}
