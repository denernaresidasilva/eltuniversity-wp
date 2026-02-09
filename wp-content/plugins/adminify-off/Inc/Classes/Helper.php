<?php

namespace WPAdminify\Inc\Classes;
use WPAdminify\Inc\Utils;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class Helper
{

	// Admin Path
	public static function jltwp_adminify_admin_path($path)
	{
		// Get custom filter path
		if (has_filter('jltwp_adminify_admin_path')) {
			return apply_filters('jltwp_adminify_admin_path', $path);
		}

		// Get plugin path
		return plugins_url($path, __DIR__);
	}

	/**
	 * Get the editor/ builder of the given post.
	 *
	 * @param int $post_id ID of the post being checked.
	 * @return string The content editor name.
	 */
	public static function get_content_editor($post_id)
	{
		$content_editor = 'default';
		$content_editor = apply_filters('udb_content_editor', $content_editor, $post_id);

		return $content_editor;
	}

	/**
	 * Sanitize Checkbox.
	 *
	 * @param string|bool $checked Customizer option.
	 */
	public function sanitize_checkbox($checked)
	{
		return ((isset($checked) && true === $checked) ? true : false);
	}



	/**
	 * Check if Gutenberg Block Editor Page
	 *
	 * WordPress v5+
	 *
	 * @return boolean
	 */
	public static function is_block_editor_page()
	{
		if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
			// The Gutenberg plugin is on.
			return true;
		}

		if (!function_exists('get_current_screen')) {
			require_once ABSPATH . '/wp-admin/includes/screen.php';
			$current_screen = get_current_screen();
			if (function_exists('get_current_screen')) {
				if (!is_null($current_screen) && $current_screen->is_block_editor) {
					// Gutenberg page on 5+.
					return true;
				}
			}
		}
		return false;
	}


	/**
	 * Classic Editor Page
	 *
	 * @return boolean
	 */
	public static function is_classic_editor_page()
	{
		if (function_exists('get_current_screen')) {
			$current_screen = get_current_screen();
			if ($current_screen->base == 'post' && empty($current_screen->is_block_editor)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if Elementor Page
	 *
	 * @return boolean
	 */
	public static function is_elementor_editor_page()
	{
		return !empty($_GET['elementor-preview']);
	}

	/**
	 * Image sanitization callback.
	 *
	 * Checks the image's file extension and mime type against a whitelist. If they're allowed,
	 * send back the filename, otherwise, return the setting default.
	 *
	 * - Sanitization: image file extension
	 * - Control: text, WP_Customize_Image_Control
	 *
	 * @see wp_check_filetype() https://developer.wordpress.org/reference/functions/wp_check_filetype/
	 *
	 * @version 1.2.2
	 *
	 * @param string               $image   Image filename.
	 * @param WP_Customize_Setting $setting Setting instance.
	 *
	 * @return string The image filename if the extension is allowed; otherwise, the setting default.
	 */
	public static function sanitize_image($image, $setting)
	{

		/**
		 * Array of valid image file types.
		 *
		 * The array includes image mime types that are included in wp_get_mime_types()
		 */
		$mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'bmp'          => 'image/bmp',
			'tif|tiff'     => 'image/tiff',
			'ico'          => 'image/x-icon',
		);

		// Allowed svg mime type in version 1.2.2.
		$allowed_mime   = get_allowed_mime_types();
		$svg_mime_check = isset($allowed_mime['svg']);

		if ($svg_mime_check) {
			$allow_mime = array('svg' => 'image/svg+xml');
			$mimes      = array_merge($mimes, $allow_mime);
		}

		// Return an array with file extension and mime_type.
		$file = wp_check_filetype($image, $mimes);

		// If $image has a valid mime_type, return it; otherwise, return the default.
		return esc_url_raw(($file['ext'] ? $image : $setting->default));
	}

	/**
	 * Get User Capabilities
	 *
	 * @param [type] $user
	 *
	 * @return void
	 */
	public static function get_user_capabilities($user = null)
	{
		$user = $user ? new \WP_User($user) : wp_get_current_user();

		return array_keys($user->allcaps);
	}

	/**
	 * Get User Roles
	 *
	 * @param [type] $user
	 *
	 * @return void
	 */
	static function get_user_roles($user)
	{
		return $user->roles;
	}

	static function nocache_headers()
	{
		if (headers_sent()) {
			return;
		}

		$headers = wp_get_nocache_headers();

		unset($headers['Last-Modified']);

		header_remove('Last-Modified');

		foreach ($headers as $name => $field_value) {
			header("{$name}: {$field_value}");
		}
	}


	/**
	 * Set allowed Hosts
	 *
	 * @param [type] $url
	 *
	 * @return void
	 */
	public static function allowed_host($url)
	{
		$url_parsed = parse_url($url);
		if (isset($url_parsed['host'])) {
			$allowed_hosts[] = $url_parsed['host'];
			add_filter('allowed_redirect_hosts', function ($hosts) use ($allowed_hosts) {
				return array_merge($hosts, $allowed_hosts);
			});
		}
	}


	/**
	 * Get Capabilites
	 *
	 * @return void
	 */
	public static function get_capabilities()
	{
		global $wp_roles;

		$caps = array();

		foreach ($wp_roles->roles as $wp_role) {
			if (isset($wp_role['capabilities']) && is_array($wp_role['capabilities'])) {
				$caps = array_merge($caps, array_keys($wp_role['capabilities']));
			}
		}

		$caps = array_unique($caps);

		$caps = (array) apply_filters('adminify_capabilities', $caps);

		sort($caps);

		return array_combine($caps, $caps);
	}

	/**
	 * Get User Capability Options
	 *
	 * @return void
	 */
	public static function get_capability_options()
	{
		$capabilities = self::get_capabilities();
		$_capabilities = [];

		foreach ($capabilities as $capability) {
			$_capabilities[] = $capability;
		}
		return $_capabilities;
	}


	/**
	 * Remove spaces from Plugin Slug
	 */
	public static function jltwp_adminify_slug_cleanup()
	{
		return str_replace('-', '_', strtolower(WP_ADMINIFY_SLUG));
	}

	/**
	 * Function current_datetime() compability for wp version < 5.3
	 *
	 * @return DateTimeImmutable
	 */
	public static function jltwp_adminify_current_datetime()
	{
		if (function_exists('current_datetime')) {
			return current_datetime();
		}

		return new \DateTimeImmutable('now', self::jltwp_adminify_wp_timezone());
	}

	/**
	 * Function jltwp_adminify_wp_timezone() compability for wp version < 5.3
	 *
	 * @return DateTimeZone
	 */
	public static function jltwp_adminify_wp_timezone()
	{
		if (function_exists('wp_timezone')) {
			return wp_timezone();
		}

		return new \DateTimeZone(self::jltwp_adminify_wp_timezone_string());
	}

	/**
	 * API Endpoint
	 *
	 * @return string
	 */
	public static function api_endpoint()
	{
		$api_endpoint_url = 'https://bo.jeweltheme.com';
		$api_endpoint     = apply_filters('jltwp_adminify_endpoint', $api_endpoint_url);

		return trailingslashit($api_endpoint);
	}

	/**
	 * CRM Endpoint
	 *
	 * @return string
	 */
	public static function crm_endpoint()
	{
		$crm_endpoint_url = 'https://bo.jeweltheme.com/wp-json/jlt-api/v1/subscribe'; // Endpoint .
		$crm_endpoint     = apply_filters('jltwp_adminify_crm_crm_endpoint', $crm_endpoint_url);

		return trailingslashit($crm_endpoint);
	}

	/**
	 * CRM Endpoint
	 *
	 * @return string
	 */
	public static function crm_survey_endpoint()
	{
		$crm_feedback_endpoint_url = 'https://bo.jeweltheme.com/wp-json/jlt-api/v1/survey'; // Endpoint .
		$crm_feedback_endpoint     = apply_filters('jltwp_adminify_crm_crm_endpoint', $crm_feedback_endpoint_url);

		return trailingslashit($crm_feedback_endpoint);
	}

	/**
	 * Function jltwp_adminify_wp_timezone_string() compability for wp version < 5.3
	 *
	 * @return string
	 */
	public static function jltwp_adminify_wp_timezone_string()
	{
		$timezone_string = get_option('timezone_string');

		if ($timezone_string) {
			return $timezone_string;
		}

		$offset  = (float) get_option('gmt_offset');
		$hours   = (int) $offset;
		$minutes = ($offset - $hours);

		$sign      = ($offset < 0) ? '-' : '+';
		$abs_hour  = abs($hours);
		$abs_mins  = abs($minutes * 60);
		$tz_offset = sprintf('%s%02d:%02d', $sign, $abs_hour, $abs_mins);

		return $tz_offset;
	}

	/**
	 * Get Merged Data
	 *
	 * @param [type] $data .
	 * @param string $start_date .
	 * @param string $end_data .
	 *
	 * @author Jewel Theme <support@jeweltheme.com>
	 */
	public static function get_merged_data($data, $start_date = '', $end_data = '')
	{
		$_data = shortcode_atts(
			array(
				'image_url'        => WP_ADMINIFY_ASSETS_IMAGE . '/promo-image.png',
				'start_date'       => $start_date,
				'end_date'         => $end_data,
				'counter_time'     => '',
				'is_campaign'      => 'false',
				'button_text'      => 'Get Premium',
				'button_url'       => 'https://wpadminify.com/pricing',
				'btn_color'        => '#CC22FF',
				'notice'           => '',
				'notice_timestamp' => '',
			),
			$data
		);

		if (empty($_data['image_url'])) {
			$_data['image_url'] = WP_ADMINIFY_ASSETS_IMAGE . '/promo-image.png';
		}

		return $_data;
	}


	/**
	 * wp_kses attributes map
	 *
	 * @param array $attrs .
	 *
	 * @author Jewel Theme <support@jeweltheme.com>
	 */
	public static function wp_kses_atts_map(array $attrs)
	{
		return array_fill_keys(array_values($attrs), true);
	}

	/**
	 * Custom method
	 *
	 * @param [type] $content .
	 *
	 * @author Jewel Theme <support@jeweltheme.com>
	 */
	public static function wp_kses_custom($content)
	{
		/**
		 * the context can be different 
		 * The context for which to retrieve tags. Allowed values are 'post', 'strip', 'data', 'entities', or the name of a field filter such as 'pre_user_description', or an array of allowed HTML elements and attributes.
		 * the post means here it will return all the tags that are allowed in post such as a, p, strong, em etc
		*/
		$allowed_tags = wp_kses_allowed_html('post'); // Details : https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/
		if( !is_array($allowed_tags) ) $allowed_tags = [];

		$custom_tags = array(
			'select'         => self::wp_kses_atts_map(array('class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'multiple', 'required', 'size')),
			'input'          => self::wp_kses_atts_map(array('class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'required', 'size', 'type', 'checked', 'readonly', 'placeholder', 'value', 'maxlength', 'min', 'max', 'multiple', 'pattern', 'step', 'autocomplete')),
			'textarea'       => self::wp_kses_atts_map(array('class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'required', 'rows', 'cols', 'wrap', 'maxlength')),
			'option'         => self::wp_kses_atts_map(array('class', 'id', 'label', 'disabled', 'label', 'selected', 'value')),
			'optgroup'       => self::wp_kses_atts_map(array('disabled', 'label', 'class', 'id')),
			'form'           => self::wp_kses_atts_map(array('class', 'id', 'data', 'style', 'width', 'height', 'accept-charset', 'action', 'autocomplete', 'enctype', 'method', 'name', 'novalidate', 'rel', 'target')),
			'svg'            => self::wp_kses_atts_map(array('class', 'xmlns', 'viewbox', 'width', 'height', 'fill', 'aria-hidden', 'aria-labelledby', 'role')),
			'rect'           => self::wp_kses_atts_map(array('rx', 'width', 'height', 'fill')),
			'path'           => self::wp_kses_atts_map(array('d', 'fill')),
			'g'              => self::wp_kses_atts_map(array('fill')),
			'defs'           => self::wp_kses_atts_map(array('fill')),
			'linearGradient' => self::wp_kses_atts_map(array('id', 'x1', 'x2', 'y1', 'y2', 'gradientUnits')),
			'stop'           => self::wp_kses_atts_map(array('stop-color', 'offset', 'stop-opacity')),
			'style'          => self::wp_kses_atts_map(array('type')),
			'div'            => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'ul'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'li'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'label'          => self::wp_kses_atts_map(array('class', 'for')),
			'span'           => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h1'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h2'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h3'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h4'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h5'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'h6'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'a'              => self::wp_kses_atts_map(array('class', 'href', 'target', 'rel')),
			'p'              => self::wp_kses_atts_map(array('class', 'id', 'style', 'data')),
			'table'          => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'thead'          => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'tbody'          => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'tr'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'th'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'td'             => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'i'              => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'button'         => self::wp_kses_atts_map(array('class', 'id')),
			'nav'            => self::wp_kses_atts_map(array('class', 'id', 'style')),
			'time'           => self::wp_kses_atts_map(array('datetime')),
			'br'             => array(),
			'strong'         => array(),
			'style'          => array(),
			'img'            => self::wp_kses_atts_map(array('class', 'src', 'alt', 'height', 'width', 'srcset', 'id', 'loading')),
		);

		$allowed_tags = array_merge_recursive($allowed_tags, $custom_tags);

		return wp_kses(stripslashes_deep($content), $allowed_tags);
	}


	/**
	 * Dashboard Icons
	 *
	 * @author Jewel Theme <support@jeweltheme.com>
	 */
	public static function dashboard_icons($icon_name = '') {

		if($icon_name === 'dashboard' ){
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.09 0H0.91C0.41 0 0 0.41 0 0.91V4.09C0 4.59 0.41 5 0.91 5H4.09C4.59 5 5 4.59 5 4.09V0.91C5 0.41 4.59 0 4.09 0ZM4.09 4.09H0.91V0.91H4.09V4.09Z" fill="#48455F"/>
					<path d="M11.09 0H7.91C7.41 0 7 0.41 7 0.91V4.09C7 4.59 7.41 5 7.91 5H11.09C11.59 5 12 4.59 12 4.09V0.91C12 0.41 11.59 0 11.09 0ZM11.09 4.09H7.91V0.91H11.09V4.09Z" fill="#48455F"/>
					<path d="M4.09 7H0.91C0.41 7 0 7.41 0 7.91V11.09C0 11.59 0.41 12 0.91 12H4.09C4.59 12 5 11.59 5 11.09V7.91C5 7.41 4.59 7 4.09 7ZM4.09 11.09H0.91V7.91H4.09V11.09Z" fill="#48455F"/>
					<path d="M11.09 7H7.91C7.41 7 7 7.41 7 7.91V11.09C7 11.59 7.41 12 7.91 12H11.09C11.59 12 12 11.59 12 11.09V7.91C12 7.41 11.59 7 11.09 7ZM11.09 11.09H7.91V7.91H11.09V11.09Z" fill="#48455F"/>
					</svg>';
		}
		if ($icon_name === 'post') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M11 0.999971V11H1V0.999971H11ZM12 -6.10352e-05H6.34789e-06L0 11.9999H12V-6.10352e-05Z" fill="#48455F"/>
					<path d="M9 2.99997V4.99997H3V2.99997H9ZM10 1.99997H2V5.99997H10V1.99997Z" fill="#48455F"/>
					<path d="M10 8.99997H2V9.99997H10V8.99997Z" fill="#48455F"/>
					<path d="M10 6.99997H2V7.99997H10V6.99997Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'database') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.09 0H0.91C0.41 0 0 0.41 0 0.91V4.09C0 4.59 0.41 5 0.91 5H4.09C4.59 5 5 4.59 5 4.09V0.91C5 0.41 4.59 0 4.09 0ZM4.09 4.09H0.91V0.91H4.09V4.09Z" fill="#48455F"/>
					<path d="M11.09 0H7.91C7.41 0 7 0.41 7 0.91V4.09C7 4.59 7.41 5 7.91 5H11.09C11.59 5 12 4.59 12 4.09V0.91C12 0.41 11.59 0 11.09 0ZM11.09 4.09H7.91V0.91H11.09V4.09Z" fill="#48455F"/>
					<path d="M4.09 7H0.91C0.41 7 0 7.41 0 7.91V11.09C0 11.59 0.41 12 0.91 12H4.09C4.59 12 5 11.59 5 11.09V7.91C5 7.41 4.59 7 4.09 7ZM4.09 11.09H0.91V7.91H4.09V11.09Z" fill="#48455F"/>
					<path d="M11.09 7H7.91C7.41 7 7 7.41 7 7.91V11.09C7 11.59 7.41 12 7.91 12H11.09C11.59 12 12 11.59 12 11.09V7.91C12 7.41 11.59 7 11.09 7ZM11.09 11.09H7.91V7.91H11.09V11.09Z" fill="#48455F"/>
					</svg>';
		}
		if ($icon_name === 'media') {
			$icon = '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4 2V3H13V12H14V2H4Z" fill="#48455F"/>
					<path d="M3 14H11C11.55 14 12 13.55 12 13V5C12 4.45 11.55 4 11 4H3C2.45 4 2 4.45 2 5V13C2 13.55 2.45 14 3 14ZM11 13H3.69L5.71 10.97L7.09 12.36L9.46 9.98L11 11.52V13ZM3 5H11V10.11L9.46 8.56L7.09 10.94L5.71 9.55L3 12.28V5Z" fill="#48455F"/>
					<path d="M7.35 8.64C7.90228 8.64 8.35 8.19228 8.35 7.64C8.35 7.08772 7.90228 6.64 7.35 6.64C6.79772 6.64 6.35 7.08772 6.35 7.64C6.35 8.19228 6.79772 8.64 7.35 8.64Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'page') {
			$icon = '<svg width="10" height="12" viewBox="0 0 10 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M8 2V0H0V12H10V2H8ZM9 11H1V1H7V3H9V11Z" fill="#48455F"/>
					<path d="M8 5H2V6H8V5Z" fill="#48455F"/>
					<path d="M8 7H2V8H8V7Z" fill="#48455F"/>
					<path d="M8 9H2V10H8V9Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'comments') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M10 0V1H1V8H0V0H10Z" fill="#48455F"/>
					<path d="M11 3V10.13L9.56 9.17L9.31 9H3V3H11ZM11 2H2.99C2.44 2 1.99 2.45 1.99 3V9.01C1.99 9.56 2.44 10.01 2.99 10.01H9L12 12.01V2.99C12 2.44 11.55 1.99 11 1.99V2Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'appearance') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M6.0001 1C6.3301 1 6.6701 1.03 7.0001 1.1C8.7601 1.44 10.2401 2.79 10.7801 4.54C11.0301 5.36 11.0701 6.19 10.9001 7.01H9.0101C7.9001 7.01 7.0101 7.91 7.0101 9.01V10.9C6.6801 10.97 6.3401 11 6.0101 11C5.5201 11 5.0301 10.92 4.5401 10.78C2.7901 10.25 1.4401 8.77 1.1001 7C0.810101 5.47 1.1801 3.99 2.1501 2.82C3.1001 1.67 4.5101 1 6.0101 1M6.0101 0C2.3001 0 -0.619899 3.35 0.110101 7.19C0.520101 9.33 2.1501 11.1 4.2401 11.73C4.8401 11.91 5.4301 12 6.0001 12C6.7001 12 7.3701 11.87 8.0001 11.65V9C8.0001 8.45 8.4501 8 9.0001 8H11.6501C12.0501 6.87 12.1401 5.59 11.7301 4.24C11.1001 2.15 9.3301 0.53 7.1901 0.11C6.7901 0.03 6.3901 0 6.0001 0H6.0101Z" fill="#48455F"/>
					<path d="M3.58008 4.95001C4.13236 4.95001 4.58008 4.5023 4.58008 3.95001C4.58008 3.39773 4.13236 2.95001 3.58008 2.95001C3.02779 2.95001 2.58008 3.39773 2.58008 3.95001C2.58008 4.5023 3.02779 4.95001 3.58008 4.95001Z" fill="#48455F"/>
					<path d="M3.20996 7.94C3.76225 7.94 4.20996 7.49229 4.20996 6.94C4.20996 6.38772 3.76225 5.94 3.20996 5.94C2.65768 5.94 2.20996 6.38772 2.20996 6.94C2.20996 7.49229 2.65768 7.94 3.20996 7.94Z" fill="#48455F"/>
					<path d="M5.42993 9.94C5.98222 9.94 6.42993 9.49229 6.42993 8.94C6.42993 8.38772 5.98222 7.94 5.42993 7.94C4.87765 7.94 4.42993 8.38772 4.42993 8.94C4.42993 9.49229 4.87765 9.94 5.42993 9.94Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'plugins') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M10.17 12H6.42V11.5C6.42 11.28 6.34 10.17 5.09 10.17C3.84 10.17 3.76 11.37 3.76 11.5V12H0V8.25001H0.5C0.72 8.25001 1.83 8.17001 1.83 6.92001C1.83 5.67001 0.72 5.59001 0.49 5.59001H0V1.84001H2.8C2.98 0.97001 3.65 0.0100098 5.08 0.0100098C6.51 0.0100098 7.17 0.98001 7.36 1.84001H10.16V4.64001C11.03 4.82001 11.99 5.49001 11.99 6.92001C11.99 8.35001 11.02 9.02001 10.16 9.20001V12H10.17ZM7.37 11H9.17V8.25001H9.67C9.89 8.25001 11 8.17001 11 6.92001C11 5.67001 9.89 5.59001 9.66 5.59001H9.17V2.84001H6.42V2.34001C6.42 2.12001 6.34 1.01001 5.09 1.01001C3.84 1.01001 3.76 2.21001 3.76 2.34001V2.84001H1V4.64001C1.87 4.82001 2.83 5.49001 2.83 6.92001C2.83 8.35001 1.86 9.02001 1 9.20001V11H2.8C2.98 10.13 3.65 9.17001 5.08 9.17001C6.51 9.17001 7.17 10.14 7.36 11H7.37Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'users') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.5 5C3.4 5 2.5 4.1 2.5 3C2.5 1.9 3.4 1 4.5 1V0C2.84 0 1.5 1.34 1.5 3C1.5 4.66 2.84 6 4.5 6V5Z" fill="#48455F"/>
					<path d="M2.69 11H0.9V9.54L2.69 8.21V7.01L0 9V12H2.69V11Z" fill="#48455F"/>
					<path d="M7.5 6C9.16 6 10.5 4.66 10.5 3C10.5 1.34 9.16 0 7.5 0C5.84 0 4.5 1.34 4.5 3C4.5 4.66 5.84 6 7.5 6ZM7.5 1C8.6 1 9.5 1.9 9.5 3C9.5 4.1 8.6 5 7.5 5C6.4 5 5.5 4.1 5.5 3C5.5 1.9 6.4 1 7.5 1Z" fill="#48455F"/>
					<path d="M9.3 7L7.5 8L5.7 7L3 9V12H12V9L9.3 7ZM11.1 11H3.9V9.54L5.76 8.16L7.09 8.9L7.49 9.12L7.89 8.9L9.22 8.16L11.08 9.54V11H11.1Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'tools') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.37001 3.34001C4.37001 3.34001 4.40001 3.42001 4.37001 3.45001L3.45001 4.37001C3.45001 4.37001 3.37001 4.40001 3.34001 4.37001L1.16001 2.19001C1.04001 2.07001 0.880012 2.02001 0.720012 2.05001C0.560012 2.08001 0.420012 2.19001 0.350012 2.34001C-0.549988 4.34001 0.340012 6.69001 2.33001 7.60001C3.26001 8.02001 4.32001 8.07001 5.28001 7.73001L9.05001 11.5C9.39001 11.84 9.83001 12.01 10.27 12.01C10.71 12.01 11.15 11.84 11.49 11.5C12.16 10.83 12.16 9.73001 11.49 9.06001L7.72001 5.29001C8.39001 3.37001 7.50001 1.21001 5.61001 0.360007C4.57001 -0.109993 3.38001 -0.109993 2.33001 0.360007C2.18001 0.430007 2.07001 0.560007 2.04001 0.730007C2.01001 0.890007 2.06001 1.06001 2.18001 1.17001L4.36001 3.35001M1.03001 3.50001L2.63001 5.10001C3.05001 5.51001 3.73001 5.51001 4.14001 5.10001L5.07001 4.17001C5.48001 3.75001 5.48001 3.07001 5.07001 2.66001L3.47001 1.06001C3.63001 1.03001 3.80001 1.02001 3.96001 1.02001C4.38001 1.02001 4.80001 1.11001 5.19001 1.28001C6.68001 1.96001 7.35001 3.72001 6.67001 5.21001C6.58001 5.40001 6.63001 5.62001 6.77001 5.77001L10.77 9.77001C11.05 10.05 11.05 10.52 10.77 10.8C10.49 11.08 10.02 11.08 9.74001 10.8L5.76001 6.79001C5.61001 6.64001 5.39001 6.60001 5.20001 6.69001C4.42001 7.04001 3.53001 7.04001 2.75001 6.69001C1.50001 6.12001 0.830012 4.79001 1.04001 3.49001L1.03001 3.50001Z" fill="#48455F"/>
					</svg>
					';
		}
		if ($icon_name === 'chart-bar') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M4.09 0H0.91C0.41 0 0 0.41 0 0.91V4.09C0 4.59 0.41 5 0.91 5H4.09C4.59 5 5 4.59 5 4.09V0.91C5 0.41 4.59 0 4.09 0ZM4.09 4.09H0.91V0.91H4.09V4.09Z" fill="#48455F"/>
					<path d="M11.09 0H7.91C7.41 0 7 0.41 7 0.91V4.09C7 4.59 7.41 5 7.91 5H11.09C11.59 5 12 4.59 12 4.09V0.91C12 0.41 11.59 0 11.09 0ZM11.09 4.09H7.91V0.91H11.09V4.09Z" fill="#48455F"/>
					<path d="M4.09 7H0.91C0.41 7 0 7.41 0 7.91V11.09C0 11.59 0.41 12 0.91 12H4.09C4.59 12 5 11.59 5 11.09V7.91C5 7.41 4.59 7 4.09 7ZM4.09 11.09H0.91V7.91H4.09V11.09Z" fill="#48455F"/>
					<path d="M11.09 7H7.91C7.41 7 7 7.41 7 7.91V11.09C7 11.59 7.41 12 7.91 12H11.09C11.59 12 12 11.59 12 11.09V7.91C12 7.41 11.59 7 11.09 7ZM11.09 11.09H7.91V7.91H11.09V11.09Z" fill="#48455F"/>
					</svg>';
		}
		if ($icon_name === 'settings') {
			$icon = '<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path d="M11.5 4.6H10.85C10.69 4.6 10.54 4.54 10.43 4.43C10.32 4.32 10.25 4.17 10.25 4.01C10.25 3.85 10.31 3.7 10.42 3.59L10.88 3.13C10.97 3.03 11.02 2.9 11.02 2.77C11.02 2.64 10.97 2.51 10.87 2.42L9.57 1.12C9.48 1.03 9.35 0.97 9.22 0.97C9.09 0.97 8.96 1.02 8.87 1.12L8.41 1.58C8.3 1.69 8.14 1.75 7.99 1.75C7.66 1.75 7.4 1.48 7.41 1.15V0.5C7.41 0.23 7.19 0 6.91 0H5.08C4.81 0 4.58 0.22 4.58 0.5V1.14C4.58 1.3 4.52 1.45 4.41 1.56C4.3 1.67 4.15 1.74 3.99 1.74C3.83 1.74 3.68 1.68 3.57 1.57L3.11 1.11C2.92 0.92 2.6 0.92 2.4 1.11L1.12 2.41C1.03 2.5 0.97 2.63 0.98 2.76C0.98 2.89 1.03 3.02 1.13 3.11L1.59 3.57C1.7 3.68 1.76 3.84 1.76 3.99C1.76 4.15 1.69 4.3 1.58 4.41C1.47 4.52 1.32 4.58 1.17 4.58H0.5C0.23 4.58 0 4.8 0 5.08V6.92C0 7.19 0.22 7.42 0.5 7.42H1.15C1.3 7.42 1.46 7.48 1.57 7.59C1.68 7.7 1.75 7.85 1.75 8.01C1.75 8.17 1.69 8.32 1.58 8.43L1.11 8.9C1.02 8.99 0.97 9.12 0.97 9.25C0.97 9.38 1.02 9.51 1.12 9.6L2.42 10.87C2.61 11.07 2.93 11.07 3.12 10.87L3.58 10.41C3.69 10.3 3.84 10.24 4 10.24C4.16 10.24 4.31 10.31 4.42 10.42C4.53 10.53 4.59 10.68 4.59 10.84V11.5C4.59 11.78 4.81 12 5.09 12H6.92C7.19 11.99 7.4 11.77 7.4 11.5V10.88C7.4 10.72 7.46 10.57 7.57 10.46C7.68 10.35 7.83 10.28 7.99 10.28C8.15 10.28 8.3 10.34 8.41 10.45L8.88 10.92C9.08 11.11 9.39 11.11 9.58 10.92L10.88 9.62C10.97 9.53 11.03 9.4 11.02 9.27C11.02 9.14 10.97 9.01 10.87 8.92L10.41 8.46C10.3 8.35 10.24 8.19 10.24 8.04C10.24 7.88 10.31 7.73 10.42 7.62C10.53 7.51 10.68 7.45 10.83 7.45H11.5C11.77 7.45 12 7.23 12 6.95V5.12C12 4.85 11.78 4.62 11.5 4.62V4.6ZM9.25 8.01C9.25 8.43 9.42 8.83 9.72 9.13L9.83 9.24L9.24 9.84L9.13 9.73C8.83 9.43 8.43 9.26 8.01 9.26C7.59 9.26 7.19 9.43 6.89 9.73C6.59 10.03 6.43 10.43 6.43 10.85V11.01H5.59V10.85C5.59 10.43 5.42 10.02 5.12 9.72C4.5 9.1 3.49 9.11 2.88 9.72L2.77 9.83L2.18 9.23L2.29 9.12C2.59 8.82 2.76 8.41 2.76 7.99C2.76 7.12 2.05 6.41 1.17 6.41H1.01V5.57H1.17C1.59 5.57 1.99 5.4 2.29 5.1C2.59 4.8 2.75 4.4 2.75 3.98C2.75 3.56 2.58 3.16 2.28 2.86L2.17 2.75L2.76 2.16L2.87 2.27C3.17 2.57 3.58 2.74 3.99 2.74C4.41 2.74 4.81 2.57 5.11 2.27C5.41 1.97 5.57 1.57 5.57 1.15V0.99H6.41V1.15C6.41 1.57 6.58 1.98 6.88 2.28C7.5 2.89 8.5 2.89 9.12 2.28L9.23 2.17L9.82 2.76L9.71 2.87C9.41 3.17 9.24 3.58 9.24 4C9.24 4.42 9.41 4.82 9.71 5.12C10.01 5.42 10.41 5.58 10.83 5.58H10.99V6.42H10.83C10.4 6.42 10.01 6.59 9.71 6.89C9.41 7.19 9.25 7.59 9.25 8.01Z" fill="#48455F"/>
					<path d="M6 8.5C4.62 8.5 3.5 7.38 3.5 6C3.5 4.62 4.62 3.5 6 3.5C7.38 3.5 8.5 4.62 8.5 6C8.5 7.38 7.38 8.5 6 8.5ZM6 4.5C5.17 4.5 4.5 5.17 4.5 6C4.5 6.83 5.17 7.5 6 7.5C6.83 7.5 7.5 6.83 7.5 6C7.5 5.17 6.83 4.5 6 4.5Z" fill="#48455F"/>
					</svg>
					';
		}
		return $icon;
	}


}
