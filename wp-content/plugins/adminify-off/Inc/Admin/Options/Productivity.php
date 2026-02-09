<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Classes\Helper;
use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\Options\Productivity\Duplicate_Post;
use WPAdminify\Inc\Admin\Options\Productivity\PostTypesOrder;
use WPAdminify\Inc\Admin\AdminSettingsModel;


if (!defined('ABSPATH')) {
    die;
} // Cannot access directly.

if (!class_exists('Productivity')) {
    class Productivity extends AdminSettingsModel
    {
        public function __construct()
        {
            $this->productivity_settings();
        }

        protected function get_defaults()
        {
            return [
				// Admin Notices
				'hide_notices'           => false,
				'hide_notices_non_admin' => false,
				'other_notices'          => [],
				'screen_help_tab'        => [
					'enable_for_screen' => false,
					'screen_help_data'  => []
				],

				'media_attachments'      => [
					'enable_media'         => false,
					// 'media_ininite_scroll' => false,
					'thumbnails_rss_feed'  => false,
					// 'allowed_upload_files' => [],
					// 'convert_to_webp'      => false,
					// 'featured_to_post'     => false,
					// 'media_lowercase'      => false,
				],
				'folders'        => [
					'enable_folders' => false,
					'enable_for'     => [ 'post', 'page' ],
					'media'          => false,
				],
				'admin_pages'      => false,
				'dashboard_widgets' => false,
				'post_types_order' => [
					'enable_pto'       => false,
					'pto_taxonomies' => [],
					'pto_posts'      => [ 'page' ],
					'pto_media'      => false,
				],
				'menu_duplicator'        => false,
				'post_duplicator'        => [
					'enable_post_duplicator' => false,
					'post_types'      => ['page'],
					'taxonomies' => [],
				],
				// Post Columns
				'custom_admin_columns'	=> [
					'enable'                       => false,
					'post_page_column_thumb_image' => [],
					'columns_data'                 => [],
					'slug_column_post_types'       => [],
				],

				// Sidebar Widgets
				'remove_widgets' => [
					'remove_widgets_type'      => 'sidebar',
					'disable_gutenberg_editor' => false,
					'sidebar_widgets_list'     => [],
					'dashboard_widgets_list'   => [],
					// 'sidebar_widgets_user_roles'               => [],
				],
            ];
        }


		/**
		 * Admin Notices: Settings
		 */
		public function admin_notices_settings(&$fields)
		{
			$fields[] = [
				'id'      => 'productivity_sub_heading',
				'type'    => 'subheading',
				'content' => Utils::adminfiy_help_urls(
					'<span></span>',
					'https://wpadminify.com/docs/adminify/productivity/hide-admin-notices',
					'https://www.youtube.com/watch?v=49Cd3dYzYHs',
					'https://www.facebook.com/groups/jeweltheme',
					\WPAdminify\Inc\Admin\AdminSettings::support_url()
				),
			];

			$fields[] = [
				'id'         => 'hide_notices',
				'type'       => 'switcher',
				'title'      => sprintf(__('Hide "Admin Notices"? %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Hide Admin Notices to make your Dashboard Clean. ', 'adminify'),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice',
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('hide_notices'),
			];
			$fields[] = [
				'id'         => 'hide_notices_non_admin',
				'type'       => 'checkbox',
				'label'      => sprintf(__('Also, Hide for Non-Admin Users? %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'title'		=> ' ',
				'default'    => $this->get_default_field('hide_notices_non_admin'),
				'dependency' => ['hide_notices', '==', 'true', 'true'],
			];

			$other_notices_data = [
				'welcome_panel'        => sprintf(__('Remove Welcome Panel %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'php_nag'              => sprintf(__('Remove "PHP Update Required" Notice %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'core_update_notice'   => sprintf(__('Hide Core Update Notice %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'plugin_update_notice' => sprintf(__('Hide Plugin Update Notice %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'theme_update_notice'  => sprintf(__('Hide Theme Update Notice %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'site_health'          => sprintf(__('Disable Site Health checks %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
			];

			$fields[] = [
				'id'         => 'other_notices',
				'type'       => 'checkbox',
				'title'      => sprintf(__('Other Notices %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Show/Remove Dashboard Welcome Panel, Hide WordPress Themes,Plugins,Core Update Notices', 'adminify'),
				'options'    => $other_notices_data,
				'default'    => $this->get_default_field('other_notices'),
				'dependency' => ['hide_notices', '==', 'true', 'true'],
			];
		}


		/**
		 * Screen & Help Tab
		 */
		public function screen_options(&$fields)
		{
			$screen_options_settings = [
				'hide_screen_options' => sprintf(__('Hide Screen Options %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'hide_help_tab'       => sprintf(__('Hide Help Tab %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
			];

			$fields[] = [
				'id'       => 'screen_help_tab',
				'title'    => sprintf(__('Screen Options and Help Tab %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle' => __('Screen Options and Help Tab', 'adminify'),
				'type'     => 'fieldset',
				'fields'   => [
					[
						'id'         => 'enable_for_screen',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-pl-0 adminify-pt-0 adminify-pro-feature',
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('screen_help_tab')['enable_for_screen'],
					],
					[
						'id'         => 'screen_help_data',
						'type'       => 'checkbox',
						'class'      => 'adminify-one-col',
						'title'      => __('', 'adminify'),
						'options'    => $screen_options_settings,
						'default'    => $this->get_default_field('screen_help_tab')['screen_help_data'],
						'dependency' => ['enable_for_screen', '==', 'true', 'true']
					]
				]
			];
		}


		/**
		 * Folders: Post Types Settings
		 */
		public function folders_post_types_settings( &$fields ) {

			$fields[] = [
				'id'         => 'folders',
				'title'      => __('Folders', 'adminify'),
				'subtitle'   => __('Folders for Posts/Pages/Custom Post Types etc.', 'adminify'),
				'class'		=> 'adminify-two-columns',
				'type'       => 'fieldset',
				'fields'     => [
					[
						'id'         => 'enable_folders',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-pl-0',
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('folders')['enable_folders'],
					],
					[
						'id'         => 'enable_for',
						'type'       => 'checkbox',
						'title'      => __('Enable for', 'adminify'),
						'subtitle'   => __('Select Post Types for enabling Folders', 'adminify'),
						'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
						'default'    => $this->get_default_field('folders')['enable_for'],
						'dependency' => ['enable_folders', '==', 'true', 'true']
					],
					[
						'id'         => 'media',
						'type'       => 'switcher',
						'text_on'    => __('Yes', 'adminify'),
						'text_off'   => __('No', 'adminify'),
						'text_width' => 80,
						'title'      => __('Enable Media Folders', 'adminify'),
						'subtitle'   => __('Enabling Folders for Media Files', 'adminify'),
						'default'    => $this->get_default_field('folders')['media'],
						'dependency' => ['enable_folders', '==', 'true', 'true']
					]

				],
			];

			$fields[] = [
				'id'         => 'admin_pages',
				'type'       => 'switcher',
				'title'      => sprintf(__('Admin Pages %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-notice adminify-pro-fieldset',
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'subtitle'   => __('Custom Admin Pages for Creating Top Level or Sub Level Menu. Supports all page builders - Gutenberg, Elementor, Bricks, Oxygen, Divi etc', 'adminify'),
				'default'    => $this->get_default_field('admin_pages'),
			];

		}


		/**
		 * Post Types Order: Options
		 */
		public function post_types_order_options( &$fields ) {

			$post_types_order_fieldset= [];

			$post_types_order_fieldset[] = [
				'id'         => 'enable_pto',
				'title'      => __('', 'adminify'),
				'class'      => 'adminify-pl-0',
				'type'       => 'switcher',
				'text_on'    => __('Show', 'adminify'),
				'text_off'   => __('Hide', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('post_types_order')['enable_pto'],
			];

			$post_types_order_fieldset[] = [
				'id'         => 'pto_posts',
				'type'       => 'checkbox',
				'title'      => __( 'Sortable Post Types', 'adminify' ),
				'subtitle'   => __( 'Select Post Types for sorting', 'adminify' ),
				'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
				'default'    => $this->get_default_field('post_types_order')['pto_posts'],
				'dependency' => ['enable_pto', '==', 'true', 'true'],
			];

			$post_types_order_fieldset[] = [
				'id'         => 'pto_media',
				'type'       => 'switcher',
				'title'      => __( 'Sortable Media Files', 'adminify' ),
				'subtitle'   => __( 'Enable/Disable Sortable Media Files on List View', 'adminify' ),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('post_types_order')[ 'pto_media' ],
				'dependency' => ['enable_pto', '==', 'true', 'true'],
			];

			$post_types_order_fieldset[] = [
				'id'         => 'pto_taxonomies',
				'type'       => 'checkbox',
				'title'      => sprintf(__('Sortable Taxonomies %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Select for Sortable Taxonomies', 'adminify'),
				'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_taxonomies',
				'query_args' => [
					'orderby' => 'post_title',
					'order'   => 'ASC',
				],
				'default'    => $this->get_default_field('post_types_order')['pto_taxonomies'],
				'dependency' => ['enable_pto', '==', 'true', 'true'],
			];


			// Dashboard Widgets Added
			$fields[] = [
				'id'         => 'dashboard_widgets',
				'type'       => 'switcher',
				'title'      => __('Dashboard & Welcome Widget', 'adminify'),
				'subtitle'   => __('Create Custom Dashboard & Sidebar Widgets', 'adminify'),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('dashboard_widgets'),
			];

			$fields[] = [
				'id'         => 'post_types_order',
				'title'      => __('Post Types Order', 'adminify'),
				'subtitle'   => __('Enable/Disable Post Types Orders to increase your productivity.', 'adminify'),
				'type'       => 'fieldset',
				'fields'     => $post_types_order_fieldset,
			];
		}


		public static function get_all_post_types() {

			$post_types     = get_post_types(
				[
					'show_ui' => true,
				],
				'objects'
			);

			$post_type_names = [];
			foreach ($post_types as $post_type) {
				if (in_array($post_type->name, ['attachment', 'wp_navigation', 'wp_block'])) {
					continue;
				}
				if($post_type->name === 'post' || $post_type->name === 'page' ){
					$post_type_names[$post_type->name] = $post_type->label;
				} else {
					$is_pro = false;
					if(class_exists('\\WPAdminify\\Pro\\Adminify_Pro')){
						$is_pro = \WPAdminify\Pro\Adminify_Pro::is_premium();
					}
					$pro_notice = empty($is_pro) ? Utils::adminify_upgrade_pro_class() : '';
					$post_type_names[$post_type->name] = $post_type->label . $pro_notice;
				}
			}
			return $post_type_names;
		}

		public static function get_all_taxonomies() {
			$taxonomies     = get_taxonomies(
				[
					'show_ui' => true,
				],
				'objects'
			);
			$taxonomy_names = [];
			foreach ( $taxonomies as $taxonomy ) {
				if ( $taxonomy->name == 'post_format' ) {
					continue;
				}

				$is_pro = false;
				if (class_exists('\\WPAdminify\\Pro\\Adminify_Pro')) {
					$is_pro = \WPAdminify\Pro\Adminify_Pro::is_premium();
				}
				$pro_notice = empty($is_pro) ? Utils::adminify_upgrade_pro_class() : '';
				$taxonomy_names[ $taxonomy->name ] = $taxonomy->label . $pro_notice;

			}
			return $taxonomy_names;
		}


        /**
         * Modules
         *
         */
		public function modules_settings(&$fields)
		{
			$fields[] = [
				'id'         => 'menu_duplicator',
				'type'       => 'switcher',
				'title'      => __('Menu Duplicator', 'adminify'),
				'subtitle'   => __('Enable Menu Duplicator to increase your productivity.', 'adminify'),
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('menu_duplicator'),
			];

			$fields[] = [
				'id'       => 'post_duplicator',
				'title'    => __('Post Duplicator', 'adminify'),
				'subtitle' => __('Enable Post/Page/Custom Post Types Duplicator to increase your productivity.', 'adminify'),
				'type'     => 'fieldset',
				'default'  => $this->get_default_field('post_duplicator'),
				'fields'     => [
					[
						'id'         => 'enable_post_duplicator',
						'type'       => 'switcher',
						'title'      => __('', 'adminify'),
						'class'      => 'adminify-pl-0',
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('post_duplicator')['enable_post_duplicator'],
					],
					[
						'id'         => 'post_types',
						'type'       => 'checkbox',
						'title'      => __('Enable for Post Types', 'adminify'),
						'subtitle'   => __('Select Post Types for Enabling Duplicate feature', 'adminify'),
						'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
						'default'    => $this->get_default_field('post_duplicator')['post_types'],
						'dependency'  => ['enable_post_duplicator', '==', 'true', 'true'],
					],
					[
						'id'         => 'taxonomies',
						'type'       => 'checkbox',
						'title'      => sprintf(__('Enable for Taxonomies %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
						'subtitle'   => __('Enable for Taxonomies', 'adminify'),
						'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_taxonomies',
						'query_args' => [
							'orderby' => 'post_title',
							'order'   => 'ASC',
						],
						'default'    => $this->get_default_field('post_duplicator')['taxonomies'],
						'dependency' => ['enable_post_duplicator', '==', 'true', 'true'],
					]

				],
			];
		}



		/**
		 * Post/Page Columns
		 */

		public function custom_admin_columns(&$admin_columns)
		{
			$admin_columns[] = [
				'id'         => 'custom_admin_columns',
				'title'      => __('Custom Admin Columns', 'adminify'),
				'subtitle' => __('Add Custom Admin Columns for Post Types or Taxonomies', 'adminify'),
				'type'       => 'fieldset',
				'fields'     => [
					[
						'id'         => 'enable',
						'type'       => 'switcher',
						'class'      => 'adminify-pl-0',
						'title'      => __('', 'adminify'),
						'text_on'    => __('Show', 'adminify'),
						'text_off'   => __('Hide', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('custom_admin_columns')['enable'],
					],
					[
						'id'      => 'columns_data',
						'type'    => 'checkbox',
						'class'   => 'adminify-one-col',
						'title'   => __('', 'adminify'),
						'options' => [
							'post_thumb_column'   => sprintf(__('Show Post Thumbnails Column? %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
							'post_page_id_column' => sprintf(__('Show Post/Page ID Column. Display "ID" column for post and page table lists. %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
							'comment_id_column'   => __('Show "Comment ID" Column for Comment, Also show "Parent ID"', 'adminify'),
							'last_login_column'   => sprintf(__('Show "Last Login" Column for Users %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
							'taxonomy_id_column'  => __('Show "Taxonomy ID" Column for all possible types of taxonomies', 'adminify'),
							'posts_slug_column'   => __('Show "URL Path" Column for Post Types', 'adminify'),
						],
						'default'    => $this->get_default_field('custom_admin_columns')['columns_data'],
						'dependency' => ['enable', '==', 'true', true],
					],
					[
						'id'           => 'post_page_column_thumb_image',
						'type'         => 'media',
						'class'        => 'custom-thumb-image',
						'title'        => __('Column Thumbnail Image', 'adminify'),
						'library'      => 'image',
						'preview_size' => 'thumbnail',
						'button_title' => __('Add Thumbnail Image', 'adminify'),
						'remove_title' => __('Remove Thumbnail Image', 'adminify'),
						'default'      => $this->get_default_field('custom_admin_columns')['post_page_column_thumb_image'],
						'dependency'   => array('columns_data', 'any', 'post_thumb_column', 'true'),
					],
					[
						'id'         => 'slug_column_post_types',
						'type'       => 'checkbox',
						'title'      => __('"URL Path" Column for Post Types', 'adminify'),
						'subtitle'   => __('Select Post Types for Enabling "URL Path" Column Slug', 'adminify'),
						'options'    => 'WPAdminify\Inc\Admin\Options\Productivity::get_all_post_types',
						'default'    => $this->get_default_field('custom_admin_columns')['slug_column_post_types'],
						'dependency' => array('columns_data', 'any', 'posts_slug_column', 'true'),
					],
				]
			];
		}




		/**
		 * Gutenberg Settings
		 */

		public function gutenberg_settings(&$fields)
		{

			$fields[] = [
				'id'       => 'remove_widgets',
				'title'    => __('Widgets Removal', 'adminify'),
				'type'     => 'fieldset',
				'subtitle' => __('Remove unwanted Sidebar Widgets & Dashboard Widgets', 'adminify'),
				'fields'   => [
					[
						'id'      => 'remove_widgets_type',
						'type'    => 'button_set',
						'class'   => 'adminify-pl-0 !adminify-flex',
						'options' => [
							'sidebar'   => __('Sidebar Widgets', 'adminify'),
							'dashboard' => __('Dashboard Widgets', 'adminify'),
						],
						'default' => $this->get_default_field('remove_widgets')['remove_widgets_type'],
					],
					[
						'id'         => 'disable_gutenberg_editor',
						'type'       => 'switcher',
						'label'      => sprintf(__('<h4>%s</h4>', 'adminify'), __('Disable Gutenberg Editor for Widgets', 'adminify')),
						'class'      => 'adminify-pl-0 adminify-col-fit',
						'text_on'    => __('Yes', 'adminify'),
						'text_off'   => __('No', 'adminify'),
						'text_width' => 80,
						'default'    => $this->get_default_field('remove_widgets')['disable_gutenberg_editor'],
						'dependency' => ['remove_widgets_type', '==', 'sidebar', true],
					],
					[
						'id'         => 'sidebar_widgets_list',
						'type'       => 'checkbox',
						'class'      => 'adminify-one-col',
						'title'      => __('', 'adminify'),
						'options'    => '\WPAdminify\Inc\Classes\Sidebar_Widgets::render_sidebar_checkboxes',
						'default'    => $this->get_default_field('remove_widgets')['sidebar_widgets_list'],
						'dependency' => ['remove_widgets_type|disable_gutenberg_editor', '==|==', 'sidebar|true', true],
					],
					[
						'id'         => 'dashboard_widgets_list',
						'type'       => 'checkbox',
						'class'      => "adminify-one-col",
						'title'      => __('', 'adminify'),
						'options'    => '\WPAdminify\Inc\Classes\Remove_DashboardWidgets::render_dashboard_checkboxes',
						'default'    => $this->get_default_field('remove_widgets')['dashboard_widgets_list'],
						'dependency' => ['remove_widgets_type', '==', 'dashboard', true],
					],
				]
			];
		}


		/**
		 * Security: Attachments
		 *
		 * @return void
		 */
		public function productivity_attachment_fields(&$attachment_fields)
		{

			$allowed_upload_files_type = [
				'svg'  => sprintf(__('SVG Files %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'avif' => sprintf(__('AVIF Files %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'ico'  => sprintf(__('ICO Files %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
				'webp' => sprintf(__('WEBP Files %s', 'adminify'), Utils::adminify_upgrade_pro_class()),
			];

			$attachment_fields_data = [
				[
					'id'         => 'enable_media',
					'type'       => 'switcher',
					'title'      => __('', 'adminify'),
					'class'      => 'adminify-pl-0',
					'text_on'    => __('Show', 'adminify'),
					'text_off'   => __('Hide', 'adminify'),
					'text_width' => 80,
					'default'    => $this->get_default_field('media_attachments')['enable_media'],
				],
				// [
				// 	'id'         => 'media_ininite_scroll',
				// 	'type'       => 'switcher',
				// 	'title'      => sprintf(__('Infinite Scroll for Media Library? %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				// 	'class'      => 'adminify-pl-0 adminify-pt-0 adminify-pro-fieldset adminify-pro-notice',
				// 	'text_on'    => __('Yes', 'adminify'),
				// 	'text_off'   => __('No', 'adminify'),
				// 	'text_width' => 80,
				// 	'subtitle'   => __('Re-enable infinite scrolling in the media library\'s grid view to ease navigation through a large collection.', 'adminify'),
				// 	'default'    => $this->get_default_field('media_attachments')['media_ininite_scroll'],
				// 	'dependency' => ['enable_media', '==', 'true', true],
				// ],
				[
					'id'         => 'thumbnails_rss_feed',
					'type'       => 'switcher',
					'title'      => __('Post Thumbnails on RSS', 'adminify'),
					'class'      => 'adminify-pl-0',
					'text_on'    => __('Yes', 'adminify'),
					'text_off'   => __('No', 'adminify'),
					'text_width' => 80,
					'subtitle'   => __('Show/Hide Post Thumbnails on RSS excerpt and Content Feed.', 'adminify'),
					'default'    => $this->get_default_field('media_attachments')['thumbnails_rss_feed'],
					'dependency' => ['enable_media', '==', 'true', true],
				],
				// [
				// 	'id'         => 'allowed_upload_files',
				// 	'type'       => 'checkbox',
				// 	'class'      => 'adminify-pl-0',
				// 	'title'      => sprintf(__('Allowed Files Uploads %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				// 	'subtitle'   => __('Allow SVG/JPEG/WEBP/ICO/AVIF Files Upload', 'adminify'),
				// 	'options'    => $allowed_upload_files_type,
				// 	'default'    => $this->get_default_field('media_attachments')['allowed_upload_files'],
				// 	'dependency' => ['enable_media', '==', 'true', true],
				// ],
				// [
				// 	'id'         => 'convert_to_webp',
				// 	'type'       => 'switcher',
				// 	'class'      => 'adminify-pl-0',
				// 	'text_on'    => __('Yes', 'adminify'),
				// 	'text_off'   => __('No', 'adminify'),
				// 	'text_width' => 80,
				// 	'class'      => 'adminify-pl-0',
				// 	'title'      => sprintf(__('Convert to WEBP %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				// 	'subtitle'   => __('Convert Uploaded image to WEBP', 'adminify'),
				// 	'default'    => $this->get_default_field('media_attachments')['convert_to_webp'],
				// 	'dependency' => ['enable_media', '==', 'true', true],
				// ],
				// [
				// 	'id'         => 'featured_to_post',
				// 	'type'       => 'switcher',
				// 	'class'      => 'adminify-pl-0',
				// 	'text_on'    => __('Yes', 'adminify'),
				// 	'text_off'   => __('No', 'adminify'),
				// 	'text_width' => 80,
				// 	'class'      => 'adminify-pl-0',
				// 	'title'      => sprintf(__('Link Featured Images to Post %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				// 	'subtitle'   => __('Wrap featured images in your theme in links to posts.', 'adminify'),
				// 	'default'    => $this->get_default_field('media_attachments')['featured_to_post'],
				// 	'dependency' => ['enable_media', '==', 'true', true],
				// ],
				// [
				// 	'id'         => 'media_lowercase',
				// 	'type'       => 'switcher',
				// 	'title'      => sprintf(__('Lowercase Filenames for Uploads %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				// 	'subtitle'   => __('Make all the filenames of new uploads to lowercase', 'adminify'),
				// 	'class'      => 'adminify-pl-0',
				// 	'text_on'    => __('Yes', 'adminify'),
				// 	'text_off'   => __('No', 'adminify'),
				// 	'text_width' => 80,
				// 	'class'      => 'adminify-pl-0',
				// 	'default'    => $this->get_default_field('media_attachments')['media_lowercase'],
				// 	'dependency' => ['enable_media', '==', 'true', true],
				// ],
			];

			$attachment_fields[] = array(
				'id'       => 'media_attachments',
				'type'     => 'fieldset',
				'title'    => __('Media Settings', 'adminify'),
				'subtitle' => __('Media Attachement Settings', 'adminify'),
				'fields'   => $attachment_fields_data,
				// 'default'  => $this->get_default_field('media_attachments'),
			);
		}


        /**
         * Productivity Settings
         *
         * @return void
         */
        public function productivity_settings()
        {
            if (!class_exists('ADMINIFY')) {
                return;
            }

			$fields = [];

			$this->admin_notices_settings($fields);

			$this->screen_options($fields);

            // Folders
			$this->folders_post_types_settings( $fields );

            // Post Types Order
			$this->post_types_order_options( $fields );

			// Duplicator Settings
			$this->modules_settings( $fields );

			// Admin Columns
			$this->custom_admin_columns($fields);

			// Media Attachment files
			$this->productivity_attachment_fields($fields);

			$this->gutenberg_settings($fields);

			$fields = apply_filters('adminify_settings/productivity', $fields, $this);

            // Productivity Section
            \ADMINIFY::createSection(
                $this->prefix,
                [
                    'title' => __('Productivity', 'adminify'),
                    'id'    => 'productivity',
                    'icon'  => 'fas fa-business-time',
                    'fields' => $fields,
                ]
            );
        }
    }
}
