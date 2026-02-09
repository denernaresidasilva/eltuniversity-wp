<?php

namespace WPAdminify\Inc\Classes\Wizard;

use \WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Utils;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class Adminify_Setup_Wizard {
	public $options;

	public function __construct() {

		if (current_user_can('manage_options') && current_user_can('administrator')) {

			add_action('wp_ajax_wpadminify_save_wizard_data', [$this, 'wpadminify_save_wizard_data']);

			$this->options = (array) AdminSettings::get_instance()->get();

			$this->jltwp_adminify_setup_wizard();

			add_action('wp_ajax_adminify_drag_and_drop_image', [$this, 'adminify_drag_and_drop_image_callback']);

		}

	}

	// Drag and Drop Image
	public function adminify_drag_and_drop_image_callback() {
		check_ajax_referer('jltwp_adminify_sw');

		$data_source = empty($_POST['settings']) ? [] : (array) wp_kses_post_deep(wp_unslash($_POST['settings']));

		$base64_image = $data_source['image_data'];

		// Extract the base64 data (remove the data URI scheme)
		list($type, $data) = explode(';', $base64_image);
		list(, $data) = explode(',', $data);
		$decoded_data = base64_decode($data);

		// Define the output file path
		$upload_dir = wp_upload_dir();
		$upload_path = $upload_dir['path'] . '/' . $data_source['image_name'];

		// Save the image to the file
		if (file_put_contents($upload_path, $decoded_data) === false) {
			wp_send_json_error('Failed to save the image.');
		}

		// Get the URL of the uploaded image
		$upload_url = $upload_dir['url'] . '/' . basename($upload_path);

		wp_send_json_success($upload_url);

		wp_die();
	}

	public function validate_before_save($settings) {
		foreach ($settings as $key => $setting) {
			$settings[$key] = $this->maybe_convert_to_boolean($setting);
		}

		return $settings;
	}

	public function maybe_convert_to_boolean(&$setting) {
		if (gettype($setting) == 'string' && ($setting === 'true' || $setting === 'false')) {
			$setting = wp_validate_boolean($setting);
		} elseif (gettype($setting) == 'array') {
			foreach ($setting as $key => $_setting) {
				$setting[$key] = $this->maybe_convert_to_boolean($_setting);
			}
		}
		return $setting;
	}

	// TEXT validation
	public function wpadminify_text_validation($text) {
		return sanitize_text_field( wp_unslash( $text ?? '' ) );
	}

	public function wpadminify_save_wizard_data() {
		check_ajax_referer('jltwp_adminify_sw');

		$settings = empty($_POST['settings']) ? [] : (array) wp_kses_post_deep(wp_unslash($_POST['settings']));

		$validate_settings = $this->validate_before_save($settings);

		$settings = get_option('_wpadminify', []);

		// Adminify UI
		if( array_key_exists('admin_ui', $validate_settings) ) {
			$settings['admin_ui']	= !empty($validate_settings['admin_ui']) ? true : false;
		}

		// Admin Bar Logo
		if( array_key_exists('admin_ui_logo_type', $validate_settings) && array_key_exists('admin_ui_light_mode', $validate_settings)) {

			$settings['light_dark_mode']['admin_ui_logo_type'] = $validate_settings['admin_ui_logo_type'];
			if($validate_settings['admin_ui_logo_type'] === 'text_logo') {
				$settings['light_dark_mode']['admin_ui_light_mode']['admin_ui_light_logo_text'] = $this->wpadminify_text_validation( $validate_settings['admin_ui_light_mode']['admin_ui_light_logo_text'] );
			}
			if($validate_settings['admin_ui_logo_type'] === 'image_logo') {
				$settings['light_dark_mode']['admin_ui_light_mode']['admin_ui_light_logo']['url'] = wp_http_validate_url($validate_settings['admin_ui_light_mode']['admin_ui_light_logo']['url']);
			}

		}

		// WP Footer Text
		if( array_key_exists('footer_text', $validate_settings) ) {
			$settings['white_label']['wordpress']['footer_text'] = wp_kses_post( $validate_settings['footer_text'] );
		}

		update_option('_wpadminify', $settings);
		wp_send_json_success(
			[
				'redirect' => true,
			]
		);
	}

	public function load_scripts() {

		// Register
		// wp_register_script('wp-adminify-vue-vendors', WP_ADMINIFY_ASSETS . 'admin/js/vendor' . Utils::assets_ext('.js'), [], WP_ADMINIFY_VER, true);
		wp_register_style('wp-adminify-sw-setup', WP_ADMINIFY_ASSETS . 'css/setup.css');
		wp_register_script('wp-adminify-sw-setup', WP_ADMINIFY_ASSETS . 'admin/js/wp-adminify--setup-wizard' . Utils::assets_ext('.js'), ['react', 'jquery'], WP_ADMINIFY_VER, true);

		// Media uploader
		wp_enqueue_media();

		// Load
		wp_enqueue_style('wp-adminify-sw-setup');
		// wp_enqueue_script('media-upload');
		wp_enqueue_script('wp-adminify-sw-setup');


		// Localize Script
		$adminify_data = [
			// 'rest_base'         => $this->get_rest_url(''),
			'ajax_url'         	=> admin_url('admin-ajax.php'),
			'settings' 			=> [
				'admin_ui'            		=> $this->options['admin_ui'],
				'admin_ui_logo_type' 		=> $this->options['light_dark_mode']['admin_ui_logo_type'],
				'admin_ui_light_mode'		=> [
					'admin_ui_light_logo' => [
						'url' => $this->options['light_dark_mode']['admin_ui_light_mode']['admin_ui_light_logo']['url']
					],
					'admin_ui_light_logo_text' => $this->options['light_dark_mode']['admin_ui_light_mode']['admin_ui_light_logo_text'] ?? '',
				],
				'footer_text'				=> $this->options['white_label']['wordpress']['footer_text'],
			],
			'images'   			=> WP_ADMINIFY_ASSETS_IMAGE,
			'wpnonce'  			=> wp_create_nonce('jltwp_adminify_sw'),
			'rest_nonce'    => wp_create_nonce('wp_rest')
		];

		wp_localize_script('wp-adminify-sw-setup', 'adminify_setup_wizard_data', $adminify_data);


		// // Dequeue Styles
		// // wp_dequeue_style('install');
		// wp_deregister_style('install');
		// // wp_deregister_style('dashicons');

		// // Dequeue Scripts
		// wp_deregister_script('debug-bar');
		// wp_deregister_script('debug-bar-js');
		// wp_deregister_script('admin-bar');
		// wp_deregister_script('shortcode');

	}

	public function jltwp_adminify_setup_wizard() {
		global $hook_suffix;

		if (empty($_GET['page']) || 'wp-adminify-setup-wizard' !== $_GET['page']) {
			return;
		}

		require_once ABSPATH . WPINC . '/media-template.php';
		// add_action( 'admin_footer', 'wp_print_media_templates' );
		// add_action( 'wp_footer', 'wp_print_media_templates' );
		// add_action( 'customize_controls_print_footer_scripts', 'wp_print_media_templates' );

		add_action( 'admin_print_footer_scripts', 'wp_print_media_templates' );

		$this->load_scripts();

		echo '<div id="wp-adminify--setup-wizard" class="wp-adminify-sw-setup-content"></div>';

		// do_action('admin_footer');
		// do_action("admin_print_footer_scripts-{$hook_suffix}");
		do_action('admin_print_footer_scripts');
		do_action("admin_footer-{$hook_suffix}");

		exit;
	}

}
