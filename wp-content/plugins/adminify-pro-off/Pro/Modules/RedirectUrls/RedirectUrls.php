<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Classes\Helper;
use \WPAdminify\Inc\Admin\AdminSettings;
use \WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

/**
 * WPAdminify
 *
 * @package Redirect URLs
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class RedirectUrls
{

	public $url;
	public $options;
	public $login_redirect_slug;
	public $redirect_admin_url;
	public $new_register_url;
	public $new_logout_url;
	public $old_login_page = false;

	public function __construct()
	{
		$this->options             = (array) AdminSettings::get_instance()->get('redirect_urls_fields');

		if( empty($this->options['enable_redirect_urls']) ) return;

		$this->options 			   = $this->options['redirect_urls_options']['redirect_urls_tabs'];
	

		$this->login_redirect_slug = !empty($this->options['new_login_url']) ? $this->options['new_login_url'] : '';
		$this->redirect_admin_url  = !empty($this->options['redirect_admin_url']) ? $this->options['redirect_admin_url'] : '';
		$this->new_register_url    = !empty($this->options['new_register_url']) ? $this->options['new_register_url'] : '';
		$this->new_logout_url      = !empty($this->options['new_logout_url']) ? $this->options['new_logout_url'] : '';

		// Defend wp-admin if the slug is set in the setting.
		if ($this->redirect_admin_url) {
			add_action('wp_loaded', [$this, 'defend_wp_admin']);
		}

		if ($this->new_register_url) {
			add_filter('register_url', [$this, 'register_redirect_url']);
			add_action('wp_loaded', [$this, 'redirect_register_url']);
		}

		// Login Redirect
		add_filter('login_redirect', [$this, 'login_redirect_callback'], 999999999999, 3);

		// Logout Redirect
		// if (!empty($this->new_logout_url)) {
		add_filter('logout_redirect', [$this, 'redirect_logout_url'], 999999999999, 3);
		// }


		// Stop if custom login slug is not set.
		if (empty($this->login_redirect_slug)) {
			return;
		}
		add_action('init', [$this, 'change_url'], 99999);
		add_action('wp_loaded', [$this, 'defend_wp_login']);
		add_action('site_url', [$this, 'site_url'], 10, 4);
		add_action('network_site_url', [$this, 'network_site_url'], 10, 3);
		add_action('wp_redirect', [$this, 'wp_redirect'], 10, 2);
		// add_action('site_option_welcome_email', [$this, 'welcome_email']);
		// remove_action('template_redirect', 'wp_redirect_admin_locations', 99999);
	}

	/**
	 * Get Redirect URL
	 */

	public function get_redirect_url($login_redirects, $user, $default_url)
	{

		$user_caps = Helper::get_user_capabilities($user);
		$user_roles = Helper::get_user_roles($user);

		// $login_redirects = wp_list_sort($login_redirects, 'redirect_order', 'DESC');

		foreach ($login_redirects as $redirect_value) {

			$con_redirect_to = sanitize_url($redirect_value['redirect_url']);

			if (empty($con_redirect_to)) continue;

			$user_types = $redirect_value['user_types'];

			// Assign value for User Types
			if ($user_types === "user_role") $redirect_value_value = $redirect_value['redirect_role'];
			if ($user_types === 'user_name') $redirect_value_value = $redirect_value['redirect_user'];
			if ($user_types === 'user_cap') $redirect_value_value = $redirect_value['redirect_cap'];

			// Check Types of Users and Redirect
			if ($user_types == 'user_name' && ($user->ID == $redirect_value_value)) return $con_redirect_to;
			if ($user_types == 'user_role' && in_array($redirect_value_value, $user_roles)) return $con_redirect_to;
			if ($user_types == 'user_cap' && in_array($redirect_value_value, $user_caps)) return $con_redirect_to;
		}

		return $default_url;
	}


	/**
	 * Login Redirect
	 */
	public function login_redirect_callback($redirect_to, $requested_redirect_to, $user)
	{
		// Replace $redirect_to with general settings if available
		$new_login_url = sanitize_url($this->login_redirect_slug);
		if (!empty($new_login_url)) $redirect_to = $new_login_url;

		// Return $redirect_to when no user found
		if (!isset($user->user_login)) return $redirect_to;

		// Return $redirect_to when login condition found
		if (empty($this->options['login_redirects'])) return $redirect_to;

		// Return matched $redirect url if found
		$new_login_url = $this->get_redirect_url($this->options['login_redirects'], $user, $redirect_to);
		if (!empty($new_login_url)) $redirect_to = $new_login_url;

		Helper::allowed_host($redirect_to);
		return $redirect_to;
	}


	/**
	 * Logout Redirect
	 *
	 * @return void
	 */
	public function redirect_logout_url($redirect_to, $requested_redirect_to, $user)
	{
		if (!empty($this->new_logout_url)) {
			$redirect_to = $this->new_logout_url;
		}

		// Return $redirect_to when no user found
		if (!isset($user->user_login)) return $redirect_to;

		// Return $redirect_to when logout condition found
		if (empty($this->options['logout_redirects'])) return $redirect_to;

		// Return matched $redirect url if found
		$this->new_logout_url = $this->get_redirect_url($this->options['logout_redirects'], $user, $redirect_to);
		if (!empty($this->new_logout_url)) $redirect_to = $this->new_logout_url;

		Helper::allowed_host($this->new_logout_url);
		return $redirect_to;
	}

	/**
	 * register redirect url
	 *
	 * @return string
	 */
	public function redirect_register_url()
	{
		if ((isset($_GET['action']) && 'register' === $_GET['action']) || (isset($_GET['registration']) && 'disabled' === $_GET['registration'])) {
			$redirect_url = site_url($this->new_register_url);
			if (get_option('permalink_structure')) {
				$redirect_url = $this->is_trailingslashit($redirect_url);
			}
			wp_safe_redirect($redirect_url);
			exit;
		}
	}
	/**
	 * wp-admin redirect url
	 *
	 * @return string
	 */
	public function register_redirect_url()
	{
		$redirect_url = site_url($this->new_register_url);

		if (get_option('permalink_structure')) {
			$redirect_url = $this->is_trailingslashit($redirect_url);
		}

		return $redirect_url;
	}
	/**
	 * Change url.
	 */
	public function change_url()
	{
		if (!$this->login_redirect_slug) {
			return;
		}

		global $pagenow;

		$uri = esc_url_raw(wp_unslash($_SERVER['REQUEST_URI']));

		$has_signup_slug   = false !== stripos($uri, 'wp-signup') ? true : false;
		$has_activate_slug = false !== stripos($uri, 'wp-activate') ? true : false;

		if (!is_multisite() && ($has_signup_slug || $has_activate_slug)) {
			return;
		}

		$request      = wp_parse_url($uri);
		$request_path = isset($request['path']) ? untrailingslashit($request['path']) : '';

		$using_permalink = get_option('permalink_structure') ? true : false;

		$has_new_slug = (isset($_GET[$this->login_redirect_slug]) && ('' != $_GET[$this->login_redirect_slug])) ? true : false;
		$has_old_slug = false !== stripos($uri, 'wp-login.php') ? true : false;

		$has_register_slug = false !== stripos($uri, 'wp-register.php') ? true : false;

		if (!is_admin() && ($has_old_slug || site_url('wp-login', 'relative') === $request_path)) {
			$pagenow                = 'index.php';
			$this->old_login_page   = true;
			$_SERVER['REQUEST_URI'] = $this->is_trailingslashit('/' . str_repeat('-/', 10));
		} elseif (site_url($this->login_redirect_slug, 'relative') === $request_path || (!$using_permalink && $has_new_slug)) {
			// If current page is new login page, let WordPress know if this is a login page.
			$pagenow = 'wp-login.php';
		} elseif (!is_admin() && ($has_register_slug || site_url('wp-register', 'relative') === $request_path)) {
			$pagenow = 'index.php';

			$this->old_login_page   = true;
			$_SERVER['REQUEST_URI'] = $this->is_trailingslashit('/' . str_repeat('-/', 10));
		}
	}

	/**
	 * Defend wp-admin
	 */
	public function defend_wp_admin()
	{
		if (isset($_GET['action']) && 'postpass' === $_GET['action'] && isset($_GET['post_password'])) {
			return;
		}

		global $pagenow;

		$request      = wp_parse_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
		$request_path = isset($request['path']) ? $request['path'] : '';

		if (is_admin() && !is_user_logged_in() && !wp_doing_ajax() && 'admin-post.php' !== $pagenow && '/wp-admin/options.php' !== $request_path) {
			wp_safe_redirect(home_url($this->admin_redirect_url()));
			exit;
		}
	}

	/**
	 * wp-admin redirect url
	 *
	 * @return string
	 */
	public function admin_redirect_url()
	{
		$redirect_url = $this->redirect_admin_url;

		if (get_option('permalink_structure')) {
			return $this->is_trailingslashit($redirect_url);
		}

		return $redirect_url;
	}

	/**
	 * Return a string with or without trailing slash based on permalink structure.
	 */
	public function is_trailingslashit($string)
	{
		$use_trailingslash = '/' === substr(get_option('permalink_structure'), -1, 1) ? true : false;
		return ($use_trailingslash ? trailingslashit($string) : untrailingslashit($string));
	}

	/**
	 * Defend wp-login.php based on the setting.
	 */
	public function defend_wp_login()
	{
		if (isset($_GET['action']) && 'postpass' === $_GET['action'] && isset($_GET['post_password'])) {
			return;
		}

		global $pagenow;

		$request      = wp_parse_url(esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])));
		$request_path = $request['path'];

		$query_string     = isset($_SERVER['QUERY_STRING']) ? esc_url_raw(wp_unslash($_SERVER['QUERY_STRING'])) : '';
		$add_query_string = $query_string ? '?' . $query_string : '';

		if ('wp-login.php' === $pagenow && $request_path !== $this->is_trailingslashit($request_path) && get_option('permalink_structure')) {
			wp_safe_redirect($this->is_trailingslashit($this->new_login_url()) . $add_query_string);
			exit;
		} elseif ($this->old_login_page) {
			$referer  = wp_get_referer();
			$referers = wp_parse_url($referer);

			$referer_contains_activate_url = false !== stripos($referer, 'wp-activate.php') ? true : false;

			if ($referer_contains_activate_url && !empty($referers['query'])) {
				parse_str($referers['query'], $referer_queries);

				$signup_key           = $referer_queries['key'];
				$wpmu_activate_signup = wpmu_activate_signup($signup_key);

				@require_once WPINC . '/ms-functions.php';

				if (!empty($signup_key) && is_wp_error($wpmu_activate_signup)) {
					if ('already_active' === $wpmu_activate_signup->get_error_code() || 'blog_taken' === $wpmu_activate_signup->get_error_code()) {
						wp_safe_redirect($this->new_login_url() . $add_query_string);
						exit;
					}
				}
			}

			$this->wp_template_loader();
		} elseif ('wp-login.php' === $pagenow) {
			$redirect_to           = admin_url();
			$requested_redirect_to = '';

			if (isset($_REQUEST['redirect_to'])) {
				$requested_redirect_to = esc_url_raw(wp_unslash($_REQUEST['redirect_to']));
			}

			if (is_user_logged_in()) {
				$user = wp_get_current_user();

				if (!isset($_REQUEST['action'])) {
					wp_safe_redirect($redirect_to);
					exit;
				}
			}

			// Prevent warnings in wp-login.php file by providing these globals.
			global $user_login, $error, $iterim_login, $action;

			@require_once ABSPATH . 'wp-login.php';
			exit;
		}
	}


	/**
	 * Filter site_url.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/site_url/
	 */
	public function site_url($url, $path, $scheme, $blog_id)
	{
		return $this->filter_old_login_page($url, $scheme);
	}


	/**
	 * Filter old login page.
	 *
	 * @param string $scheme Scheme to give the site URL context. Accepts 'http', 'https', 'login', 'login_post', 'admin', 'relative' or null.
	 */
	public function filter_old_login_page($url, $scheme = null)
	{
		if (false !== stripos($url, 'wp-login.php?action=postpass')) {
			return $url;
		}

		$url_contains_old_login_url     = false !== stripos($url, 'wp-login.php') ? true : false;
		$referer_contains_old_login_url = false !== stripos(wp_get_referer(), 'wp-login.php') ? true : false;

		if ($url_contains_old_login_url && !$referer_contains_old_login_url) {
			if (is_ssl()) {
				$scheme = 'https';
			}

			$url_parts = explode('?', $url);

			if (isset($url_parts[1])) {
				parse_str($url_parts[1], $args);

				if (isset($args['login'])) {
					$args['login'] = rawurlencode($args['login']);
				}

				$url = add_query_arg($args, $this->new_login_url($scheme));
			} else {
				$url = $this->new_login_url($scheme);
			}
		}

		return $url;
	}

	/**
	 * Filter network_site_url.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/network_site_url/
	 */
	public function network_site_url($url, $path, $scheme)
	{
		return $this->filter_old_login_page($url, $scheme);
	}

	/**
	 * Filter wp_redirect.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/wp_redirect/
	 */
	public function wp_redirect($location, $status)
	{
		return $this->filter_old_login_page($location);
	}

	/**
	 * WordPress template loader.
	 *
	 * @return void
	 */
	public function wp_template_loader()
	{
		global $pagenow;

		$pagenow = 'index.php';

		if (!defined('WP_USE_THEMES')) {
			define('WP_USE_THEMES', true);
		}

		wp();

		require_once ABSPATH . WPINC . '/template-loader.php';

		exit;
	}

	/**
	 * Get new login url.
	 *
	 * @param string|null $scheme Scheme to give the site URL context. Accepts 'http', 'https', 'login', 'login_post', 'admin', 'relative' or null.
	 */
	public function new_login_url($scheme = null)
	{
		$login_url = site_url($this->login_redirect_slug, $scheme);

		if (get_option('permalink_structure')) {
			return $this->is_trailingslashit($login_url);
		} else {
			return home_url('/', $scheme) . '?' . $this->login_redirect_slug;
		}

		return $login_url;
	}
}
