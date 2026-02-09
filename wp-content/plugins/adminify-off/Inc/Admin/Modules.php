<?php

namespace WPAdminify\Inc\Admin;

use WPAdminify\Inc\Utils;
use \WPAdminify\Inc\Admin\AdminSettings;
use \WPAdminify\Inc\Admin\AdminSettingsModel;
use \WPAdminify\Inc\Modules\MenuEditor\MenuEditor;
use \WPAdminify\Inc\Modules\MenuDuplicator\MenuDuplicator;
use \WPAdminify\Inc\Modules\PostDuplicator\PostDuplicator;
use \WPAdminify\Inc\Modules\PostTypesOrder\PostTypesOrder;
use \WPAdminify\Inc\Modules\Folders\Folders;
use \WPAdminify\Inc\Modules\DisableComments\DisableComments;
use \WPAdminify\Inc\Modules\ServerInformation\ServerInformation;
use \WPAdminify\Inc\Modules\DismissNotices\Dismiss_Admin_Notices;
use \WPAdminify\Inc\Modules\DashboardWidget\DashboardWidget;
use WPAdminify\Inc\Admin\Options\RollbackVersion;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}
/**
 * WP Adminify
 *
 * @package Modules
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class Modules extends AdminSettingsModel
{

	public function __construct()
	{
		$this->modules_init();
	}


	/**
	 * Include Moduels
	 *
	 * @return void
	 */
	public function modules_init()
	{
		$this->options = AdminSettings::get_instance()->get();

		new Dismiss_Admin_Notices();

		if (!empty($this->options['folders']['enable_folders'])) {
			new Folders();
		}

		if (!empty($this->options['post_duplicator']['enable_post_duplicator'])) {
			new PostDuplicator();
		}

		if (!empty($this->options['menu_duplicator'])) {
			new MenuDuplicator();
		}

		if (!empty($this->options['post_types_order']['enable_pto'])) {
			new PostTypesOrder();
		}

		if (!empty($this->options['disable_comments']['enable_disable_comments'] ) ) {
			new DisableComments();
		}

		if (!empty($this->options['dashboard_widgets'])) {
			new DashboardWidget();
		}

		// new ServerInformation();

		MenuEditor::get_instance();

		// TO DO: Turned Off for future release, after making Network Options stable
		// if (Utils::check_modules($this->options['server_info'])) {
		// new RollbackVersion();
		// }
	}
}
