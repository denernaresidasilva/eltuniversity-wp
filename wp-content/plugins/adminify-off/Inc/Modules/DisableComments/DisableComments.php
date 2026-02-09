<?php

namespace WPAdminify\Inc\Modules\DisableComments;

use \Elementor;
use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Classes\Multisite_Helper;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;


// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPAdminify
 *
 * @package Module: Disable Comments
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class DisableComments extends AdminSettingsModel {

	public $options;
	public $disable_comments;

	public function __construct() {
		$this->options = (array) AdminSettings::get_instance()->get();
		$this->disable_comments = $this->options['disable_comments'];


		if (!empty($this->disable_comments['disable_comments']['enable_disable_comments'])) {
			return;
		}

		// Disable Comments
		if(!empty($this->disable_comments['post_types'])){
			add_action( 'admin_init', [ $this, 'disable_comments_settings' ] );	// Supported Hooks: 'init', 'admin_init', 'wp_loaded', 'do_meta_boxes'
		}


		// Close comments on the front-end
		if (!empty($this->disable_comments['apply_for']) && in_array('close_front', $this->disable_comments['apply_for'])) {
			add_filter( 'comments_open', '__return_false', 20, 2 );
			add_filter( 'pings_open', '__return_false', 20, 2 );
		}

		// Remove comment notes
		if (!empty($this->disable_comments['apply_for']) && in_array('comments_notes', $this->disable_comments['apply_for'])) {
			add_filter('comment_form_defaults', [$this, 'remove_comments_notes']);
		}

		// Remove comments links from admin bar
		if (!empty($this->disable_comments['apply_for']) && in_array('admin_bar', $this->disable_comments['apply_for'])) {
			// add_action('init', [$this, 'jltma_adminify_disable_comments_admin_bar_menu']);
			add_action( 'wp_before_admin_bar_render', [ $this, 'jltma_adminify_remove_admin_bar_menus' ], 0 );
		}

		// URL field remove from Comments
		if (!empty($this->disable_comments['apply_for']) && in_array('comments_url_field', $this->disable_comments['apply_for'])) {
			add_filter( 'comment_form_default_fields', [ $this,
			'remove_url_field_comment_form' ] );
		}

		// Remove comments page in menu
		if (!empty($this->disable_comments['apply_for']) && in_array('admin_menu', $this->disable_comments['apply_for'])) {
			add_action( 'admin_menu', [ $this, 'remove_comments_admin_menu' ] );
		}

		// Remove "Discussion" submenu from Settings  menu
		if (!empty($this->disable_comments['apply_for']) && in_array('discussion_menu', $this->disable_comments['apply_for'])) {
			add_action( 'admin_menu', [$this, 'remove_discussion_submenu'] );
		}
	}

	/**
	 * Remove Discussion Submenu
	 */
	public function remove_discussion_submenu() {
		remove_submenu_page('options-general.php', 'options-discussion.php');
	}

	/**
	 * Remove Comments Menu
	 */
	public function remove_comments_admin_menu(){
		remove_menu_page('edit-comments.php');
	}

	/**
	 * Disable Comments Settings
	 *
	 * @return void
	 */
	public function disable_comments_settings() {

		// Disable support for comments and trackbacks in post types
		$post_types = $this->disable_comments['post_types'];
		foreach ($post_types as $post_type) {
			if (post_type_supports($post_type, 'comments')) {
				remove_post_type_support($post_type, 'comments');
				remove_post_type_support($post_type, 'trackbacks');
				\remove_meta_box('commentstatusdiv', $post_type, 'normal');
				\remove_meta_box('commentstatusdiv', $post_type, 'side');
				remove_meta_box('commentsdiv', $post_type, 'normal');
				remove_meta_box('commentsdiv', $post_type, 'side');
				remove_meta_box('trackbacksdiv', $post_type, 'normal');
				remove_meta_box('trackbacksdiv', $post_type, 'side');
				// wp_dequeue_script('admin-comments');	// edit-comments.js
			}
		}



		// Remove styles for .recentcomments
		if (!empty($this->disable_comments['apply_for']) && in_array('recentcomments', $this->disable_comments['apply_for'])) {
			add_action('widgets_init', [$this, 'jltwp_adminify_remove_recent_comments_style']);
			add_filter( 'show_recent_comments_widget_style', '__return_false' );
		}

		// Redirect any user trying to access comments page.
		if (!empty($this->disable_comments['apply_for']) && in_array('menu_redirect', $this->disable_comments['apply_for'])) {
			global $pagenow;
			if ($pagenow === 'edit-comments.php') {
				wp_safe_redirect(admin_url());
				exit;
			}
		}

		// Updated Options
		add_filter('xmlrpc_allow_anonymous_comments', '__return_false');
		add_filter('xmlrpc_methods', [$this, 'disable_xmlrpc_comments']);
		add_filter('rest_endpoints', [$this, 'disable_rest_api_comments_endpoints']);
		add_filter('rest_pre_insert_comment', [$this, 'return_blank_comment'], 10, 2);
	}

	// Remove styles for .recentcomments
	public function jltwp_adminify_remove_recent_comments_style()
	{
		global $wp_widget_factory;
		remove_action('wp_head', [$wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style']);
	}


	/**
	 * Return blank comment before inserting to DB
	 *
	 * @link https://plugins.trac.wordpress.org/browser/disable-comments/tags/2.4.5/disable-comments.php
	 */
	public function return_blank_comment($prepared_comment, $request)
	{
		return;
	}


	/**
	 * Disables comments endpoint in REST API
	 *
	 * @link https://plugins.trac.wordpress.org/browser/disable-comments/tags/2.4.5/disable-comments.php
	 */
	public function disable_rest_api_comments_endpoints($endpoints)
	{
		if (isset($endpoints['comments'])) {
			unset($endpoints['comments']);
		}

		if (isset($endpoints['/wp/v2/comments'])) {
			unset($endpoints['/wp/v2/comments']);
		}

		if (isset($endpoints['/wp/v2/comments/(?P<id>[\d]+)'])) {
			unset($endpoints['/wp/v2/comments/(?P<id>[\d]+)']);
		}

		return $endpoints;
	}

	public function disable_xmlrpc_comments($methods)
	{
		unset($methods['wp.newComment']);
		return $methods;
	}

	// Remove Comment Notes
	public function remove_comments_notes($defaults){
		$defaults['comment_notes_before'] = '';

		return $defaults;
	}

	/* Remove from the administration bar */
	public function jltma_adminify_remove_admin_bar_menus() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'comments' );
	}

	// Remove Comments Menu
	public function jltma_adminify_disable_comments_admin_bar_menu() {
		if ( is_admin_bar_showing() ) {
			remove_action( 'admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
		}
	}


	/**
	 * Remove url field from comment form
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */

	public function remove_url_field_comment_form( $fields ) {

		if ( isset( $fields['url'] ) ) {
			unset( $fields['url'] );
		}
		return $fields;
	}
}
