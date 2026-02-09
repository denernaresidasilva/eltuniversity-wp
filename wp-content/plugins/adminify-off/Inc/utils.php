<?php

namespace WPAdminify\Inc;

use ReturnTypeWillChange;
use WPAdminify\Inc\Classes\Helper;
// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class Utils
{
	public $post_types = '';

	/**
	 * Check if the Module is Enable/Disable
	 *
	 * @param [type] $value
	 *
	 * @return void
	 */
	public static function check_modules($value)
	{
		if (empty($value)) {
			return;
		} else {
			return true;
		}
	}

	/**
	 * Check given value empty or not
	 *
	 * @param [type] $value
	 *
	 * @return void
	 */
	public static function check_is_empty($value, $default = '')
	{
		$value = !empty(esc_attr($value)) ? $value : $default;
		return $value;
	}


	/**
	 * Check hidden for rules or not
	 *
	 * @param [type] $value
	 *
	 * @return bool
	 */
	public static function restricted_for($disabled_for)
	{
		if (empty($disabled_for)) {
			return false;
		}

		if (!function_exists('wp_get_current_user')) {
			include ABSPATH . 'wp-includes/pluggable.php';
		}

		$restrict_for = array();

		if (!is_array($disabled_for)) {
			$restrict_for[] = $disabled_for;
		} else {
			$restrict_for = $disabled_for;
		}


		$current_user  = wp_get_current_user();
		$current_name  = $current_user->display_name;
		$current_roles = $current_user->roles;
		// $all_roles     = wp_roles()->get_names();
		
		if (in_array($current_name, $restrict_for)) {
			return true;
		}
		
		// if (in_array('Super Admin', $restrict_for) || in_array('administrator', $restrict_for)) {
		// 	return true;
		// }

		// Super Admin for Multisite
		if (is_super_admin() && is_multisite()) {
			if (in_array('Super Admin', $restrict_for) || in_array('administrator', $restrict_for)) {
				return true;
			} else {
				return false;
			}
		}

		$formattedroles = [];

		foreach ($restrict_for as $item) {
			$item = is_string($item) ? strtolower($item) : $item;
			$item = is_string($item) ? str_replace(' ', '_', $item) : $item;
			array_push($formattedroles, $item);
		}

		foreach ($current_roles as $role) {
			// $role_name = $all_roles[$role];
			if (in_array($role, $restrict_for)) {
				return true;
			}
		}
	}


	/**
	 * Check Site wide plugin settings
	 */
	public static function is_site_wide($plugin)
	{
		if (!is_multisite()) {
			return false;
		}

		$plugins = get_site_option('active_sitewide_plugins');
		if (isset($plugins[$plugin])) {
			return true;
		}

		return false;
	}

	/**
	 * Get All Taxonomies
	 *
	 * @return void
	 */
	public static function get_all_taxonomies()
	{
		$taxonomies     = get_taxonomies(
			[
				'show_ui' => true,
			],
			'objects'
		);
		$taxonomy_names = [];
		foreach ($taxonomies as $taxonomy) {
			if ($taxonomy->name == 'post_format') {
				continue;
			}
			$taxonomy_names[$taxonomy->name] = $taxonomy->label;
		}
		return $taxonomy_names;
	}

	/**
	 * Restricts for role / user
	 */

	public static function restrict_for($disabled_for)
	{
		if (!is_array($disabled_for)) {
			return false;
		}

		require_once ABSPATH . '/wp-includes/pluggable.php';

		if (!function_exists('wp_get_current_user')) {
			return false;
		}
		
		$current_user  = wp_get_current_user();
		$current_name  = $current_user->display_name;
		$current_roles = $current_user->roles;

		$formattedroles = [];

		foreach ($disabled_for as $item) {
			$item = strtolower($item);
			$item = str_replace(' ', '_', $item);
			array_push($formattedroles, $item);
		}

		if (in_array($current_name, $disabled_for)) {
			return true;
		}

		foreach ($current_roles as $role) {
			if (in_array($role, $formattedroles)) {
				return true;
			}
		}
	}


	// Get Taxonomies
	public static function get_taxonomies()
	{
		$args       = [
			'public'  => true,
			'show_ui' => true,
		];
		$output     = 'objects';
		$taxonomies = get_taxonomies($args, $output);
		$taxonomies = $taxonomies;
		return $taxonomies;
	}

	// Get Post Types
	public static function get_post_types()
	{
		$args       = [
			'public'  => true,
			'show_ui' => true,
		];
		$output     = 'objects';
		$post_types = get_post_types($args, $output);
		$post_types = $post_types;
		return $post_types;
	}

	// Get Post Meta
	public static function post_meta($meta_key)
	{
		$meta_key = get_post_meta(get_the_ID(), $meta_key, true);
		return $meta_key;
	}


	// verfiy current page id
	public static function jltwp_adminify_currentpage_id($id)
	{
		if (!function_exists('get_current_screen')) {
			return true;
		}

		$screen = get_current_screen();
		return is_object($screen) && $screen->id == $id;
	}

	/**
	 * Get Current Admin Page Title
	 *
	 * @param string $title
	 *
	 * @return void
	 */
	public static function admin_page_title($title = '')
	{
		$title = isset($title) && !empty($title) ? $title : WP_ADMINIFY;
		echo esc_html($title);

		if (is_multisite()) {
			$text = ' | ' . esc_html__('Current Blog ID', 'adminify') . ': ' . get_current_blog_id(); ?>
			<?php echo self::wp_kses_custom(self::admin_page_subtitle($text)); ?>
		<?php } ?>
	<?php
	}

	/**
	 * Get Current Admin Subtitle
	 */

	public static function admin_page_subtitle($text)
	{
	?>
		<span style="color:#8b959e;" <?php
										if (is_rtl()) {
											echo ' dir="rtl"';
										}
										?>>
			<?php echo esc_html($text); ?>
		</span>

	<?php
	}

	public static function convert_name_to_class($name)
	{
		$class = str_replace([' ', ',', '.', '"', "'", '/', '\\', '+', '=', ')', '(', '*', '&', '^', '%', '$', '#', '@', '!', '~', '`', '<', '>', '?', '[', ']', '{', '}', '|', ':'], '', $name);
		return $class;
	}

	public static function jltwp_adminify_class_cleanup($string)
	{
		// Lower case everything
		$string = strtolower($string);
		// Make alphanumeric (removes all other characters)
		$string = preg_replace('/[^a-z0-9_\s-]/', '', $string);
		// Clean up multiple dashes or whitespaces
		$string = preg_replace('/[\s-]+/', ' ', $string);
		// Convert whitespaces and underscore to dash
		$string = preg_replace('/[\s_]/', '-', $string);
		return $string;
	}

	public static function sanitize_id($url)
	{
		$url = preg_replace('/^customize.php\?return=.*$/', 'customize', $url);
		$url = preg_replace('/(&|&amp;|&#038;)?_wpnonce=([^&]+)/', '', $url);
		return str_replace(['.php', '.', '/', '?', '='], ['', '_', '_', '_', '_'], $url);
	}

	/**
	 * Strip tags and it's content from the given string.
	 *
	 * @link https://stackoverflow.com/questions/14684077/remove-all-html-tags-from-php-string/#answer-39320168
	 *
	 * @param string $text The string being stripped.
	 * @return string The stripped string.
	 */
	public static function strip_tags_content($text)
	{
		$cleanup = preg_replace('@<(\w+)\b.*?>.*?</\1>@si', '', $text);
		$cleanup = wp_strip_all_tags($cleanup);
		$cleanup = trim($cleanup);

		return $cleanup;
	}

	/**
	 * String to ID
	 * Remove Space replace with underscore and lowercase
	 *
	 * @param void
	 *
	 * @return string
	 */
	public static function string_to_id($string)
	{
		$string_replace   = str_replace(' ', '_', $string);
		$formatted_string = strtolower($string_replace);
		return $formatted_string;
	}

	/**
	 * ID to String
	 * Remove Space replace with underscore and lowercase
	 *
	 * @param void
	 *
	 * @return string
	 */
	public static function id_to_string($string)
	{
		$string_replace   = str_replace('_', ' ', $string);
		$formatted_string = ucwords($string_replace);
		return $formatted_string;
	}

	/**
	 * Check is Plugin Active
	 *
	 * @param [type] $plugin_path
	 *
	 * @return boolean
	 */
	public static function is_plugin_active( $plugin_path )
	{
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		return is_plugin_active( $plugin_path );
	}

	/**
	 * Check if Block Editor Page
	 *
	 * @return boolean
	 */
	public static function is_block_editor_page() {
		if (function_exists('get_current_screen')) {
			$current_screen = get_current_screen();
			if (!empty($current_screen->is_block_editor)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check is Plugin Active
	 *
	 * @param [type] $plugin_path
	 *
	 * @return boolean
	 */
	public static function is_plugin_installed_and_active($plugin_path)
	{
		// Check if the plugin is active
		if (is_plugin_active($plugin_path)) {
			return true; // Plugin is both installed and active
		} else {
			return false; // Plugin is either not installed or not active
		}
	}

	public static function is_plugin_installed($plugin_slug, $plugin_file)
	{
		$installed_plugins = get_plugins();
		return isset($installed_plugins[$plugin_file]);
	}

	public static function jltwp_adminify_notification_bar()
	{
		$plugin_slug = 'adminify-notification-bar';
		$plugin_file = 'adminify-notification-bar/adminify-notification-bar.php';
		$addons_page_url = network_admin_url('admin.php?page=wp-adminify-settings-addons');

		// $addons_download_link = jltwp_adminify()->_get_latest_download_local_url(14434);

		$html = '';
		if (self::is_plugin_installed($plugin_slug, $plugin_file)) {
			if (!current_user_can('install_plugins')) {
				return;
			}

			if (!self::is_plugin_active($plugin_file)) {
				$activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin_file);
				$html .= '<a class="wp-adminify-addons-download addon-active" href="' . $activation_url . '" ><span class="pr-1">' . esc_html__('Activate', 'adminify') . '</span><i class="dashicons dashicons-yes-alt"></i></a>';
			}
		} else {
			// $install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=' . $plugin_slug), 'install-plugin_' . $plugin_slug);
			$html = '<a class="wp-adminify-addons-download addon-download" href="' . $addons_page_url . '"><span class="wp-adminify-addons-download-text">' . esc_html__('Download', 'adminify') . '</span><i class="dashicons dashicons-download"></i></a>';

			activate_plugin($plugin_file);
		}

		$html_content = '<div class="adminify-title">
						<h4>' . esc_html__('Notification Bar', 'adminify') . '</h4>
						<div class="adminify-subtitle-text">' . esc_html__('Cookie Notice, Promotional Bar on frontend', 'adminify') . '</div>
					</div>
					<div class="adminify-fieldset">
						<div class="adminify--addons" style="width: 100px;">
						' . wp_kses_post($html) . '
						</div>
					</div>
					<div class="clear"></div>';

		echo wp_kses_post($html_content);
	}


	/**
	 * User Defined Settings
	 */

	public static function get_user_preference($key)
	{
		$userid  = get_current_user_id();
		$current = get_user_meta($userid, '_wpadminify_preferences', true);
		$value   = false;

		if (is_array($current)) {
			if (isset($current[$key])) {
				$value = $current[$key];
			}
		}

		return $value;
	}

	/**
	 * Sanitises and strips tags of input from ajax
	 *
	 * @since 1.0.0
	 * @variables $values = item to clean (array or string)
	 */
	public static function clean_ajax_input($values)
	{
		if (is_array($values)) {
			foreach ($values as $index => $in) {
				if (is_array($in)) {
					$values[$index] = self::clean_ajax_input($in);
				} else {
					$values[$index] = strip_tags($in);
				}
			}
		} else {
			$values = strip_tags($values);
		}

		return $values;
	}

	/**
	 * Check Ajax Error Messages
	 *
	 * @param [type] $message
	 *
	 * @return void
	 */
	public static function ajax_error_message($message)
	{
		$returndata                  = [];
		$returndata['error']         = true;
		$returndata['error_message'] = $message;
		return json_encode($returndata);
	}

	/**
	 * Get All Updates
	 * Themes, Plugins, Core etc
	 *
	 * @return void
	 */
	public static function get_all_updates()
	{
		$allupdates              = [];
		$allupdates['total']     = 0;
		$allupdates['wordpress'] = 0;
		$allupdates['theme']     = 0;
		$allupdates['plugin']    = 0;

		if (!is_admin()) {
			return $allupdates;
		}

		if (!current_user_can('install_plugins')) {
			return $allupdates;
		}
		$updatesTotal = 0;
		if (is_super_admin() && is_admin()) {
			$pluginUpdates = get_plugin_updates();
			$themeUpdates  = get_theme_updates();
			$wpUpdates     = get_core_updates();

			if (isset($wpUpdates[0])) {
				$wpversion = $wpUpdates[0]->version;
				global $wp_version;

				if ($wpversion > $wp_version) {
					$wpUpdates = 1;
				} else {
					$wpUpdates = 0;
				}
			} else {
				$wpUpdates = 0;
			}

			$updatesTotal = count($pluginUpdates) + count($themeUpdates) + $wpUpdates;

			$allupdates['total']     = $updatesTotal;
			$allupdates['wordpress'] = $wpUpdates;
			$allupdates['theme']     = $themeUpdates;
			$allupdates['plugin']    = $pluginUpdates;
		}
		return $allupdates;
	}


	/**
	 * Upgrade Pro Icon
	 *
	 * @return void
	 */
	public static function jltwp_adminify_upgrade_pro_icon()
	{
		return '<svg class="adminify-pro-notice-icon is-pulled-left mr-2" width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M21.8233 0.18318C21.8541 0.215042 21.8753 0.255055 21.8843 0.298527C22.5767 3.32046 20.085 9.8239 16.4731 13.4209C15.8343 14.064 15.1407 14.6502 14.4002 15.1727C14.4955 16.2787 14.411 17.3846 13.9985 18.3318C12.8302 21.0043 9.75855 21.7824 8.44197 21.9937C8.36979 22.0059 8.29577 22.0012 8.22571 21.9799C8.15566 21.9587 8.09147 21.9215 8.03819 21.8713C7.9849 21.821 7.94397 21.7591 7.9186 21.6904C7.89323 21.6217 7.88411 21.548 7.89196 21.4751L8.36241 17.189C8.04096 17.1858 7.71987 17.1663 7.40039 17.1305C7.17735 17.1087 6.96893 17.0096 6.81109 16.8503L5.15076 15.1924C4.99146 15.0346 4.89242 14.8259 4.87084 14.6026C4.83497 14.281 4.81547 13.9578 4.8124 13.6343L0.524795 14.1076C0.451994 14.1156 0.378341 14.1065 0.309629 14.0811C0.240917 14.0558 0.17902 14.0148 0.128806 13.9615C0.0785915 13.9081 0.0414297 13.8438 0.0202432 13.7736C-0.000943262 13.7035 -0.0055768 13.6293 0.00670721 13.5571C0.223273 12.2368 1.00065 9.16771 3.67065 7.99148C4.61695 7.57859 5.72728 7.4965 6.83761 7.59481C7.35968 6.85546 7.94513 6.16306 8.58733 5.52547C12.1879 1.92304 18.8337 -0.585245 21.7099 0.118627C21.7531 0.128924 21.7924 0.151317 21.8233 0.18318ZM12.224 7.92186C12.3151 8.37957 12.5397 8.79996 12.8695 9.12986C13.0882 9.34908 13.348 9.52298 13.6339 9.64164C13.9198 9.7603 14.2263 9.82137 14.5358 9.82137C14.8453 9.82137 15.1517 9.7603 15.4377 9.64164C15.7236 9.52298 15.9833 9.34908 16.202 9.12986C16.5318 8.79996 16.7565 8.37957 16.8475 7.92186C16.9386 7.46415 16.892 6.98968 16.7137 6.55848C16.5353 6.12727 16.2332 5.7587 15.8455 5.49939C15.4578 5.24007 15.002 5.10166 14.5358 5.10166C14.0695 5.10166 13.6137 5.24007 13.226 5.49939C12.8384 5.7587 12.5362 6.12727 12.3579 6.55848C12.1795 6.98968 12.1329 7.46415 12.224 7.92186ZM5.47798 18.5161C5.99754 18.4262 6.4292 18.321 6.69831 18.0511C6.83974 17.9032 7.0897 18.0256 7.07153 18.2311C6.99299 18.8853 6.69667 19.4941 6.23032 19.9593C5.07579 21.1158 0.785726 21.225 0.785726 21.225C0.785726 21.225 0.894746 16.9334 2.04927 15.7768C2.51439 15.3115 3.12167 15.0153 3.77443 14.9353C3.81912 14.9292 3.86459 14.9374 3.90439 14.9586C3.94419 14.9799 3.97629 15.0131 3.99613 15.0537C4.01597 15.0942 4.02255 15.14 4.01493 15.1845C4.00731 15.229 3.98588 15.2699 3.95368 15.3015C3.80635 15.449 3.56965 16.0767 3.48961 16.5245C3.27991 17.7056 4.31069 18.7152 5.47798 18.5161Z" fill="#000"/>
            </svg>';
	}


	// Upgrade to Pro Notice
	public static function adminify_upgrade_pro_img_notice( $image ){
		return '<div class="adminify-pro-notice"><img src="' . esc_url_raw($image ) . '" /></div>';
	}

	// Upgrade to Pro Notice Class
	public static function upgrade_pro_class($classname = '')
	{
		if(empty($classname)){
			// return ' adminify-depend-visible adminify-depend-on adminify-pro-notice ';
			return ' adminify-pro-feature adminify-pro-notice';
		} else {
			echo esc_attr($classname);
		}
	}

	// Upgrade to Pro Notice
	public static function adminify_upgrade_pro($custom_message = '', $style='')
	{

		if (empty($custom_message)) {
			$pro_content = sprintf(__('<strong>(Upgrade to Pro)</strong>', 'adminify'));
		} else {
			$pro_content = $custom_message;
		}

		if($style === 'inline'){
			return '<strong style="font-size:16px;">' . $pro_content . '</strong>';
		}

		$upgrade_notice_msg = sprintf(
			__('<div class="adminify-pro-notice adminify-missing-addons"> %1$s %2$s </div>', 'adminify'),
			self::jltwp_adminify_upgrade_pro_icon(),
			self::wp_kses_custom($pro_content)
		);
		return self::wp_kses_custom($upgrade_notice_msg);
	}



	/**
	 * Documentation SVG Icon
	 */
	public static function docs_icon()
	{
		return '<svg class="is-pulled-left mr-1 width="9" height="12" viewBox="0 0 9 12" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5.25 3.1875V0H0.5625C0.250781 0 0 0.250781 0 0.5625V11.4375C0 11.7492 0.250781 12 0.5625 12H8.4375C8.74922 12 9 11.7492 9 11.4375V3.75H5.8125C5.50313 3.75 5.25 3.49687 5.25 3.1875ZM6.75 8.71875C6.75 8.87344 6.62344 9 6.46875 9H2.53125C2.37656 9 2.25 8.87344 2.25 8.71875V8.53125C2.25 8.37656 2.37656 8.25 2.53125 8.25H6.46875C6.62344 8.25 6.75 8.37656 6.75 8.53125V8.71875ZM6.75 7.21875C6.75 7.37344 6.62344 7.5 6.46875 7.5H2.53125C2.37656 7.5 2.25 7.37344 2.25 7.21875V7.03125C2.25 6.87656 2.37656 6.75 2.53125 6.75H6.46875C6.62344 6.75 6.75 6.87656 6.75 7.03125V7.21875ZM6.75 5.53125V5.71875C6.75 5.87344 6.62344 6 6.46875 6H2.53125C2.37656 6 2.25 5.87344 2.25 5.71875V5.53125C2.25 5.37656 2.37656 5.25 2.53125 5.25H6.46875C6.62344 5.25 6.75 5.37656 6.75 5.53125ZM9 2.85703V3H6V0H6.14297C6.29297 0 6.43594 0.0585938 6.54141 0.164062L8.83594 2.46094C8.94141 2.56641 9 2.70938 9 2.85703Z" fill="#0347FF"/>
        </svg>';
	}
	/**
	 * Video Tutorials SVG Icon
	 */
	public static function video_tutorials_icon()
	{
		return '<svg class="is-pulled-left mr-1 width="8" height="10" viewBox="0 0 8 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M7.5 4.13399C8.16667 4.51889 8.16667 5.48114 7.5 5.86604L1.5 9.33014C0.833334 9.71504 0 9.23392 0 8.46412V1.53592C0 0.766115 0.833333 0.28499 1.5 0.66989L7.5 4.13399Z" fill="#C30052"/>
            </svg>';
	}

	/**
	 * Facebook Group SVG Icon
	 */
	public static function fbgroup_icon()
	{
		return '<svg class="is-pulled-left mr-1 width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M1.82594 0C0.814448 0 0 0.814448 0 1.82594V8.17405C0 9.18554 0.814448 9.99999 1.82594 9.99999H5.26656V6.09062H4.23281V4.68312H5.26656V3.48062C5.26656 2.53587 5.87735 1.66844 7.28437 1.66844C7.85404 1.66844 8.2753 1.72313 8.2753 1.72313L8.24217 3.0375C8.24217 3.0375 7.81254 3.03344 7.34374 3.03344C6.83635 3.03344 6.75499 3.26722 6.75499 3.65532V4.68313H8.28248L8.21592 6.09063H6.75499V10H8.17404C9.18553 10 9.99998 9.18555 9.99998 8.17406V1.82595C9.99998 0.814458 9.18553 9.99998e-06 8.17404 9.99998e-06H1.82593L1.82594 0Z" fill="#3B5998"/>
        </svg>';
	}

	/**
	 * Support SVG Icon
	 */
	public static function support_icon()
	{
		return '<svg class="is-pulled-left mr-1 width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path fill-rule="evenodd" clip-rule="evenodd" d="M10 5C10 6.32608 9.47322 7.59785 8.53553 8.53553C7.59785 9.47322 6.32608 10 5 10C3.67392 10 2.40215 9.47322 1.46447 8.53553C0.526784 7.59785 0 6.32608 0 5C0 3.67392 0.526784 2.40215 1.46447 1.46447C2.40215 0.526784 3.67392 0 5 0C6.32608 0 7.59785 0.526784 8.53553 1.46447C9.47322 2.40215 10 3.67392 10 5ZM8.75 5C8.75 5.62063 8.59937 6.20563 8.3325 6.72125L7.38 5.76813C7.52262 5.32668 7.5395 4.85425 7.42875 4.40375L8.405 3.4275C8.62625 3.90563 8.75 4.4375 8.75 5ZM5.52187 7.44563L6.50938 8.43312C6.03375 8.64254 5.51968 8.75046 5 8.75C4.45699 8.75068 3.92036 8.63294 3.4275 8.405L4.40375 7.42875C4.77042 7.5186 5.15266 7.52437 5.52187 7.44563ZM2.59875 5.69812C2.47576 5.27463 2.46692 4.82614 2.57313 4.39812L2.52313 4.44812L1.56687 3.49C1.35738 3.96582 1.24945 4.48011 1.25 5C1.25 5.59625 1.38937 6.16 1.63687 6.66062L2.59938 5.69812H2.59875ZM3.27875 1.66687C3.81076 1.39189 4.40112 1.24891 5 1.25C5.59625 1.25 6.16 1.38937 6.66062 1.63687L5.69812 2.59938C5.21829 2.45961 4.70759 2.46679 4.23187 2.62L3.27875 1.6675V1.66687ZM6.25 5C6.25 5.33152 6.1183 5.64946 5.88388 5.88388C5.64946 6.1183 5.33152 6.25 5 6.25C4.66848 6.25 4.35054 6.1183 4.11612 5.88388C3.8817 5.64946 3.75 5.33152 3.75 5C3.75 4.66848 3.8817 4.35054 4.11612 4.11612C4.35054 3.8817 4.66848 3.75 5 3.75C5.33152 3.75 5.64946 3.8817 5.88388 4.11612C6.1183 4.35054 6.25 4.66848 6.25 5Z" fill="#4E4B66"/>
            </svg>';
	}

	/* White Label Upgrade Pro */
	public static function jltwp_adminify_white_label_upgrade()
	{
	?>
		<div class="wp-adminify-white-label-notice-content">
			<div class="wp-adminify-white-label-notice-logo">
				<img src="<?php echo esc_url(WP_ADMINIFY_ASSETS_IMAGE) . 'logos/logo-text-light.svg'; ?>" alt="<?php echo esc_attr(WP_ADMINIFY); ?>">
			</div>
			<h2>
				<?php echo sprintf(__('Upgrade <span>Pro</span> for White Labeling', 'adminify')); ?>
			</h2>
			<p>
				<?php
				echo sprintf(__('<strong>%1$s</strong> can be completely re-branded with your own brand Logo, Name and Author Details. Your clients will never know what tools you are using to build their website and will think that this is your own tool set. White-labeling works as long as your license is active. ', 'adminify'), esc_html(WP_ADMINIFY));
				?>
				<br>
				<em><?php esc_html_e('Note: Agency or Higher Plans Only', 'adminify'); ?></em>
			</p>
			<a class="wp-adminify-btn wp-adminify-get-pro" href="<?php echo esc_url('https://wpadminify.com/pricing/'); ?>" target="_blank">
				<?php esc_html_e('Upgrade Now', 'adminify'); ?>
			</a>
		</div>
<?php
	}


	public static function get_widget_template_options()
	{
		$type           = 'widget';
		$page_templates = self::jltwp_adminify_get_page_templates($type);

		$options[-1] = __('Select', 'adminify');

		if (count($page_templates)) {
			foreach ($page_templates as $id => $name) {
				$options[$id] = $name;
			}
		} else {
			$options['no_template'] = __('No saved templates found!', 'adminify');
		}

		return $options;
	}

	public static function get_section_template_options()
	{
		$type           = 'section';
		$page_templates = self::jltwp_adminify_get_page_templates($type);

		$options[-1] = __('Select', 'adminify');

		if (count($page_templates)) {
			foreach ($page_templates as $id => $name) {
				$options[$id] = $name;
			}
		} else {
			$options['no_template'] = __('No saved templates found!', 'adminify');
		}

		return $options;
	}

	public static function get_page_template_options()
	{
		$type           = 'page';
		$page_templates = self::jltwp_adminify_get_page_templates($type);

		$options[-1] = __('Select', 'adminify');

		if (count($page_templates)) {
			foreach ($page_templates as $id => $name) {
				$options[$id] = $name;
			}
		} else {
			$options['no_template'] = __('No saved templates found!', 'adminify');
		}

		return $options;
	}

	public static function get_theme_presets($theme = null)
	{

		$common_var = [
			'--adminify-menu-vertical-padding'          => '10px',
			'--adminify-menu-horizontal-padding'		=> '8px',
			'--adminify-menu-wrapper-padding-top'       => '16px',
			'--adminify-menu-wrapper-padding-right'     => '8px',
			'--adminify-menu-wrapper-padding-bottom'    => '16px',
			'--adminify-menu-wrapper-padding-left'      => '8px',
			'--adminify-submenu-vertical-padding'       => '4px',
			'--adminify-submenu-wrapper-padding-top'    => '8px',
			'--adminify-submenu-wrapper-padding-right'  => '0px',
			'--adminify-submenu-wrapper-padding-bottom' => '8px',
			'--adminify-submenu-wrapper-padding-left'   => '28px',
			// '--adminify-menu-text-align' 			=> 'left',
			// '--adminify-menu-text-transform' 		=> '',
			// '--adminify-menu-text-decoration' 		=> 'none',
			'--adminify-menu-font-size'      			=> '13px',
			'--adminify-menu-line-height'    			=> '20px',
			'--adminify-menu-letter-spacing' 			=> '',
			'--adminify-menu-width'						=> '260px'
		];

		$presets = [

			// Base/ Default Preset
			'preset1' => [
				'--adminify-preset-background'         => '#F9F9F9',
				'--adminify-primary'				   => '#3a3ae9',
				// '--adminify-text-color'                => '#ffffff',
				'--adminify-menu-border'		    	=> '#dedee7',
				'--adminify-menu-bg' 					=> '#ffffff',
				'--adminify-menu-hover-bg'				=> '#3a3ae9',
				'--adminify-menu-text-color' 			=> '#48455f',
				'--adminify-menu-text-hover-color' 		=> '#ffffff',
				'--adminify-menu-active-bg'				=> '#3a3ae9',
				'--adminify-menu-active-color' 			=> '#ffffff',
				'--adminify-submenu-wrapper-bg' 		=> '#ffffff',
				'--adminify-submenu-hover-bg' 			=> 'transparent',
				'--adminify-submenu-text-color' 		=> '#48455f',
				'--adminify-submenu-text-hover-color' 	=> '#3a3ae9',
				'--adminify-submenu-active-bg' 			=> 'transparent',
				'--adminify-submenu-active-color' 		=> '#3a3ae9',
				'--adminify-notif-bg-color' 			=> '#3a3ae9',
				'--adminify-notif-color' 				=> '#ffffff',
			],

			'preset2' => [
				'--adminify-preset-background'         => '#F9F9F9',
				'--adminify-primary'				   => '#0347FF',
				'--adminify-notif-bg-color'            => '#FF1C69',
				// '--adminify-text-color'                => '#ffffff',
				'--adminify-menu-border'		    	=> '#404052',
				'--adminify-menu-bg' 					=> '#14142B',
				'--adminify-menu-hover-bg'				=> '#0347FF',
				'--adminify-menu-text-color' 			=> '#ffffff',
				'--adminify-menu-text-hover-color' 		=> '#ffffff',
				'--adminify-menu-active-bg'				=> '#0347FF',
				'--adminify-menu-active-color' 			=> '#ffffff',
				'--adminify-submenu-wrapper-bg' 		=> '#14142B',
				'--adminify-submenu-hover-bg' 			=> 'transparent',
				'--adminify-submenu-text-color' 		=> '#ffffff',
				'--adminify-submenu-text-hover-color' 	=> '#0347FF',
				'--adminify-submenu-active-bg' 			=> 'transparent',
				'--adminify-submenu-active-color' 		=> '#0347FF',
				'--adminify-notif-bg-color' 			=> '#0347FF',
				'--adminify-notif-color' 				=> '#ffffff',
			],
		];

		$pro_presets = apply_filters('adminify_settings/color_presets', $common_var);

		if( !empty(array_key_exists('preset3', $pro_presets)) ) {
			$presets = array_merge($presets, $pro_presets);
		}

		// Merge $common_var variable with every preset
		foreach ($presets as $key => $value) {
			$presets[$key] = array_merge($value, $common_var);
		}

		// return all presets
		if (is_null($theme)) {
			return $presets;
		}

		// return specific preset
		if (array_key_exists($theme, $presets)) {
			return $presets[$theme];
		}

		// specific preset not found
		return [];
	}

	public static function jltwp_adminify_get_page_templates($type = '')
	{
		$args = [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
		];

		if ($type) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => $type,
				],
			];
		}

		$page_templates = get_posts($args);

		$options = [];

		if (!empty($page_templates) && !is_wp_error($page_templates)) {
			foreach ($page_templates as $post) {
				$options[$post->ID] = $post->post_title;
			}
		}
		return $options;
	}

	public static function assets_ext($ext)
	{
		if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
			return $ext;
		}
		return '.min' . $ext;
	}

	public static function wp_kses_atts_map(array $attrs)
	{
		return array_fill_keys(array_values($attrs), true);
	}

	public static function wp_kses_custom($content)
	{
		/**
		 * the context can be different 
		 * The context for which to retrieve tags. Allowed values are 'post', 'strip', 'data', 'entities', or the name of a field filter such as 'pre_user_description', or an array of allowed HTML elements and attributes.
		 * the post means here it will return all the tags that are allowed in post such as a, p, strong, em etc
		*/
		$allowed_tags = wp_kses_allowed_html('post'); // Details : https://developer.wordpress.org/reference/functions/wp_kses_allowed_html/
		if( !is_array($allowed_tags) ) $allowed_tags = [];

		$custom_tags = [
			'select'         => self::wp_kses_atts_map(['class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'multiple', 'required', 'size']),
			'input'          => self::wp_kses_atts_map(['class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'required', 'size', 'type', 'checked', 'readonly', 'placeholder', 'value', 'maxlength', 'min', 'max', 'multiple', 'pattern', 'step', 'autocomplete']),
			'textarea'       => self::wp_kses_atts_map(['class', 'id', 'style', 'width', 'height', 'title', 'data', 'name', 'autofocus', 'disabled', 'required', 'rows', 'cols', 'wrap', 'maxlength']),
			'option'         => self::wp_kses_atts_map(['class', 'id', 'label', 'disabled', 'label', 'selected', 'value']),
			'optgroup'       => self::wp_kses_atts_map(['disabled', 'label', 'class', 'id']),
			'form'           => self::wp_kses_atts_map(['class', 'id', 'data', 'style', 'width', 'height', 'accept-charset', 'action', 'autocomplete', 'enctype', 'method', 'name', 'novalidate', 'rel', 'target']),
			'svg'            => self::wp_kses_atts_map(['class', 'xmlns', 'viewbox', 'width', 'height', 'fill', 'aria-hidden', 'aria-labelledby', 'role']),
			'rect'           => self::wp_kses_atts_map(['rx', 'width', 'height', 'fill']),
			'path'           => self::wp_kses_atts_map(['d', 'fill']),
			'g'              => self::wp_kses_atts_map(['fill']),
			'defs'           => self::wp_kses_atts_map(['fill']),
			'linearGradient' => self::wp_kses_atts_map(['id', 'x1', 'x2', 'y1', 'y2', 'gradientUnits']),
			'stop'           => self::wp_kses_atts_map(['stop-color', 'offset', 'stop-opacity']),
			'style'          => self::wp_kses_atts_map(['type']),
			'div'            => self::wp_kses_atts_map(['class', 'id', 'style']),
			'ul'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'li'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'label'          => self::wp_kses_atts_map(['class', 'for']),
			'span'           => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h1'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h2'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h3'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h4'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h5'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'h6'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'a'              => self::wp_kses_atts_map(['class', 'href', 'target', 'rel']),
			'p'              => self::wp_kses_atts_map(['class', 'id', 'style', 'data']),
			'table'          => self::wp_kses_atts_map(['class', 'id', 'style']),
			'thead'          => self::wp_kses_atts_map(['class', 'id', 'style']),
			'tbody'          => self::wp_kses_atts_map(['class', 'id', 'style']),
			'tr'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'th'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'td'             => self::wp_kses_atts_map(['class', 'id', 'style']),
			'i'              => self::wp_kses_atts_map(['class', 'id', 'style']),
			'button'         => self::wp_kses_atts_map(['class', 'id']),
			'nav'            => self::wp_kses_atts_map(['class', 'id', 'style']),
			'time'           => self::wp_kses_atts_map(['datetime']),
			'br'             => [],
			'strong'         => [],
			'style'          => [],
			'img'            => self::wp_kses_atts_map(['class', 'src', 'alt', 'height', 'width', 'srcset', 'id', 'loading']),
		];

		$allowed_tags = array_merge_recursive($allowed_tags, $custom_tags);

		return wp_kses(stripslashes_deep($content), $allowed_tags);
	}


	/**
	 * Missing Addon/Plugin Notice
	 *
	 * @param  [type] $args
	 *
	 * @return void
	 */
	public static function missing_plugin_notice( $plugin_name = '' )
	{
		/* translators: %s: Plugin Name */
		$missing_plugin_notice = sprintf(
			__('<strong>"%1$s"</strong> plugin is not Activated! Please install and activate <strong>%1$s</strong> Plugin <p> <a class="adminify-missing-addons" href="%2$s">%3$s</a></p>', 'adminify'),
			esc_html($plugin_name),
			admin_url('admin.php?page=wp-adminify-addons-plugins'),
			__('Install Now', 'adminify')
		);
		return '<div class="adminify-missing-addons">' . self::jltwp_adminify_upgrade_pro_icon() . self::wp_kses_custom( $missing_plugin_notice ) . '</div>';
	}


	/**
	 * Update Existing Data to New Options data
	 *
	 * @param [type] $get_option_value
	 * @param [type] $update_option_value
	 * @param [type] $option_name
	 *
	 * @return void
	 */
	public static function update_options($get_option_value, $option_name)
	{

		// Retrieve the current option value
		$current_value = get_option($option_name);
		// $current_value =  self::$option_data;
		// Initialize the current value if it's empty
		if (empty($current_value)) {
			$current_value = [];
		}
		// If $get_option_value is provided, use it to update the current value
		if (!empty($get_option_value)) {
			// Ensure current value is an array before merging
			if (!is_array($current_value)) {
				$current_value = [];
			}
			// Merge the provided value into the current value
			$current_value = array_merge($current_value, $get_option_value);
		}
		// Update the specific key within the option with the new value
		// $current_value[$option_key] = $update_option_value;

		// Update the option with the new value
		update_option($option_name, $current_value);
		// Optionally, return the updated value
		return get_option($option_name);
	}


	/**
	 * Replace Array Keys
	 *
	 * @return void
	 */
	public static function replace_keys($sourceArray, $newArray){
		$new_value = [];
		if( ! is_array($sourceArray)) $sourceArray = [];
		foreach ($newArray as $key => $value) {
			if (!array_key_exists($key, $sourceArray)) {
				return $sourceArray;
			}
			$new_value[$value] = $sourceArray[$key];
			unset($sourceArray[$key]);
		}
		return array_merge($sourceArray, $new_value);
	}


	/**
 	* Move Array Keys
	 *
	 * @return void
	 */
	public static function move_keys(&$sourceArray, $keysToMove)
	{
		$destinationArray = [];
		foreach ($keysToMove as $key) {
			if (array_key_exists($key, $sourceArray)) {
				$destinationArray[$key] = $sourceArray[$key];
				unset($sourceArray[$key]);
			}
		}
		return $destinationArray;
	}


	/**
	 * Remove unnecessary keys.
	 *
	 * @param string $option_name The name of the option.
	 * @param array $keys_to_remove An array of keys to remove.
	 */
	public static function removeKeys($sourceArray, $keysToRemove)
	{
		foreach ($keysToRemove as $key) {
			if (array_key_exists($key, $sourceArray)) {
				unset($sourceArray[$key]);
			}
		}
		return $sourceArray;
	}



	/**
	 * Move Nested Keys
	 *
	 * @return void
	 */
	public static function moveNestedKeys(&$sourceArray, $moveInstructions) {
		$destinationArray = [];
		foreach ($moveInstructions as $sourcePath => $destPath) {
			$sourcePathArray = explode('.', $sourcePath);
			$destPathArray = explode('.', $destPath);
			$sourceValue = self::getNestedValue($sourceArray, $sourcePathArray);
			if ($sourceValue !== null) {
				self::setNestedValue($destinationArray, $destPathArray, $sourceValue);
				self::unsetNestedValue($sourceArray, $sourcePathArray);
			}
		}

		return array_merge_recursive($sourceArray, $destinationArray);
	}

	public static function getNestedValue(&$array, $pathArray) {
		$value = &$array;
		foreach ($pathArray as $key) {
			if (is_array($value) && array_key_exists($key, $value)) {
				$value = &$value[$key];
			} else {
				return null;
			}
		}
		return $value;
	}

	public static function setNestedValue(&$array, $pathArray, $value) {
		$temp = &$array;
		foreach ($pathArray as $key) {
			if (!isset($temp[$key])) {
				$temp[$key] = [];
			}
			$temp = &$temp[$key];
		}
		$temp = $value;
	}

	public static function unsetNestedValue(&$array, $pathArray) {
		$temp = &$array;
		foreach ($pathArray as $key) {
			if (isset($temp[$key])) {
				if (count($pathArray) === 1) {
					unset($temp[$key]);
				} else {
					self::unsetNestedValue($temp[$key], array_slice($pathArray, 1));
				}
				return;
			}
		}
	}



	/**
	 *
	 * Checkbox Method
	 */
	public static function checkboxes(&$old_db, $checkbox_keys) {
		$result = [];
		$parentKey = "";
		$childKey = "";

		$thirdStep = [];

		foreach ($checkbox_keys as $key => $value) {

			$sourceArray = explode(".", $value);

			foreach ($sourceArray as $x => $element) {
				if (!array_key_exists($element, $result) && $x === 0) {
					$parentKey = $element;
					if (count($sourceArray) === 3) {
						$result[$element] = [];
					} else {
						$result[$element] = [];
					}
				}


				if ($x === 1 && !empty($old_db[$key])) {
					$childKey = $element;
					if (count($sourceArray) === 3) {
						$result[$parentKey][$element] = [];
					} else {
						$result[$parentKey][] = $element;
					}
				} elseif ($x === 1 && empty($old_db[$key]) && count($sourceArray) === 3) {
					$childKey = $element;
					if (!array_key_exists($element, $result[$parentKey])) {
						$result[$parentKey][$element] = [];
					}
				}

				if ($x === 2 && !empty($old_db[$key]) && count($sourceArray) === 3) {
					$thirdStep[] = $element;
					$result[$parentKey][$childKey] = $thirdStep;
				}
			}
		}

		return array_merge_recursive($old_db, $result);
	}

	public static function adminify_upgrade_pro_class($inline_badge=false){
		if(!empty($inline_badge)){
			return '<span class="adminify-pro-checkbox adminify-pro-badge adminify-pro-notice">Pro</span>';
		}
		return '<span class="adminify-pro-checkbox">' . self::jltwp_adminify_upgrade_pro_icon() . ' <strong>(Upgrade to Pro)</strong>' . '</span>';
	}

	public static function adminify_upgrade_pro_badge($badge_text = 'Pro'){
		if(!empty($badge_text)){
			return '<span class="adminify-pro-badge">' . $badge_text . '</span>';
		}
	}

	/*
	* Compares the version of WordPress running to the $version specified.
	* Usage: Utils::check_wp_version('>=', '4.0')
	version_compare( $wp_version, '4.3', '>=' )
	* @param string $operator
	* @param string $version
	* @returns boolean
	*/
	public static function check_wp_version( $operator = '>', $version = '6.6' ) {
		global $wp_version;
		return version_compare( $wp_version, $version, $operator );
	}

	public static function adminfiy_help_urls($module_name = '', $docs = '', $youtube = '', $facebook_grp = '', $support = '')
	{
		$help_content = '';

		// Modules
		if (empty($module_name)) {
			$module_name = '';
		} else {
			$module_name = $module_name;
		}

		$help_content_urls = '';

		// Docs
		if (!empty($docs)) {
			$help_content_urls .= '<a class="adminify-tag adminify-tag-purple adminify-docs-url" href="' . esc_url( $docs ) . '" target="_blank"> ' . self::docs_icon() . ' Docs</a>';
		}

		// youtube
		if (!empty($youtube)) {
			$help_content_urls .= '<a class="adminify-tag adminify-tag-danger adminify-video-url" href="' . esc_url( $youtube ) . '" target="_blank">' . self::video_tutorials_icon() . ' Video Tutorial</a>';
		}

		// facebook_grp
		if (!empty($facebook_grp)) {
			$help_content_urls .= '<a class="adminify-tag adminify-tag-primary adminify-fbgroup-url" href="' . esc_url($facebook_grp) . '" target="_blank">' . self::fbgroup_icon() . ' Facebook Group</a>';
		}

		// Support
		if ( !empty($support)) {
			$help_content_urls .= '<a class="adminify-tag adminify-tag-secondary adminify-support-url" href="' . esc_url($support) . '" target="_blank">' . self::support_icon() . ' Support</a>';
		}

		$help_content = sprintf( '%1$s <div class="adminify-helps">%2$s</div>', $module_name, $help_content_urls );
		return $help_content;
	}

}
