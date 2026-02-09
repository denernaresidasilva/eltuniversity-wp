<?php
namespace WPAdminify\Pro;

use WPAdminify\Inc\Classes\Tweaks;
use WPAdminify\Inc\Admin\AdminSettings;
// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @module Tweaks
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class Tweaks_Pro extends Tweaks{

	public $options;
	public $current_url_path;
	public $heartbeat_api;

	public function __construct()
	{
		$this->options = (array) AdminSettings::get_instance()->get();
		$this->heartbeat_api = $this->options['heartbeat_api'];

		$this->media_attachments();
		$this->performances();
		// $this->security_others();

		add_action('admin_head', [$this, 'change_admin_area_logo'], 99);

		if (!empty($this->options['media_attachments']['allowed_upload_files'])) {
			add_filter('upload_mimes', [$this, 'allow_files_upload_mime_types']);
			add_filter( 'wp_check_filetype_and_ext', [$this, 'check_uploaded_filetype_extension'], 10, 5 );

			// WebP support
			// add_filter('file_is_displayable_image', 'display_webp_media', 10, 2);
		}

		// Convert Uploaded Images to WebP Format
		if (!empty($this->options['media_attachments']['convert_to_webp'])) {
			add_filter('wp_handle_upload', [$this, 'handle_upload_convert_to_webp']);
		}

		if (!empty($this->options['media_attachments']['convert_to_webp'])) {
			add_filter('post_thumbnail_html', [$this, 'autolink_featured_images'], 20, 3);
		}


		// Remove Update & New Content
		if(!empty($this->options['white_label']['wordpress']['admin_bar_cleanup'])){
			add_action("wp_before_admin_bar_render", [$this, "admin_bar_cleanup"], 0);
		}
	}

	/**
	 * Remove Update & New Content
	 */
	public function admin_bar_cleanup() {
		global $wp_admin_bar;

		if (in_array('updates', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
			$wp_admin_bar->remove_menu('updates');
		}

		if (in_array('new_content', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
			$wp_admin_bar->remove_menu('new-content');
		}
	}


	/**
	 * Wrap the thumbnail in a link to the post.
	 * Only use this if your theme doesn't already wrap thumbnails in a link.
	 *
	 * @param string $html The thumbnail HTML to wrap in an anchor.
	 * @param int    $post_id The post ID.
	 * @param int    $post_image_id The image id.
	 *
	 * @return string
	 */
	function autolink_featured_images($html, $post_id, $post_image_id)
	{
		$html = '<a href="' . get_permalink($post_id) . '" title="' . esc_attr(get_the_title($post_id)) . '">' . esc_html($html) . '</a>';
		return $html;
	}

	/**
     * Remove Gutenberg hooks added via feature plugin.
     *
     * @link https://plugins.trac.wordpress.org/browser/classic-editor/tags/1.6.2/classic-editor.php#L138
     */
    public function remove_all_gutenberg_hooks() {

        remove_action( 'admin_menu', 'gutenberg_menu' );
        remove_action( 'admin_init', 'gutenberg_redirect_demo' );

        // Gutenberg 5.3+
        remove_action( 'wp_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
        remove_action( 'admin_enqueue_scripts', 'gutenberg_register_scripts_and_styles' );
        remove_action( 'admin_notices', 'gutenberg_wordpress_version_notice' );
        remove_action( 'rest_api_init', 'gutenberg_register_rest_widget_updater_routes' );
        remove_action( 'admin_print_styles', 'gutenberg_block_editor_admin_print_styles' );
        remove_action( 'admin_print_scripts', 'gutenberg_block_editor_admin_print_scripts' );
        remove_action( 'admin_print_footer_scripts', 'gutenberg_block_editor_admin_print_footer_scripts' );
        remove_action( 'admin_footer', 'gutenberg_block_editor_admin_footer' );
        remove_action( 'admin_enqueue_scripts', 'gutenberg_widgets_init' );
        remove_action( 'admin_notices', 'gutenberg_build_files_notice' );

        remove_filter( 'load_script_translation_file', 'gutenberg_override_translation_file' );
        remove_filter( 'block_editor_settings', 'gutenberg_extend_block_editor_styles' );
        remove_filter( 'default_content', 'gutenberg_default_demo_content' );
        remove_filter( 'default_title', 'gutenberg_default_demo_title' );
        remove_filter( 'block_editor_settings', 'gutenberg_legacy_widget_settings' );
        remove_filter( 'rest_request_after_callbacks', 'gutenberg_filter_oembed_result' );

        // Previously used, compat for older Gutenberg versions.
        remove_filter( 'wp_refresh_nonces', 'gutenberg_add_rest_nonce_to_heartbeat_response_headers' );
        remove_filter( 'get_edit_post_link', 'gutenberg_revisions_link_to_editor' );
        remove_filter( 'wp_prepare_revision_for_js', 'gutenberg_revisions_restore' );

        remove_action( 'rest_api_init', 'gutenberg_register_rest_routes' );
        remove_action( 'rest_api_init', 'gutenberg_add_taxonomy_visibility_field' );
        remove_filter( 'registered_post_type', 'gutenberg_register_post_prepare_functions' );

        remove_action( 'do_meta_boxes', 'gutenberg_meta_box_save' );
        remove_action( 'submitpost_box', 'gutenberg_intercept_meta_box_render' );
        remove_action( 'submitpage_box', 'gutenberg_intercept_meta_box_render' );
        remove_action( 'edit_page_form', 'gutenberg_intercept_meta_box_render' );
        remove_action( 'edit_form_advanced', 'gutenberg_intercept_meta_box_render' );
        remove_filter( 'redirect_post_location', 'gutenberg_meta_box_save_redirect' );
        remove_filter( 'filter_gutenberg_meta_boxes', 'gutenberg_filter_meta_boxes' );

        remove_filter( 'body_class', 'gutenberg_add_responsive_body_class' );
        remove_filter( 'admin_url', 'gutenberg_modify_add_new_button_url' ); // old
        remove_action( 'admin_enqueue_scripts', 'gutenberg_check_if_classic_needs_warning_about_blocks' );
        remove_filter( 'register_post_type_args', 'gutenberg_filter_post_type_labels' );
    }

	// Enable WebP thumbnails
	function display_webp_media($result, $path) {
		if ($result === false) {
			$info = pathinfo($path);
			$ext = $info['extension'];
			if ($ext === 'webp') {
				return array(
					'ext' => 'jpg',
					'mime-type' => 'image/jpeg'
				);
			}
		}
		return $result;
	}

	public function allow_files_upload_mime_types($mime_types){
		// By default, only administrator users are allowed to add SVGs.
		// To enable more user types edit or comment the lines below but beware of
		// the security risks if you allow any user to upload SVG files.
		if (!current_user_can('administrator')) {
			return $mime_types;
		}

		$allowed_upload_files = $this->options['media_attachments']['allowed_upload_files'];
		if (!empty($allowed_upload_files) && in_array('svg', $allowed_upload_files)) {
			$mime_types['svg']  = 'image/svg+xml';
		}
		if (!empty($allowed_upload_files) && in_array('webp', $allowed_upload_files)) {
			$mime_types['webp'] = 'image/webp';
		}
		if (!empty($allowed_upload_files) && in_array('avif', $allowed_upload_files)) {
			$mime_types['avif'] = 'image/avif';
		}
		if (!empty($allowed_upload_files) && in_array('ico', $allowed_upload_files)) {
			$mime_types['ico']  = 'image/vnd.microsoft.icon';
		}

		return $mime_types;
	}

	/**
	 * Add SVG files mime check.
	 *
	 * @param array        $wp_check_filetype_and_ext Values for the extension, mime type, and corrected filename.
	 * @param string       $file Full path to the file.
	 * @param string       $filename The name of the file (may differ from $file due to $file being in a tmp directory).
	 * @param string[]     $mimes Array of mime types keyed by their file extension regex.
	 * @param string|false $real_mime The actual mime type or false if the type cannot be determined.
	 */
	public function check_uploaded_filetype_extension($wp_check_filetype_and_ext, $file, $filename, $mimes, $real_mime){
		if (!$wp_check_filetype_and_ext['type']) {
			$check_filetype  = wp_check_filetype($filename, $mimes);
			$ext             = $check_filetype['ext'];
			$type            = $check_filetype['type'];
			$proper_filename = $filename;
			if ($type && 0 === strpos($type, 'image/') && 'svg' !== $ext) {
				$ext  = false;
				$type = false;
			}
			$wp_check_filetype_and_ext = compact('ext', 'type', 'proper_filename');
		}
		return $wp_check_filetype_and_ext;
	}


	/**
	 * Convert Uploaded Images to WebP Format
	 *
	 * This snippet converts uploaded images (JPEG, PNG, GIF) to WebP format
	 * automatically in WordPress. Ideal for use in a theme's functions.php file,
	 * or with plugins like Code Snippets or WPCodeBox.
	 *
	 * Usage Instructions:
	 * - Add this snippet to your theme's functions.php file, or add it as a new
	 *   snippet in Code Snippets or WPCodeBox.
	 * - The snippet hooks into WordPress's image upload process and converts
	 *   uploaded images to the WebP format.
	 *
	 * Optional Configuration:
	 * - By default, the original image file is deleted after conversion to WebP.
	 *   If you prefer to keep the original image file, simply comment out or remove
	 *   the line '@unlink( $file_path );' in the wpturbo_handle_upload_convert_to_webp function.
	 *   This will preserve the original uploaded image file alongside the WebP version.
	 */

	public function handle_upload_convert_to_webp($upload){
		if ($upload['type'] == 'image/jpeg' || $upload['type'] == 'image/png' || $upload['type'] == 'image/gif') {
			$file_path = $upload['file'];
			// Check if ImageMagick or GD is available
			if (extension_loaded('imagick') || extension_loaded('gd')) {
				$image_editor = wp_get_image_editor($file_path);
				if (!is_wp_error($image_editor)) {
					$file_info = pathinfo($file_path);
					$dirname   = $file_info['dirname'];
					$filename  = $file_info['filename'];
					// Create a new file path for the WebP image
					$new_file_path = $dirname . '/' . $filename . '.webp';
					// Attempt to save the image in WebP format
					$saved_image = $image_editor->save($new_file_path, 'image/webp');
					if (!is_wp_error($saved_image) && file_exists($saved_image['path'])) {
						// Success: replace the uploaded image with the WebP image
						$upload['file'] = $saved_image['path'];
						$upload['url']  = str_replace(basename($upload['url']), basename($saved_image['path']), $upload['url']);
						$upload['type'] = 'image/webp';
						// Optionally remove the original image
						@unlink($file_path);
					}
				}
			}
		}
		return $upload;
	}
	/**
	 * Disable Automatic Updates Emails
	 *
	 * @return void
	 */
	public function security_others(){

		// Disable Automatic Updates
		if (!empty($this->options['disable_automatic_emails'])) {
			// Disable auto-update emails.
			add_filter('auto_core_update_send_email', '__return_false');
			// Disable auto-update emails for plugins.
			add_filter('auto_plugin_update_send_email', '__return_false');
			// Disable auto-update emails for themes.
			add_filter('auto_theme_update_send_email', '__return_false');
		}

		// Disable Language Switching
		if (!empty($this->options['disable_language_switcher_login'])) {
			add_filter('login_display_language_dropdown', '__return_false');
		}

	}


	// Gutenberg Editor WordPress Logo Change
	public function change_admin_area_logo()
	{
		if (!empty($this->options['screen_help_tab']['enable_for_screen'])) {
			$this->remove_tabs();
		}


		if (jltwp_adminify()->can_use_premium_code__premium_only()) {
			if (!empty($this->options['admin_favicon_logo'])) {
				echo '<link rel="shortcut icon" type="image/x-icon" href="' . esc_url($this->options['admin_favicon_logo']['url']) . '" />';
			}
		}

		// it is not a necessary thing but it prevents this CSS to be added on every WordPress admin page
		$screen = get_current_screen();
		if (!$screen->is_block_editor) {
			return;
		}

		$gutenberg_editor_logo = $this->options['gutenberg_editor_logo'];
		if (!empty($gutenberg_editor_logo['url'])) {
			$gutenberg_editor_logo_url = $gutenberg_editor_logo['url'];

			/* adds a custom image */

			echo '<style>

					body.js.is-fullscreen-mode .edit-post-header a.components-button:before{
						background-image: url( ' . esc_url($gutenberg_editor_logo_url) . ' );
						background-size: cover;
						/* you can the image paddings with the parameters below*/
						top: 10px;
						right: 10px;
						bottom: 10px;
						left: 10px;
						border-radius: 50%;
					}

					body.js.is-fullscreen-mode .edit-post-header a.components-button svg {
						display: none !important;
					}

					.components-tab-panel__tabs-item:before {
						top: 32px;
					}
					.wp-adminify.is-fullscreen-mode #wpadminbar{
						display: none !important;
					}

				</style>';
		}
	}


	public function media_attachments(){
		// Media Library Infinite Scroll
		if(!empty($this->options['media_attachments']['media_ininite_scroll'])){
			add_filter('media_library_infinite_scrolling', '__return_true');
		}
	}

	public function performances(){

		// Disable All Embeds
		if(!empty($this->options['disable_embeds'])){
			add_action('init', [$this, 'disable_embeds'], 9999);
		}

		// Heartbit Control
		if(!empty($this->heartbeat_api['enabled']) ){
			add_filter('heartbeat_settings', [ $this, 'change_heartbeat_frequency'], 99, 2);
			add_action('admin_enqueue_scripts', [ $this, 'change_disable_heartbeat'], 99);
			add_action('wp_enqueue_scripts', [ $this, 'change_disable_heartbeat'], 99);
		}

		// Revisions Control
		if (!empty($this->options['revisions'] && !empty($this->options['revisions']['revisions_enable']) )) {
			add_filter('wp_revisions_to_keep', [ $this, 'max_limit_posts_revisions'], 10, 2);
		}
	}

	/**
	 * Maximum Number of revisions
	 */

	public function max_limit_posts_revisions( $num, $post  ){
		$post_types = $this->options['revisions']['post_types'];
		$revisions_limit = $this->options['revisions']['limit'];

		$limited_post_types = array();
		foreach ($post_types as $key => $value) {
			if ($value) {
				$limited_post_types[] = $key;
			}
		}

		// Adjust the number of revisions to keep if set for the post type accordingly.
		$post_type = $post->post_type;
		if (in_array($post_type, $limited_post_types)) {
			$num = $revisions_limit;
		}

		return $num;
	}

	/**
	 * Change Heartbit Frequency
	 * @since 4.0
	 * @return void
	 */
	public function change_heartbeat_frequency( $settings ){

		if (wp_doing_cron()) {
			return $settings;
		}

		$this->get_url_path();

		// Disable heartbeat autostart
		$settings['autostart'] = false;

		if (is_admin()) {

			if ('/wp-admin/post.php' == $this->current_url_path || '/wp-admin/post-new.php' == $this->current_url_path) {

				// Consider modifying the interval on post edit screens to reduce CPU load.
				if ( !empty($this->heartbeat_api['on_post_create']) && 'modify' == $this->heartbeat_api['on_post_create']) {
					$settings['minimalInterval'] = absint($this->heartbeat_api['on_post_create_modify']);
				}
			} else {

				// Consider modifying the interval on back-end.
				if (!empty($this->heartbeat_api['backend']) && 'modify' == $this->heartbeat_api['backend']) {
					$settings['minimalInterval'] = absint($this->heartbeat_api['backend_modify']);
				}
			}
		} else {

			// Maybe modify interval on the frontend
			if ( !empty($this->heartbeat_api['on_frontend']) && 'modify' == $this->heartbeat_api['on_frontend']) {
				$settings['minimalInterval'] = absint($this->heartbeat_api['on_frontend_modify']);
			}
		}
		return $settings;
	}


	/**
	 * Consider disabling Heartbeat ticks based on settings for each location.
	 *
	 * @since 4.0
	 */
	public function change_disable_heartbeat()
	{

		global $pagenow;

		if (is_admin()) {
			if ('post.php' == $pagenow || 'post-new.php' == $pagenow) {

				// Maybe disable on post creation / edit screens
				if ( !empty($this->heartbeat_api['on_post_create']) && 'disable' == $this->heartbeat_api['on_post_create']) {
					wp_deregister_script('heartbeat');
					return;
				}
			} else {
				// Maybe disable on the rest of admin pages
				if (!empty($this->heartbeat_api['backend']) && 'disable' == $this->heartbeat_api['backend']) {
					wp_deregister_script('heartbeat');
					return;
				}
			}
		} else {

			// Maybe disable on the frontend
			if ( !empty($this->heartbeat_api['on_frontend']) && 'disable' == $this->heartbeat_api['on_frontend']) {
				wp_deregister_script('heartbeat');
				return;
			}
		}
	}

	/**
	 * Set current location
	 * Supported locations [editor,dashboard,frontend]
	 */
	public function get_url_path()
	{

		global $pagenow;

		if (isset($_SERVER['HTTP_HOST'])) {
			$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER["HTTP_HOST"] . '' . $_SERVER["REQUEST_URI"];
		} else {
			$url = get_admin_url() . $pagenow;
		}

		$request_path = parse_url($url, PHP_URL_PATH); // e.g. '/wp-admin/post.php'
		$this->current_url_path = $request_path;
	}

	/**
	 * Disable all embeds in WordPress.
	 */
	public function disable_embeds() {
		// Remove the REST API endpoint.
		remove_action('rest_api_init', 'wp_oembed_register_route');
		// Turn off oEmbed auto discovery.
		add_filter('embed_oembed_discover', '__return_false');
		// Don't filter oEmbed results.
		remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);
		// Remove oEmbed discovery links.
		remove_action('wp_head', 'wp_oembed_add_discovery_links');
		// Remove oEmbed-specific JavaScript from the front-end and back-end.
		remove_action('wp_head', 'wp_oembed_add_host_js');
		add_filter('tiny_mce_plugins', function ($plugins) {
			return array_diff($plugins, array('wpembed'));
		});
		// Remove all embeds rewrite rules.
		add_filter('rewrite_rules_array', function ($rules) {
			foreach ($rules as $rule => $rewrite) {
				if (false !== strpos($rewrite, 'embed=true')) {
					unset($rules[$rule]);
				}
			}
			return $rules;
		});
		// Remove filter of the oEmbed result before any HTTP requests are made.
		remove_filter('pre_oembed_result', 'wp_filter_pre_oembed_result', 10);
	}

	/**
	 * Remove Screen Tabs
	 *
	 * @return void
	 */
	public function remove_tabs(){
		if( !empty($this->options['screen_help_tab']['screen_help_data']) && in_array('hide_help_tab', $this->options['screen_help_tab']['screen_help_data'] ) ) {
			$screen = get_current_screen();
			if( !empty( $screen ) ) {
				$screen->remove_help_tabs();
			}
		}

		if( !empty($this->options['screen_help_tab']['screen_help_data']) && in_array('hide_screen_options', $this->options['screen_help_tab']['screen_help_data'] ) ) {
			add_filter('screen_options_show_screen', '__return_false');
		}
	}
}
