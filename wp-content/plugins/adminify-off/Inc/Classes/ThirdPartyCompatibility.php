<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Modules\MenuEditor\MenuEditorOptions;
// no direct access allowed
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * WPAdminify
 * Third Party Plugins Compatibility
 *
 * @author Jewel Theme <support@jeweltheme.com>
 */
class ThirdPartyCompatibility {
    public $menu_settings;

    public function __construct() {
        $this->menu_settings = ( new MenuEditorOptions() )->get();
        add_action( 'init', [$this, 'register_actions_on_init'], 0 );
        add_action( 'admin_init', [$this, 'register_actions_on_admin_init'], 0 );
        // 24-3-24
        add_action( 'admin_enqueue_scripts', [$this, 'jltwp_adminify_reset_theme_conflicts'], 999999 );
        // 28-6-24
        add_action( 'admin_head', [$this, 'jltwp_adminify_plugin_conflicts'], 999999 );
    }

    public function jltwp_adminify_plugin_conflicts() {
        $adminify_ui = AdminSettings::get_instance()->get( 'admin_ui' );
        if ( !empty( $adminify_ui ) ) {
            // Motopress Hotel Booking Lite
            if ( Utils::is_plugin_active( 'motopress-hotel-booking-lite/motopress-hotel-booking.php' ) ) {
                echo '<style>
				.wp-adminify.bookings_page_mphb_calendar .widefat thead th,
				.wp-adminify.bookings_page_mphb_calendar .widefat tfoot th,
				.wp-adminify.bookings_page_mphb_calendar .widefat tbody td {
					padding: 0 !important;
				}
				.adminify-ui .widefat tbody tr td:first-child {
					border-left-color: inherit !important;
				}
				</style>';
            }
        }
        if ( Utils::is_plugin_active( 'squirrly-seo/squirrly.php' ) ) {
            echo '<style>
            .wp-adminify.adminify-ui.block-editor-page .interface-interface-skeleton {
                top: 0 !important;
            }
            </style>';
        }
        if ( Utils::is_plugin_active( 'surecart / surecart.php' ) || Utils::is_plugin_active( 'suremember/suremember.php' ) ) {
            if ( !empty( $adminify_ui ) ) {
                echo '<style>
							.css-wa3qun,.backdrop-blur-sm {
								top: 0 !important;
							}
							#suremembers-settings-content::before, #sc-settings-content::before{
									width: 250px !important;
							}
						</style>';
            }
        }
        if ( Utils::is_plugin_active( 'advanced-database-cleaner-pro/advanced-db-cleaner.php' ) ) {
            echo '<style>
            .wp-adminify.toplevel_page_advanced_db_cleaner #wpbody-content .wp-list-table.widefat td{
                padding: 10px 10px;
            }
			.wp-adminify.toplevel_page_advanced_db_cleaner #wpbody-content input#aDBc_keep_button_revision {
				padding: 0px 3px !important;
				line-height: inherit !important;
			}
            </style>';
        }
        if ( Utils::is_plugin_active( 'insert-headers-and-footers/ihaf.php' ) ) {
            echo '<style>
            .wp-adminify .wpcode-code-type-picker, .wp-adminify .wpcode-code-type-picker-backdrop {
                left: 0;
            }
            </style>';
        }
        if ( Utils::is_plugin_active( 'advanced-access-manager/aam.php' ) ) {
            add_filter(
                'jltwp_adminify_menu_option_compatibility_filter',
                array($this, 'apply_menu_restrictions_via_filter'),
                10,
                2
            );
        }
        if ( Utils::is_plugin_active( 'meta-box/meta-box.php' ) ) {
            echo '<style>
				.adminify-ui .og.mb {
							left: 0;
					}
				</style>';
        }
        if ( Utils::is_plugin_active( 'shopengine/shopengine.php' ) ) {
            global $pagenow;
            // e.g. "edit.php"
            $post_type = ( isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : '' );
            // Build the slug
            $slug = ( $post_type ? "{$pagenow}?post_type={$post_type}" : $pagenow );
            // Example usage:
            if ( $slug === 'edit.php?post_type=shopengine-template' ) {
                echo '<style>
								#wpfooter {
										position: inherit !important;
										display: flex;
										flex-direction: column-reverse;
								}
						</style>';
            }
        }
    }

    public function apply_menu_restrictions_via_filter( $menu_options, $menu ) {
        $aam_options = get_option( 'aam_access_settings', [] );
        if ( is_array( $aam_options ) ) {
            $simplified_aam_option = $this->simplify_aam_menu_restriction( $aam_options );
            $menu_options = $this->update_aam_menu_option( $menu_options, $simplified_aam_option );
        }
        return $menu_options;
    }

    public function simplify_aam_menu_restriction( $aam_option ) {
        $simplified_aam_option = [];
        foreach ( $aam_option['role'] as $role => $aam_role ) {
            if ( !empty( $aam_role['menu'] ) ) {
                foreach ( $aam_role['menu'] as $menu_item => $value ) {
                    if ( !$value ) {
                        continue;
                    }
                    $menu_item = str_replace( 'menu-', '', $menu_item );
                    if ( !isset( $simplified_aam_option[$menu_item] ) ) {
                        $simplified_aam_option[$menu_item] = [];
                    }
                    $simplified_aam_option[$menu_item][] = $role;
                }
            }
        }
        return $simplified_aam_option;
    }

    public function update_aam_menu_option( $menu_options, $simplified_aam_option ) {
        foreach ( $menu_options as $key => $item ) {
            // If this menu item is in restricted list, update hidden_for
            if ( isset( $simplified_aam_option[$key] ) ) {
                if ( !isset( $item['hidden_for'] ) ) {
                    $item['hidden_for'] = [];
                }
                $item['hidden_for'] = array_merge( $item['hidden_for'], $simplified_aam_option[$key] );
                $item['hidden_for'] = array_unique( $item['hidden_for'] );
            }
            // If submenu exists, apply the function recursively
            // if (isset($item['submenu']) && is_array($item['submenu'])) {
            //     $item['submenu'] = update_aam_menu_option($item['submenu'], $simplified_aam_option);
            // }
            $menu_options[$key] = $item;
        }
        return $menu_options;
    }

    public function jltwp_adminify_reset_theme_conflicts() {
        global $pagenow;
        if ( $pagenow == 'wp-login.php' || $pagenow == 'wp-register.php' || $pagenow == 'customize.php' ) {
            return;
        }
        $screen = get_current_screen();
        $theme = wp_get_theme();
        // Neve WordPress Theme
        if ( 'Neve' == $theme->name || 'Neve' == $theme->parent_theme ) {
            wp_enqueue_style(
                'wp-adminify_neve-theme',
                WP_ADMINIFY_ASSETS . 'css/themes/neve.min.css',
                false,
                WP_ADMINIFY_VER
            );
        }
        // Phlox WordPress Theme
        if ( 'Phlox' == $theme->name || 'Phlox' == $theme->parent_theme ) {
            echo '<style>
            .wp-adminify.adminify-ui #wpcontent .wp-adminify.adminify-top_bar {
                opacity: 1 !important;
            }
            </style>';
        }
        // Enfold WordPress Theme
        if ( 'Enfold' == $theme->name || 'Enfold' == $theme->parent_theme ) {
            echo '<style>
				.wp-adminify.avia-advanced-editor-enabled #wpadminbar{
					display: none !important;
				}
            </style>';
        }
        // Third Party Plugin CSS Conflict
        // $jltwp_adminify_plugin_conflict_css = '';
        // if (Utils::is_plugin_active('quillforms/quillforms.php')) {
        // $jltwp_adminify_plugin_conflict_css = '//css code here';
        // $jltwp_adminify_plugin_conflict_css = preg_replace('#/\*.*?\*/#s', '', $jltwp_adminify_plugin_conflict_css);
        // $jltwp_adminify_plugin_conflict_css = preg_replace('/\s*([{}|:;,])\s+/', '$1', $jltwp_adminify_plugin_conflict_css);
        // $jltwp_adminify_plugin_conflict_css = preg_replace('/\s\s+(.*)/', '$1', $jltwp_adminify_plugin_conflict_css);
        // }
        // $adminify_ui = AdminSettings::get_instance()->get('admin_ui');
        // if (!empty($adminify_ui)) {
        // wp_add_inline_style('wp-adminify-admin', wp_strip_all_tags($jltwp_adminify_plugin_conflict_css));
        // } else {
        // wp_add_inline_style('wp-adminify-default-ui', wp_strip_all_tags($jltwp_adminify_plugin_conflict_css));
        // }
        if ( Utils::is_plugin_active( 'stackable-ultimate-gutenberg-blocks-premium/plugin.php' ) ) {
            echo '<style>
            .wp-adminify.adminify-ui #wpcontent .wp-adminify.adminify-top_bar {
                opacity: 1 !important;
            }
            </style>';
        }
        if ( Utils::is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            echo '<style>
			.adminify-ui #woocommerce-embedded-root .woocommerce-layout__header, .adminify-ui .woocommerce-layout .woocommerce-layout__header {
				width: 100%;
				top: 0;
			}
			.adminify-ui #woocommerce-embedded-root .woocommerce-layout__header .woocommerce-layout__header-wrapper, .adminify-ui .woocommerce-layout .woocommerce-layout__header .woocommerce-layout__header-wrapper  {
				margin-right: 1rem;
			}
			body.woocommerce-page #wpwrap #wpcontent,
			body.woocommerce-page.woocommerce_page_wc-admin #wpwrap #wpbody-content {
					overflow-x: unset !important;
					position: unset !important;
			}
            </style>';
        }
        if ( Utils::is_plugin_active( 'tinymce-templates/tinymce-templates.php' ) ) {
            echo '<style>#tinymce-templates-wrap #tinymce-templates-preview { height: 500px !important; }</style>';
        }
        if ( Utils::is_plugin_active( 'elementor/elementor.php' ) ) {
            echo '<style>
                body.wp-adminify-admin-bar.admin-bar .dialog-lightbox-widget {
                    height: calc(100vh - 0px) !important;
                }
                body.wp-adminify-admin-bar.admin-bar #e-admin-top-bar-root{
                    padding-top: 70px !important;
                    width: calc(100% + 20px) !important;
                    margin-left: -20px;
                }
                body.wp-adminify-admin-bar.admin-bar #e-admin-top-bar-root .e-admin-top-bar{
                    padding-left: 20px;
                }
            </style>';
        }
        if ( Utils::is_plugin_active( 'one-click-demo-import/one-click-demo-import.php' ) ) {
            echo '<style>
                .wp-adminify.appearance_page_one-click-demo-import .button-hero {
                    min-height: auto !important;
                }
            </style>';
        }
        if ( Utils::is_plugin_active( 'wpforms-lite/wpforms.php' ) ) {
            if ( Utils::jltwp_adminify_currentpage_id( 'dashboard' ) ) {
                wp_enqueue_style(
                    'wpforms-full',
                    WPFORMS_PLUGIN_URL . 'assets/css/frontend/classic/wpforms-full.css',
                    [],
                    WPFORMS_VERSION
                );
            }
        }
        if ( Utils::is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
            echo '<style>
                #ed_toolbar{
                    width: 100% !important;
                }
                #ed_toolbar #qt_content_dfw{
                    line-height: inherit;
                    padding: 0;
                }
            </style>';
        }
        if ( Utils::is_plugin_active( 'updraftplus/updraftplus.php' ) ) {
            echo '<style>
				.wp-adminify.settings_page_updraftplus input{
					border-radius: 4px !important;
					border: 1px solid #CCC !important;
				}
                .wp-adminify.adminify-dark-mode .updraft_feat_table,
                .wp-adminify.adminify-dark-mode .updraft_feat_table td,
                .wp-adminify.adminify-dark-mode .updraft_next_scheduled_backups_wrapper > div,
                .wp-adminify.adminify-dark-mode .updraft_migrate_widget_module_content{
                    background: inherit;
                }
                .wp-adminify.adminify-dark-mode .updraft_premium_cta,
                .wp-adminify.adminify-dark-mode .updraft_premium_cta__bottom,
                .wp-adminify.adminify-dark-mode .udp-box,
                .wp-adminify.adminify-dark-mode .updraftcentral_cloud_connect,
                .wp-adminify.adminify-dark-mode .updraft_advert_bottom,
                .wp-adminify.adminify-dark-mode #remote-storage-container label{
                    background: #020407;
                }
                .wp-adminify.adminify-dark-mode .expertmode .advanced_settings_container .advanced_settings_menu .advanced_tools_button{
                    color: inherit;
                }
            </style>';
        }
        if ( Utils::is_plugin_active( 'tinymce-advanced/tinymce-advanced.php' ) ) {
            echo '<style>
                .wp-editor-container .mce-container-body .mce-menubar.mce-toolbar{
                    position: initial !important;
                }
                .wp-editor-container .mce-container-body .mce-toolbar-grp .mce-container-body{
                    position: relative !important;
                }
                .wp-editor-container .mce-container-body .mce-toolbar-grp .mce-toolbar.mce-first {
                    padding-right: 0 !important;
                }
                #ed_toolbar{
                    width: 100% !important;
                }
                #ed_toolbar #qt_content_dfw{
                    line-height: inherit;
                    padding: 0;
                }
            </style>';
        }
        if ( Utils::is_plugin_active( 'email-template-customizer-for-woo/email-template-customizer-for-woo.php' ) ) {
            if ( $screen->id === 'edit-viwec_template' ) {
                echo '<style>
								#wpfooter{
									position: relative !important;
									display: flex;
									flex-direction: column-reverse;
								}
							</style>';
            }
        }
        // Third Party localize script
        wp_localize_script( 'wp-adminify-admin', 'WPAdminify_ThirdParty', $this->thirdparty_create_js_object() );
    }

    public function thirdparty_create_js_object() {
        // betterlinks menu settings
        $betterlinks = [
            'active' => false,
        ];
        if ( Utils::is_plugin_active( 'betterlinks/betterlinks.php' ) ) {
            if ( is_array( $this->menu_settings ) && array_key_exists( 'betterlinks', $this->menu_settings ) ) {
                $menu_name = ( !empty( $this->menu_settings['betterlinks']['name'] ) ? esc_html( $this->menu_settings['betterlinks']['name'] ) : '' );
                $submenu_manage = ( !empty( $this->menu_settings['betterlinks']['submenu']['betterlinks']['name'] ) ? esc_html( $this->menu_settings['betterlinks']['submenu']['betterlinks']['name'] ) : '' );
                $submenu_name = ( !empty( $this->menu_settings['betterlinks']['submenu']['betterlinks-analytics']['name'] ) ? esc_html( $this->menu_settings['betterlinks']['submenu']['betterlinks-analytics']['name'] ) : '' );
                $submenu_settings = ( !empty( $this->menu_settings['betterlinks']['submenu']['betterlinks-settings']['name'] ) ? esc_html( $this->menu_settings['betterlinks']['submenu']['betterlinks-settings']['name'] ) : '' );
                $betterlinks = [
                    'active'           => true,
                    'menu_name'        => esc_html( $menu_name ),
                    'submenu_manage'   => esc_html( $submenu_manage ),
                    'submenu_name'     => esc_html( $submenu_name ),
                    'submenu_settings' => esc_html( $submenu_settings ),
                ];
            }
        }
        return [
            'ajax_url'     => admin_url( 'admin-ajax.php' ),
            'better_links' => wp_kses_post_deep( $betterlinks ),
        ];
    }

    public function register_actions_on_init() {
        // Brizy Builder
        if ( isset( $_REQUEST['brizy-edit-iframe'] ) ) {
            add_filter( 'wp_adminify_defer_skip', '__return_true' );
            add_filter( 'wp_adminify_skip_removing_dashicons', '__return_true' );
        }
    }

    public function register_actions_on_admin_init() {
        $adminify_ui = AdminSettings::get_instance()->get( 'admin_ui' );
        if ( !empty( $adminify_ui ) ) {
            // Commented On: 24-3-24
            add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], 999 );
        }
        // Commented On: 10-6-24
        // add_filter('adminify_third_party_styles', [$this, 'register_compatability_styles']);
        // Fluent CRM
        add_action( 'fluentcrm_skip_no_conflict', '__return_true' );
        // Fluent FORM
        add_action( 'fluentform_skip_no_conflict', '__return_true' );
    }

    /**
     * Register Third Party Styles
     *
     * @since 1.0.0
     */
    public function register_compatability_styles( $plugin_supports ) {
        if ( !is_array( $plugin_supports ) ) {
            $plugin_supports = [];
        }
        $plugin_dir = WP_ADMINIFY_ASSETS . 'css/plugins/';
        $plugin_files = list_files( WP_ADMINIFY_PATH . 'assets/css/plugins/', 1 );
        if ( !empty( $plugin_files ) ) {
            foreach ( $plugin_files as $file ) {
                $plugin_supports[wp_basename( $file, '.min.css' )] = $plugin_dir . wp_basename( $file );
            }
        }
        return $plugin_supports;
    }

    /**
     * Admin Enqueue Third Party Scripts/Styles
     *
     * @return void
     */
    public function enqueue_scripts() {
        $plugin_supports = [];
        $plugin_supports = apply_filters( 'adminify_third_party_styles', $plugin_supports );
        // Check Plugin Activated for Site Wide
        if ( is_multisite() ) {
            $active_plugins = get_site_option( 'active_sitewide_plugins' );
            foreach ( $active_plugins as $active_path => $active_plugin ) {
                if ( is_plugin_active_for_network( $active_path ) ) {
                    $string = explode( '/', $active_path );
                    $pluginname = $string[0];
                    if ( isset( $plugin_supports[$pluginname] ) ) {
                        if ( $plugin_supports[$pluginname] != '' ) {
                            wp_register_style(
                                'wp-adminify_site-wide_' . $pluginname . '_css',
                                $plugin_supports[$pluginname],
                                [],
                                WP_ADMINIFY_VER
                            );
                            wp_enqueue_style( 'wp-adminify_site-wide_' . $pluginname . '_css' );
                        }
                    }
                }
            }
        }
        // Check Plugin Activated for Individual Sites
        $activeplugins = get_option( 'active_plugins' );
        foreach ( $activeplugins as $plugin ) {
            if ( Utils::is_plugin_active( $plugin ) ) {
                $string = explode( '/', $plugin );
                $pluginname = $string[0];
                if ( isset( $plugin_supports[$pluginname] ) ) {
                    if ( $plugin_supports[$pluginname] != '' ) {
                        wp_register_style(
                            'wp-adminify_' . $pluginname . '_css',
                            $plugin_supports[$pluginname],
                            [],
                            WP_ADMINIFY_VER
                        );
                        wp_enqueue_style( 'wp-adminify_' . $pluginname . '_css' );
                    }
                }
            }
        }
    }

}
