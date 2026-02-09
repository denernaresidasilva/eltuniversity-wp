<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Modules\DismissNotices\Dismiss_Admin_Notices;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

/**
 * Package: Dismiss Notice
 *
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class DismissNotice_Pro extends Dismiss_Admin_Notices
{

	public $options;
	private $other_notices;
	private $core_notices;

	public function __construct()
	{
		$this->options       = (array) AdminSettings::get_instance()->get();
		$this->other_notices = $this->options['other_notices'];

		if (!empty($this->other_notices) && in_array('php_nag', $this->options)) {
			add_action('wp_dashboard_setup', [$this, 'remove_php_update_notice']);
		}

		if (!empty($this->other_notices) && in_array('core_update_notice', $this->other_notices)) {
			$this->core_update_notice();
		}

		if (!empty($this->other_notices) && in_array('plugin_update_notice', $this->other_notices)) {
			add_filter('site_transient_update_plugins', [$this, 'plugin_update_notice_callback']);
		}

		if (!empty($this->other_notices) && in_array('theme_update_notice', $this->other_notices)) {
			add_filter('site_transient_update_themes', [$this, 'theme_update_notice_callback']);
		}

		// Disable Site Health checks
		if (!empty($this->other_notices) && in_array('site_health', $this->other_notices)) {
			add_filter('site_status_tests', [$this, 'disable_site_health_update_check']);
		}

		// Hide Admin Notices
		if (!empty($this->options['hide_notices'])) {
			// Priority of 0 to render before any notices.
			add_action('network_admin_notices', [$this, 'admin_notices_secondary_menu_wrap'], 0);
			add_action('user_admin_notices', [$this, 'admin_notices_secondary_menu_wrap'], 0);
			add_action('admin_notices', [$this, 'admin_notices_secondary_menu_wrap'], 0);

			// Priority of 999999 to render after all notices.
			add_action('all_admin_notices', [$this, 'finish_notice_capture'], 99999999);

			add_action('admin_bar_menu', [$this, 'admin_notices_secondary_menu']);
			add_action('admin_print_footer_scripts', [$this, 'admin_notices_secondary_menu_inline_css']);

			add_action('wp_ajax_adminify_log_notices', [$this, 'adminify_log_notices']);
			add_action('wp_ajax_adminify_hide_notice_forever', [$this, 'adminify_hide_notice_forever']);

			add_action('admin_enqueue_scripts', array($this, 'admin_notices_scripts'), 100);
		}
	}

	/**
	 * Hide Notices Script
	 *
	 * @return void
	 */
	public function admin_notices_scripts()
	{

		add_thickbox();

		wp_register_script('wp-adminify-hide-notices', WP_ADMINIFY_PRO_URL . '/js/hide-notices' . Utils::assets_ext('.js'), array('react', 'jquery', 'thickbox'), WP_ADMINIFY_VER, true);
		wp_enqueue_script('wp-adminify-hide-notices');


		wp_localize_script(
			'wp-adminify-hide-notices',
			'WP_ADMINIFY_HIDE_NOTICES',
			array(
				'title'              => esc_html__('Admin Notices', 'adminify'),
				'title_empty'        => esc_html__('No Admin Notices', 'adminify'),
				'date_time_preamble' => esc_html__('First logged: ', 'adminify'),
				'settings'           => [
					'success_level_notices'          => 'popup-only',
					'error_level_notices'            => 'popup-only',
					'warning_level_notices'          => 'popup-only',
					'information_level_notices'      => 'popup-only',
					'no_level_notices'               => 'popup-only',
					'wordpress_system_admin_notices' => 'leave',
					'popup_style'                    => 'slide-in',
					// 'slide_in_background'            => '#1d2327',
					'slide_in_background'            => !empty($this->options['admin_ui']) ? 'var(--adminify-preset-background)' : '#1d2327',
					'css_selector'                   => '',
					'visibility'                     => array('choice' => 'all'),
				],
				'ajaxurl'            => admin_url('admin-ajax.php'),
				'nonce'              => wp_create_nonce('adminify-hide-notices-ajax-nonce'),
			)
		);

		// Using Pointers.
		wp_enqueue_style('wp-pointer');
		wp_enqueue_script('wp-pointer');

		// Register our action.
		add_action('admin_print_footer_scripts', [$this, 'print_footer_scripts']);
	}



	public function print_footer_scripts()
	{

		$first_element_id  = 'wp-admin-bar-adminify_notification_count';
		$second_element_id = 'menu-settings';
?>
		<script>
			jQuery(
				function() {
					var {
						__
					} = wp.i18n;


					var second = jQuery('#<?php echo esc_attr($second_element_id); ?>').pointer({
						content: "<h3>" + __('Configure the Admin Notices Manager', 'adminify') + "</h3>" +
								"<p>" + __('Configure how the plugin handles different types of admin notices from the Settings > Admin Notices menu item.', 'adminify') + "</p>",
						position: {
							edge: 'left',
							align: 'center'
						},

						close: function() {
							jQuery.post(
								ajaxurl, {
									pointer: '<?php echo 'adminify-admin-settings-menu'; ?>',
									action: 'dismiss-wp-pointer',
								}
							);
						},

					});
					<?php
					if (self::is_dismissed('adminify-admin-notifications-menu') && !self::is_dismissed('adminify-admin-settings-menu')) {
					?>
						second.pointer('open');
					<?php
					}
					?>
				}
			);
		</script>
<?php
	}

	public static function is_dismissed(string $pointer): bool
	{

		$dismissed = array_filter(explode(',', (string) get_user_meta(get_current_user_id(), 'dismissed_wp_pointers', true)));

		return in_array($pointer, (array) $dismissed, true);
	}

	/**
	 * Handles AJAX requests for hiding a notice forever.
	 *
	 * @return false|void
	 *
	 */
	public static function adminify_hide_notice_forever()
	{
		// If we have a nonce posted, check it.
		if (wp_doing_ajax() && isset($_POST['_nonce'])) {
			$nonce_check = wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'adminify-hide-notices-ajax-nonce');
			if (!$nonce_check) {
				return false;
			}
		}

		if (isset($_POST['dismiss_notice_hash'])) {
			$currently_held_options = get_option('adminify-hidden-notices', array());
			array_push($currently_held_options, $_POST['dismiss_notice_hash']);
			update_option('adminify-hidden-notices', $currently_held_options);
			wp_send_json_success();
		}
	}

	/**
	 * Handles AJAX requests for logging the notices.
	 *
	 * @return false|void
	 */
	public function adminify_log_notices()
	{

		// Check nonces
		if (wp_doing_ajax() && isset($_POST['_nonce'])) {
			$nonce_check = wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_nonce'])), 'adminify-hide-notices-ajax-nonce');
			if (!$nonce_check) {
				return false;
			}
		}

		if (isset($_POST['notices']) && (!empty($_POST['notices'] && is_array($_POST['notices'])))) {
			$currently_held_options = get_option('adminify-notices', array());
			$hidden_forever         = get_option('adminify-hidden-notices', array());
			$hashed_notices         = array();
			$details                = array();
			$format                 = get_option('date_format') . ' ' . get_option('time_format');

			foreach ($_POST['notices'] as $index => $notice) {
				$hash = wp_hash($notice);

				$current_time            = current_time('timestamp');
				$hashed_notices[$hash] = $current_time;
				$details[$index]       = array($hash, date_i18n($format,
					$current_time
				));

				// Do we already know about this notice?
				if (isset($currently_held_options[$hash])) {
					$hashed_notices[$hash] = $currently_held_options[$hash];
					$details[$index]       = array($hash, date_i18n($format, $currently_held_options[$hash]));
				}

				if (in_array($hash, $hidden_forever, true)) {
					$details[$index] = 'do-not-display';
				}
			}

			update_option('adminify-notices', $hashed_notices);
			wp_send_json_success($details);
		}
	}

	/**
	 * Wrap Hide Notices
	 *
	 * @return void
	 */
	public function admin_notices_secondary_menu_wrap()
	{
		$capability = 'manage_options'; //for admin user
		// $capability = 'read';	// for non admin users

		if (current_user_can($capability)) {
			echo sprintf(__('<div class="adminify-notices-wrapper">', 'adminify'),  __('Admin Notices', 'adminify'));

		}else{
			echo '<div class="non-admin-notices-wrapper">';
		}
		?>
		<style>
			#wpbody-content > .notice {
				display: none;
			}
			.adminify-notices-wrapper > *:not(.hide-notice--ignored) {
				display: none !important;
			}
		</style>
		<?php
	}

	/**
	 * Prints the beginning of wrapper element after all notices.
	 */
	public static function finish_notice_capture()
	{
		echo '</div><!-- /.adminify-notices-wrapper -->';
	}

	/**
	 * Admin bar menu item for the hidden admin notices
	 *
	 * @link https://developer.wordpress.org/reference/classes/wp_admin_bar/add_menu/
	 * @link https://developer.wordpress.org/reference/classes/wp_admin_bar/add_node/
	 */
	public function admin_notices_secondary_menu($admin_bar)
	{
		// Show the Notices menu in wp-admin, except when in the Customizer preview
		if (is_admin() && !is_customize_preview()) {

			$capability = 'manage_options';
			if (!empty($this->options['hide_notices_non_admin']) && jltwp_adminify()->can_use_premium_code__premium_only()) {
				$capability = 'read';
			}

			if (current_user_can($capability)) {
				$admin_bar->add_menu(array(
					'id'     => 'adminify_notification_count',
					'title'  => __('', 'adminify'),
					'parent' => 'top-secondary',
					'href'   => '#'
				));
			}
		}
	}


	/**
	 * Inline CSS to hide notices on page load in wp-admin pages.
	 *
	 */
	public function admin_notices_secondary_menu_inline_css()
	{
		// Check Capabilities
		$capability = 'manage_options';
		if ( !empty($this->options['hide_notices_non_admin']) && jltwp_adminify()->can_use_premium_code__premium_only() ) {
			$capability = 'read';
		}

		if (is_admin() && !is_customize_preview() && current_user_can($capability)) {
			$admin_notices_css = '#adminify-container-slide-in{box-shadow:-10px 0px 40px -20px #252525;} #wp-admin-bar-adminify_notification_count>a{cursor:not-allowed}
				#wp-admin-bar-adminify_notification_count.has-data>a{cursor:pointer}
				.adminify-notification-counter{background-color:#ca4a1f;border-radius:50% !important;-webkit-box-sizing:border-box !important;box-sizing:border-box !important;font-size:11px !important;height:18px !important;line-height:1.6 !important;min-width:18px;text-align:center;vertical-align:text-bottom;display:inline;padding:4px 7px 4px 6px !important;color:#fff;margin-left:7px !important;opacity:0;-webkit-transition:all .2s ease-in-out !important;transition:all .2s ease-in-out !important}
				.adminify-notification-counter>span{line-height:100% !important}
				.adminify-notification-counter.display{opacity:1;-webkit-transition:all .2s ease-in-out !important;transition:all .2s ease-in-out !important}
				.adminify-pointer .wp-pointer-arrow{left:50%;margin-left:-7px}
				#adminify-container-slide-in{padding-top:34px;height:100vh;position:fixed;background:#222;right:-782px;z-index:1000;top:0;-webkit-transition:all .3s ease-in-out;transition:all .3s ease-in-out;width:750px}
				#adminify-container-slide-in.show{right:0;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}
				#adminify-container-slide-in .notice,#adminify-container-slide-in div.error,#adminify-container-slide-in div.updated{padding:1px 12px !important}
				.adminify-notice-timestap{border-top:1px solid #eee;padding:9px 0 10px;font-size:11px;margin-top:14px;display:block}
				.adminify-notice-timestap span{font-style:italic}
				.adminify-notice-timestap a{float:right}
				#adminify-slide-in-content{max-height:calc(100vh - 100px);overflow-y:auto;direction:ltr;scrollbar-color:#333;scrollbar-width:thin}
				#adminify-slide-in-content::-webkit-scrollbar{width:20px}
				#adminify-slide-in-content::-webkit-scrollbar-track{background-color:#222;border-radius:100px}
				#adminify-slide-in-content::-webkit-scrollbar-thumb{border-radius:100px;border:0;border-left:0;border-right:0;background-color:#333}
				#adminify-system-notices .notice,#adminify-error-notices .notice,#adminify-warning-notices .notice,#adminify-success-notices .notice,#adminify-information-notices .notice{margin-bottom:15px}
				#adminify-system-notices .notice p,#adminify-error-notices .notice p,#adminify-warning-notices .notice p,#adminify-success-notices .notice p,#adminify-information-notices .notice p{width:calc(100% - 38px);font-size:13px;line-height:22px}
				#adminify-system-notices .notice.is-dismissible,#adminify-error-notices .notice.is-dismissible,#adminify-warning-notices .notice.is-dismissible,#adminify-success-notices .notice.is-dismissible,#adminify-information-notices .notice.is-dismissible{padding-right:12px !important}
				#adminify-system-notices .notice-addon-available,#adminify-error-notices .notice-addon-available,#adminify-warning-notices .notice-addon-available,#adminify-success-notices .notice-addon-available,#adminify-information-notices .notice-addon-available{display:block}
				#adminify-notice-purged-text{opacity:0;color:green;display:inline-block;font-size:12px;padding:7px 12px;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}
				#adminify-notice-purged-text.visible{opacity:1;-webkit-transition:all .2s ease-in-out;transition:all .2s ease-in-out}
				.settings_page_admin_notices_settings #setting-error-settings_updated+#setting-error-settings_updated{display:none}
				@media all and (max-width:540px){#adminify-container-slide-in{right:-330px;width:330px}
			}';
			printf('<style id="adminify-hide-admin-notices">%s</style>', wp_strip_all_tags($admin_notices_css));
		}
	}



	/**
	 * Remove "PHP Update Required" Notice
	 */
	public function remove_php_update_notice()
	{
		remove_meta_box('dashboard_php_nag', 'dashboard', 'normal');
	}



	// WordPress Core Update Notice
	public function core_update_notice()
	{
		add_filter('update_footer', '__return_false', 20);
		add_filter('site_transient_update_core', [$this, 'core_update_notice_callback']);

		// TODO
		// Remove nags
		remove_action('admin_notices', 'update_nag', 3);
		remove_action('admin_notices', 'maintenance_nag');

		// Disable WP version check
		remove_action('wp_version_check', 'wp_version_check');
		remove_action('admin_init', 'wp_version_check');
		wp_clear_scheduled_hook('wp_version_check');

		add_filter('pre_option_update_core', '__return_null');

		// Disable theme version checks
		remove_action('wp_update_themes', 'wp_update_themes');
		remove_action('admin_init', '_maybe_update_themes');
		wp_clear_scheduled_hook('wp_update_themes');

		remove_action('load-themes.php', 'wp_update_themes');
		remove_action('load-update.php', 'wp_update_themes');
		remove_action('load-update-core.php', 'wp_update_themes');

		// Disable plugin version checks
		remove_action('wp_update_plugins', 'wp_update_plugins');
		remove_action('admin_init', '_maybe_update_plugins');
		wp_clear_scheduled_hook('wp_update_plugins');

		remove_action('load-plugins.php', 'wp_update_plugins');
		remove_action('load-update.php', 'wp_update_plugins');
		remove_action('load-update-core.php', 'wp_update_plugins');

		// Disable auto updates
		wp_clear_scheduled_hook('wp_maybe_auto_update');

		remove_action('wp_maybe_auto_update', 'wp_maybe_auto_update');
		remove_action('admin_init', 'wp_maybe_auto_update');
		remove_action('admin_init', 'wp_auto_update_core');



		// Disable core update
		add_filter('pre_transient_update_core', [$this, 'override_version_info']);
		add_filter('pre_site_transient_update_core', [$this, 'override_version_info']);

		// Disable theme updates
		add_filter('pre_transient_update_themes', [$this, 'override_version_info']);
		add_filter('pre_site_transient_update_themes', [$this, 'override_version_info']);
		add_action('pre_set_site_transient_update_themes', [$this, 'override_version_info'], 20);

		// Disable plugin updates
		add_filter('pre_transient_update_plugins', [$this, 'override_version_info']);
		add_filter('pre_site_transient_update_plugins', [$this, 'override_version_info']);
		add_action('pre_set_site_transient_update_plugins', [$this, 'override_version_info'], 20);

		// Disable auto updates
		add_filter('automatic_updater_disabled', '__return_true');
		if (!defined('AUTOMATIC_UPDATER_DISABLED')) {
			define('AUTOMATIC_UPDATER_DISABLED', true);
		}
		if (!defined('WP_AUTO_UPDATE_CORE')) {
			define('WP_AUTO_UPDATE_CORE', false);
		}

		add_filter('auto_update_core', '__return_false');
		add_filter('wp_auto_update_core', '__return_false');
		add_filter('allow_minor_auto_core_updates', '__return_false');
		add_filter('allow_major_auto_core_updates', '__return_false');
		add_filter('allow_dev_auto_core_updates', '__return_false');

		add_filter('auto_update_plugin', '__return_false');
		add_filter('auto_update_theme', '__return_false');
		add_filter('auto_update_translation', '__return_false');

		remove_action('init', 'wp_schedule_update_checks');

		// Disable update emails
		add_filter('auto_core_update_send_email', '__return_false');
		add_filter('send_core_update_notification_email', '__return_false');
		add_filter('automatic_updates_send_debug_email', '__return_false');

		// Remove Dashboard >> Updates menu
		add_action('admin_menu', [$this, 'remove_updates_menu']);
	}

	public function remove_updates_menu()
	{
		global $submenu;
		remove_submenu_page('index.php', 'update-core.php');
	}

	public function override_version_info()
	{

		include(ABSPATH . WPINC . '/version.php'); // get $wp_version from here

		$current = (object)array(); // create empty object
		$current->updates = array();
		$current->response = array();
		$current->version_checked = $wp_version;
		$current->last_checked = time();

		return $current;
	}

	/**
	 * Disable Background Updates and Auto-Updates tests in Site Health tests
	 *
	 * @since 4.0.0
	 */
	public function disable_site_health_update_check($tests)
	{
		unset($tests['async']['background_updates']);
		unset($tests['direct']['plugin_theme_auto_updates']);
		return $tests;
	}


	// WordPress Core Update Notice Callback
	public function core_update_notice_callback($site_transient_update_core)
	{
		if (!empty($site_transient_update_core) && !empty($site_transient_update_core->updates[0]) && !empty($site_transient_update_core->updates[0]->response)) {
			$site_transient_update_core->updates[0]->response = 'latest';
		}

		return $site_transient_update_core;
	}

	// Plugin Update Notice Callback
	public function plugin_update_notice_callback($site_transient_update_plugins)
	{
		if (!empty($site_transient_update_plugins->response)) {
			unset($site_transient_update_plugins->response);
		}
		return $site_transient_update_plugins;
	}

	// Theme Update Notice Callback
	public function theme_update_notice_callback($site_transient_update_themes)
	{
		if (!empty($site_transient_update_themes->response)) {
			unset($site_transient_update_themes->response);
		}
		return $site_transient_update_themes;
	}
}
