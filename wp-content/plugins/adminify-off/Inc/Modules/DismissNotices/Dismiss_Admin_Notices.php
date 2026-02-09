<?php

namespace WPAdminify\Inc\Modules\DismissNotices;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;


// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Adminify
 * Module: Dismiss Admin Notices
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

if ( ! class_exists( 'Dismiss_Admin_Notices' ) ) {
	class Dismiss_Admin_Notices extends AdminSettingsModel {
		public function __construct() {
			$this->options       = (array) AdminSettings::get_instance()->get();

			// Remove Gutenberg Panel
			if (!empty($this->options['other_notices']) && in_array('welcome_panel', $this->options['other_notices'])) {
				add_action('wp_dashboard_setup', [$this, 'remove_welcome_panel'], 999);
			}
		}

		//Remove Welcome Panel
		public function remove_welcome_panel()
		{
			remove_action('welcome_panel', 'wp_welcome_panel');
		}
	}
}
