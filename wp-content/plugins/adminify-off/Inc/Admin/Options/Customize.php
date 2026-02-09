<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;


if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'Customize' ) ) {
	class Customize extends AdminSettingsModel {

		public $defaults = [];

		public function __construct() {
			$this->customize_settings();
		}

		public function get_defaults()
		{
			return [
				'admin_ui'                 => false,
				'gutenberg_editor_logo'    => '',
				'admin_favicon_logo'       => [],
				'adminify_theme'           => 'preset1',
				'admin_bar_search'         => true,
				'admin_bar_notif'          => true,
				'admin_bar_dark_light_btn' => true,
				'body_fields'              => [
					'adminify_custom_bg'                 => false,
					'admin_general_bg'                   => 'gradient',
					// 'admin_general_bg_color'             => '',
					'admin_general_bg_gradient'          => [
						'background-color'              => '#0347FF',
						'background-gradient-color'     => '#fd1919',
						'background-gradient-direction' => '135deg',
					],
					'admin_general_bg_image'             => '',
					// 'admin_general_bg_slideshow'         => '',
					// 'admin_general_bg_video_type'        => 'youtube',
					// 'admin_general_bg_video_self_hosted' => '',
					// 'admin_general_bg_video_youtube'     => '',
					// 'admin_general_bg_video_loop'        => true,
					// 'admin_general_bg_video_poster'      => '',
				],
				'adminify_theme_custom_colors'       	 => [
					'--adminify-preset-background' 	=> '#F9F9F9',
					'--adminify-primary'           	=> '#3a3ae9',
					'--adminify-menu-bg'           	=> '#4738a6',
					'--adminify-menu-text-color'   	=> '#ffffff',
					'--adminify-menu-border'		=> '#7062cd',
					'--adminify-notif-bg-color'    	=> '#FD49A0',
				],
				'admin_general_google_font'          => [
					'font-family' => '',
					'font-weight' => '400',
					'type'        => 'google',
					'font-size'   => '',
					'line-height' => '',
					'color'       => '',
					'output'      => 'body',
				],
				'admin_glass_effect'                 => true,
				// 'admin_general_button_color'         => [
				//     'primary_color'   => '#0347FF',
				//     'secondary_color' => '#fff',
				// ],
				// 'admin_danger_button_color'          => [
				//     'primary_color'   => '#c30052',
				//     'secondary_color' => '#fff',
				// ],

				// Dark/Light Mode Settings
				'light_dark_mode'			=> [
					'admin_ui_mode'                => 'light',
					'admin_ui_logo_type'           => 'image_logo',
					'admin_ui_light_mode'          => [
						'admin_ui_light_logo_text'      => __('WP Adminify', 'adminify'),
						'admin_ui_light_logo_text_typo' => '',
						'admin_ui_light_logo'           => [
							'url'  => ''
						],
						'mini_admin_ui_light_logo'      => '',
						'light_logo_size'                => [
							'width'  => '120',
							'height' => '32',
							'unit'   => 'px',
						]
					],
					'admin_ui_dark_mode'           => [
						'admin_ui_dark_logo_text'      => __('WP Adminify', 'adminify'),
						'admin_ui_dark_logo_text_typo' => '',
						'admin_ui_dark_logo'           => '',
						'mini_admin_ui_dark_logo'      => '',
						'dark_logo_size'                => [
							'width'  => '150',
							'height' => '45',
							'unit'   => 'px',
						],
						'schedule_dark_mode'	=> [
							'enable_schedule_dark_mode'     => false,
							'schedule_dark_mode_type'       => 'system',
							'schedule_dark_mode_start_time' => '',
							'schedule_dark_mode_end_time'   => '',
						]
					],

				],



				// Post Status Colors
				'post_status_bg_colors'        => [
					'publish' => 'transparent',
					'pending' => 'transparent',
					'future'  => 'transparent',
					'private' => 'transparent',
					'draft'   => 'transparent',
					'trash'   => 'transparent',
				],
			];
		}

		public function admin_ui_settings(&$fields)
		{
			$fields[] = [
				'id'      => 'layout_mode_setting_subheading',
				'type'    => 'subheading',
				'content' => Utils::adminfiy_help_urls(
					'<span></span>',
					'https://wpadminify.com/docs/adminify/customize/adminify-ui-templates',
					'https://www.youtube.com/watch?v=jeWdKbEHYVU',
					'https://www.facebook.com/groups/jeweltheme',
					\WPAdminify\Inc\Admin\AdminSettings::support_url()
				),
			];

			$fields[] = [
				'id'         => 'admin_ui',
				'type'       => 'switcher',
				'title'      => __('Adminify UI', 'adminify'),
				'subtitle'   => __('Choose to Enable Adminify UI for your Dashboard.', 'adminify'),
				'text_on'    => __('Enable', 'adminify'),
				'text_off'   => __('Disable', 'adminify'),
				'text_width' => '100',
				'default'    => $this->get_default_field('admin_ui'),
			];


			$fields[] = [
				'id'      => 'adminify_theme',
				'type'    => 'image_select',
				'title'    => __('Adminify UI Templates', 'adminify'),
				'options' => [
					'preset1' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-1.png',
					'preset2' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-2.png',
					'preset3' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-3.png',
					'preset4' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-4.png',
					'preset5' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-5.png',
					'preset6' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-6.png',
					'preset7' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-7.png',
					'preset8' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-8.png',
					'preset9' => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-9.png',
					'custom'  => WP_ADMINIFY_ASSETS_IMAGE . 'presets/preset-10.png',
				],
				'default' => $this->get_default_field('adminify_theme'),
				'dependency' => ['admin_ui', '==', 'true'],
			];

			$fields[] = [
				// 'id'         => 'adminify_theme_custom_colors',
				// 'title'      => __(' ', 'adminify'),
				// 'type'       => 'notice',
				// 'style'      => 'warning',
				// 'content'    => Utils::adminify_upgrade_pro(),
				// 'dependency' => [ 'adminify_theme|adminify_theme', '!=|!=', 'preset1|preset2', 'any'],

				'id'         => 'adminify_theme_custom_colors',
				'type'       => 'color_group',
				'class'		 => 'adminify-pro-fieldset',
				'title'      => sprintf(__('Custom Color Preset %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Custom Template Color Presets', 'adminify'),
				'options'    => [
					'--adminify-preset-background'    => __('Body BG', 'adminify'),
					'--adminify-primary'              => __('Primary Color', 'adminify'),
					'--adminify-menu-bg'              => __('Menu BG', 'adminify'),
					'--adminify-menu-text-color'      => __('Menu Text', 'adminify'),
					'--adminify-menu-border'          => __('Menu Border', 'adminify'),
					'--adminify-notif-bg-color'       => __('Notification BG', 'adminify'),
				],
				'default'    => $this->get_default_field('adminify_theme_custom_colors'),
				'dependency' => ['adminify_theme|admin_ui', '==|==', 'custom|true', 'true'],
			];
		}


		public function general_glass_effect_bg(&$fields)
		{
			$fields[] = [
				'id'      => 'admin_glass_effect',
				'title'   => __('Glass Effect', 'adminify'),
				'type'    => 'notice',
				'style'   => 'warning',
				'content' => Utils::adminify_upgrade_pro(),
			];
		}

		// Admin Bar Settings
		public function admin_bar_settings( &$fields ){

			$fields[] = [
			    'id'         => 'admin_bar_search',
			    'type'       => 'switcher',
			    'title'      => __('Search Form', 'adminify'),
			    'text_on'    => __('Show', 'adminify'),
			    'text_off'   => __('Hide', 'adminify'),
			    'text_width' => '100',
			    'default'    => $this->get_default_field('admin_bar_search'),
				'dependency' => ['admin_ui', '==', 'true', 'true'],
			];

			$fields[] = [
			    'id'         => 'admin_bar_notif',
			    'type'       => 'switcher',
			    'title'      => __('Notifications Icon', 'adminify'),
			    'text_on'    => __('Show', 'adminify'),
			    'text_off'   => __('Hide', 'adminify'),
			    'text_width' => '100',
			    'default'    => $this->get_default_field('admin_bar_notif'),
			    'dependency' => ['admin_ui', '==', 'true', 'true'],
			];

			$fields[] = [
			    'id'         => 'admin_bar_dark_light_btn',
			    'type'       => 'switcher',
			    'title'      => __('Light/Dark Switcher', 'adminify'),
			    'text_on'    => __('Show', 'adminify'),
			    'text_off'   => __('Hide', 'adminify'),
			    'text_width' => '100',
			    'default'    => $this->get_default_field('admin_bar_dark_light_btn'),
			];
		}

		// Body Color Settings
		public function body_fields_settings(&$fields)
		{
			$background_settings      = [];
			$this->background_settings($background_settings);

			$fields[] = [
				'id'         => 'body_fields',
				'type'       => 'fieldset',
				'class'      => 'adminify-nopadding',
				'title'      => sprintf(__('Custom Background %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'   => __('Choose to Enable Custom Background for your Dashboard.', 'adminify'),
				'fields'     => $background_settings,
				'dependency' => ['admin_ui', '==', 'true', 'true'],
			];

			$fields[] = [
				'id'             => 'admin_general_google_font',
				'type'           => 'typography',
				'class'		     => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'title'    	     => sprintf(__('Body Font %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'line_height'    => false,
				'text_align'     => false,
				'text_transform' => false,
				'subset'         => false,
				'letter_spacing' => false,
				'font_size' 	 => false,
				'color' 	 	 => false,
				'default'        => $this->get_default_field('admin_general_google_font'),
				// 'dependency' 	 => ['admin_ui', '==', 'true'],
			];

			$fields[] = [
				'id'           => 'admin_favicon_logo',
				'type'         => 'media',
				'class'		   => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'title'        => sprintf(__('Admin Favicon %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle'     => __('Admin Area Favicon Icon', 'adminify'),
				'library'      => 'image',
				'preview_size' => 'thumbnail',
				'button_title' => __('Add Favicon', 'adminify'),
				'remove_title' => __('Remove Favicon', 'adminify'),
				'default'      => $this->get_default_field('admin_favicon_logo'),
			];

			// Gutenberg Editor Logo
			$fields[] = [
				'id'           => 'gutenberg_editor_logo',
				'type'         => 'media',
				'title'        => sprintf(__('Gutenberg Editor Logo %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'class'		   => 'adminify-pro-fieldset adminify-pro-notice adminify-pro-pointer',
				'subtitle'     => __('Replace Gutenberg Post/Page WordPress Logo', 'adminify'),
				'library'      => 'image',
				'preview_size' => 'thumbnail',
				'button_title' => __('Add Editor Logo', 'adminify'),
				'remove_title' => __('Remove Editor Logo', 'adminify'),
				'default'      => $this->get_default_field('gutenberg_editor_logo'),
			];

		}



		public function background_settings(&$fields)
		{
			$fields[] = [
				'id'         => 'adminify_custom_bg',
				'type'       => 'switcher',
				'title'      => __('', 'adminify'),
				'class'      => '!adminify-flex adminify-pt-0 adminify-pl-0 adminify-pro-feature',
				'subtitle'   => __('Choose to Enable Custom Background for your Dashboard.', 'adminify'),
				'text_on'    => __('Show', 'adminify'),
				'text_off'   => __('Hide', 'adminify'),
				'text_width' => 80,
				'default'    => $this->get_default_field('body_fields')['adminify_custom_bg'],
			];


			$fields[] = [
				'id'      => 'admin_general_bg',
				'type'    => 'button_set',
				'class'   => Utils::upgrade_pro_class(),
				'title'   => 'Background Type',
				'options' => [
					// 'color'     => __('Color', 'adminify'),
					'gradient'  => __('Gradient', 'adminify'),
					'image'     => __('Image', 'adminify'),
					// 'slideshow' => __('Slideshow', 'adminify'),
					// 'video'     => __('Video', 'adminify'),
				],
				'default'    => $this->get_default_field('body_fields')['admin_general_bg'],
				'dependency' => ['adminify_custom_bg', '==', 'true', true],
			];

			// $fields[] = [
			//     'id'         => 'admin_general_bg_color',
			//     'type'       => 'color',
			//     'title'      => __('Background Color', 'adminify'),
			//      'default'    => $this->get_default_field('body_fields')['admin_general_bg_color'],
			//     'dependency' => ['admin_general_bg', '==', 'color', true],
			// ];

			// Gradient BG
			$fields[] = [
				'id'                    => 'admin_general_bg_gradient',
				'type'                  => 'background',
				'class'                 => Utils::upgrade_pro_class(),
				'title'                 => __('Gradient Color', 'adminify'),
				'background_color'      => true,
				'background_image'      => false,
				'background_position'   => false,
				'background_repeat'     => false,
				'background_attachment' => true,
				'background_size'       => false,
				'background_origin'     => false,
				'background_clip'       => false,
				'background_blend_mode' => false,
				'background_gradient'   => true,
				'default'               => $this->get_default_field('body_fields')['admin_general_bg_gradient'],
				'dependency'            => ['admin_general_bg|adminify_custom_bg', '==|==', 'gradient|true', true],
			];

			// Image
			$fields[] = [
				'id'                    => 'admin_general_bg_image',
				'type'                  => 'background',
				'class'                 => Utils::upgrade_pro_class() . ' adminify-pro-pointer',
				'title'                 => __('Background Image', 'adminify'),
				'background_color'      => false,
				'background_image'      => true,
				'background_position'   => true,
				'background_repeat'     => true,
				'background_attachment' => true,
				'background_size'       => true,
				'background_origin'     => false,
				'background_clip'       => false,
				'background_blend_mode' => false,
				'background_gradient'   => false,
				'default'               => $this->get_default_field('body_fields')['admin_general_bg_image'],
				'dependency'            => ['admin_general_bg|adminify_custom_bg', '==|==', 'image|true', true],
			];
		}


		public function buttons_customize_settings(&$fields)
		{
			// $fields[] = [
			//     'type'    => 'subheading',
			//     'content' => __('Button Customization', 'adminify'),
			// ];

			// $fields[] = [
			//     'id'       => 'admin_general_button_color',
			//     'type'     => 'color_group',
			//     'title'    => __('Button', 'adminify'),
			//     'subtitle' => __('Change Admin Button Colors', 'adminify'),
			//     'options'  => [
			//         'primary_color'   => __('Primary Color', 'adminify'),
			//         'secondary_color' => __('Secondary Color', 'adminify'),
			//     ],
			//     'default'  => $this->get_default_field('admin_general_button_color'),
			// ];

			// $fields[] = [
			//     'id'       => 'admin_danger_button_color',
			//     'type'     => 'color_group',
			//     'title'    => __('Danger Button', 'adminify'),
			//     'subtitle' => __('Change Admin Delete Button Colors', 'adminify'),
			//     'options'  => [
			//         'primary_color'   => __('Background Color', 'adminify'),
			//         'secondary_color' => __('Hover Background Color', 'adminify'),
			//     ],
			//     'default'  => $this->get_default_field('admin_danger_button_color'),
			// ];
		}


		/**
		 * Logo Options Settings
		 *
		 * @return void
		 */
		public function layout_mode_setting_fields(&$fields)
		{
			$dark_light_data[] = [
				'id'       => 'admin_ui_mode',
				'type'     => 'button_set',
				'title'    => __('Layout Mode', 'adminify'),
				'options'  => [
					'light' => __('Light Mode', 'adminify'),
					'dark'  => __('Dark Mode', 'adminify'),
				],
				'default'    => $this->get_default_field('light_dark_mode')['admin_ui_mode'],
			];


			$dark_light_data[] = [
				'id'      => 'admin_ui_logo_type',
				'type'    => 'button_set',
				'title'   => __('Logo Type', 'adminify'),
				'options' => [
					'image_logo' => __('Image', 'adminify'),
					'text_logo'  => __('Text', 'adminify'),
				],
				'default'    => $this->get_default_field('light_dark_mode')['admin_ui_logo_type'],
				'dependency' => ['admin_ui', '==', 'true', 'true'],
			];

			$dark_light_data[] = [
				'id'     => 'admin_ui_light_mode',
				'title'  => __(' ', 'adminify'),
				'class'  => 'adminify-one-col adminify-pl-0',
				'type'   => 'fieldset',
				'fields' => [
					[
						'id'         => 'admin_ui_light_logo_text',
						'type'       => 'text',
						'title'      => __('Logo Text', 'adminify'),
						'dependency' => ['admin_ui_logo_type', '==', 'text_logo', 'true'],
						'default'    => $this->get_default_field('light_dark_mode')['admin_ui_light_mode']['admin_ui_light_logo_text'],
					],
					[
						'id'              => 'admin_ui_light_logo_text_typo',
						'type'            => 'typography',
						'title'           => __('Logo Text Typography', 'adminify'),
						'font_family'     => true,
						'font_weight'     => true,
						'font_style'      => true,
						'font_size'       => true,
						'line_height'     => false,
						'letter_spacing'  => true,
						'text_align'      => false,
						'text_transform'  => false,
						'color'           => true,
						'subset'          => false,
						'word_spacing'    => false,
						'text_decoration' => false,
						'dependency'      => ['admin_ui_logo_type', '==', 'text_logo', 'true'],
						'default'         => $this->get_default_field('light_dark_mode')['admin_ui_light_mode']['admin_ui_light_logo_text_typo'],
					],
					[
						'id'           => 'admin_ui_light_logo',
						'type'         => 'media',
						'title'        => __('Light Logo', 'adminify'),
						'library'      => 'image',
						'preview_size' => 'thumbnail',
						'button_title' => __('Add Light Logo', 'adminify'),
						'remove_title' => __('Remove Light Logo', 'adminify'),
						'default'      => $this->get_default_field('light_dark_mode')['admin_ui_light_mode']['admin_ui_light_logo'],
						'dependency'   => ['admin_ui_logo_type', '==', 'image_logo', 'true'],
					],
					[
						'id'         => 'light_logo_size',
						'type'       => 'dimensions',
						'title'      => __('Logo Size', 'adminify'),
						'default'    => $this->get_default_field('light_dark_mode')['admin_ui_light_mode']['light_logo_size'],
						'dependency' => ['admin_ui_logo_type', '==', 'image_logo', 'true'],
					],

					[
						'id'           => 'mini_admin_ui_light_logo',
						'type'         => 'media',
						'title'        => __('Mini Logo', 'adminify'),
						'library'      => 'image',
						'preview_size' => 'thumbnail',
						'button_title' => __('Add Mini Light Logo', 'adminify'),
						'remove_title' => __('Remove Mini Light Logo', 'adminify'),
						'default'      => $this->get_default_field('light_dark_mode')['admin_ui_light_mode']['mini_admin_ui_light_logo'],
						'dependency'   => ['admin_ui|layout_type', '==|==|==', 'true|vertical', 'true'],
					],
				],
				'dependency' => ['admin_ui|admin_ui_mode', '==|==', 'true|light', 'true'],
			];

			$dark_light_data[] = [
				'id'     => 'admin_ui_dark_mode',
				'type'   => 'fieldset',
				'title'  => __(' ', 'adminify'),
				'class'  => 'adminify-one-col adminify-pl-0',
				'fields' => [
					[
						'id'         => 'admin_ui_dark_logo_text',
						'type'       => 'text',
						'title'      => __('Logo Text', 'adminify'),
						'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['admin_ui_dark_logo_text'],
						'dependency' => ['admin_ui_logo_type', '==', 'text_logo', 'true'],
					],
					[
						'id'              => 'admin_ui_dark_logo_text_typo',
						'type'            => 'typography',
						'title'           => __('Logo Text Typography', 'adminify'),
						'font_family'     => true,
						'font_weight'     => true,
						'font_style'      => true,
						'font_size'       => true,
						'line_height'     => false,
						'letter_spacing'  => true,
						'text_align'      => false,
						'text_transform'  => false,
						'color'           => true,
						'subset'          => false,
						'word_spacing'    => false,
						'text_decoration' => false,
						'dependency'      => ['admin_ui_logo_type', '==', 'text_logo', 'true'],
						'default'         => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['admin_ui_dark_logo_text_typo'],
					],
					[
						'id'           => 'admin_ui_dark_logo',
						'type'         => 'media',
						'title'        => __('Dark Logo', 'adminify'),
						'library'      => 'image',
						'preview_size' => 'thumbnail',
						'button_title' => __('Add Dark Logo', 'adminify'),
						'remove_title' => __('Remove Dark Logo', 'adminify'),
						'default'      => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['admin_ui_dark_logo'],
						'dependency'   => ['admin_ui_logo_type', '==', 'image_logo', 'true'],
					],
					[
						'id'         => 'dark_logo_size',
						'type'       => 'dimensions',
						'title'      => __('Logo Size', 'adminify'),
						'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['dark_logo_size'],
						'dependency' => ['admin_ui_logo_type', '==', 'image_logo', 'true'],
					],
					[
						'id'           => 'mini_admin_ui_dark_logo',
						'type'         => 'media',
						'title'        => __('Mini Logo', 'adminify'),
						'library'      => 'image',
						'preview_size' => 'thumbnail',
						'button_title' => __('Add Dark Mini Logo', 'adminify'),
						'remove_title' => __('Remove Dark Mini Logo', 'adminify'),
						'default'      => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['mini_admin_ui_dark_logo'],
						'dependency'   => ['admin_ui_logo_type|admin_ui|layout_type', '==|==|==', 'image_logo|true|vertical', 'true'],
					],

					[
						'id'     => 'schedule_dark_mode',
						'title'  => sprintf(__('Schedule Dark Mode %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
						'type'   => 'fieldset',
						'class'  => 'adminify-mt-10',
						'fields' => [
							[
								'id'         => 'enable_schedule_dark_mode',
								'type'       => 'switcher',
								'class'   	 => 'adminify-pro-feature adminify-pt-0 adminify-pl-0',
								'text_on'    => __('Enable', 'adminify'),
								'text_off'   => __('Disable', 'adminify'),
								'text_width' => '100',
								'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['schedule_dark_mode']['enable_schedule_dark_mode'],
							],
							[
								'id'         => 'schedule_dark_mode_type',
								'title'      => __('Schedule Type', 'adminify'),
								'type'       => 'button_set',
								'class'		=> Utils::upgrade_pro_class(),
								'options'    => [
									'system' => __('System', 'adminify'),
									'custom' => __('Custom', 'adminify'),
								],
								'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['schedule_dark_mode']['schedule_dark_mode_type'],
								'dependency' => ['enable_schedule_dark_mode|admin_ui_mode', '==|==', 'true|dark', 'true'],
							],
							[
								'id'         => 'schedule_dark_mode_start_time',
								'type'       => 'datetime',
								'class'		=> Utils::upgrade_pro_class() . ' adminify-pro-pointer',
								'title'      => __('Start Time', 'adminify'),
								'settings'   => [
									'noCalendar' => true,
									'enableTime' => true,
								],
								'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['schedule_dark_mode']['schedule_dark_mode_start_time'],
								'dependency' => [
									[
										'enable_schedule_dark_mode', '==', 'true', 'true'
									],
									[
										'schedule_dark_mode_type', '==', 'custom', 'true'
									],
								],
							],
							[
								'id'         => 'schedule_dark_mode_end_time',
								'type'       => 'datetime',
								'class'		=> Utils::upgrade_pro_class() . ' adminify-pro-pointer',
								'title'      => __('End Time', 'adminify'),
								'settings'   => [
									'noCalendar' => true,
									'enableTime' => true,
								],
								'default'    => $this->get_default_field('light_dark_mode')['admin_ui_dark_mode']['schedule_dark_mode']['schedule_dark_mode_end_time'],
								'dependency' => [
									[
										'enable_schedule_dark_mode', '==', 'true', 'true'
									],
									[
										'schedule_dark_mode_type', '==', 'custom', 'true'
									],
								],
							]
						],
						'dependency' => ['admin_ui_mode', '==', 'dark', 'true'],
					]

				],
				'dependency' => ['admin_ui|admin_ui_mode', '==|==', 'true|dark', 'true'],
			];

			$fields[] = array(
				'id'       => 'light_dark_mode',
				'type'     => 'fieldset',
				'title'    => __('Light/Dark Mode', 'adminify'),
				'subtitle' => __('Enable/Disable Light or Dark Mode', 'adminify'),
				'fields'   => $dark_light_data,
				'default'  => $this->get_default_field('light_dark_mode'),
				'dependency' => ['admin_ui', '==', 'true', 'true'],
			);
		}


		/**
		 * Post Status colors
		 */
		public function post_status_bg_colors(&$fields)
		{


			$fields[] = [
				'id'       => 'post_status_bg_colors',
				'type'     => 'color_group',
				'class'		=> 'adminify-pro-fieldset',
				'title'    => sprintf(__('Post Status Background Colors %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
				'subtitle' => __('Background Color by Post Status type', 'adminify'),
				'options'  => [
					'publish' => __('Publish BG Color', 'adminify'),
					'pending' => __('Pending BG Color', 'adminify'),
					'future'  => __('Future BG Color', 'adminify'),
					'private' => __('Private BG Color', 'adminify'),
					'draft'   => __('Draft BG Color', 'adminify'),
					'trash'   => __('Trash BG Color', 'adminify'),
				],
				'default'  => $this->get_default_field('post_status_bg_colors'),
			];
		}

		public function gutenberg_editor_customization( &$fields){
			$fields[] = [

			];
		}

		public function admin_customization( &$fields){
		}

		public function customize_settings() {
			if ( ! class_exists( 'ADMINIFY' ) ) {
				return;
			}


			$fields = [];

			$this->admin_ui_settings($fields);
			$this->body_fields_settings($fields);
			// $this->admin_customization($fields);
			// self::general_glass_effect_bg($fields);
			// $this->buttons_customize_settings($fields);
			$this->layout_mode_setting_fields($fields);
			$this->admin_bar_settings($fields);
			// $this->gutenberg_editor_customization($fields);

			$fields = apply_filters('adminify_settings/customize', $fields, $this);
			$this->post_status_bg_colors($fields);
			$fields = apply_filters('adminify_settings/post_status', $fields, $this);

			// Admin UI Section
			\ADMINIFY::createSection(
				$this->prefix,
				[
					'title'  => __('Customize', 'adminify'),
					'icon'   => 'fas fa-fill-drip',
					'fields' => $fields,
				]
			);

		}
	}
}
