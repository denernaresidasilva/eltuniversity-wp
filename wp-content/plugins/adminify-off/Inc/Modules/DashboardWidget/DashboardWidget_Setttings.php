<?php

namespace WPAdminify\Inc\Modules\DashboardWidget;

use WPAdminify\Inc\Utils;
// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

/**
 * WPAdminify
 *
 * @package Module: Dashboard Widget
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */

if (!class_exists('DashboardWidget_Setttings')) {

	class DashboardWidget_Setttings extends DashboardWidgetModel
	{

		public function __construct()
		{
			// this should be first so the default values get stored
			$this->dashboard_widget_settings();
			parent::__construct((array) get_option($this->prefix));

			add_action('admin_enqueue_scripts', [$this, 'dash_widget_setting_css']);
		}


		/**
		 * Dashboard Admin Custom CSS
		 *
		 * @return void
		 */
		public function dash_widget_setting_css()
		{
			$css = '
			.adminify-dashboard-widgets .adminify-header{
				margin-top:30px;
			}
			.adminify-dashboard-widgets .adminify-header-right{
				padding: 25px;
			}
			.adminify-dashboard-widgets .wp-adminify-dashboard-widgets form{
				background: #fff;
			}
			.adminify-dashboard-widgets .adminify-section .adminify-field-subheading{
				background-color: transparent;
			}';

			printf('<style>body.wp-adminify{%s}</style>', wp_strip_all_tags($css));
		}



		public function get_defaults()
		{
			return [
				'dashboard_widget_types' => [
					'dashboard_widgets' => [
						'title'                   => '',
						'widget_pos'              => 'normal',
						'widget_type'             => 'editor',
						'dashw_video'             => [
							'dashw_type_video_type'       => 'self_hosted',
							'dashw_type_video_title'      => '',
							'dashw_type_video_type_self_hosted' => [
								'url'         => '',
								'id'          => '',
								'width'       => '',
								'height'      => '',
								'thumbnail'   => '',
								'alt'         => '',
								'title'       => '',
								'description' => '',
							],
							'dashw_type_video_type_youtube' => '',
							'dashw_type_video_type_vimeo'   => '',
							'dashw_type_video_type_iframe'  => '',
						],
						'dashw_type_editor'       => '',
						'dashw_type_icon'         => '',
						'dashw_type_icon_tooltip' => '',
						'dashw_type_icon_link'    => [
							'url'    => 'https://wpadminify.com/',
							'text'   => __('WP Adminify', 'adminify'),
							'target' => '_blank',
						],
						'dashw_type_shortcode'    => '',
						'dashw_type_script'       => '<script>
    ;(function($) {
        "use strict";
        $(document).ready( function() {
            // Write your JS code here
        });
    })( jQuery );
</script>',
						'dashw_type_rss_feed'     => '',
						'dashw_type_rss_count'    => 5,
						'dashw_type_rss_excerpt'  => true,
						'dashw_type_rss_date'     => true,
						'dashw_type_rss_author'   => true,
						'user_roles'              => '',

					],
					'welcome_dash_widget' => [
						'enable_custom_welcome_dash_widget' => false,
						'widget_template_type'  => 'specific_page',
						'custom_page'           => '',
						'panel_height'          => 600,
						'elementor_section_id'  => '',
						'elementor_widget_id'   => '',
						'elementor_template_id' => '',
						'oxygen_template_id'    => '',
						'dismissible'           => true,
						'user_roles'            => '',
					],
				],
			];
		}


		/**
		 * Welcome Widget Settings
		 *
		 * @param [type] $welcome_widgets
		 *
		 * @return void
		 */
		public function welcome_widgets_settings(&$welcome_widgets)
		{
			$welcome_widget_fields = [];
			$this->welcome_widget_fields($welcome_widget_fields);
			$welcome_widgets[] = [
				'id'     => 'welcome_dash_widget',
				'type'   => 'fieldset',
				'class'	 => 'adminify-one-col',
				'title'  => '',
				'fields' => $welcome_widget_fields,
			];
		}

		public function welcome_widget_fields(&$welcome_widget_fields)
		{
			$welcome_widget_fields[] = [
				'id'         => 'enable_custom_welcome_dash_widget',
				'type'       => 'switcher',
				'title'      => __('Enable Custom Welcome Panel?', 'adminify'),
				'subtitle'   => __('Enable if you want to show any Elementor Template/Page on Welcome Panel', 'adminify'),
				'text_on'    => __('Enable', 'adminify'),
				'text_off'   => __('Disable', 'adminify'),
				'default'    => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['enable_custom_welcome_dash_widget'],
				'text_width' => 100,
			];

			// Default Page
			$page_type_options = [
				'specific_page' => __('Page', 'adminify'),
			];

			// Oxygen Builder Support
			if (Utils::is_plugin_active('oxygen/functions.php')) {
				$page_oxygen_options = [];
				$page_oxygen_options = [
					'oxygen_template' => __('Oxygen Template', 'adminify'),
				];
				$page_type_options   = array_merge($page_type_options, $page_oxygen_options);
			}

			// Elementor Builder
			if (Utils::is_plugin_active('elementor/elementor.php')) {
				$page_elementory_options = [];
				$page_elementory_options = [
					'elementor_template' => __('Elementor Page Template', 'adminify'),
					'elementor_section'  => __('Elementor Saved Section', 'adminify'),
					'elementor_widget'   => __('Elementor Saved Widget', 'adminify'),
				];
				$page_type_options       = array_merge($page_type_options, $page_elementory_options);
			}

			$welcome_widget_fields[] = [
				'id'          => 'widget_template_type',
				'type'        => 'select',
				'title'       => __('Select Page/Template', 'adminify'),
				'placeholder' => __('Select an option', 'adminify'),
				'options'     => $page_type_options,
				'default'     => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['widget_template_type'],
				'dependency'  => ['enable_custom_welcome_dash_widget', '==', 'true', true],
			];

			// Default Page
			$welcome_widget_fields[] = [
				'id'          => 'custom_page',
				'type'        => 'select',
				'title'       => __('Select Page', 'adminify'),
				'placeholder' => __('Select a Page', 'adminify'),
				'options'     => 'pages',
				'default'     => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['custom_page'],
				'dependency'  => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'specific_page|true', true],
			];


			$welcome_widget_fields[] = [
				'id'          => 'panel_height',
				'type'        => 'number',
				'title'       => __('Panel Height', 'adminify'),
				'unit'        => 'px',
				'default'     => 100,
				'default'     => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['panel_height'],
				'dependency'  => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'specific_page|true', true],
			];

			// Oxygen Builder
			if (Utils::is_plugin_active('oxygen/functions.php')) {
				$welcome_widget_fields[] = [
					'id'          => 'oxygen_template_id',
					'type'        => 'select',
					'title'       => __('Select Template', 'adminify'),
					'placeholder' => __('Select a Template', 'adminify'),
					'options'     => 'posts',
					'query_args'  => [
						'post_type' => 'ct_template',
					],
					'default'     => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['oxygen_template_id'],
					'dependency'  => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'oxygen_template|true', true],
				];
			}

			// Elementor Builder
			if (Utils::is_plugin_active('elementor/elementor.php')) {
				$welcome_widget_fields[] = [
					'id'         => 'elementor_section_id',
					'type'       => 'select',
					'title'      => __('Saved Section', 'adminify'),
					'options'    => 'WPAdminify\Inc\Utils::get_section_template_options',
					'default'    => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['elementor_section_id'],
					'dependency' => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'elementor_section|true', true],
				];

				$welcome_widget_fields[] = [
					'id'         => 'elementor_widget_id',
					'type'       => 'select',
					'title'      => __('Saved Widget', 'adminify'),
					'options'    => 'WPAdminify\Inc\Utils::get_widget_template_options',
					'default'    => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['elementor_widget_id'],
					'dependency' => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'elementor_widget|true', true],
				];

				$welcome_widget_fields[] = [
					'id'         => 'elementor_template_id',
					'type'       => 'select',
					'title'      => __('Saved Template', 'adminify'),
					'options'    => 'WPAdminify\Inc\Utils::get_page_template_options',
					'default'    => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['elementor_template_id'],
					'dependency' => ['widget_template_type|enable_custom_welcome_dash_widget', '==|==', 'elementor_template|true', true],
				];
			}


			$welcome_widget_fields[] = [
				'id'         => 'dismissible',
				'type'       => 'switcher',
				'title'      => sprintf(__('Dismissible %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice',
				'default'    => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['dismissible'],
				'dependency' => ['enable_custom_welcome_dash_widget', '==', 'true', true],
			];

			$welcome_widget_fields[] = [
				'id'          => 'user_roles',
				'type'        => 'select',
				'title'       => __('Allowed User Roles', 'adminify'),
				'subtitle'    => __('Allow users to access this section', 'adminify'),
				'placeholder' => __('Select a role', 'adminify'),
				'chosen'      => true,
				'multiple'    => true,
				'options'     => 'roles',
				'default'     => $this->get_default_field('dashboard_widget_types')['welcome_dash_widget']['user_roles'],
				'dependency'  => ['enable_custom_welcome_dash_widget', '==', 'true', true],
			];
		}

		/**
		 * Dashboard Widgets Setting
		 *
		 * @param [type] $dash_widgets_setting
		 *
		 * @return void
		 */
		public function dash_widget_setting_setup(&$dash_widgets_setting)
		{
			$dash_widgets = [];
			$this->dashboard_widgets($dash_widgets);

			$welcome_widgets = [];
			$this->welcome_widgets_settings($welcome_widgets);

			$dash_widgets_setting[] = [
				'type'    => 'subheading',
				'id'      => 'dashboard_widget_types_subheading',
				'content' => Utils::adminfiy_help_urls(
					__('Custom Dashboard & Welcome Widgets', 'adminify'),
					'https://wpadminify.com/kb/wordpress-custom-dashboard-widget',
					'https://www.youtube.com/playlist?list=PLqpMw0NsHXV-EKj9Xm1DMGa6FGniHHly8',
					'https://www.facebook.com/groups/jeweltheme',
					\WPAdminify\Inc\Admin\AdminSettings::support_url()
				),
			];

			$dash_widgets_setting[] = [
				'id'    => 'dashboard_widget_types',
				'type'  => 'tabbed',
				'title' => '',
				'tabs'  => [
					[
						'title'  => __('Dashboard Widgets', 'adminify'),
						'id'     => 'dashboard_widgets_tab',
						'fields' => $dash_widgets,
					],

					[
						'title'  => __('Welcome Widget', 'adminify'),
						'id'     => 'welcome_widgets_tab',
						'fields' => $welcome_widgets,
					],

				],
			];
		}

		/**
		 * Dashboard Widgets Section
		 *
		 * @param [type] $dash_widgets
		 *
		 * @return void
		 */
		public function dashboard_widgets(&$dash_widgets)
		{
			$dashboard_group_fields = [];
			$this->dashboard_widget_group_fields($dashboard_group_fields);

			$dash_widgets[] = [
				'id'                     => 'dashboard_widgets',
				'type'                   => 'group',
				'title'                  => '',
				'max'                    => 2,
				'max_text'               => __('Get <strong>Pro Version</strong> to Unlock this feature. <a href="https://wpadminify.com/pricing" target="_blank">Upgrade to Pro Now!</a>', 'adminify'),
				'accordion_title_title'  => __('Dashboard Widget Name:', 'adminify'),
				'accordion_title_prefix' => __('Dashboard Widget Name: ', 'adminify'),
				'accordion_title_number' => true,
				'accordion_title_auto'   => true,
				'button_title'           => __('Add New Widget', 'adminify'),
				'fields'                 => $dashboard_group_fields,
			];
		}


		public function dashboard_widget_group_fields(&$dashboard_group_fields)
		{
			$dashboard_widget_video = [];
			$this->dashboard_widget_video($dashboard_widget_video);

			$dashboard_group_fields[] = [
				'id'      => 'title',
				'type'    => 'text',
				'title'   => __('Widget Title', 'adminify'),
				'default' => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['title'],
			];

			$dashboard_group_fields[] = [
				'id'      => 'widget_pos',
				'type'    => 'button_set',
				'title'   => __('Widget Position', 'adminify'),
				'options' => [
					'side'   => __('Side', 'adminify'),
					'normal' => __('Normal', 'adminify'),
				],
				'default' => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['widget_pos'],
			];

			$dashboard_group_fields[] = [
				'id'      => 'widget_type',
				'type'    => 'button_set',
				'title'   => __('Content Type', 'adminify'),
				'options' => [
					'editor'    => __('Editor', 'adminify'),
					'icon'      => __('Icon', 'adminify'),
					'video'     => __('Video', 'adminify'),
					'shortcode' => __('Shortcode', 'adminify'),
					'rss_feed'  => __('RSS Feed', 'adminify'),
					'script'    => __('Script', 'adminify'),
				],
				'default' => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['widget_type'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_video',
				'type'       => 'fieldset',
				'title'      => __('Video', 'adminify'),
				'fields'     => $dashboard_widget_video,
				'dependency' => ['widget_type', '==', 'video'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_editor',
				'type'       => 'wp_editor',
				'title'      => __('Content', 'adminify'),
				'subtitle'   => __('Contents with Editor and HTML mode', 'adminify'),
				'height'     => '100px',
				'dependency' => ['widget_type', '==', 'editor'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_editor'],
			];


			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_icon',
				'type'       => 'icon',
				'title'      => sprintf(__('Icon %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset  adminify-pro-notice adminify-pro-pointer',
				'dependency' => ['widget_type', '==', 'icon'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_icon'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_icon_tooltip',
				'type'       => 'text',
				'title'      => sprintf(__('Tooltip Text %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset  adminify-pro-notice adminify-pro-pointer',
				'dependency' => ['widget_type', '==', 'icon'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_icon_tooltip'],
			];


			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_icon_link',
				'type'       => 'link',
				'title'      => sprintf(__('Link %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_icon_link'],
				'dependency' => ['widget_type', '==', 'icon'],
			];


			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_shortcode',
				'type'       => 'textarea',
				'title'      => sprintf(__('Shortcode %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'dependency' => ['widget_type', '==', 'shortcode'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_shortcode'],
			];


			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_script',
				'type'       => 'code_editor',
				'title'      => sprintf(__('Script %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'subtitle'   => __('Write Custom Script code inside <strong>&lt;script&gt;&lt;/script&gt;</strong> tag.', 'adminify'),
				'settings'   => [
					'theme' => 'dracula',
					'mode'  => 'htmlmixed',
				],
				'sanitize'   => false,
				'dependency' => ['widget_type', '==', 'script'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_script'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_rss_feed',
				'type'       => 'text',
				'title'      => sprintf(__('RSS Feed URL %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'dependency' => ['widget_type', '==', 'rss_feed'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_rss_feed'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_rss_count',
				'type'       => 'number',
				'title'      => sprintf(__('No. of Feed Posts %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_rss_count'],
				'dependency' => ['widget_type', '==', 'rss_feed'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_rss_excerpt',
				'type'       => 'switcher',
				'title'      => sprintf(__('Show Excerpt? %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_rss_excerpt'],
				'dependency' => ['widget_type', '==', 'rss_feed'],
			];

			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_rss_date',
				'type'       => 'switcher',
				'title'      => sprintf(__('Show Date? %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_rss_date'],
				'dependency' => ['widget_type', '==', 'rss_feed'],
			];


			$dashboard_group_fields[] = [
				'id'         => 'dashw_type_rss_author',
				'type'       => 'switcher',
				'title'      => sprintf(__('Show Author? %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'text_on'    => __('Yes', 'adminify'),
				'text_off'   => __('No', 'adminify'),
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_type_rss_author'],
				'dependency' => ['widget_type', '==', 'rss_feed'],
			];


			$dashboard_group_fields[] = [
				'id'          => 'user_roles',
				'type'        => 'select',
				'title'       => __('Allowed User Roles', 'adminify'),
				'subtitle'    => __('Allow users to access this section', 'adminify'),
				'placeholder' => __('Select a role', 'adminify'),
				'chosen'      => true,
				'multiple'    => true,
				'options'     => 'roles',
				'default'     => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['user_roles'],
			];
		}

		public function dashboard_widget_video(&$dashboard_widget_video)
		{
			$dashboard_widget_video[] = [
				'id'      => 'dashw_type_video_type',
				'type'    => 'button_set',
				'title'   => __('Video Type', 'adminify'),
				'options' => [
					'self_hosted' => __('Self Hosted ', 'adminify'),
					'youtube'     => __('Youtube', 'adminify'),
					'vimeo'       => __('Vimeo', 'adminify'),
					'iframe'      => __('iFrame', 'adminify'),
				],
				'default' => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_type'],
			];


			$dashboard_widget_video[] = [
				'id'      => 'dashw_type_video_title',
				'type'    => 'text',
				'title'   => sprintf(__('Text %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'   => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'default' => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_title'],
				'dependency' => ['dashw_type_video_type', '!==', 'iframe', true],
			];


			$dashboard_widget_video[] = [
				'id'         => 'dashw_type_video_type_self_hosted',
				'type'       => 'media',
				'title'      => sprintf(__('Upload Video %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'library'    => 'video',
				'preview'    => true,
				'dependency' => ['dashw_type_video_type', '==', 'self_hosted'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_type_self_hosted'],
			];

			$dashboard_widget_video[] = [
				'id'         => 'dashw_type_video_type_youtube',
				'type'       => 'text',
				'title'      => sprintf(__('Youtube URL %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'validate'   => 'adminify_validate_url',
				'dependency' => ['dashw_type_video_type', '==', 'youtube'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_type_youtube'],
			];

			$dashboard_widget_video[] = [
				'id'         => 'dashw_type_video_type_vimeo',
				'type'       => 'text',
				'title'      => sprintf(__('Vimeo URL %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'validate'   => 'adminify_validate_url',
				'dependency' => ['dashw_type_video_type', '==', 'vimeo'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_type_vimeo'],
			];

			$dashboard_widget_video[] = [
				'id'         => 'dashw_type_video_type_iframe',
				'type'       => 'text',
				'title'      => sprintf(__('iFrame Video URL %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'      => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'validate'   => 'adminify_validate_url',
				'dependency' => ['dashw_type_video_type', '==', 'iframe'],
				'default'    => $this->get_default_field('dashboard_widget_types')['dashboard_widgets']['dashw_video']['dashw_type_video_type_iframe'],
			];
		}

		public function dashboard_widget_settings()
		{
			if (!class_exists('ADMINIFY')) {
				return;
			}

			// WP Adminify Dashboard Widgets Settings
			\ADMINIFY::createOptions(
				$this->prefix,
				[

					// Framework Title
					'framework_title'         => __('WP Adminify Dashboard Widget <small>by Jewel Theme</small>', 'adminify'),
					'framework_class'         => 'adminify-dashboard-widgets',

					// menu settings
					'menu_title'              => __('Dashboard Widget', 'adminify'),
					'menu_slug'               => 'adminify-dashboard-widgets',
					'menu_type'               => 'submenu',                      // menu, submenu, options, theme, etc.
					'menu_capability'         => 'manage_options',
					'menu_icon'               => '',
					'menu_position'      	  => 18,
					'menu_hook_priority'      => 21,
					'menu_hidden'             => false,
					'menu_parent'             => 'wp-adminify-settings',

					// footer
					'footer_text'             => ' ',
					'footer_after'            => ' ',
					'footer_credit'           => ' ',

					// menu extras
					'show_bar_menu'           => false,
					'show_sub_menu'           => false,
					'show_in_network'         => false,
					'show_in_customizer'      => false,

					'show_search'             => false,
					'show_reset_all'          => false,
					'show_reset_section'      => false,
					'show_footer'             => true,
					'show_all_options'        => true,
					'show_form_warning'       => true,
					'sticky_header'           => false,
					'save_defaults'           => false,
					'ajax_save'               => true,

					// admin bar menu settings
					'admin_bar_menu_icon'     => '',
					'admin_bar_menu_priority' => 45,

					// database model
					'database'                => 'options',   // options, transient, theme_mod, network(multisite support)
					'transient_time'          => 0,

					// typography options
					'enqueue_webfont'         => true,
					'async_webfont'           => false,

					// others
					'output_css'              => false,

					// theme and wrapper classname
					'nav'                     => 'normal',
					'theme'                   => 'dark',
					'class'                   => 'wp-adminify-dashboard-widgets',
				]
			);

			$dash_widgets_setting = [];
			$this->dash_widget_setting_setup($dash_widgets_setting);
			$dash_widgets_setting = apply_filters('adminify_settings/dashboar_widgets', $dash_widgets_setting, $this);

			\ADMINIFY::createSection(
				$this->prefix,
				[
					'title'  => __('Dashboard Widget', 'adminify'),
					'icon'   => 'fas fa-bolt',
					'fields' => $dash_widgets_setting,
				]
			);
		}
	}
}
