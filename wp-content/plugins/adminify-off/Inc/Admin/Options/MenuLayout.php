<?php

namespace WPAdminify\Inc\Admin\Options;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettingsModel;
use WPAdminify\Inc\Modules\MenuEditor\MenuEditor;

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.



class MenuLayout extends AdminSettingsModel {

	public function __construct() {
		$this->menu_layout_settings();
	}


	public function get_defaults() {
		return [
			'menu_layout_settings' => [
				'layout_type'           => 'vertical',
				'menu_hover_submenu'    => 'two_step',
				'icon_style'            => 'classic',
				'menu_mode'             => 'classic',
				'horz_menu_type'        => 'both',
				'horz_dropdown_icon'    => true,
				'horz_toplinks'         => false,
				'horz_bubble_icon_hide' => false,
				// 'horz_long_menu_break'  => true,
				'menu_styles'           => [
					'menu_typography'          => [
						'font-family' => 'Nunito Sans',
						'type'        => 'google',
					],
					'menu_width'			   => '',
					'menu_wrapper_padding'     => '',
					'menu_vertical_padding'    => '',
					'horz_menu_parent_padding' => '',
					'submenu_wrapper_padding'  => '',
					'submenu_vertical_space'   => '',
					'parent_menu_colors'       => [
						'wrap_bg'      => '',
						'hover_bg'     => '',
						'text_color'   => '',
						'text_hover'   => '',
						'active_color' => '',
					],
					'sub_menu_colors'          => [
						'wrap_bg'      => '',
						'hover_bg'     => '',
						'text_color'   => '',
						'text_hover'   => '',
						'active_bg'    => '',
						'active_color' => '',
					],
					'notif_colors'             => [
						'notif_bg'    => '',
						'notif_color' => '',
					],
				],
				'user_info_fields' 		=> [
					'enable_user_info'            	=> false,
					'user_info_content'     => 'text',
					'user_info_avatar'      => 'rounded',
				],
				'user_info_style'       => [
					'info_text_color'       => '',
					'info_text_hover_color' => '',
					'info_text_border'      => '',
					'info_icon_color'       => '',
					'info_icon_hover_color' => '',
				],
			],

		];
	}


	public function menu_layout_settings_tab( &$settings_tab ) {
		$settings_tab[] = [
			'id'      => 'layout_type',
			'type'    => 'button_set',
			'title'   => __( 'Menu Type', 'adminify' ),
			'options' => [
				'vertical'   => __( 'Vertical Menu', 'adminify' ),
				'horizontal' => __( 'Horizontal Menu', 'adminify' ),
			],
			'default' => $this->get_default_field( 'menu_layout_settings' )['layout_type'],
		];

		$settings_tab[] = [
			'id'         => 'horizontal_menu_id_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'class'      => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( 'Horizontal Menu Requires "Adminify UI" Enabled from "Customize" Tab ' ),
			'dependency' => [ 'admin_ui|layout_type', '!=|==', 'true|horizontal', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'menu_mode',
			'type'       => 'button_set',
			'title'      => __( 'Menu Mode', 'adminify' ),
			'options'    => [
				'classic'   => __( 'Default', 'adminify' ),
				'icon_menu' => __( 'Folded', 'adminify' ),
				'rounded'   => __( 'Rounded', 'adminify' ),
			],
			'default'    => $this->get_default_field( 'menu_layout_settings' )['menu_mode'],
			'dependency' => [ 'layout_type', '==', 'vertical', 'true' ],
		];

		$settings_tab[] = [
			'id'		=> 'rounded_menu_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'class'		 => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( 'Rounded Menu Mode Requires "Adminify UI" Module Enabled from "WP Adminify>Customize" Menu ' ),
			'dependency' => [ 'admin_ui|layout_type|menu_mode', '!=|==|==', 'true|vertical|rounded', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'icon_style',
			'type'       => 'button_set',
			'title'      => __( 'Icon Style', 'adminify' ),
			'options'    => [
				'classic' => __( 'Default', 'adminify' ),
				'rounded' => __( 'Rounded', 'adminify' ),
			],
			'dependency' => [ 'admin_ui|layout_type|menu_mode', '==|==|==', 'true|vertical|icon_menu', 'true' ],
			'default'    => $this->get_default_field( 'menu_layout_settings' )['icon_style'],
		];

		$settings_tab[] = [
			'id'         => 'menu_hover_submenu',
			'type'       => 'button_set',
			'title'      => __( 'Sub Menu Style', 'adminify' ),
			'options'    => [
				'two_step'   => __( 'Two Step', 'adminify' ),
				'accordion' => __( 'Accordion', 'adminify' ),
				'toggle'    => __( 'Toggle', 'adminify' ),
			],
			'dependency' => [ 'layout_type', '==', 'vertical', 'true' ],
			'default'    => $this->get_default_field( 'menu_layout_settings' )['menu_hover_submenu'],
		];

		$settings_tab[] = [
			'id'		=> 'two_step_menu_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'class'		 => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( 'Two Step Menu Requires "Adminify UI" Module Enabled from "WP Adminify>Customize" Menu ' ),
			'dependency' => [ 'admin_ui|layout_type|menu_hover_submenu', '!=|==|==', 'true|vertical|two_step', 'true' ],
		];

		$settings_tab[] = [
			'id'		=> 'accordion_menu_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'class'		 => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( 'Accordion Menu Requires "Adminify UI" Module Enabled from "WP Adminify>Customize" Menu ' ),
			'dependency' => [ 'admin_ui|layout_type|menu_hover_submenu', '!=|==|==', 'true|vertical|accordion', 'true' ],
		];

		$settings_tab[] = [
			'id'		=> 'toggle_menu_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'class'		 => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( 'Toggle Menu Requires "Adminify UI" Module Enabled from "WP Adminify>Customize" Menu ' ),
			'dependency' => [ 'admin_ui|layout_type|menu_hover_submenu', '!=|==|==', 'true|vertical|toggle', 'true' ],
		];

		// $settings_tab[] = [
		// 	'id'         => 'horizontal_menu_notice',
		// 	'type'       => 'notice',
		// 	'title'      => __( 'Horizontal Menu', 'adminify' ),
		// 	'style'      => 'warning',
		// 	'content'    => Utils::adminify_upgrade_pro(),
		// 	'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		// ];


		$settings_tab[] = [
			'id'         => 'horizontal_menu_notice',
			'type'       => 'notice',
			'style'      => 'warning',
			'title'      => __( ' ', 'adminify' ),
			// 'class'      => 'adminify-one-col',
			'content'    => Utils::adminify_upgrade_pro( '<strong>Horizontal Menu is available in PRO version</strong>' ),
			'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'user_info_fields',
			'type'       => 'fieldset',
			'title'    	 => sprintf(__('User Info %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
			'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|vertical', 'true' ],
			'fields'     => [
				[
					'id'         => 'enable_user_info', // user_info
					'type'       => 'switcher',
					'class'		 => 'adminify-pt-0 adminify-pl-0 !adminify-flex adminify-pro-feature',
					'label'      => __( 'Show or Hide User Info with Avatar on Admin Menu', 'adminify' ),
					'text_on'    => __( 'Show', 'adminify' ),
					'text_off'   => __( 'Hide', 'adminify' ),
					'text_width' => 100,
					'default'    => $this->get_default_field( 'menu_layout_settings' )['user_info_fields']['enable_user_info'],

				],
				[
					'id'         => 'user_info_content',
					'type'       => 'button_set',
					'class'		 => Utils::upgrade_pro_class(),
					'title'      => __( 'Content Type', 'adminify' ),
					'options'    => [
						'text' => __( 'Text', 'adminify' ),
						'icon' => __( 'Icon', 'adminify' ),
					],
					'default'    => $this->get_default_field( 'menu_layout_settings' )['user_info_fields']['user_info_content'],
					'dependency' => [ 'enable_user_info|layout_type', '==|==', 'true|vertical', 'true' ],
				],
				[
					'id'         => 'user_info_avatar',
					'type'       => 'button_set',
					'class'		 => Utils::upgrade_pro_class(),
					'title'      => __( 'Avatar Type', 'adminify' ),
					'options'    => [
						'rounded' => __( 'Rounded', 'adminify' ),
						'square'  => __( 'Square', 'adminify' ),
					],
					'default'    => $this->get_default_field( 'menu_layout_settings' )['user_info_fields']['user_info_avatar'],
					'dependency' => [ 'enable_user_info|layout_type', '==|==', 'true|vertical', 'true' ],
				]
			]
		];

		$settings_tab[] = [
			'id'      	=> 'horz_menu_type',
            'type'    	=> 'button_set',
			'class'		=> 'adminify-pro-fieldset',
			'title'    	=> sprintf(__('Menu Item Style %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
            'options' 	=> [
                'icons_only' => __( 'Icon Only', 'adminify' ),
                'text_only'  => __( 'Text Only', 'adminify' ),
                'both'       => __( 'Both', 'adminify' ),
            ],
            'default'    => $this->get_default_field( 'menu_layout_settings' )['horz_menu_type'],
            'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'horz_dropdown_icon',
            'type'       => 'switcher',
			'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
			'title'    	 => sprintf(__('Dropdown Toggle Icon %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
            'label'      => __( 'Show/Hide Dropdown Icon', 'adminify' ),
            'text_on'    => __( 'Show', 'adminify' ),
            'text_off'   => __( 'Hide', 'adminify' ),
            'text_width' => 100,
            'default'    => $this->get_default_field( 'menu_layout_settings' )['horz_dropdown_icon'],
            'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'horz_toplinks',
            'type'       => 'switcher',
			'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
			'title'    	 => sprintf(__('Top Menu Links %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
            'label'      => __( 'Parent/Top Menu Links clickable', 'adminify' ),
            'text_on'    => __( 'Enabled', 'adminify' ),
            'text_off'   => __( 'Disabled', 'adminify' ),
            'text_width' => 100,
            'default'    => $this->get_default_field( 'menu_layout_settings' )['horz_toplinks'],
            'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		$settings_tab[] = [
			'id'         => 'horz_bubble_icon_hide',
            'type'       => 'switcher',
			'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
			'title'    	 => sprintf(__('Bubble Icon %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
            'label'      => __( 'Show/Hide Update or Plugins Bubble Icon', 'adminify' ),
            'text_on'    => __( 'Show', 'adminify' ),
            'text_off'   => __( 'Hide', 'adminify' ),
            'text_width' => 100,
            'default'    => $this->get_default_field( 'menu_layout_settings' )['horz_bubble_icon_hide'],
            'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		// $settings_tab[] = [
		// 	'id'         => 'horz_long_menu_break',
        //     'type'       => 'switcher',
		// 	'class'		=> 'adminify-pro-fieldset adminify-pro-notice',
        //     'title'      => __( 'Break Long Lists', 'adminify' ),
		// 	'title'    	 => sprintf(__('Break Long Lists %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
        //     'label'      => __( 'Break Menu Lines if Main menu gets longer lists and doesn\'t cover screen witdh', 'adminify' ),
        //     'text_on'    => __( 'Enable', 'adminify' ),
        //     'text_off'   => __( 'Disable', 'adminify' ),
        //     'text_width' => 100,
        //     'default'    => $this->get_default_field( 'menu_layout_settings' )['horz_long_menu_break'],
        //     'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		// ];

	}




	public function menu_layout_style_tab( &$menu_styles_tab ) {
		// $menu_styles_tab[] = [
		// 	'type'    => 'subheading',
		// 	'content' => __( 'Menu Styles', 'adminify' ),
		// ];

		$menu_styles_tab[] = [
			'id'                 => 'menu_typography',
			'type'               => 'typography',
			'title'              => __( 'Font Settings', 'adminify' ),
			'font_family'        => false,
			'font_weight'        => true,
			'font_style'         => true,
			'font_size'          => true,
			'line_height'        => true,
			'letter_spacing'     => true,
			'text_align'         => false,
			'text_transform'     => false,
			'color'              => false,
			'subset'             => false,
			'backup_font_family' => false,
			'font_variant'       => false,
			'word_spacing'       => false,
			'text_decoration'    => false,
			'default'            => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['menu_typography'],
		];

		$menu_styles_tab[] = [
			'id'      => 'menu_width',
			'type'    => 'dimensions',
			'title'   => __( 'Menu Width', 'adminify' ),
			'height' => false,
			'units'  => array('px'),
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['menu_width'],
		];

		$menu_styles_tab[] = [
			'id'      => 'menu_wrapper_padding',
			'type'    => 'spacing',
			'title'   => __( 'Menu Wrapper Padding', 'adminify' ),
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['menu_wrapper_padding'],
		];

		$menu_styles_tab[] = [
			'id'         => 'menu_vertical_padding',
			'type'       => 'slider',
			'title'      => __( 'Parent Menu Vertical Padding', 'adminify' ),
			'unit'       => 'px',
			'min'        => 1,
			'max'        => 100,
			'step'       => 1,
			'default'    => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['menu_vertical_padding'],
			'dependency' => [ 'layout_type', '==', 'vertical', 'true' ],
		];

		$menu_styles_tab[] = [
			'id'         => 'horz_menu_parent_padding',
            'type'       => 'slider',
			'class'		 => 'adminify-pro-fieldset adminify-pro-notice',
			'title'      => sprintf(__('Parent Menu Horizontal Padding %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
            'unit'       => 'px',
            'min'        => 1,
            'max'        => 100,
            'step'       => 1,
            'default'    => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['horz_menu_parent_padding'],
            'dependency' => [ 'admin_ui|layout_type', '==|==', 'true|horizontal', 'true' ],
		];

		$menu_styles_tab[] = [
			'id'      => 'submenu_wrapper_padding',
			'type'    => 'spacing',
			'title'   => __( 'Sub Menu Wrapper Padding', 'adminify' ),
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['submenu_wrapper_padding'],
		];

		$menu_styles_tab[] = [
			'id'      => 'submenu_vertical_space',
			'type'    => 'slider',
			'title'   => __( 'Sub Menu Vertical Padding', 'adminify' ),
			'unit'    => 'px',
			'min'     => 1,
			'max'     => 100,
			'step'    => 1,
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['submenu_vertical_space'],
		];

		$menu_styles_tab[] = [
			'id'      => 'admin_menu_color_sub',
			'type'    => 'subheading',
			'content' => __( 'Color Settings', 'adminify' ),
		];

		$menu_styles_tab[] = [
			'id'      => 'parent_menu_colors',
			'type'    => 'color_group',
			'title'   => __( 'Parent Menu Colors', 'adminify' ),
			'options' => [
				'wrap_bg'      => __( 'Wrap BG', 'adminify' ),
				'hover_bg'     => __( 'Menu Hover BG', 'adminify' ),
				'text_color'   => __( 'Text Color', 'adminify' ),
				'text_hover'   => __( 'Text Hover', 'adminify' ),
				'active_bg'    => __( 'Active Menu BG', 'adminify' ),
				'active_color' => __( 'Active Menu Color', 'adminify' ),
			],
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['parent_menu_colors'],
		];

		$menu_styles_tab[] = [
			'id'      => 'sub_menu_colors',
			'type'    => 'color_group',
			'title'   => __( 'Sub Menu Colors', 'adminify' ),
			'options' => [
				'wrap_bg'      => __( 'Wrap BG', 'adminify' ),
				'hover_bg'     => __( 'Submenu Hover BG', 'adminify' ),
				'text_color'   => __( 'Text Color', 'adminify' ),
				'text_hover'   => __( 'Text Hover', 'adminify' ),
				'active_bg'    => __( 'Active Submenu BG', 'adminify' ),
				'active_color' => __( 'Active Submenu Color', 'adminify' ),
			],
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['sub_menu_colors'],
		];

		$menu_styles_tab[] = [
			'id'      => 'notif_colors',
			'type'    => 'color_group',
			'title'   => __( 'Notification Colors', 'adminify' ),
			'options' => [
				'notif_bg'    => __( 'Background', 'adminify' ),
				'notif_color' => __( 'Text Color', 'adminify' ),
			],
			'default' => $this->get_default_field( 'menu_layout_settings' )['menu_styles']['notif_colors'],
		];
	}


	public function menu_styles_tab( &$styles_tab ) {
		$menu_styles_tab      = [];
		// $user_info_styles_tab = [];
		$this->menu_layout_style_tab( $menu_styles_tab );
		// $this->user_info_style_tab( $user_info_styles_tab );

		$styles_tab[] = [
			'id'     => 'menu_styles',
			'type'   => 'fieldset',
			'title'  => '',
			'class'  => 'adminify-one-col',
			'fields' => $menu_styles_tab,
		];

		$styles_tab[] = [
			'id'      => 'user_info_style',
			'type'    => 'color_group',
			'class'   => 'adminify-pro-fieldset',
			'title'   => sprintf(__('User Info Colors %s', 'adminify'), Utils::adminify_upgrade_pro_badge()),
			'options' => [
                'info_text_color'       => __('Link Color', 'adminify'),
                'info_text_hover_color' => __('Hover Color', 'adminify'),
                'info_text_border'      => __('Border', 'adminify'),
                'info_icon_color'       => __('Icon Color', 'adminify'),
                'info_icon_hover_color' => __('Hover Icon Color', 'adminify'),
            ],
            'dependency' => ['layout_type|enable_user_info', '==|==', 'vertical|true', 'true'
            ],
		];
	}

	/**
	 * Menu Editor Root
	 */
	public static function jltwp_adminify_menu_editor_contents() {
		$menu_editor = MenuEditor::get_instance();
		$menu_editor->jltwp_adminify_menu_editor_contents();
	}


	/**
	 * Menu Layout Settings
	 */
	public function menu_layout_settings() {
		if ( ! class_exists( 'ADMINIFY' ) ) {
			return;
		}

		$settings_tab = [];
		$styles_tab   = [];

		$this->menu_layout_settings_tab( $settings_tab );
		$this->menu_styles_tab( $styles_tab );

		$settings_tab = apply_filters('adminify_settings/admin_menu/settings', $settings_tab, $this);
		$styles_tab = apply_filters('adminify_settings/admin_menu/styles', $styles_tab, $this);

		// Menu Layout Section
		\ADMINIFY::createSection(
			$this->prefix,
			[
				'title'  => __('Admin Menu', 'adminify' ),
				'icon'   => 'fas fa-bars',
				'fields' => [
					[
						'type'    => 'subheading',
						'content' => Utils::adminfiy_help_urls(
							__('Admin Menu Settings', 'adminify' ),
							'https://wpadminify.com/docs/adminify/admin-menu/admin-menu-editor',
							'https://www.youtube.com/watch?v=zbEYlAg91QM',
							'https://www.facebook.com/groups/jeweltheme',
							\WPAdminify\Inc\Admin\AdminSettings::support_url()
						),
					],
					[
						'id'    => 'menu_layout_settings',
						'type'  => 'tabbed',
						'title' => '',
						'tabs'  => [
							[
								'title'  => __( 'Menu Editor', 'adminify' ),
								'fields' => [
									[
										'id'    => 'menu_editor',
										'type'  => 'callback',
										'class'	=> 'adminify-one-col',
										'function' => '\WPAdminify\Inc\Admin\Options\MenuLayout::jltwp_adminify_menu_editor_contents',
									]
								],
							],
							[
								'title'  => __( 'Settings', 'adminify' ),
								'fields' => $settings_tab,
							],
							[
								'title'  => __( 'Styles', 'adminify' ),
								'fields' => $styles_tab,
							],
						],
						'default' => $this->get_defaults()['menu_layout_settings']
					],
				],
			]
		);
	}
}
