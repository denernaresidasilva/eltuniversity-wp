<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Classes\OutputCSS_Body;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class OutputCSS_Body_Pro extends OutputCSS_Body
{

	public $admin_bg_type;
	public $options;

	public function __construct()
	{
		parent::__construct();
		$this->admin_bg_type = !empty($this->options['body_fields']['admin_general_bg']) ? $this->options['body_fields']['admin_general_bg'] : 'gradient';

		add_action('admin_enqueue_scripts', [$this, 'jltwp_adminify_output_body_styles'], 99999);
		// add_filter('admin_body_class', [$this, 'add_glassmorphism_body_classes']);
	}


	/**
	 * Glass Effect Body Class
	 *
	 * @return void
	 */
	public function add_glassmorphism_body_classes($classes)
	{
		$bodyclass          = '';
		$admin_glass_effect = !empty($this->options['admin_glass_effect']) ? $this->options['admin_glass_effect'] : '';
		if ($admin_glass_effect) {
			$bodyclass .= ' adminify-glass-effect ';
		}
		return $classes . $bodyclass;
	}

	public function jltwp_adminify_output_body_styles() {
		$jltwp_adminify_output_body_css = '';

		$body_fields = $this->options['body_fields'];

		// Background Types
		// $admin_bg_color    = !empty($this->options['admin_general_bg_color']) ? $this->options['admin_general_bg_color'] : '';

		$admin_google_font = !empty($this->options['admin_general_google_font']) ? $this->options['admin_general_google_font'] : '';

		// If Custom Background Enabled
		if (!empty($body_fields['adminify_custom_bg'])) {

			// Background Types
			if (!empty($this->admin_bg_type)) {
				$jltwp_adminify_output_body_css .= 'html, body.wp-adminify{';
				// Background Type: Gradient
				if ($this->admin_bg_type == 'gradient') {
					$admin_bg_gradient = !empty($body_fields['admin_general_bg_gradient']) ? $body_fields['admin_general_bg_gradient'] : '';
					$gradient_bg_attachment = !empty($body_fields['admin_general_bg_gradient']['background-attachment']) ? $body_fields['admin_general_bg_gradient']['background-attachment'] : 'scroll';

					if (!empty($admin_bg_gradient)) {
						$jltwp_adminify_output_body_css .= 'background-image : linear-gradient(' . esc_attr($admin_bg_gradient['background-gradient-direction']) . ', ' . esc_attr($admin_bg_gradient['background-color']) . ' , ' . esc_attr($admin_bg_gradient['background-gradient-color']) . ');';
						$jltwp_adminify_output_body_css .= 'background-attachment: ' . $gradient_bg_attachment . ';';
						$jltwp_adminify_output_body_css .= 'height: auto;';
					}
				}

				// Background Type: Image
				if ($this->admin_bg_type == 'image') {
					$general_bg_image = $body_fields['admin_general_bg_image'];
					$admin_bg_image    = !empty($general_bg_image['background-image']['url']) ? $general_bg_image['background-image']['url'] : '';
					$bg_repeat = !empty($general_bg_image['background-repeat']) ? $general_bg_image['background-repeat'] : 'no-repeat';
					$bg_position = !empty($general_bg_image['background-position']) ? $general_bg_image['background-position'] : 'center center';
					$bg_size = !empty($general_bg_image['background-size']) ? $general_bg_image['background-size'] : 'cover';
					$bg_attachment = !empty($general_bg_image['background-attachment']) ? $general_bg_image['background-attachment'] : 'scroll';

					if (!empty($admin_bg_image)) {
						$jltwp_adminify_output_body_css .= 'background-image: url(' . esc_attr($admin_bg_image) . ');';
						$jltwp_adminify_output_body_css .= 'background-repeat: ' . $bg_repeat . ';';
						$jltwp_adminify_output_body_css .= 'background-position: ' . $bg_position .';';
						$jltwp_adminify_output_body_css .= 'background-size: ' . $bg_size . ';';
						$jltwp_adminify_output_body_css .= 'background-attachment: ' . $bg_attachment . ';';
						$jltwp_adminify_output_body_css .= 'height: auto;';
					}
				}

				$jltwp_adminify_output_body_css .= '}';
			}

		}



		// Typography Settings
		if (!empty($admin_google_font)) {
			$jltwp_adminify_output_body_css .= 'html, body.wp-adminify, body.wp-adminify #wpadminbar *:not(#wpadminbar .ab-icon){';

			if (!empty($this->options['admin_general_google_font']['font-family'])) {
				$jltwp_adminify_output_body_css .= 'font-family: "' . esc_attr($this->options['admin_general_google_font']['font-family']) . '" !important;';
			}

			if (!empty($this->options['admin_general_google_font']['font-weight'])) {
				$jltwp_adminify_output_body_css .= 'font-weight: ' . esc_attr($this->options['admin_general_google_font']['font-weight']) . '  !important;';
			}

			if (!empty($this->options['admin_general_google_font']['font-style'])) {
				$jltwp_adminify_output_body_css .= 'font-style: ' . esc_attr($this->options['admin_general_google_font']['font-style']) . ';';
			}

			if (!empty($this->options['admin_general_google_font']['font-size'])) {
				$jltwp_adminify_output_body_css .= 'font-size: ' . esc_attr($this->options['admin_general_google_font']['font-size']) . 'px;';
			}

			if (!empty($this->options['admin_general_google_font']['line-height'])) {
				$jltwp_adminify_output_body_css .= 'line-height: ' . esc_attr($this->options['admin_general_google_font']['line-height']) . 'px;';
			}

			if (!empty($this->options['admin_general_google_font']['color'])) {
				$jltwp_adminify_output_body_css .= 'color: ' . esc_attr($this->options['admin_general_google_font']['color']) . ';';
			}

			$jltwp_adminify_output_body_css .= '}';
		}

		// Combine the values from above and minifiy them.
		$jltwp_adminify_output_body_css = preg_replace('#/\*.*?\*/#s', '', $jltwp_adminify_output_body_css);
		$jltwp_adminify_output_body_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $jltwp_adminify_output_body_css);
		$jltwp_adminify_output_body_css = preg_replace('/\s\s+(.*)/', '$1', $jltwp_adminify_output_body_css);

		printf('<style>%s</style>', wp_strip_all_tags( $jltwp_adminify_output_body_css ));


		// Slideshow Scripts
		// if ($this->admin_bg_type == 'slideshow') {
		// 	wp_enqueue_style('wp-adminify-vegas', WP_ADMINIFY_ASSETS . 'vendors/vegas/vegas.min.css');
		// 	wp_enqueue_script('wp-adminify-vegas', WP_ADMINIFY_ASSETS . 'vendors/vegas/vegas.min.js', ['jquery'], WP_ADMINIFY_VER, true);
		// }

		// // Video Scripts
		// if ($this->admin_bg_type == 'video') {
		// 	wp_enqueue_script('wp-adminify-vidim', WP_ADMINIFY_ASSETS . 'vendors/vidim/vidim.min.js', ['jquery'], WP_ADMINIFY_VER, true);
		// }
	}
}
