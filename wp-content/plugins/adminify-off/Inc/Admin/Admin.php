<?php

namespace WPAdminify\Inc\Admin;

use WPAdminify\Inc\Utils;
use \WPAdminify\Inc\Classes\CustomAdminColumns;
use \WPAdminify\Inc\Classes\Tweaks;
use \WPAdminify\Inc\Classes\MenuStyle;
use \WPAdminify\Inc\Classes\AdminBar;
use \WPAdminify\Inc\Classes\OutputCSS;
use \WPAdminify\Inc\Classes\ThirdPartyCompatibility;
use \WPAdminify\Inc\Classes\AdminFooterText;
use \WPAdminify\Inc\Admin\Modules;
use \WPAdminify\Inc\Classes\Sidebar_Widgets;
use \WPAdminify\Inc\Classes\Remove_DashboardWidgets;
use WPAdminify\Inc\Classes\Adminify_Rollback;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\Frames\Init as FrameInit;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}
/**
 * WP Adminify
 * Admin Class
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

if (!class_exists('Admin')) {
	class Admin
	{
		public $options = [];

		public function __construct()
		{
			$this->options = AdminSettings::get_instance()->get();

			$this->jltwp_adminify_modules_manager();

			// Remove Page Header like - Dashboard, Plugins, Users etc
			// add_action('admin_head', [$this, 'remove_page_headline'], 99);


			// Freemius Hooks
			jltwp_adminify()->add_filter('plugin_icon', array($this, 'jltwp_adminify_logo_icon'));

			add_action('admin_menu', array($this, 'support_menu'), 1100);
			add_action('admin_menu', [$this, 'submenu_link_new_tab']);
			add_action('plugins_loaded', array($this, 'jltwp_adminify_ajax_data_save'), 999);
			// jltwp_adminify()->add_filter('support_forum_url', [$this, 'jltwp_adminify_support_forum_url']);

			// Disable deactivation feedback form
			jltwp_adminify()->add_filter('show_deactivation_feedback_form', '__return_false');

			// Disable after deactivation subscription cancellation window
			jltwp_adminify()->add_filter('show_deactivation_subscription_cancellation', '__return_false');

			$this->disable_gutenberg_editor();

			add_filter('show_admin_bar', [ $this, 'jltwp_adminify_removeAdminBar'], PHP_INT_MAX);
			add_action('wp_head', [ $this,'jltwp_adminify_remove_header_for_baknd'], PHP_INT_MAX);

		}

		function jltwp_adminify_removeAdminBar($status)
		{
			if (!empty($_GET['bknd']) && sanitize_text_field(wp_unslash($_GET['bknd']))) {
				return false;
			}
			return $status;
		}

		function jltwp_adminify_remove_header_for_baknd() {
				if (isset($_GET['bknd']) && $_GET['bknd'] == '1') {
						echo '<script>
						jQuery(document).ready(function($){
							$("header").remove();
							$("footer").remove();
						})
					</script>';
				}
		}


		public function jltwp_adminify_ajax_data_save()
		{
			$ajax_data_save = new \WPAdminify\Inc\Classes\Notifications\What_We_Collect();
			$ajax_data_save->jltwp_adminify_collect_ajax_data();
		}

		public function disable_gutenberg_editor()
		{
			// Sidebar Widgets Remove
			if (!empty($this->options['remove_widgets']['disable_gutenberg_editor'])) {
				// Disable Gutenberg for Block Editor
				add_filter('gutenberg_use_widgets_block_editor', '__return_false');
				// Disable Gutenberg for widgets.
				add_filter('use_widgets_block_editor', '__return_false');
			}

			// Disable Block Editor Gutenberg
			if (!empty($this->options["disable_gutenberg"]['disable_for']) && in_array('block_editor', $this->options["disable_gutenberg"]['disable_for'])) {
				add_filter('use_block_editor_for_post', '__return_false');
				add_action('wp_enqueue_scripts', [$this, 'remove_backend_gutenberg_scripts'], 20);
			}

			// Remove all scripts and styles added by Gutenberg
			if (isset($this->options["disable_gutenberg"]['disable_gutenberg_enable']) && $this->options["disable_gutenberg"]['disable_gutenberg_enable'] && in_array('remove_gutenberg_scripts', $this->options["disable_gutenberg"]['disable_for'])) {
				add_action('wp_enqueue_scripts', [$this, 'remove_gutenberg_scripts']);
				remove_action('enqueue_block_assets', 'wp_enqueue_registered_block_scripts_and_styles');
			}
		}


		// Dequeue all Frontend scripts and styles added by Gutenberg
		public function remove_gutenberg_scripts()
		{
			wp_dequeue_style('wp-block-library');
			wp_dequeue_style('wc-block-style'); // Remove WooCommerce block CSS

			// Remove Inline CSS
			// wp_deregister_style('wp-block-library-inline');
			// wp_dequeue_style('wp-block-library-inline');
		}

		/**
		 * Remove Gutenberg Scripts
		 *
		 * @return void
		 */
		public function remove_backend_gutenberg_scripts()
		{
			if (is_admin()) {
				// Remove CSS on the front end.
				wp_dequeue_style('wp-block-library');

				// Remove Gutenberg theme.
				wp_dequeue_style('wp-block-library-theme');

				// Remove inline global CSS on the front end.
				wp_dequeue_style('global-styles');
			}
		}

		/**
		 * Adminify Logo
		 *
		 * @param [type] $logo
		 *
		 * @return void
		 */
		public function jltwp_adminify_logo_icon($logo)
		{
			$logo = WP_ADMINIFY_PATH . '/assets/images/adminify.svg';
			return $logo;
		}


		/**
		 * WP Adminify: Modules
		 */
		public function jltwp_adminify_modules_manager()
		{
			// new MenuStyle();
			new Modules();
			new AdminBar();
			new Tweaks();
			new OutputCSS();
			new ThirdPartyCompatibility();
			new AdminFooterText();
			new Sidebar_Widgets();
			new Remove_DashboardWidgets();

			if (!empty($this->options['admin_ui']) && preg_match('/https:\/\//', site_url()) && is_ssl()) {
				FrameInit::instance();
			}

			// Version Rollback
			// Adminify_Rollback::get_instance();
		}


		/**
		 * Remove Page Headlines: Dashboard, Plugins, Users
		 *
		 * @return void
		 */
		public function remove_page_headline()
		{
			$screen = get_current_screen();
			if (empty($screen->id)) {
				return;
			}

			if (in_array(
				$screen->id,
				[
					'dashboard',
					'nav-menus',
					'edit-tags',
					'themes',
					'widgets',
					'plugins',
					'plugin-install',
					'users',
					'user',
					'profile',
					'tools',
					'import',
					'export',
					'export-personal-data',
					'erase-personal-data',
					'options-general',
					'options-writing',
					'options-reading',
					'options-discussion',
					'options-media',
					'options-permalink',
				]
			)) {
				echo '<style>#wpbody-content .wrap > h1,#wpbody-content .wrap > h1.wp-heading-inline{display:none;}</style>';
			}
		}


		public function support_menu()
		{
			// $this->submenu_link_new_tab();
			// $adminify_ui = AdminSettings::get_instance()->get('admin_ui');
			$support_url = 'adminify-support';
			// if($adminify_ui ) {
				// $support_urlsss = \WPAdminify\Inc\Admin\AdminSettings::support_url();
			// }
			add_submenu_page(
				'wp-adminify-settings',       // Ex. wp-adminify-settings
				__('Get Support', 'adminify'),
				__('Support', 'adminify'),
				'manage_options',
				$support_url,
				function() {
					wp_safe_redirect(\WPAdminify\Inc\Admin\AdminSettings::support_url(), 301, 'adminify'); exit;
				}, // Redirect to external URL
				60
			);
		}


		public function submenu_link_new_tab()
		{
			add_action('admin_footer', function () {
?>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						// Replace 'your-parent-menu-slug' and 'your-submenu-slug' with actual menu slugs
						// $('a.toplevel_page_wp-adminify-settings a[href="admin.php?page=adminify-support"]').attr('target', '_blank');
						$('a[href="admin.php?page=adminify-support"]').attr('target', '_blank');
					});
				</script>
<?php
			});
		}

	}
}
