<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

/**
 * @package WP Adminify
 * @author: Jewel Theme<support@jeweltheme.com>
 */

class Tweaks extends AdminSettingsModel
{

	public $redirect_status = 301;
	private $performance;
	private $security_head;
	private $security_feed;
	private $security_rest_api;
	private $security_posts;
	private $security_media;
	private $custom_gravatar;

	public function __construct()
	{
		$this->options           = (array) AdminSettings::get_instance()->get();
		$this->performance       = $this->options['performance'];
		$this->security_head     = $this->options['security_head'];
		$this->security_feed     = $this->options['security_feed'];
		$this->security_rest_api = $this->options['security_rest_api'];
		$this->security_posts    = $this->options['post_archives'];
		$this->security_media    = $this->options['media_attachments'];
		$this->custom_gravatar   = $this->options['custom_gravatar'];



		if (!empty($this->security_head['enable_security_head'])) {
			$this->security_head();
		}

		$this->security_feed();

		if (!empty($this->security_rest_api['security_rest_api_enable'])) {
			$this->security_rest_api();
		}

		if (!empty($this->security_posts['post_archives_enable'])) {
			$this->security_post_archives();
		}

		$this->security_attachments();

		if(!empty($this->performance['performance_enable'])){
			$this->adminify_performances();
		}

		// Add Custom Default Gravatar Image
		if (!empty($this->custom_gravatar) && array_key_exists('enable', $this->custom_gravatar)) {
			// Add Custom Default Gravatar Image
			add_filter('avatar_defaults', [$this, 'add_custom_gravatar_image'], 99);
		}

		// If Admin bar Editor Plugin not Installed
		if (! class_exists('\JewelTheme\AdminBarEditor\AdminBarEditor')) {
			if (!empty($this->options['white_label']['wordpress']['remove_howdy_msg']) || !empty($this->options['white_label']['wordpress']['change_howdy_text'])) {
				// Change Howdy Text
				// add_action('admin_bar_menu', [$this, 'remove_from_admin_bar'], 999);
				// add_action('admin_bar_menu', [$this, 'clear_node_title'], 999);
				add_filter('admin_bar_menu', [$this, 'adminify_change_howdy_text'], 9999);
			}
		}


		// Remove Admin Bar Logo
		if(!empty($this->options['white_label']['wordpress']['admin_bar_cleanup'])){
			add_action("wp_before_admin_bar_render", [$this, "admin_bar_cleanup"], 0);
		}

	}


	/**
	 * Security: Post & Archives
	 *
	 * @return void
	 */
	public function security_post_archives(){
		$post_archives_data = $this->security_posts['post_archives_data'];

		// tag and category hooks
		if (!empty($post_archives_data) && in_array('last_modified_date', $post_archives_data)) {
			add_filter('the_content', [$this, 'last_updated_date'], 10, 1);
			add_filter('wp_head', [$this, 'last_updated_date_style']);
		}

		/** Remove Capital P Dangit */
		if (!empty($post_archives_data) && in_array('capital_p_dangit', $post_archives_data)) {
			remove_filter('the_title', 'capital_P_dangit', 11);
			remove_filter('the_content', 'capital_P_dangit', 11);
			remove_filter('comment_text', 'capital_P_dangit', 31);
		}

		// Remove date archives
		if (!empty($post_archives_data) && (
				in_array('archives_date', $post_archives_data) ||
				in_array('archives_author', $post_archives_data) ||
				in_array('archives_tag', $post_archives_data) ||
				in_array('archives_category', $post_archives_data) ||
				in_array('archives_postformat', $post_archives_data) ||
				in_array('archives_search', $post_archives_data)
			)) {
			add_action('template_redirect', [$this, 'redirect_to_home']);
		}

		// // Remove Author archives
		// if (!empty($post_archives_data) && in_array('archives_author', $post_archives_data)) {
		// 	if (is_author()) {
		// 		add_action('template_redirect', [$this, 'redirect']);
		// 	}
		// }


		// // Remove tag archives
		// if (!empty($post_archives_data) && in_array('archives_tag', $post_archives_data)) {
		// 	if (is_tag()) {
		// 		add_action('template_redirect', [$this, 'redirect']);
		// 	}
		// }

		// // Remove category archives
		// if (!empty($post_archives_data) && in_array('archives_category', $post_archives_data)) {
		// 	if (is_category()) {
		// 		add_action('template_redirect', [$this, 'redirect']);
		// 	}
		// }

		// // Remove archives post formats
		// if (!empty($post_archives_data) && in_array('archives_postformat', $post_archives_data)) {
		// 	if (is_tax('post_format')) {
		// 		add_action('template_redirect', [$this, 'redirect']);
		// 	}
		// }

		// // Remove search page
		// if (!empty($post_archives_data) && in_array('archives_search', $post_archives_data)) {
		// 	if (is_search()) {
		// 		add_action('template_redirect', [$this, 'redirect']);
		// 	}
		// }

	}

	public function url_redirection(){
		global $wp_query;
		$wp_query->set_404();
		wp_redirect(esc_url(home_url()), 301);
	}

	public function redirect_to_home(){
		$post_archives_data = $this->security_posts['post_archives_data'];

		// Remove Author archives
		if (!empty($post_archives_data) && in_array('archives_author', $post_archives_data)) {
			if (is_author()) {
				$this->url_redirection();
			}
		}


		// Remove tag archives
		if (!empty($post_archives_data) && in_array('archives_tag', $post_archives_data)) {
			if (is_tag()) {
				$this->url_redirection();
			}
		}

		// Remove category archives
		if (!empty($post_archives_data) && in_array('archives_category', $post_archives_data)) {
			if (is_category()) {
				$this->url_redirection();
			}
		}

		// Remove archives post formats
		if (!empty($post_archives_data) && in_array('archives_postformat', $post_archives_data)) {
			if (is_tax('post_format')) {
				$this->url_redirection();
			}
		}

		// Remove search page
		if (!empty($post_archives_data) && in_array('archives_search', $post_archives_data)) {
			if (is_search()) {
				$this->url_redirection();
			}
		}
	}


	/**
	 * Security: Attachments
	 *
	 * @return void
	 */
	public function security_attachments(){

		// Add Featured Image or Post Thumbnail to RSS Feed
		if (!empty($this->security_media['thumbnails_rss_feed'])) {
			add_filter('the_excerpt_rss', [$this, 'jltwp_adminify_rss_post_thumbnail']);
			add_filter('the_content_feed', [$this, 'jltwp_adminify_rss_post_thumbnail']);
		}

	}

	/**
	 * Disable REST API
	 *
	 * @return void
	 */
	public function security_rest_api(){
		$this->security_rest_api = $this->security_rest_api['security_rest_api_data'];

		/** Disable REST API */
		if (!empty($this->security_rest_api) && in_array('rest_api', $this->security_rest_api)) {

			// All REST API Hooks
			if (version_compare(get_bloginfo('version'), '4.7', '>=')) {
				add_filter('rest_authentication_errors', [$this, 'disable_rest_api']);
			} else {
				// Filters for WP-API version 1.x
				add_filter('json_enabled', '__return_false');
				add_filter('json_jsonp_enabled', '__return_false');

				// Filters for WP-API version 2.x
				add_filter('rest_enabled', '__return_false');
				add_filter('rest_jsonp_enabled', '__return_false');
			}

			remove_action('wp_head', 'rest_output_link_wp_head', 10); // Disable REST API links in the HTML <head> tag
			remove_action('wp_head', 'wp_oembed_add_discovery_links');	// Remove oEmbed discovery links.
			remove_action('template_redirect', 'rest_output_link_header', 11, 0); // Disable the REST API link in HTTP headers.
			remove_action('xmlrpc_rsd_apis', 'rest_output_rsd'); // Remove the REST API URL from the WordPress RSD endpoint.
		}


		// Remove X-Powered-By
		if (!empty($this->security_rest_api) && in_array('powered', $this->security_rest_api)) {
			// add_action('wp', [$this, 'remove_powered']); // OLD way

			// Hook into 'send_headers' to remove the "X-Powered-By" header
			add_action('send_headers', [$this, 'remove_powered']);
		}
	}


	public function disable_rest_api(){
		if (!is_user_logged_in()) {
			return new \WP_Error(
				'rest_api_authentication_required',
				__('The REST API has been restricted to authenticated users.', 'adminify'),
				[
					'status' => rest_authorization_required_code()
				]
			);
		}
	}


	/**
	 * Security Head
	 *
	 * @return void
	 */
	public function security_head(){

		$this->security_head = $this->security_head['security_head_data'];

		// Disable XML-RPC
		if (!empty($this->security_head) && in_array('xmlrpc', $this->security_head)) {
			// add_filter('xmlrpc_enabled', '__return_false');
			add_filter('wp_xmlrpc_server_class', [$this, 'adminify_disable_wp_xmlrpc']);
		}

		// Remove WordPress Version Number
		if (!empty($this->security_head) && in_array('generator_wp_version', $this->security_head)) {
			remove_action('wp_head', 'wp_generator'); // Remove WordPress Generator Version
			add_filter('the_generator', '__return_false'); // Remove Generator Name From Rss Feeds.
		}

		// Remove Revolution Slider generator version
		if (!empty($this->security_head) && in_array('revslider_generator', $this->security_head)) {
			add_filter('revslider_meta_generator', '__return_empty_string');
		}

		// Remove woocommerce generator version
		if (!empty($this->security_head) && in_array('wc_generator', $this->security_head)) {
			remove_action('wp_head', 'wc_generator_tag');
		}

		// Remove wpml meta generator tag
		if (!empty($this->security_head) && in_array('wpml_generator', $this->security_head)) {
			add_action('wp_head', [$this, 'remove_wpml_generator'], 0);
		}

		// Remove wpbakery visual_composer meta generator tag
		if (!empty($this->security_head) && in_array('js_composer_generator', $this->security_head)) {
			if (class_exists('Vc_Manager')) {
				remove_action('wp_head', [visual_composer(), 'addMetaData'], 1);
			}
		}

		// Remove Yoast SEO meta generator tag
		if (!empty($this->security_head) && in_array('yoast_generator', $this->security_head)) {
			add_filter('wpseo_debug_markers', '__return_false');
		}

		// REMOVE wlwmanifest.xml.
		if (!empty($this->security_head) && in_array('wlwmanifest', $this->security_head)) {
			remove_action('wp_head', 'wlwmanifest_link');
		}

		// Remove Really Simple Discovery Link.
		if (!empty($this->security_head) && in_array('rsd', $this->security_head)) {
			remove_action('wp_head', 'rsd_link');
		}

		// Remove Shortlink Url
		if (!empty($this->security_head) && in_array('shortlink', $this->security_head)) {
			remove_action('wp_head', 'wp_shortlink_wp_head');
			remove_action('template_redirect', 'wp_shortlink_header', 100, 0);
		}

		/** Remove Version Query Strings from Scripts/Styles */
		if (!empty($this->security_head) && in_array('canonical', $this->security_head)) {
			remove_action('embed_head', 'rel_canonical');

			// Function to remove the canonical URL
			//

			// Hook the function to wp_head
			add_action('wp_head', [$this, 'remove_wp_canonical_url'] , 1);
			// Yoast SEO Canonical URL
			if (Utils::is_plugin_active('wordpress-seo/wp-seo.php')) {
				add_filter('wpseo_canonical', '__return_false');
			}
		}


		/* Disable Self Pings */
		if (!empty($this->security_head) && in_array('self_ping', $this->security_head)) {
			add_action('pre_ping', [$this, 'disable_self_ping']);
		}

	}

	/**
	 * Disable XMLRPC file
	 *
	 * @return void
	 */
	public function adminify_disable_wp_xmlrpc($data)
	{
		http_response_code(403);
	}

	/**
	 * Security Feed Links
	 *
	 * @return void
	 */
	public function security_feed(){

		// Remove Feed Links
		if (!empty($this->security_feed)) {
			remove_action('wp_head', 'feed_links_extra', 3); // Remove Every Extra Links to Rss Feeds.
			remove_action('wp_head', 'feed_links', 2);	// Remove Head link <head/>
			remove_action('do_feed_rdf', 'do_feed_rdf', 10, 0);
			remove_action('do_feed_rss', 'do_feed_rss', 10, 0);
			remove_action('do_feed_rss2', 'do_feed_rss2', 10, 1);
			remove_action('do_feed_atom', 'do_feed_atom', 10, 1);

			// Redirect to Home
			add_action('do_feed', [$this, 'redirect'], 1);
			add_action('do_feed_rdf', [$this, 'redirect'], 1);
			add_action('do_feed_rss', [$this, 'redirect'], 1);
			add_action('do_feed_rss2', [$this, 'redirect'], 1);
			add_action('do_feed_atom', [$this, 'redirect'], 1);

		}
	}



	/*
	* Change Howdy Text
	*/
	public function adminify_change_howdy_text($wp_admin_bar)
	{
		// Remove Howdy Message entirely
		if (!empty($this->options['white_label']['wordpress']['remove_howdy_msg'])) {

			// Remove the entire "My Account" section and rebuild it later.
			remove_action('admin_bar_menu', 'wp_admin_bar_my_account_item', 7);

			$current_user = wp_get_current_user();
			$user_id = get_current_user_id();
			$profile_url  = get_edit_profile_url($user_id);

			$avatar = get_avatar($user_id, 26); // size 26x26 pixels
			$display_name = $current_user->display_name;
			$class = $avatar ? 'with-avatar' : 'no-avatar';
			$wp_admin_bar->add_menu(array(
				'id'        => 'my-account',
				'parent'    => 'top-secondary',
				'title'     => $display_name . $avatar,
				'href'      => $profile_url,
				'meta'      => array(
					'class'     => $class,
				),
			));
		}

		// Change Howdy Text
		if (!empty($this->options['white_label']['wordpress']['change_howdy_text'])) {

			// Get the current user information
			$my_account = $wp_admin_bar->get_node('my-account');

			if (isset($my_account->title)) {

				// Replace the "Howdy" text with "Welcome"
				$changed_howdy_text = str_replace('Howdy', Utils::wp_kses_custom($this->options['white_label']['wordpress']['change_howdy_text']), $my_account->title);

				// Update the node with the new title
				$wp_admin_bar->add_node([
					'id'     => 'my-account',
					'parent' => 'top-secondary',
					'title'  => $changed_howdy_text,
				]);
			}

		}
	}

	/**
	 * Remove Canonical URL
	 *
	 * @return void
	 */
	public function remove_wp_canonical_url()
	{
		remove_action('wp_head', 'rel_canonical');
	}

	/**
	 * Removes admin bar logo from Admin Toolbar
	 */
	public function admin_bar_cleanup()
	{
		global $wp_admin_bar;
		//TODO: Check for Network wide site "my-sites"

		if (in_array('wp_logo', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
			$wp_admin_bar->remove_menu('wp-logo');
		}
		if (in_array('site_name', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
			$wp_admin_bar->remove_menu('site-name');
		}
		if (in_array('comments', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
			$wp_admin_bar->remove_menu('comments');
		}
		// if (in_array('menu_toggle', $this->options['white_label']['wordpress']['admin_bar_cleanup'])) {
		// 	$wp_admin_bar->remove_menu('menu-toggle');
		// }
	}



	// Clear the node titles
	// This will only work if the node is using a :before element for the icon
	public function clear_node_title($wp_admin_bar)
	{
		$all_toolbar_nodes = $wp_admin_bar->get_nodes();
		// Create an array of node ID's we'd like to remove
		$clear_titles = [
			'site-name',
			'customize',
		];

		foreach ($all_toolbar_nodes as $node) {

			// Run an if check to see if a node is in the array to clear_titles
			if (in_array($node->id, $clear_titles)) {
				// use the same node's properties
				$args = $node;

				// make the node title a blank string
				$args->title = '';

				// update the Toolbar node
				$wp_admin_bar->add_node($args);
			}
		}
	}

	// Remove items from the admin bar
	public function remove_from_admin_bar($wp_admin_bar)
	{
		/*
		* Placing items in here will only remove them from admin bar
		* when viewing the fronte end of the site
		*/
		if (!is_admin()) {
			// Example of removing item generated by plugin. Full ID is #wp-admin-bar-si_menu
			$wp_admin_bar->remove_node('si_menu');

			// WordPress Core Items (uncomment to remove)
			$wp_admin_bar->remove_node('updates');
			$wp_admin_bar->remove_node('comments');
			$wp_admin_bar->remove_node('new-content');
			// $wp_admin_bar->remove_node('wp-logo');
			// $wp_admin_bar->remove_node('site-name');
			$wp_admin_bar->remove_node('my-account');
			// $wp_admin_bar->remove_node('search');
			// $wp_admin_bar->remove_node('customize');
		}

		/*
		* Items placed outside the if statement will remove it from both the frontend
		* and backend of the site
		*/
		$wp_admin_bar->remove_node('wp-logo');
	}

	// Custom Avatars
	public function add_custom_gravatar_image($avatar_defaults)
	{
		// if (!empty($this->custom_gravatar) && array_key_exists('image', $this->custom_gravatar)) {
		// 	foreach ($this->custom_gravatar['image'] as $key => $value) {
		// 		$avatar_url                     = esc_url_raw($value['avatar_image']['url']);
		// 		$avatar_defaults[$avatar_url] = $value['avatar_name'];
		// 	}
		// }
		$custom_avatar_url = 'https://scontent.fzyl2-2.fna.fbcdn.net/v/t39.30808-6/348970682_781621106945149_5992028158112526767_n.jpg?_nc_cat=100&ccb=1-7&_nc_sid=cc71e4&_nc_ohc=gUNSZF2ODw8Q7kNvgG5T48V&_nc_ht=scontent.fzyl2-2.fna&oh=00_AYDLLCAW7eyeRQakH_Fy9R57hYPAekMNIcEHLGi12watcA&oe=66906465'; // Replace with your custom avatar URL
		$avatar_defaults[$custom_avatar_url] = 'Custom Gravatar'; // Text to display in the avatar dropdown

		return $avatar_defaults;
	}

	// Check Last Login Column
	public function last_login_column_info( $user_login )
    {
        $user = get_user_by( 'login', $user_login );
        // by username
        update_user_meta( $user->ID, 'adminify_last_login_on', time() );
    }


	// Remove WPML Generator
	public function remove_wpml_generator()
	{
		if (!empty($GLOBALS['sitepress'])) {
			remove_action(current_filter(), [$GLOBALS['sitepress'], 'meta_generator_tag']);
		}
	}


	/**
	 * Disable WP emojicons from TinyMCE
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function disable_emojicons_tinymce($plugins)
	{
		if (is_array($plugins)) {
			return array_diff($plugins, ['wpemoji']);
		} else {
			return [];
		}
	}

	/**
	 * Disable WP emojicons from TinyMCE
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function remove_dns_prefetch( $urls, $relation_type ) {
		if ('dns-prefetch' == $relation_type) {
			// Strip out any URLs referencing the WordPress.org emoji location
			$emoji_svg_url_base = 'https://s.w.org/images/core/emoji/';
			foreach ($urls as $key => $url) {
				if (is_string($url) && false !== strpos($url, $emoji_svg_url_base)) {
					unset($urls[$key]);
				}
			}
		}
		return $urls;
	}

	public function remove_powered()
	{
		if (function_exists('header_remove')) {
			header_remove('X-Powered-By');
		}
	}



	/**
	 * Remove all rewrite rules related to embeds.
	 *
	 * @param array $rules WordPress rewrite rules.
	 * @return array Rewrite rules without embeds rules.
	 */
	public function adminify_disable_embeds_rewrites($rules)
	{
		foreach ($rules as $rule => $rewrite) {
			if (false !== strpos($rewrite, 'embed=true')) {
				unset($rules[$rule]);
			}
		}

		return $rules;
	}

	// Redirect 403
	public function redirect_to_403($url = false)
	{
		status_header(403); // Send an HTTP 403 Forbidden status header
		die('403 Forbidden'); // End execution and display a 403 Forbidden message
	}

	// Redirect function
	public function redirect($url = false)
	{
		if ($url) {
			$target = $url;
		} else {
			$target = esc_url(home_url());
		}

		$target = apply_filters('wp_adminify_redirect_target', $target);
		$status = apply_filters('wp_adminify_redirect_status', $this->redirect_status);

		wp_redirect($target, $status);
		die();
	}


	/* Add Featured Image or Post Thumbnail to RSS Feed */
	public function jltwp_adminify_rss_post_thumbnail($content)
	{
		global $post;
		if (has_post_thumbnail($post->ID)) {
			$content = '<p>' . get_the_post_thumbnail($post->ID) .
				'</p>' . get_the_content();
		}
		return $content;
	}

	// Display Last Updated Date of Your Posts
	public function last_updated_date($content)
	{
		if (!is_single()) {
			return $content;
		}

		$u_time          = get_the_time('U');
		$u_modified_time = get_the_modified_time('U');

		if ($u_modified_time >= $u_time + 86400) {
			$custom_content = sprintf(
				__('<div class="wp-adminify-last-updated"><strong><span>%1$s</span></strong><span>%2$s %3$s</span></div>', 'adminify'),
				esc_html__('Last Updated on ', 'adminify'),
				get_the_modified_time('F jS, Y'),
				get_the_modified_time('h:i a')
			);
			return $custom_content . $content;
		}

		return $content;
	}

	public function last_updated_date_style()
	{
		echo '<style>.wp-adminify-last-updated{ border: 1px dashed red; padding: 5px 10px;}</style>';
	}


	// Remove Default Image Links typeremove_image_link
	public function security_attachment_imagelink()
	{
		$image_set = get_option('image_default_link_type');

		if ($image_set !== 'none') {
			update_option('image_default_link_type', 'none');
		}
	}


	/* Disable Self Pings */
	public function disable_self_ping(&$links)
	{
		$home = esc_url( home_url() );

		// Additional URLs and explode into an array.
		$extra_urls = (!empty($this->options['self_ping_sites'])) ? $this->options['self_ping_sites'] : '';
		$extra_urls = explode(PHP_EOL, $extra_urls);

		if ( is_array( $extra_urls ) ) {
			$url_array = $extra_urls;
		} else {
			$url_array = array();
		}

		foreach ( $links as $l => $link ) {
			if ( 0 === strpos( $link, $home ) ) {
				unset( $links[ $l ] );
			}
			foreach ( $url_array as $url ) {
				$url = trim( $url );
				if ( 0 === strpos( $link, $url ) && '' !== $url ) {
					unset( $links[ $l ] );
				}
			}
		}

	}


	/** Remove Dashicons from Admin Bar for non logged in users **/
	public function jltwp_adminify_remove_dashicons()
	{
		global $pagenow;

		if (!is_user_logged_in()) {

			// This retrieves the /path/file.php?param=val part of the URL
			$current_request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);

			// for homepage
			if (empty($current_request_uri)) {
				wp_dequeue_style('dashicons');
				wp_deregister_style('dashicons');
			} else {
				// Exclude the login page, where Dashicons assets are required for proper styling.
				if (false !== strpos($current_request_uri, 'wp-login.php') || 'wp-login.php' === $pagenow) {
					// Do nothing on wp-login.php
				} elseif (false !== strpos($current_request_uri, 'protected-page=view')) {
					// Exclude the password protection form.
					// Do nothing on protected-page=view.
				} else {
					// Dequeue Dashicons from contents e.g., yoursite.com/content-url/. Not for wp-login.php,
					wp_dequeue_style('dashicons');
					wp_deregister_style('dashicons');
				}
			}
		}
	}


	/** Control Interval Heartbeat API **/
	public function control_heartbeat_api($settings)
	{
		$settings['interval'] = 60;
		return $settings;
	}

	/** Remove Query Strings from Scripts/Styles **/
	public function remove_script_versions($src)
	{
		if( !empty($src) ) {
			$parts = explode('?ver', $src);
			return $parts[0];
		}
	}

	/** Browser Cache Expires & GZIP Compression **/
	public function jltwp_adminify_htaccess()
	{
		// We get the main WordPress .htaccess filepath.
		$ruta_htaccess = get_home_path() . '.htaccess'; // https://codex.wordpress.org/Function_Reference/get_home_path !

		$lineas   = [];
		$lineas[] = '<IfModule mod_expires.c>';
		$lineas[] = '# Activar caducidad de contenido';
		$lineas[] = 'ExpiresActive On';
		$lineas[] = '# Directiva de caducidad por defecto';
		$lineas[] = 'ExpiresDefault "access plus 1 month"';
		$lineas[] = '# Para el favicon';
		$lineas[] = 'ExpiresByType image/x-icon "access plus 1 year"';
		$lineas[] = '# Imagenes';
		$lineas[] = 'ExpiresByType image/gif "access plus 1 month"';
		$lineas[] = 'ExpiresByType image/png "access plus 1 month"';
		$lineas[] = 'ExpiresByType image/jpg "access plus 1 month"';
		$lineas[] = 'ExpiresByType image/jpeg "access plus 1 month"';
		$lineas[] = '# CSS';
		$lineas[] = 'ExpiresByType text/css "access 1 month"';
		$lineas[] = '# Javascript';
		$lineas[] = 'ExpiresByType application/javascript "access plus 1 year"';
		$lineas[] = '</IfModule>';
		$lineas[] = '<IfModule mod_deflate.c>';
		$lineas[] = '# Activar compresión de contenidos estáticos';
		$lineas[] = 'AddOutputFilterByType DEFLATE text/plain text/html';
		$lineas[] = 'AddOutputFilterByType DEFLATE text/xml application/xml application/xhtml+xml application/xml-dtd';
		$lineas[] = 'AddOutputFilterByType DEFLATE application/rdf+xml application/rss+xml application/atom+xml image/svg+xml';
		$lineas[] = 'AddOutputFilterByType DEFLATE text/css text/javascript application/javascript application/x-javascript';
		$lineas[] = 'AddOutputFilterByType DEFLATE font/otf font/opentype application/font-otf application/x-font-otf';
		$lineas[] = 'AddOutputFilterByType DEFLATE font/ttf font/truetype application/font-ttf application/x-font-ttf';
		$lineas[] = '</IfModule>';

		insert_with_markers($ruta_htaccess, 'WP Adminify by Jewel Theme', $lineas); // https://developer.wordpress.org/reference/functions/insert_with_markers/ !
	}


	/**
	 * Performance Cleanup
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function adminify_performances()
	{
		$performance_data = $this->performance['performance_data'];

		// Remove jQuery Migrate

		// Remove Dashicons from Frontend
		if (!empty($performance_data) && in_array('dashicons', $performance_data)) {
			add_action('wp_print_styles', [$this, 'jltwp_adminify_remove_dashicons'], 100);
		}

		/** Remove Version Query Strings from Scripts/Styles */
		if (!empty($performance_data) && in_array('version_strings', $performance_data)) {
			if(!is_admin()){
				add_filter('script_loader_src', [$this, 'remove_script_versions'], 15, 1);
				add_filter('style_loader_src', [$this, 'remove_script_versions'], 15, 1);
			}
		}


		// Remove Emoji Styles and Scripts
		if (!empty($performance_data) && in_array('emoji', $performance_data)) {
			remove_action('wp_head', 'print_emoji_detection_script', 7); // Remove Emoji's Styles and Scripts.
			remove_action('embed_head', 'print_emoji_detection_script');
			remove_action('embeded_head', 'print_emoji_detection_script');
			remove_action('admin_print_scripts', 'print_emoji_detection_script'); // Remove Emoji's Styles and Scripts.
			remove_action('wp_print_styles', 'print_emoji_styles'); // Remove Emoji's Styles and Scripts.
			remove_action('admin_print_styles', 'print_emoji_styles'); // Remove Emoji's Styles and Scripts.
			remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
			remove_filter('the_content_feed', 'wp_staticize_emoji');
			remove_filter('comment_text_rss', 'wp_staticize_emoji');
			add_filter('option_use_smilies', '__return_false');
			add_filter('emoji_svg_url', '__return_false');
			add_action('admin_init', [$this, 'remove_admin_emojis']);
			add_filter('tiny_mce_plugins', [$this, 'disable_emojicons_tinymce']);
			add_filter('wp_resource_hints', [$this, 'remove_dns_prefetch'], 10, 2);
		}



		add_action('wp_default_scripts', [$this, 'remove_wp_default_scripts'], 9999);

		/** Secure method for Defer Parsing of JavaScript moving ALL JS from Header to Footer */
		if (in_array('defer_parsing_js_footer', $performance_data)) {
			add_filter('script_loader_tag', [$this, 'defer_parsing_of_js'], 10, 2);
		}

		/** Browser Cache Expires & GZIP Compression */
		if (in_array('cache_gzip_compression', $performance_data)) {
			register_activation_hook(__FILE__, [$this, 'jltwp_adminify_htaccess']);
		}


		// Remove Gravatar Query Strings
		if (!empty($performance_data) && in_array('gravatar_query_strings', $performance_data)) {
			add_filter('get_avatar_url', [$this, 'remove_avatar_query_string']);
		}
	}

	/* Remove Gravatar Query Strings */
	public function remove_avatar_query_string($url)
	{
		$url_parts = explode('?', $url);
		return $url_parts[0];
	}


	public function remove_admin_emojis()
	{
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('admin_print_styles', 'print_emoji_styles');
	}


	/** Secure method for Defer Parsing of JavaScript moving ALL JS from Header to Footer **/
	public function defer_parsing_of_js($tag, $handle)
	{
		$skip = apply_filters('wp_adminify_defer_skip', false, $tag, $handle);

		if ($skip) {
			return $tag;
		}

		if (is_admin()) {
			return $tag;
		}
		if (strpos($tag, '/wp-includes/js/jquery/jquery')) {
			return $tag;
		}
		if (isset($_SERVER['HTTP_USER_AGENT']) && strpos(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 'MSIE 9.') !== false) {
			return $tag;
		} else {
			return str_replace(' src', ' defer src', $tag);
		}
	}

	/**
	 * Remove jQuery Migrate Script
	 *
	 * @param [type] $scripts
	 *
	 * @return void
	 */
	public function remove_wp_default_scripts($scripts)
	{
		// Frontend
		if (!is_admin() && in_array('jquery_migrate_front', $this->performance['performance_data'])) {
			$scripts->registered["jquery"]->deps = array_diff($scripts->registered["jquery"]->deps, ["jquery-migrate"]);
		}

		// Backend
		if (is_admin() && in_array('jquery_migrate_back', $this->performance['performance_data'])) {
			$scripts->registered["jquery"]->deps = array_diff($scripts->registered["jquery"]->deps, ["jquery-migrate"]);
		}

	}
}
