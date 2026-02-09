<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
// no direct access allowed
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class OutputCSS_Body {
    public $url;

    public $options;

    public $adminify_ui;

    public function __construct() {
        $this->options = (array) AdminSettings::get_instance()->get();
        $this->adminify_ui = Utils::check_modules( $this->options['admin_ui'] );
        add_action( 'admin_enqueue_scripts', [$this, 'jltwp_adminify_admin_ui_preset_vars'] );
    }

    /**
     * CSS variables
     * @return [Variables Array with key value pair]
     */
    public function jltwp_adminify_output_styles() {
        $menu_styles = $this->options['menu_layout_settings']['menu_styles'];
        $default_ui_css = '';
        $jltwp_adminify_css_var = [];
        // Style: Menu Typography
        $menu_typography = $menu_styles['menu_typography'];
        // Font Sizes
        if ( !empty( $menu_typography['font-size'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-font-size'] = esc_attr( $menu_typography['font-size'] . 'px' );
            } else {
                $default_ui_css .= '#adminmenu a.menu-top { font-size: ' . esc_attr( $menu_typography['font-size'] . 'px' ) . ';}';
                $default_ui_css .= '#adminmenu .wp-submenu a { font-size: ' . esc_attr( $menu_typography['font-size'] - 1 . 'px' ) . ';}';
            }
        }
        // Line Height
        if ( !empty( $menu_typography['line-height'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-line-height'] = esc_attr( $menu_typography['line-height'] . 'px' );
            } else {
                $default_ui_css .= '#adminmenu a.menu-top, #adminmenu .wp-submenu a, #adminmenu div.wp-menu-image:before { line-height: ' . esc_attr( $menu_typography['line-height'] ) . 'px;}';
            }
        }
        // Letter Spacing
        if ( !empty( $menu_typography['letter-spacing'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-letter-spacing'] = esc_attr( $menu_typography['letter-spacing'] . 'px' );
            } else {
                $default_ui_css .= '#adminmenu a.menu-top, #adminmenu .wp-submenu a { letter-spacing: ' . esc_attr( $menu_typography['letter-spacing'] . 'px' ) . ';}';
            }
        }
        // Menu Width
        if ( !empty( $menu_styles['menu_width']['width'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-width'] = esc_attr( $menu_styles['menu_width']['width'] . 'px' );
            } else {
                $default_ui_css .= '#wpcontent, #wpfooter { margin-left: ' . esc_attr( $menu_styles['menu_width']['width'] . 'px' ) . ';}';
                $default_ui_css .= '#adminmenuback, #adminmenuwrap, #adminmenu { width: ' . esc_attr( $menu_styles['menu_width']['width'] . 'px' ) . ';}';
                $default_ui_css .= '#adminmenu .wp-submenu, &.folded #adminmenuback, &.folded #adminmenuwrap, &.folded #adminmenu { width: auto;}';
                $default_ui_css .= '#adminmenu .wp-submenu:not(#adminmenu .wp-has-current-submenu .wp-submenu) { left: ' . esc_attr( $menu_styles['menu_width']['width'] . 'px' ) . ';}';
                $default_ui_css .= '#adminmenu .wp-submenu:not(#adminmenu .wp-has-current-submenu .wp-submenu) { left: calc(' . esc_attr( $menu_styles['menu_width']['width'] . 'px' ) . esc_attr( ( !empty( $menu_styles['menu_wrapper_padding']['left'] ) ? ' - ' . $menu_styles['menu_wrapper_padding']['left'] . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) : " - 0px" ) ) . ');}';
                $default_ui_css .= '&.folded #adminmenu .wp-submenu:not(#adminmenu .wp-has-current-submenu .wp-submenu), &.folded #adminmenu .opensub .wp-submenu-wrap { left: calc(36px' . esc_attr( ( !empty( $menu_styles['menu_wrapper_padding']['right'] ) ? ' + ' . $menu_styles['menu_wrapper_padding']['right'] . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) : ' + 0px' ) ) . ')!important;}';
                $default_ui_css .= '&.folded div#adminmenuwrap {height: 100%;}';
                //Folder Module
                if ( !empty( $this->options['folders']['enable_folders'] ) ) {
                    $default_ui_css .= '.wp-adminify--folder-widget{left: ' . esc_attr( $menu_styles['menu_width']['width'] . 'px' ) . ';}';
                }
            }
        }
        // Box sizing for padding
        if ( (!empty( $menu_styles['menu_wrapper_padding']['top'] ) && $menu_styles['menu_wrapper_padding']['top'] !== '' || !empty( $menu_styles['menu_wrapper_padding']['right'] ) && $menu_styles['menu_wrapper_padding']['right'] !== '' || !empty( $menu_styles['menu_wrapper_padding']['bottom'] ) && $menu_styles['menu_wrapper_padding']['bottom'] !== '' || !empty( $menu_styles['menu_wrapper_padding']['left'] ) && $menu_styles['menu_wrapper_padding']['left'] !== '') && empty( $this->adminify_ui ) ) {
            $default_ui_css .= '#adminmenu { box-sizing: border-box;} #adminmenu .wp-submenu {width: auto;}';
        }
        // Folded left & right padding
        if ( (!empty( $menu_styles['menu_wrapper_padding']['right'] ) && $menu_styles['menu_wrapper_padding']['right'] !== '' || !empty( $menu_styles['menu_wrapper_padding']['left'] ) && $menu_styles['menu_wrapper_padding']['left'] !== '') && empty( $menu_styles['menu_width']['width'] ) && empty( $this->adminify_ui ) ) {
            $left_padding = ( !empty( $menu_styles['menu_wrapper_padding']['left'] ) ? $menu_styles['menu_wrapper_padding']['left'] . 'px' : '0px' );
            $right_padding = ( !empty( $menu_styles['menu_wrapper_padding']['right'] ) ? $menu_styles['menu_wrapper_padding']['right'] . 'px' : '0px' );
            $default_ui_css .= '&.folded #adminmenuback, &.folded #adminmenuwrap, &.folded #adminmenu { width: calc(36px + ' . $left_padding . ' + ' . $right_padding . ');}';
            $default_ui_css .= '&.folded #adminmenu .opensub .wp-submenu {left: calc(36px + ' . $right_padding . ')}';
        }
        // Menu Wrapper Padding
        if ( !empty( $menu_styles['menu_wrapper_padding']['top'] ) && $menu_styles['menu_wrapper_padding']['top'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-wrapper-padding-top'] = esc_attr( $menu_styles['menu_wrapper_padding']['top'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu { padding-top: ' . esc_attr( $menu_styles['menu_wrapper_padding']['top'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['menu_wrapper_padding']['right'] ) && $menu_styles['menu_wrapper_padding']['right'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-wrapper-padding-right'] = esc_attr( $menu_styles['menu_wrapper_padding']['right'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu { padding-right: ' . esc_attr( $menu_styles['menu_wrapper_padding']['right'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) . ';}';
                $default_ui_css .= '#adminmenu li.wp-has-submenu.wp-not-current-submenu:hover:after, #adminmenu li.wp-has-submenu.wp-not-current-submenu:focus-within:after { right: -' . esc_attr( $menu_styles['menu_wrapper_padding']['right'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['menu_wrapper_padding']['bottom'] ) && $menu_styles['menu_wrapper_padding']['bottom'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-wrapper-padding-bottom'] = esc_attr( $menu_styles['menu_wrapper_padding']['bottom'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu { padding-bottom: ' . esc_attr( $menu_styles['menu_wrapper_padding']['bottom'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['menu_wrapper_padding']['left'] ) && $menu_styles['menu_wrapper_padding']['left'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-wrapper-padding-left'] = esc_attr( $menu_styles['menu_wrapper_padding']['left'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu { padding-left: ' . esc_attr( $menu_styles['menu_wrapper_padding']['left'] ) . esc_attr( $menu_styles['menu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        // Sub Menu Wrapper Padding
        if ( !empty( $menu_styles['submenu_wrapper_padding']['top'] ) && $menu_styles['submenu_wrapper_padding']['top'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-wrapper-padding-top'] = esc_attr( $menu_styles['submenu_wrapper_padding']['top'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu { padding-top: ' . esc_attr( $menu_styles['submenu_wrapper_padding']['top'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['submenu_wrapper_padding']['right'] ) && $menu_styles['submenu_wrapper_padding']['right'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-wrapper-padding-right'] = esc_attr( $menu_styles['submenu_wrapper_padding']['right'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu { padding-right: ' . esc_attr( $menu_styles['submenu_wrapper_padding']['right'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['submenu_wrapper_padding']['bottom'] ) && $menu_styles['submenu_wrapper_padding']['bottom'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-wrapper-padding-bottom'] = esc_attr( $menu_styles['submenu_wrapper_padding']['bottom'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu { padding-bottom: ' . esc_attr( $menu_styles['submenu_wrapper_padding']['bottom'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['submenu_wrapper_padding']['left'] ) && $menu_styles['submenu_wrapper_padding']['left'] !== '' ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-wrapper-padding-left'] = esc_attr( $menu_styles['submenu_wrapper_padding']['left'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu { padding-left: ' . esc_attr( $menu_styles['submenu_wrapper_padding']['left'] ) . esc_attr( $menu_styles['submenu_wrapper_padding']['unit'] ) . ';}';
            }
        }
        // Vertical Menu Parent Padding
        if ( !empty( $this->options['menu_layout_settings']['layout_type'] ) && $this->options['menu_layout_settings']['layout_type'] === 'vertical' ) {
            if ( !empty( $menu_styles['menu_vertical_padding'] ) ) {
                if ( $this->adminify_ui ) {
                    $jltwp_adminify_css_var['--adminify-menu-vertical-padding'] = esc_attr( $menu_styles['menu_vertical_padding'] ) . 'px';
                } else {
                    $default_ui_css .= '#adminmenu { display: flex; flex-direction: column; row-gap: ' . esc_attr( $menu_styles['menu_vertical_padding'] ) . 'px;}';
                }
            }
        }
        // Submenu Item Padding
        if ( !empty( $menu_styles['submenu_vertical_space'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-vertical-padding'] = esc_attr( $menu_styles['submenu_vertical_space'] ) . 'px';
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu { display: flex; flex-direction: column; row-gap: ' . esc_attr( $menu_styles['submenu_vertical_space'] ) . 'px;}';
            }
        }
        // Parent Menu Colors
        $menu_colors = $menu_styles['parent_menu_colors'];
        // Background Color
        if ( !empty( $menu_colors['wrap_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-bg'] = esc_attr( $menu_colors['wrap_bg'] );
            } else {
                $default_ui_css .= '#adminmenuback, #adminmenuwrap, #adminmenu { background: ' . esc_attr( $menu_colors['wrap_bg'] ) . ';}';
            }
        }
        // Menu Item Hover Background
        if ( !empty( $menu_colors['hover_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-hover-bg'] = esc_attr( $menu_colors['hover_bg'] );
            } else {
                $default_ui_css .= '#adminmenu li.menu-top:hover, #adminmenu li.opensub > a.menu-top, #adminmenu li > a.menu-top:focus { background: ' . esc_attr( $menu_colors['hover_bg'] ) . ';}';
            }
        }
        // Text Color
        if ( !empty( $menu_colors['text_color'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-text-color'] = esc_attr( $menu_colors['text_color'] );
            } else {
                $default_ui_css .= '#adminmenu li.menu-top > a, #adminmenu li.menu-top > a > .wp-menu-image::before, #collapse-menu #collapse-button { color: ' . esc_attr( $menu_colors['text_color'] ) . ';}';
            }
        }
        // Text Color Hover
        if ( !empty( $menu_colors['text_hover'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-text-hover-color'] = esc_attr( $menu_colors['text_hover'] );
            } else {
                $default_ui_css .= '#adminmenu li.menu-top:hover > a.menu-top, #adminmenu li.menu-top:hover > a.menu-top > .wp-menu-image::before { color: ' . esc_attr( $menu_colors['text_hover'] ) . ';}';
            }
        }
        // Active Menu Background Color
        if ( !empty( $menu_colors['active_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-active-bg'] = esc_attr( $menu_colors['active_bg'] );
            } else {
                $default_ui_css .= '#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, #adminmenu li.current a.menu-top { background: ' . esc_attr( $menu_colors['active_bg'] ) . ';}';
            }
        }
        // Active Menu Color
        if ( !empty( $menu_colors['active_color'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-menu-active-color'] = esc_attr( $menu_colors['active_color'] );
            } else {
                $default_ui_css .= '#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, #adminmenu li.wp-has-current-submenu > a.menu-top > .wp-menu-image::before, #adminmenu li.current a.menu-top, #adminmenu li.current a.menu-top > .wp-menu-image::before { color: ' . esc_attr( $menu_colors['active_color'] ) . ';}';
            }
        }
        // Sub Menu BG wrapper Colors
        $submenu_colors = $menu_styles['sub_menu_colors'];
        if ( !empty( $submenu_colors['wrap_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-wrapper-bg'] = esc_attr( $submenu_colors['wrap_bg'] );
            } else {
                $default_ui_css .= '#adminmenu > li .wp-submenu { background: ' . esc_attr( $submenu_colors['wrap_bg'] ) . ';}';
            }
        }
        // Submenu Item Hover Bg
        if ( !empty( $submenu_colors['hover_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-hover-bg'] = esc_attr( $submenu_colors['hover_bg'] );
            } else {
                $default_ui_css .= '#adminmenu > li .wp-submenu > li:hover > a { background: ' . esc_attr( $submenu_colors['hover_bg'] ) . ';}';
            }
        }
        // Submenu Text Color
        if ( !empty( $submenu_colors['text_color'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-text-color'] = esc_attr( $submenu_colors['text_color'] );
            } else {
                $default_ui_css .= '#adminmenu > li .wp-submenu > li > a { color: ' . esc_attr( $submenu_colors['text_color'] ) . ';}';
            }
        }
        // Submenu hover text color
        if ( !empty( $submenu_colors['text_hover'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-text-hover-color'] = esc_attr( $submenu_colors['text_hover'] );
            } else {
                $default_ui_css .= '#adminmenu > li .wp-submenu > li:hover > a { color: ' . esc_attr( $submenu_colors['text_hover'] ) . ';}';
            }
        }
        // Submenu active Bg
        if ( !empty( $submenu_colors['active_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-active-bg'] = esc_attr( $submenu_colors['active_bg'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu li.current a { background: ' . esc_attr( $submenu_colors['active_bg'] ) . ';}';
            }
        }
        // Submenu active color
        if ( !empty( $submenu_colors['active_color'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-submenu-active-color'] = esc_attr( $submenu_colors['active_color'] );
            } else {
                $default_ui_css .= '#adminmenu .wp-submenu li.current a { color: ' . esc_attr( $submenu_colors['active_color'] ) . ';}';
            }
        }
        // User Info Styles
        // if (jltwp_adminify()->can_use_premium_code__premium_only()) {
        // 	if (!empty($this->options['user_info_style']['info_text_color'])) {
        // 		$jltwp_adminify_output_css .= '.wp_adminify_user-details a { color:' . esc_attr($this->options['user_info_style']['info_text_color']) . ' !important;}';
        // 	}
        // 	if (!empty($this->options['info_text_hover_color'])) {
        // 		$jltwp_adminify_output_css .= '.wp_adminify_user-details a:hover { color:' . esc_attr($this->options['user_info_style']['info_text_hover_color']) . ' !important;}';
        // 	}
        // 	if (!empty($this->options['user_info_style']['info_text_border'])) {
        // 		if (!empty($this->options['user_info_style']['info_text_border']['all'])) {
        // 			$jltwp_adminify_output_css .= '.wp_adminify_user {
        //                 border:' . esc_attr($this->options['user_info_style']['info_text_border']['all']) . 'px ' . esc_attr($this->options['user_info_style']['info_text_border']['style']) .--adminify-menu-text-color-2:#444444; ' ' . esc_attr($this->options['user_info_style']['info_text_border']['color']) . ';}';
        // 		}
        // 	}
        // 	if (!empty($this->options['user_info_style']['info_icon_color'])) {
        // 		$jltwp_adminify_output_css .= '.wp_adminify_user-actions i { color:' . esc_attr($this->options['user_info_style']['info_icon_color']) . ' !important;}';
        // 	}
        // 	if (!empty($this->options['user_info_style']['info_icon_hover_color'])) {
        // 		$jltwp_adminify_output_css .= '.wp_adminify_user-actions i:hover { color:' . esc_attr($this->options['user_info_style']['info_icon_hover_color']) . ' !important;}';
        // 	}
        // }
        // Notification Counter
        // Background Color
        if ( !empty( $menu_styles['notif_colors']['notif_bg'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-notif-bg-color'] = esc_attr( $menu_styles['notif_colors']['notif_bg'] );
            } else {
                $default_ui_css .= '#adminmenu .menu-counter, #adminmenu .awaiting-mod, #adminmenu .update-plugins { background: ' . esc_attr( $menu_styles['notif_colors']['notif_bg'] ) . ';}';
            }
        }
        if ( !empty( $menu_styles['notif_colors']['notif_color'] ) ) {
            if ( $this->adminify_ui ) {
                $jltwp_adminify_css_var['--adminify-notif-color'] = esc_attr( $menu_styles['notif_colors']['notif_color'] );
            } else {
                $default_ui_css .= '#adminmenu .menu-counter, #adminmenu .awaiting-mod, #adminmenu .update-plugins { color: ' . esc_attr( $menu_styles['notif_colors']['notif_color'] ) . ';}';
            }
        }
        // Combine the values from above and minifiy them.
        // $jltwp_adminify_output_css = preg_replace('#/\*.*?\*/#s', '', $jltwp_adminify_output_css);
        // $jltwp_adminify_output_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $jltwp_adminify_output_css);
        // $jltwp_adminify_output_css = preg_replace('/\s\s+(.*)/', '$1', $jltwp_adminify_output_css);
        // return $jltwp_adminify_output_css;
        if ( $this->adminify_ui ) {
            return $jltwp_adminify_css_var;
        } else {
            return $jltwp_adminify_css_var = $default_ui_css;
        }
    }

    public function jltwp_adminify_logo_text_styles() {
        // Light Logo Typography
        $admin_bar_light_logo_text_typo = $this->options['light_dark_mode']['admin_ui_light_mode']['admin_bar_light_logo_text_typo'];
        $light_logo_css = '&.adminify-light-mode .navbar .wp-adminify-site-name {';
        // Logo Text Light
        if ( !empty( $admin_bar_light_logo_text_typo['font-size'] ) ) {
            $light_logo_css .= 'font-size: ' . esc_attr( $admin_bar_light_logo_text_typo['font-size'] ) . 'px;';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['font-family'] ) ) {
            $light_logo_css .= 'font-family: ' . esc_attr( $admin_bar_light_logo_text_typo['font-family'] ) . ';';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['font-weight'] ) ) {
            $light_logo_css .= 'font-weight: ' . esc_attr( $admin_bar_light_logo_text_typo['font-weight'] ) . ';';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['text-transform'] ) ) {
            $light_logo_css .= 'text-transform: ' . esc_attr( $admin_bar_light_logo_text_typo['text-transform'] ) . ';';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['text-decoration'] ) ) {
            $light_logo_css .= 'text-decoration: ' . esc_attr( $admin_bar_light_logo_text_typo['text-decoration'] ) . ';';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['line-height'] ) ) {
            $light_logo_css .= 'line-height: ' . esc_attr( $admin_bar_light_logo_text_typo['line-height'] ) . 'px;';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['letter-spacing'] ) ) {
            $light_logo_css .= 'letter-spacing: ' . esc_attr( $admin_bar_light_logo_text_typo['letter-spacing'] ) . 'px;';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['word-spacing'] ) ) {
            $light_logo_css .= 'word-spacing: ' . esc_attr( $admin_bar_light_logo_text_typo['word-spacing'] ) . 'px;';
        }
        if ( !empty( $admin_bar_light_logo_text_typo['color'] ) ) {
            $light_logo_css .= 'color: ' . esc_attr( $admin_bar_light_logo_text_typo['color'] ) . ';';
        }
        $light_logo_css .= '}';
        // Logo Text Dark
        $admin_bar_logo_dark = $this->options['light_dark_mode']['admin_ui_dark_mode']['admin_ui_dark_logo_text_typo'];
        $dark_logo_css = '&.adminify-dark-mode .navbar .wp-adminify-site-name {';
        if ( !empty( $admin_bar_logo_dark['font-size'] ) ) {
            $dark_logo_css .= 'font-size: ' . esc_attr( $admin_bar_logo_dark['font-size'] ) . 'px;';
        }
        if ( !empty( $admin_bar_logo_dark['font-family'] ) ) {
            $dark_logo_css .= 'font-family: ' . esc_attr( $admin_bar_logo_dark['font-family'] ) . ';';
        }
        if ( !empty( $admin_bar_logo_dark['font-weight'] ) ) {
            $dark_logo_css .= 'font-weight: ' . esc_attr( $admin_bar_logo_dark['font-weight'] ) . ';';
        }
        if ( !empty( $admin_bar_logo_dark['text-transform'] ) ) {
            $dark_logo_css .= 'text-transform: ' . esc_attr( $admin_bar_logo_dark['text-transform'] ) . ';';
        }
        if ( !empty( $admin_bar_logo_dark['text-decoration'] ) ) {
            $dark_logo_css .= 'text-decoration: ' . esc_attr( $admin_bar_logo_dark['text-decoration'] ) . ';';
        }
        if ( !empty( $admin_bar_logo_dark['line-height'] ) ) {
            $dark_logo_css .= 'line-height: ' . esc_attr( $admin_bar_logo_dark['line-height'] ) . 'px;';
        }
        if ( !empty( $admin_bar_logo_dark['letter-spacing'] ) ) {
            $dark_logo_css .= 'letter-spacing: ' . esc_attr( $admin_bar_logo_dark['letter-spacing'] ) . 'px;';
        }
        if ( !empty( $admin_bar_logo_dark['word-spacing'] ) ) {
            $dark_logo_css .= 'word-spacing: ' . esc_attr( $admin_bar_logo_dark['word-spacing'] ) . 'px;';
        }
        if ( !empty( $admin_bar_logo_dark['color'] ) ) {
            $dark_logo_css .= 'color: ' . esc_attr( $admin_bar_logo_dark['color'] ) . ';';
        }
        $dark_logo_css .= '}';
        return $light_logo_css . $dark_logo_css;
    }

    public function jltwp_adminify_admin_ui_preset_vars() {
        global $pagenow;
        if ( $pagenow == 'wp-login.php' || $pagenow == 'wp-register.php' || $pagenow == 'customize.php' ) {
            return;
        }
        // CSS for Adminify UI
        if ( !empty( $this->options['admin_ui'] ) ) {
            if ( array_key_exists( 'adminify_theme', $this->options ) && !empty( $this->options['adminify_theme'] ) ) {
                $theme = $this->options['adminify_theme'];
            } else {
                $theme = 'preset1';
                // get the default value dynamically
            }
            $preset = (array) Utils::get_theme_presets( $theme );
            if ( empty( $preset ) ) {
                $custom_preset = [
                    '--adminify-menu-hover-bg'                  => $this->options['adminify_theme_custom_colors']['--adminify-primary'],
                    '--adminify-menu-text-hover-color'          => '#ffffff',
                    '--adminify-menu-active-bg'                 => $this->options['adminify_theme_custom_colors']['--adminify-primary'],
                    '--adminify-menu-active-color'              => '#ffffff',
                    '--adminify-submenu-wrapper-bg'             => $this->options['adminify_theme_custom_colors']['--adminify-menu-bg'],
                    '--adminify-submenu-hover-bg'               => 'transparent',
                    '--adminify-submenu-text-color'             => $this->options['adminify_theme_custom_colors']['--adminify-menu-text-color'],
                    '--adminify-submenu-text-hover-color'       => $this->options['adminify_theme_custom_colors']['--adminify-primary'],
                    '--adminify-submenu-active-bg'              => 'transparent',
                    '--adminify-submenu-active-color'           => $this->options['adminify_theme_custom_colors']['--adminify-primary'],
                    '--adminify-menu-border'                    => '#7062cd',
                    '--adminify-notif-color'                    => '#ffffff',
                    '--adminify-menu-vertical-padding'          => '10px',
                    '--adminify-menu-horizontal-padding'        => '8px',
                    '--adminify-menu-wrapper-padding-top'       => '16px',
                    '--adminify-menu-wrapper-padding-right'     => '8px',
                    '--adminify-menu-wrapper-padding-bottom'    => '16px',
                    '--adminify-menu-wrapper-padding-left'      => '8px',
                    '--adminify-submenu-vertical-padding'       => '4px',
                    '--adminify-submenu-wrapper-padding-top'    => '8px',
                    '--adminify-submenu-wrapper-padding-right'  => '0px',
                    '--adminify-submenu-wrapper-padding-bottom' => '8px',
                    '--adminify-submenu-wrapper-padding-left'   => '28px',
                    '--adminify-menu-font-size'                 => '13px',
                    '--adminify-menu-line-height'               => '20px',
                    '--adminify-menu-letter-spacing'            => '',
                    '--adminify-menu-width'                     => '260px',
                ];
                $preset = array_merge( $custom_preset, $this->options['adminify_theme_custom_colors'] );
            }
            // Dynamic Css Variables Array
            $css_var = $this->jltwp_adminify_output_styles();
            $preset_style = '';
            foreach ( $preset as $prop => $val ) {
                // Check Duplicate Array
                if ( !array_key_exists( $prop, $css_var ) ) {
                    $preset_style .= sprintf( '%s:%s;', esc_attr( $prop ), esc_attr( $val ) );
                }
            }
            foreach ( $css_var as $prop => $val ) {
                $preset_style .= sprintf( '%s:%s;', esc_attr( $prop ), esc_attr( $val ) );
            }
            if ( empty( $preset_style ) ) {
                return;
            }
            // Text Logo styles
            // if($this->options['light_dark_mode']['admin_ui_logo_type'] === 'text_logo') {
            // 	// $text_logo_css = $this->jltwp_adminify_logo_text_styles();
            // 	if(!empty($text_logo_css)) {
            // 		printf('<style id="adminify_text_logo">body.adminify-ui{%s}</style>', wp_strip_all_tags($text_logo_css));
            // 	}
            // }
            printf( '<style>body.wp-adminify{%s}</style>', wp_strip_all_tags( $preset_style ) );
            wp_enqueue_script(
                'adminify-theme-presetter',
                WP_ADMINIFY_ASSETS . 'admin/js/wp-adminify-theme-presetter.js',
                ['jquery'],
                null,
                true
            );
            wp_localize_script( 'adminify-theme-presetter', 'adminify_preset_themes', Utils::get_theme_presets() );
        } else {
            printf( '<style>body.wp-adminify{%s}</style>', wp_strip_all_tags( $this->jltwp_adminify_output_styles() ) );
        }
    }

}
