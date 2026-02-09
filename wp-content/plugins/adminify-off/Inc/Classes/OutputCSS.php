<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Classes\OutputCSS_Body;


// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class OutputCSS
{
	public $options;

	public function __construct()
	{
		$this->options = (array) AdminSettings::get_instance()->get();

		new OutputCSS_Body();

		if (is_multisite() && is_network_admin()) {
			return; // only display to network admin if multisite is enbaled
		}

		add_action('admin_enqueue_scripts', [$this, 'jltwp_adminify_output_styles'], 100);
		add_action('admin_footer', [$this, 'jltwp_adminify_output_scripts'], 100);
		add_filter('admin_body_class', [$this, 'add_body_classes']);
	}

	public function add_body_classes($classes)
	{
		$folded				= $this->options['menu_layout_settings']['menu_mode'] === 'icon_menu' ? 'folded' : '';
		$color_mode         = !empty($this->options['light_dark_mode']['admin_ui_mode']) ? $this->options['light_dark_mode']['admin_ui_mode'] : 'light';
		$color_preset       = !empty($this->options['adminify_theme']) ? $this->options['adminify_theme'] : 'preset1';
		// $icon_style         = !empty($this->options['menu_layout_settings']['icon_style']) ? $this->options['menu_layout_settings']['icon_style'] : 'classic';
		// $menu_hover_submenu = !empty($this->options['menu_layout_settings']['menu_hover_submenu']) ? $this->options['menu_layout_settings']['menu_hover_submenu'] : 'classic';
		// $menu_mode          = !empty($this->options['menu_layout_settings']['menu_mode']) ? $this->options['menu_layout_settings']['menu_mode'] : 'classic';

		$bodyclass = '';
		if($folded) {
			$bodyclass .= ' ' . $folded;
		}

		if ($color_mode == 'light') {
			$bodyclass .= ' adminify-light-mode ';
		}
		if ($color_mode == 'dark') {
			$bodyclass .= ' adminify-dark-mode ';
		}

		if ($color_preset != 'preset1') {
			$bodyclass .= ' color-preset-adminify-icon-white';
		}
		

		// // Submenu Hover Style
		// if ($menu_hover_submenu == 'classic') {
		// 	$bodyclass .= ' adminify-default-v-menu ';
		// } elseif ($menu_hover_submenu == 'accordion') {
		// 	$bodyclass .= ' adminify-accordion-v-menu ';
		// } elseif ($menu_hover_submenu == 'toggle') {
		// 	$bodyclass .= ' adminify-toggle-v-menu ';
		// }

		// // Active Menu Style
		// if (($menu_mode == 'rounded') || (($menu_mode == 'icon_menu') && ($icon_style == 'rounded'))) {
		// 	$bodyclass .= ' adminify-rounded-v-menu ';
		// 	$bodyclass .= ' adminify-round-open-menu ';
		// }

		return $classes . $bodyclass;
	}

	public function jltwp_adminify_output_styles()
	{
		// $jltwp_adminify_output_css = '';
		// $jltwp_adminify_output_css.= 'body.wp-adminify{';

		// $jltwp_adminify_output_css.= '}';

		// // Combine the values from above and minifiy them.
		// $jltwp_adminify_output_css = preg_replace('#/\*.*?\*/#s', '', $jltwp_adminify_output_css);
		// $jltwp_adminify_output_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $jltwp_adminify_output_css);
		// $jltwp_adminify_output_css = preg_replace('/\s\s+(.*)/', '$1', $jltwp_adminify_output_css);

		// $adminify_ui = AdminSettings::get_instance()->get('admin_ui');

		// if (!empty($adminify_ui)) {
		// 	wp_add_inline_style('wp-adminify-admin', wp_strip_all_tags($jltwp_adminify_output_css));
		// } else {
		// 	wp_add_inline_style('wp-adminify-default-ui', wp_strip_all_tags($jltwp_adminify_output_css));
		// }

		// Custom CSS
		if (!empty($this->options['devtools_tabs']['custom_css'])) {
			echo "\n<!-- Start of WP Adminify - Admin Area Custom CSS -->\n";
			echo "<style>\n";
			echo wp_strip_all_tags($this->options['devtools_tabs']['custom_css']);
			echo "\n</style>";
			echo "\n<!-- /End of WP Adminify - Admin Area Custom CSS -->\n";
		}
	}

	public function jltwp_adminify_output_scripts()
	{
		// Custom JS
		if (!empty($this->options['devtools_tabs']['custom_js'])) {
			echo "\n<!-- Start of WP Adminify - Admin Area Custom JS -->\n";
			echo "<script>\n";
			echo wp_strip_all_tags($this->options['devtools_tabs']['custom_js']);
			echo "\n</script>";
			echo "\n<!-- /End of WP Adminify - Admin Area Custom JS -->\n";
		}
	}
}
