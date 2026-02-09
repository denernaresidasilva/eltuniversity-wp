<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;


// no direct access allowed
if (! defined('ABSPATH')) {
	exit;
}

/**
 * WPAdminify
 *
 * @package Admin Pages
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

class AdminPages_MetaBoxes extends AdminPagesModel
{

	public function __construct()
	{
		// this should be first so the default values get stored
		$this->admin_pages_metaboxes();
		parent::__construct((array) get_option($this->prefix));

		add_filter('submenu_file', [$this, 'set_submenu'], 100, 2);

		add_filter('manage_adminify_admin_page_posts_columns', [$this, 'set_columns']);
		add_action('manage_adminify_admin_page_posts_custom_column', [$this, 'adminify_column_content'], 10, 2);

		// Fix Gutenberg Icon Picker Issue
		add_action('admin_footer', [$this, 'fix_gutenberg_icon_picker'], 9999);
	}

	function is_block_editor()
	{
		$current_screen = \get_current_screen();
		return method_exists($current_screen, 'is_block_editor') && $current_screen->is_block_editor();
	}

	/**
	 * Fix Gutenberg Icon Picker Issue
	 */
	public function fix_gutenberg_icon_picker()
	{
		if (!$this->is_block_editor()) {
			return;
		}
?>

		<script>
			(function($) {
				$(document).ready(function() {
					$('#wpfooter #adminify-modal-icon').appendTo(document.body);
				});
			})(jQuery);
		</script>

<?php

	}


	public function get_defaults()
	{
		return [
			'_wp_adminify_menu_type'     => 'top_level',
			'_wp_adminify_sub_menu_item' => '',
			'_wp_adminify_menu_order'    => 10,
			'_wp_adminify_menu_icon'     => 'dashicons dashicons-admin-site-alt',
			'_wp_adminify_page_title'    => '',
			'_wp_adminify_remove_margin' => '',
			'_wp_adminify_remove_notice' => '',
			'_wp_adminify_user_roles'    => 'administrator',
			'_wp_adminify_script_type'   => 'css',
			'_wp_adminify_custom_js'     => '',
			'_wp_adminify_custom_css'    => '',
		];
	}



	// Set List Table Admin Pages Columns
	public function set_columns($columns)
	{
		$columns = [
			'cb'          => '<input type="checkbox" />',
			'title'       => esc_html__('Page Name', 'adminify'),
			'icon'        => esc_html__('Menu Icon', 'adminify'),
			'parent_menu' => esc_html__('Parent Menu', 'adminify'),
			'roles'       => esc_html__('User Roles', 'adminify'),
		];

		return $columns;
	}

	// User Roles Column Content
	public function user_roles_column_content($allowed_roles, $post_id)
	{
		$user_roles = Utils::post_meta('_wp_adminify_user_roles');
		$roles      = is_serialized($user_roles) ? unserialize($user_roles) : $user_roles;
		$roles      = empty($roles) ? ['all'] : $roles;
		$roles      = implode(', ', $roles);

		return $roles;
	}


	// List Table Admin Pages Column Contents
	public function adminify_column_content($column, $post_id)
	{
		$admin_menu_type = Utils::post_meta('_wp_adminify_menu_type');

		switch ($column) {
			case 'parent_menu':
				$parent_menu = esc_html__('None', 'adminify');
				if ($admin_menu_type === 'sub_level') {
					$parent_slug = Utils::post_meta('_wp_adminify_sub_menu_item');

					foreach ($GLOBALS['menu'] as $menu) {
						if ($menu[2] === $parent_slug) {
							$parent_menu = $menu[0];
							break;
						}
					}
				}
				echo esc_html($parent_menu);
				break;

			case 'roles':
				$allowed_roles = esc_html__('All', 'adminify');
				$allowed_roles = apply_filters('adminify_admin_page_user_roles', $this->user_roles_column_content($allowed_roles, $post_id));
				echo esc_html(ucwords($allowed_roles));
				break;

			case 'icon':
				$menu_icon = Utils::post_meta('_wp_adminify_menu_icon');

				$icon_class = $menu_icon ? $menu_icon : 'dashicons dashicons-no is-empty';

				echo ('sub_level' === $admin_menu_type ? esc_html__('None', 'adminify') : '<i class="' . esc_attr($icon_class) . '"></i>');
				break;
		}
	}

	public function set_submenu($submenu_file, $parent_file)
	{
		global $current_screen, $parent_file;
		if (in_array($current_screen->base, ['post', 'edit']) && 'adminify_admin_page' === $current_screen->post_type) {
			$submenu_file = 'edit.php?post_type=adminify_admin_page';
		}
		return $submenu_file;
	}


	public static function get_wp_admin_menus()
	{
		global $menu;

		$options = [];

		if (! empty($menu) && is_array($menu)) {
			foreach ($menu as $item) {
				if (! empty($item[0])) {
					// the preg_replace removes "Comments" & "Plugins" menu spans.
					$options[$item[2]] = (preg_replace('/\<span.*?>.*?\<\/span><\/span>/s', '', $item[0]));
				}
			}
		}

		return $options;
	}

	public static function get_wp_admin_submenus()
	{
		global $submenu;

		$options = [];

		if (! empty($submenu) && is_array($submenu)) {
			foreach ($submenu as $items) {
				foreach ($items as $item) {
					if (! empty($item[0])) {
						$options[$item[1]] = (preg_replace('/\<span.*?>.*?\<\/span><\/span>/s', '', $item[0]));
					}
				}
			}
		}

		return $options;
	}


	public function get_menu_type_fiels(&$menu_type_fields)
	{
		$menu_type_fields[] = [
			'id'      => $this->prefix . 'menu_type',
			'type'    => 'select',
			'title'   => esc_html__('Menu Type', 'adminify'),
			'options' => [
				'top_level' => esc_html__('Top Level Menu', 'adminify'),
				'sub_level' => esc_html__('Sub Menu', 'adminify'),
			],
			'default' => $this->get_default_field('_wp_adminify_menu_type'),
		];

		$menu_type_fields[] = [
			'id'          => $this->prefix . 'sub_menu_item',
			'type'        => 'select',
			'title'       => esc_html__('Select Sub Menu', 'adminify'),
			'placeholder' => esc_html__('Select a menu', 'adminify'),
			'options'     => 'WPAdminify\Pro\AdminPages_MetaBoxes::get_wp_admin_menus',
			'dependency'  => [$this->prefix . 'menu_type', '==', 'sub_level', 'true'],
			'default'     => $this->get_default_field('_wp_adminify_sub_menu_item'),
		];

		$menu_type_fields[] = [
			'id'      => $this->prefix . 'menu_order',
			'type'    => 'text',
			'title'   => esc_html__('Menu Order', 'adminify'),
			'default' => $this->get_default_field('_wp_adminify_menu_order'),
		];

		$menu_type_fields[] = [
			'id'      => $this->prefix . 'menu_icon',
			'type'    => 'icon',
			'title'   => esc_html__('Icon', 'adminify'),
			'default' => $this->get_default_field('_wp_adminify_menu_icon'),
		];
	}


	public function admin_pages_metaboxes()
	{
		if (! class_exists('ADMINIFY')) {
			return;
		}

		// Admin Pages Metabox
		\ADMINIFY::createMetabox(
			$this->prefix,
			[
				'title'     => esc_html__('Menu Attributes', 'adminify'),
				'post_type' => 'adminify_admin_page',
				'context'   => 'side',
				'priority'  => 'low',
				'data_type' => 'unserialize',
			]
		);

		$menu_type_fields = [];
		$this->get_menu_type_fiels($menu_type_fields);

		// Activate
		\ADMINIFY::createSection(
			$this->prefix,
			[
				'fields' => $menu_type_fields,
				'class'  => 'adminify-pt-0 adminify-pl-0',
			]
		);

		// Display Options
		\ADMINIFY::createMetabox(
			$this->prefix . 'admin_page_display',
			[
				'title'     => esc_html__('Display Options', 'adminify'),
				'post_type' => 'adminify_admin_page',
				'context'   => 'normal',
				'priority'  => 'high',
				'data_type' => 'unserialize',
			]
		);

		$remove_notice = [];
		$this->get_remove_notice_fields($remove_notice);
		\ADMINIFY::createSection(
			$this->prefix . 'admin_page_display',
			[
				'title'  => esc_html__('Enable/Disable', 'adminify'),
				'fields' => $remove_notice,
			]
		);

		// User Roles
		\ADMINIFY::createSection(
			$this->prefix . 'admin_page_display',
			[
				'title'  => 'User Roles Access',
				'fields' => [
					[
						'id'          => $this->prefix . 'user_roles',
						'type'        => 'select',
						'title'       => esc_html__('Allow users to access this page', 'adminify'),
						'placeholder' => esc_html__('Select a role', 'adminify'),
						'chosen'      => true,
						'multiple'    => true,
						'options'     => 'roles',
						'default'     => $this->get_default_field('_wp_adminify_user_roles'),
					],

				],
			]
		);

		$custom_css_js = [];
		$this->get_custom_css_js($custom_css_js);

		// Advanced
		\ADMINIFY::createSection(
			$this->prefix . 'admin_page_display',
			[
				'title'  => esc_html__('Custom CSS/JS', 'adminify'),
				'fields' => $custom_css_js,
			]
		);
	}


	public function get_remove_notice_fields(&$remove_notice)
	{
		$remove_notice[] = [
			'id'      => $this->prefix . 'page_title',
			'type'    => 'checkbox',
			'title'   => esc_html__('Remove Page Title', 'adminify'),
			'label'   => esc_html__('Remove Page Title from your created Custom Admin Page.', 'adminify'),
			'default' => $this->get_default_field('_wp_adminify_page_title'),
		];
		$remove_notice[] = [
			'id'      => $this->prefix . 'remove_margin',
			'type'    => 'checkbox',
			'title'   => esc_html__('Remove Page Margin', 'adminify'),
			'label'   => esc_html__('Remove default Page Margin from Custom Admin Page', 'adminify'),
			'default' => $this->get_default_field('_wp_adminify_remove_margin'),
		];
		$remove_notice[] = [
			'id'      => $this->prefix . 'remove_notice',
			'type'    => 'checkbox',
			'title'   => esc_html__('Remove Admin Notices', 'adminify'),
			'label'   => esc_html__('Remove Admin Notices from Custom Admin page', 'adminify'),
			'default' => $this->get_default_field('_wp_adminify_remove_notice'),
		];
	}

	/**
	 * Custom CSS/JS Options
	 *
	 * @param [type] $custom_css_js
	 *
	 * @return void
	 */
	public function get_custom_css_js(&$custom_css_js)
	{
		$custom_css_js[] = [
			'id'      => $this->prefix . 'script_type',
			'type'    => 'button_set',
			'title'   => esc_html__('Snippet Type', 'adminify'),
			'options' => [
				'css' => esc_html__('CSS', 'adminify'),
				'js'  => esc_html__('JS', 'adminify'),
			],
			'default' => $this->get_default_field('_wp_adminify_script_type'),
		];

		$custom_css_js[] = [
			'id'         => $this->prefix . 'custom_js',
			'type'       => 'code_editor',
			'title'      => esc_html__('Custom JS', 'adminify'),
			'subtitle'   => esc_html__('Add your Custom Script here', 'adminify'),
			'settings'   => [
				'theme' => 'dracula',
				'mode'  => 'javascript',
			],
			'dependency' => [$this->prefix . 'script_type', '==', 'js'],
			'default'    => $this->get_default_field('_wp_adminify_custom_js'),
		];

		$custom_css_js[] = [
			'id'         => $this->prefix . 'custom_css',
			'type'       => 'code_editor',
			'title'      => esc_html__('Custom CSS', 'adminify'),
			'subtitle'   => esc_html__('Add your Custom CSS here', 'adminify'),
			'settings'   => [
				'theme' => 'mbo',
				'mode'  => 'css',
			],
			'dependency' => [$this->prefix . 'script_type', '==', 'css'],
			'default'    => $this->get_default_field('_wp_adminify_custom_css'),
		];
	}
}
