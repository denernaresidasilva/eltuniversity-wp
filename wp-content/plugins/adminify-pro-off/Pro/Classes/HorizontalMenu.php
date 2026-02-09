<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Admin\AdminSettingsModel;

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}

class HorizontalMenu extends AdminSettingsModel
{
	public $options;
	public function __construct()
	{
		$this->options = (array) AdminSettings::get_instance()->get('menu_layout_settings');

		if (!empty($this->options['layout_type']) && $this->options['layout_type'] !== 'horizontal') {
			return;
		}

		$adminify_ui = AdminSettings::get_instance()->get('admin_ui');
		if (empty($adminify_ui)) {
			return;
		}

		// Horizontal menu: Add new Admin menu on Header
		add_filter('in_admin_header', [$this, 'jltwp_adminify_horizontal_menu'], -99999);
		add_action('admin_head', [$this, 'jltwp_adminify_horizontal_menu_scripts'], 9999);
		add_filter('admin_body_class', [$this, 'jltwp_adminify_admin_menu_body_class']);
	}

	// Body Class
	public function jltwp_adminify_admin_menu_body_class($classes)
	{
		$admin_ui_mode = (array) AdminSettings::get_instance()->get();
		$admin_ui_mode = (!empty($admin_ui_mode['light_dark_mode']['admin_ui_mode'])) ? $admin_ui_mode['light_dark_mode']['admin_ui_mode'] : 'light';

		$classes .= ' horizontal-menu ';

		if ($admin_ui_mode == 'light') {
			$classes .= ' horizontal-light-mode ';
		}
		if ($admin_ui_mode == 'dark') {
			$classes .= ' horizontal-dark-mode ';
		}

		return $classes;
	}

	public function jltwp_adminify_horizontal_menu()
	{
		global $self, $menu, $submenu, $parent_file, $submenu_file, $plugin_page, $pagenow;

		$current_screen = get_current_screen();
		if (!empty($current_screen->is_block_editor)) {
			return;
		}


		$menu    = apply_filters('wp_adminify_adminmenu_menu', $menu);
		$submenu = apply_filters('wp_adminify_adminmenu_submenu', $submenu);

		$wp_adminify_menu = '<div id="adminify-horizontal-menu-wrapper" class="wp-adminify-horizontal-menu"><ul class="horizontal-menu">';

		$wp_adminify_menu = apply_filters('wp_adminify_adminmenu_adminify_menu', $wp_adminify_menu);

		if (isset($this->options['show_bloglink']) && $this->options['show_bloglink']) {
			$wp_adminify_menu .= '<li id="wp_adminify_bloglink" class="wp_adminify_toplevel navbar-item">' . wp_kses_post($this->jltwp_adminify_blogtitle()) . '</li>';
		}

		$first = true;
		// 0 = menu_title, 1 = capability, 2 = menu_slug, 3 = page_title, 4 = classes, 5 = hookname, 6 = icon_url.
		foreach ($menu as $key => $item) {

			$admin_is_parent = false;
			$class           = [];
			$aria_attributes = '';
			$aria_hidden     = '';
			$is_separator    = false;

			// Top level menu
			if (strpos($item[4], 'wp-menu-separator') !== false) {
				continue;
			}

			if ($first) {
				$class[] = 'wp-first-item';
				$first   = false;
			}

			if (!empty($submenu[$item[2]])) {
				if (!empty($submenu[$item[2]])) {
					$class[]       = 'wp-has-submenu navbar-link';
					$submenu_items = $submenu[$item[2]];
				}
			}

			if (($parent_file && $item[2] === $parent_file) || (empty($typenow) && $self === $item[2])) {
				if (!empty($submenu_items)) {
					$class[] = 'wp-has-current-submenu current wp-menu-open';
				} else {
					$class[]          = 'current';
					$aria_attributes .= 'aria-current="page"';
				}
			} else {
				$class[] = 'wp-not-current-submenu';
				if (!empty($submenu_items)) {
					$aria_attributes .= 'aria-haspopup="true"';
				}
			}

			if (!empty($item[4])) {
				$class[] = esc_attr($item[4]);
			}

			$class  = $class ? ' class="' . join(' ', $class) . '"' : '';
			$id     = isset($item[5]) && !empty($item[5]) ? 'wp_adminify_' . esc_attr($item[5]) : '';
			$anchor = $item[0];

			if (isset($submenu_as_parent) && !empty($submenu[$item[2]])) {
				$submenu_items = array_values($submenu_items);  // Re-index.
				$menu_hook     = get_plugin_page_hook($submenu_items[0][2], $item[2]);
				$menu_file     = $submenu_items[0][2];
				$pos           = strpos($menu_file, '?');

				if (false !== $pos) {
					$menu_file = substr($menu_file, 0, $pos);
				}

				if (!empty($menu_hook) || (('index.php' !== $submenu_items[0][2]) && file_exists(WP_PLUGIN_DIR . "/$menu_file") && !file_exists(ABSPATH . "/wp-admin/$menu_file"))) {
					$admin_is_parent = true;
					echo '<a href="admin.php?page=' . esc_attr($submenu_items[0][2]) . '" ' . esc_attr($class) . ' ' . esc_attr($aria_attributes) . '>' . wp_kses_post($arrow) . '<div class="wp-menu-image ' . esc_attr($img_class) . '" ' . esc_attr($img_style) . ' aria-hidden="true">' . wp_kses_post($img) . '</div><div class="wp-menu-name">' . esc_html($title) . '</div></a>';
				} else {
					echo '<a href="' . esc_attr($submenu_items[0][2]) . '"  ' . esc_attr($class) . ' ' . esc_attr($aria_attributes) . '>' . wp_kses_post($arrow) . '<div class="wp-menu-image ' . esc_attr($img_class) . '" ' . esc_attr($img_style) . ' aria-hidden="true">' . wp_kses_post($img) . '</div><div class="wp-menu-name">' . esc_html($title) . '</div></a>';
				}
			} elseif (current_user_can($item[1])) {
				$menu_hook = get_plugin_page_hook($item[2], 'admin.php');
				if (('index.php' != $item[2]) && file_exists(WP_PLUGIN_DIR . "/{$item[2]}") || !empty($menu_hook)) {
					$admin_is_parent = true;
					$href            = 'admin.php?page=' . esc_attr($item[2]);
				} else {
					$href = $item[2];
				}
			}

			$horz_menu_type = !empty($this->options['horz_menu_type']) ? $this->options['horz_menu_type'] : 'both';
			$imgstyle       = ($horz_menu_type == 'both' || $horz_menu_type == 'icons_only') ? '' : 'style="display:none"';
			$img            = '';
			if (isset($item[6]) && !empty($item[6])) {
				preg_match('/^dashicons/', $item[6], $matches);
				if ('none' === $item[6] || 'div' === $item[6]) {
					$img = '<i ' . esc_attr($imgstyle) . ' class="wp-menu-image"><br /></i>';
				} elseif (!empty($matches)) {
					$img = '<i ' . esc_attr($imgstyle) . ' class="wp-menu-image dashicons ' . esc_attr($item[6]) . '"><br /></i>';
				} else {
					// $img = '<img ' . esc_attr($imgstyle) . ' class="wp-menu-image" src="' . $item[6] . '" alt="" />';
					$img = sprintf('<img ' . esc_attr($imgstyle) . ' class="wp-menu-image" src="%1$s%2$s">', 'data:', $item[6] );
				}
			}

			// Make Top Menu Item Links Clickable
			if (!empty($this->options['horz_toplinks'])) {
				$href = "href='$href'";
			} else {
				$href = (!empty($submenu[$item[2]])) ? '' : ('href=' . esc_url($href));
			}

			$is_dropdown = !empty($submenu[$item[2]]) ? ' has-dropdown dropdown ' : ' ';

			if ($horz_menu_type == 'icons_only') {
				$wp_adminify_menu .= '<li class="wp_adminify_toplevel navbar-item' . esc_attr($is_dropdown) . 'is-hoverable topmenu-' . esc_attr($id) . '" id="' . esc_attr($id) . '"><a ' . $href . ' ' . esc_attr($class) . '>' . wp_kses_post( $img ) . '</a>';
			} elseif ($horz_menu_type == 'text_only') {
				$wp_adminify_menu .= '<li class="wp_adminify_toplevel navbar-item' . esc_attr($is_dropdown) . 'is-hoverable topmenu-' . esc_attr($id) . '" id="' . esc_attr($id) . '"><a ' . $href . ' ' . esc_attr($class) . '><span class="compact">' . wp_kses_post($anchor) . '</span></a>';
			} elseif ($horz_menu_type == 'both') {
				$wp_adminify_menu .= '<li class="wp_adminify_toplevel navbar-item' . esc_attr($is_dropdown) . 'is-hoverable topmenu-' . esc_attr($id) . '" id=' . esc_attr($id) . '><a ' . $href . ' ' . esc_attr($class) . '>' . wp_kses_post($img) . ' ' . wp_kses_post($anchor) . '</a>';
			}

			// Sub level menus
			if (!empty($submenu[$item[2]])) {
				if (!isset($ulclass)) {
					$ulclass = 'navbar-dropdown adminify-dropdown-menu is-boxed';
				}

				if ($horz_menu_type == 'icons_only') {
					$ulclass .= ' adminify-icons-only';
				}

				$wp_adminify_menu .= '<ul class="' . esc_attr($ulclass) . '">';
				$first             = true;
				foreach ($submenu[$item[2]] as $sub_key => $sub_item) {
					if (!current_user_can($sub_item[1])) {
						continue;
					}

					$class = [];
					if ($first) {
						$class[] = 'wp-first-item';
						$first   = false;
					}
					if (isset($submenu_file)) {
						if ($submenu_file == $sub_item[2]) {
							$class[] = 'current';
						}
						// If plugin_page is set the parent must either match the current page or not physically exist.
						// This allows plugin pages with the same hook to exist under different parents.
					} elseif ((isset($plugin_page) && $plugin_page == $sub_item[2] && (!file_exists($item[2]) || ($item[2] == $self))) || (!isset($plugin_page) && $self == $sub_item[2])) {
						$class[] = 'current';
					}

					$subclass = $class ? ' class="' . join(' ', $class) . '"' : '';

					$menu_hook = get_plugin_page_hook($sub_item[2], $item[2]);

					if ((('index.php' != $sub_item[2]) && file_exists(WP_PLUGIN_DIR . "/{$sub_item[2]}")) || !empty($menu_hook)) {
						// If admin.php is the current page or if the parent exists as a file in the plugins or admin dir
						$parent_exists = (!$admin_is_parent && file_exists(WP_PLUGIN_DIR . "/{$item[2]}") && !is_dir(WP_PLUGIN_DIR . "/{$item[2]}")) || file_exists($item[2]);
						if ($parent_exists) {
							$suburl = esc_attr($item[2]) . '?page=' . esc_attr($sub_item[2]);
						} elseif ('admin.php' == $pagenow || !$parent_exists) {
							$suburl = 'admin.php?page=' . esc_attr($sub_item[2]);
						} else {
							$suburl = esc_attr($item[2]) . '?page=' . esc_attr($sub_item[2]);
						}

						// Get icons?
						// if ($this->options['horz_submenu_icons']) {
						$plugin_icon = apply_filters('wp_adminify_menu_icon', esc_attr($sub_item[2]));
						$plugin_icon = apply_filters('wp_adminify_menu_icon_' . esc_attr($sub_item[2]), esc_attr($sub_item[2]));
						if ($plugin_icon != $sub_item[2]) {
							// we have an icon: no default plugin class & we store the icon location
							$plugin_icons[Utils::sanitize_id($sub_item[2])] = $plugin_icon;
							$icon = '';
						} else {
							// no icon: default plugin class
							$icon = 'wp_adminify_plugin';
						}
						// }
					} else {
						$suburl = esc_attr($sub_item[2]);
					}

					// Custom logout menu?
					if ($sub_item[2] == 'adminify_admin_menu_logout') {
						$suburl = wp_logout_url();
					}

					$subid     = 'adminifysub_' . Utils::sanitize_id($sub_item[2]);
					$subanchor = wp_kses_post($sub_item[0]);

					if (!isset($icon)) {
						$icon = '';
					}

					$wp_adminify_menu .= '<li class="wp_adminify_sublevel navbar-item ' . esc_attr($icon) . '" id="' . esc_attr($subid) . '"><a href="' . esc_attr($suburl) . '" ' . esc_attr($subclass) . '>' . wp_kses_post($subanchor) . '</a></li>';
				}

				$wp_adminify_menu .= '</ul>';
			}
			$wp_adminify_menu .= '</li>';
		}

		$wp_adminify_menu .= '</ul></div>';

		// Plugins: hack $wp_adminify_menu now it's complete
		$wp_adminify_menu = apply_filters('post_wp_adminify_menu', $wp_adminify_menu);

		if (isset($plugin_icons)) {
			global $text_direction;
			$align = ($text_direction == 'rtl' ? 'right' : 'left');
			echo "\n" . '<style type="text/css">' . "\n";
			foreach ($plugin_icons as $hook => $icon) {
				$hook = plugin_basename($hook);
				// echo "#adminifysub_$hook a {background-image:url($icon);}\n";
				echo '#adminifysub_' . esc_attr($hook) . ' a {background:url(' . esc_url($icon) . ') center ' . esc_attr($align) . ' no-repeat;}';
			}
			echo "</style>\n";
		}

		echo Utils::wp_kses_custom($wp_adminify_menu);
	}


	public function jltwp_adminify_blogtitle()
	{
		$blogname = get_bloginfo('name', 'display');
		if ('' == $blogname) {
			$blogname = '&nbsp;';
		}
		$title_class = '';
		if (function_exists('mb_strlen')) {
			if (mb_strlen($blogname, 'UTF-8') > 30) {
				$title_class = 'class="long-title"';
			}
		} else {
			if (strlen($blogname) > 30) {
				$title_class = 'class="long-title"';
			}
		}
		$url = trailingslashit(get_bloginfo('url'));

		return '<a ' . esc_attr($title_class) . ' href="' . esc_url($url) . '" title="' . __('Visit Site', 'adminify') . '" target="_blank">' . wp_kses_post($blogname) . ' &raquo;</a>';
	}



	public function jltwp_adminify_horizontal_menu_scripts()
	{
		$this->jltwp_adminify_horizontal_menu_css();
		// $this->jltwp_adminify_horizontal_menu_js();
	}

	public function jltwp_adminify_horizontal_menu_js()
	{
?>
		<script type="text/javascript">
			// Top level icons
			jQuery('[name="_wpadminify[menu_layout_settings][horz_toplinks]"]').click(function() {
				// console.log("Top Menu triggered");
			});
		</script>
<?php
	}

	public function jltwp_adminify_horizontal_menu_css()
	{
		global $text_direction;
		$dir = ($text_direction == 'rtl' ? 'right' : 'left');

		$menu_css = '';
		// Hide Original Menu
		$menu_css .= '#adminmenumain{display:none;}';
		$menu_css .= '#wpbody-content .wrap {
            margin-' . esc_attr($dir) . ':15px
        }';
		$menu_css .= '#media-upload-header #sidemenu li {
            display:auto;
        }';
		$menu_css .= '#wp-adminify-wrapper {
            overflow: visible;
            position: fixed;
            top: 32px;
        }';
		$menu_css .= '#screen-meta {
            display:none;
        }';
		$menu_css .= '#wpcontent {
            margin-top: 30px;
        }';
		$menu_css .= '@media screen and (max-width: 1030px) {
            #wpcontent {
                margin-top: 50px;
            }
        }';

		$menu_css .= '@media screen and (max-width: 959px) {
            #wpcontent {
                margin-left: 0px;
            }
            #adminmenuback {
                width: 0px;
            }
        }';
		$menu_css .= '@media screen and (max-width: 890px) {
            #wpadminbar .quicklinks > ul > li > a {
                padding: 0 4px;
            }
            #wpadminbar #wp-admin-bar-my-sites a.ab-item,
            #wpadminbar #wp-admin-bar-site-name a.ab-item {
                width: 80px;
                text-overflow: ellipsis;
            }
        }';

		$menu_css .= '@media screen and (max-width: 780px) {
            #wpadminbar .quicklinks > ul > li > a {
                padding: initial;
            }
            #wpadminbar #wp-admin-bar-my-sites a.ab-item,
            #wpadminbar #wp-admin-bar-site-name a.ab-item {
                width: 52px;
            }
            #adminify-horizontal-menu-wrapper {
                top: 46px;
            }
            #wpcontent {
                padding-left: 0px;
            }
        }';

		$menu_css .= '@media screen and (max-width: 599px) {
            #wpadminbar {
                position: fixed;
            }
            #adminify-horizontal-menu-wrapper {
                background-color: #4e4b66;
            }
        }';

		$menu_css .= '@media screen and (max-width: 590px) {
                #wpcontent {
                    margin-top: 108px;
                }
        }';

		$menu_css .= '@media screen and (max-width: 400px) {
            #wpcontent {
                margin-top: 110px;
            }
        }';
		$menu_css .= '@media screen and (max-width: 320px) {
            #wpcontent {
                margin-top: 130px;
            }
        }';

		$menu_css .= '#wpbody-content .wrap > h1 {
            margin-top: -3px;
            padding-top: 0;
        }';
		$menu_css .= '.wp_adminify_toplevel {
            margin: 0 3px;
        }';
		$menu_css .= 'span.update-plugins, span.awaiting-mod {
            top: 2px;
        }';

		$menu_css .= '#adminmenuback, #adminmenuwrap, #adminmenu,.folded #adminmenu .wp-submenu.sub-open, .folded #adminmenu .wp-submenu-wrap,.folded #adminmenuback, .folded #adminmenuwrap, .folded #adminmenu, .folded #adminmenu li.menu-top, .js.folded #adminmenuback, .js.folded #adminmenuwrap, .js.folded #adminmenu, .js.folded #adminmenu li.menu-top {
            width: 0;
        }';

		/* added for WP 3.2 */
		$menu_css .= '#adminmenuback, #adminmenuwrap, #adminmenu, .folded #adminmenu .wp-submenu.sub-open, .folded #adminmenu .wp-submenu-wrap, .folded #adminmenuback, .folded #adminmenuwrap, .folded #adminmenu, .folded #adminmenu li.menu-top, .js.folded #adminmenuback, .js.folded #adminmenuwrap, .js.folded #adminmenu, .js.folded #adminmenu li.menu-top {
            width: 0;
        }';
		$menu_css .= '#wpcontent, #footer, .folded #wpcontent, .folded #footer, .js.folded #wpcontent, .js.folded #footer {
            margin-left: 0px;
            padding-left:0px;
            margin-right:0px;
        } ';
		$menu_css .= '#wphead {
            background:#D1E5EE;
            margin-right:0px;
            margin-left:0px;
            padding-right:15px;
            padding-left:18px;
        }';

		// Comment/Update Bubble Show/Hide
		if (empty($this->options['horz_bubble_icon_hide'])) {
			/* Hide bubbles */
			$menu_css .= 'span.count-0 {display:none;}';
		}

		// Long Menu Break/Slide
		// horz_long_menu_break

		// Dropdown Icon Show/Hide
		if (empty($this->options['horz_dropdown_icon'])) {
			$menu_css .= '.wp-adminify.horizontal-menu .wp-adminify-horizontal-menu ul.horizontal-menu li.has-dropdown a:after {
                content: "";
            }';
			$menu_css .= '.wp-adminify.horizontal-menu .wp-adminify-horizontal-menu ul.horizontal-menu li.has-dropdown a{ padding-right:15px;}';
		}

		$menu_css .= '.wp-adminify.horizontal-menu .wp-adminify-horizontal-menu ul.horizontal-menu li a span[class*="count-"]{
            color: #fff;
            display: inline-block;
            font-size: 10px;
            min-width: 16px;
            line-height: 12px;
            -moz-border-radius: 3px;
            -webkit-border-radius: 3px;
            border-radius: 3px;
            right: 15px;
            top: 1px;
            position: absolute;
            text-align: center;
        }
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li a span[class*="count-"],
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li:hover a span[class*="count-"],
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li.current a span[class*="count-"] {
            background-color: #4e4b66;
            color: #fff;
        }
        .wp-adminify.horizontal-menu .wp-adminify-horizontal-menu ul.horizontal-menu li a span.awaiting-mod[class*="count-"] {
            top: 4px;
        }

        .wp-adminify.horizontal-menu .wp-adminify-horizontal-menu ul.horizontal-menu li a [class*="count-"] [class*="-count"]:before {
            content: "";
            width: 0px;
            height: 0px;
            position: absolute;
            border-left: 3px solid #4e4b66;
            border-right: 3px solid transparent;
            border-top: 5px solid #4e4b66;
            border-bottom: 5px solid transparent;
            right: 0;
            bottom: -6px;
        }
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li a span[class*="count-"] [class*="-count"]:before,
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li:hover a [class*="count-"] [class*="-count"]:before,
        .wp-adminify .wp-adminify-horizontal-menu ul.horizontal-menu li.current a [class*="count-"] [class*="-count"]:before {
            border-left-color: #4e4b66;
            border-top-color: #4e4b66;
        }';

		$menu_css .= '.topbar-disabled .adminify-top_bar {
            display: none;
        }
        .wp-adminify.horizontal-menu.topbar-disabled.sticky-menu .wp-adminify-horizontal-menu {
            position: inherit;
            top: 0;
        }
        .wp-adminify.horizontal-menu.topbar-disabled.sticky-menu #wpwrap,
        .wp-adminify.horizontal-menu.sticky-menu #wpcontent {
            margin-top: 0;
        }
        .wp-adminify.horizontal-menu.topbar-disabled #wpbody-content .adminify-options {
            margin-top: 20px;
        }';

		/* Hide all header */

		// $menu_css .= '#wpadminbar {display:none;}';
		$menu_css .= 'html.wp-toolbar{padding-top: 0px}';

		/* Just for IE7 */
		$menu_css .= '#wphead {
            #border-top-width: 31px;
        }';

		$menu_css .= '#wp_adminify .wp_adminify_sublevel a { padding-' . esc_attr($dir) . ':5px;}';
		$menu_css .= '#media-upload-header #sidemenu { display: block; }';

		$menu_css = preg_replace('#/\*.*?\*/#s', '', $menu_css);
		$menu_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $menu_css);
		$menu_css = preg_replace('/\s\s+(.*)/', '$1', $menu_css);

		// wp_register_style('horizontal-menu', false);
		wp_register_style('horizontal-menu', WP_ADMINIFY_ASSETS . 'css/wp-adminify-horizontal-menu' . Utils::assets_ext('.css'), false, WP_ADMINIFY_VER);
		wp_enqueue_style('horizontal-menu');

		// Add inline style.
		// wp_add_inline_style('horizontal-menu', wp_strip_all_tags($menu_css));
	}
}
