<?php

namespace WPAdminify\Inc\Classes;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Classes\DarkModeConflicts;
use WPAdminify\Inc\Admin\AdminSettingsModel;
// no direct access allowed
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class AdminBar extends AdminSettingsModel {
    public $options;

    public $post_types;

    public $adminify_ui;

    public function __construct() {
        $this->options = (array) AdminSettings::get_instance()->get();
        $this->adminify_ui = $this->options['admin_ui'];
        $global_ui_mode = ( empty( $this->options['light_dark_mode']['admin_ui_mode'] ) ? 'light' : $this->options['light_dark_mode']['admin_ui_mode'] );
        $current_ui_mode = ( empty( get_user_meta( get_current_user_id(), 'color_mode', true ) ) ? $global_ui_mode : get_user_meta( get_current_user_id(), 'color_mode', true ) );
        if ( $current_ui_mode === 'dark' ) {
            new DarkModeConflicts();
        }
        // $admin_bar_user_roles = !empty($this->options['admin_bar_user_roles']) ? $this->options['admin_bar_user_roles'] : '';
        // if (Utils::restricted_for($admin_bar_user_roles)) {
        // return;
        // }
        // Disable the default admin bar
        // add_filter('show_admin_bar', '__return_false');
        // Add admin-bar support if not already activated
        // add_theme_support('admin-bar', ['callback' => '__return_false']);
        // 05-06-2024
        // Check Adminify Setup Wizard Page
        if ( !empty( $_GET['page'] ) && 'wp-adminify-setup-wizard' == $_GET['page'] ) {
            return;
        }
        $this->initialize();
        // if (!empty($this->adminify_ui) && !empty($this->options['admin_bar_search'] )) {
        // 	add_action('wp_admin_bar_class', [$this, 'load_wp_admin_bar_class']);
        // 	add_action('adminify/before/secondary_menu', [$this, 'before_secondary_menu']);
        // }
        if ( is_admin() ) {
            // Switcher for Default UI
            if ( empty( $this->adminify_ui ) ) {
                if ( !empty( $this->options['admin_bar_dark_light_btn'] ) ) {
                    add_action( 'admin_bar_menu', [$this, 'jltwp_adminify_dark_mode_switcher_icon'] );
                }
            }
        }
    }

    // Add Switcher Icon on Admin Bar Secondary
    public function jltwp_adminify_dark_mode_switcher_icon( $wp_admin_bar ) {
        $args = [
            'parent' => 'top-secondary',
            'id'     => 'wp-adminify-admin-bar-switcher',
            'title'  => $this->jltwp_adminify_dark_mode_switcher_dom(),
            'meta'   => false,
        ];
        $wp_admin_bar->add_node( $args );
    }

    public function jltwp_adminify_dark_mode_switcher_dom() {
        $global_color_mode = ( !empty( $this->options['light_dark_mode']['admin_ui_mode'] ) ? $this->options['light_dark_mode']['admin_ui_mode'] : 'light' );
        $color_mode = ( empty( get_user_meta( get_current_user_id(), 'color_mode', true ) ) ? $global_color_mode : get_user_meta( get_current_user_id(), 'color_mode', true ) );
        $color_var = ( !empty( $this->adminify_ui ) ? 'var(--adminify-menu-text-color)' : '#fff' );
        $adminbar_switcher_icon = '<div id="wp-adminify-color-mode-wrapper">
			<div class="mode-icon adminify-color-mode-' . esc_attr( $color_mode ) . '-active">
                <svg viewBox="0 0 24 24" width="24" height="24" class="darkIcon" ><path fill="' . $color_var . '" d="M9.37,5.51C9.19,6.15,9.1,6.82,9.1,7.5c0,4.08,3.32,7.4,7.4,7.4c0.68,0,1.35-0.09,1.99-0.27C17.45,17.19,14.93,19,12,19 c-3.86,0-7-3.14-7-7C5,9.07,6.81,6.55,9.37,5.51z M12,3c-4.97,0-9,4.03-9,9s4.03,9,9,9s9-4.03,9-9c0-0.46-0.04-0.92-0.1-1.36 c-0.98,1.37-2.58,2.26-4.4,2.26c-2.98,0-5.4-2.42-5.4-5.4c0-1.81,0.89-3.42,2.26-4.4C12.92,3.04,12.46,3,12,3L12,3z"></path></svg>
                <svg viewBox="0 0 24 24" width="24" height="24" class="lightIcon"><path fill="' . $color_var . '" d="M12,9c1.65,0,3,1.35,3,3s-1.35,3-3,3s-3-1.35-3-3S10.35,9,12,9 M12,7c-2.76,0-5,2.24-5,5s2.24,5,5,5s5-2.24,5-5 S14.76,7,12,7L12,7z M2,13l2,0c0.55,0,1-0.45,1-1s-0.45-1-1-1l-2,0c-0.55,0-1,0.45-1,1S1.45,13,2,13z M20,13l2,0c0.55,0,1-0.45,1-1 s-0.45-1-1-1l-2,0c-0.55,0-1,0.45-1,1S19.45,13,20,13z M11,2v2c0,0.55,0.45,1,1,1s1-0.45,1-1V2c0-0.55-0.45-1-1-1S11,1.45,11,2z M11,20v2c0,0.55,0.45,1,1,1s1-0.45,1-1v-2c0-0.55-0.45-1-1-1C11.45,19,11,19.45,11,20z M5.99,4.58c-0.39-0.39-1.03-0.39-1.41,0 c-0.39,0.39-0.39,1.03,0,1.41l1.06,1.06c0.39,0.39,1.03,0.39,1.41,0s0.39-1.03,0-1.41L5.99,4.58z M18.36,16.95 c-0.39-0.39-1.03-0.39-1.41,0c-0.39,0.39-0.39,1.03,0,1.41l1.06,1.06c0.39,0.39,1.03,0.39,1.41,0c0.39-0.39,0.39-1.03,0-1.41 L18.36,16.95z M19.42,5.99c0.39-0.39,0.39-1.03,0-1.41c-0.39-0.39-1.03-0.39-1.41,0l-1.06,1.06c-0.39,0.39-0.39,1.03,0,1.41 s1.03,0.39,1.41,0L19.42,5.99z M7.05,18.36c0.39-0.39,0.39-1.03,0-1.41c-0.39-0.39-1.03-0.39-1.41,0l-1.06,1.06 c-0.39,0.39-0.39,1.03,0,1.41s1.03,0.39,1.41,0L7.05,18.36z"></path></svg>
				<svg width="16" height="16" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="systemIcon">
					<path d="M20 13V4.2C20 3.0799 20 2.51984 19.782 2.09202C19.5903 1.71569 19.2843 1.40973 18.908 1.21799C18.4802 1 17.9201 1 16.8 1H5.2C4.07989 1 3.51984 1 3.09202 1.21799C2.71569 1.40973 2.40973 1.71569 2.21799 2.09202C2 2.51984 2 3.0799 2 4.2V13M3.66667 17H18.3333C18.9533 17 19.2633 17 19.5176 16.9319C20.2078 16.7469 20.7469 16.2078 20.9319 15.5176C21 15.2633 21 14.9533 21 14.3333C21 14.0233 21 13.8683 20.9659 13.7412C20.8735 13.3961 20.6039 13.1265 20.2588 13.0341C20.1317 13 19.9767 13 19.6667 13H2.33333C2.02334 13 1.86835 13 1.74118 13.0341C1.39609 13.1265 1.12654 13.3961 1.03407 13.7412C1 13.8683 1 14.0233 1 14.3333C1 14.9533 1 15.2633 1.06815 15.5176C1.25308 16.2078 1.79218 16.7469 2.48236 16.9319C2.73669 17 3.04669 17 3.66667 17Z" stroke="' . $color_var . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
            </div>
            <div class="light-dark-dropdown">
                <div class="light">
                    <svg viewBox="0 0 24 24" width="24" height="24" class="lightIcon"><path fill="' . $color_var . '" d="M12,9c1.65,0,3,1.35,3,3s-1.35,3-3,3s-3-1.35-3-3S10.35,9,12,9 M12,7c-2.76,0-5,2.24-5,5s2.24,5,5,5s5-2.24,5-5 S14.76,7,12,7L12,7z M2,13l2,0c0.55,0,1-0.45,1-1s-0.45-1-1-1l-2,0c-0.55,0-1,0.45-1,1S1.45,13,2,13z M20,13l2,0c0.55,0,1-0.45,1-1 s-0.45-1-1-1l-2,0c-0.55,0-1,0.45-1,1S19.45,13,20,13z M11,2v2c0,0.55,0.45,1,1,1s1-0.45,1-1V2c0-0.55-0.45-1-1-1S11,1.45,11,2z M11,20v2c0,0.55,0.45,1,1,1s1-0.45,1-1v-2c0-0.55-0.45-1-1-1C11.45,19,11,19.45,11,20z M5.99,4.58c-0.39-0.39-1.03-0.39-1.41,0 c-0.39,0.39-0.39,1.03,0,1.41l1.06,1.06c0.39,0.39,1.03,0.39,1.41,0s0.39-1.03,0-1.41L5.99,4.58z M18.36,16.95 c-0.39-0.39-1.03-0.39-1.41,0c-0.39,0.39-0.39,1.03,0,1.41l1.06,1.06c0.39,0.39,1.03,0.39,1.41,0c0.39-0.39,0.39-1.03,0-1.41 L18.36,16.95z M19.42,5.99c0.39-0.39,0.39-1.03,0-1.41c-0.39-0.39-1.03-0.39-1.41,0l-1.06,1.06c-0.39,0.39-0.39,1.03,0,1.41 s1.03,0.39,1.41,0L19.42,5.99z M7.05,18.36c0.39-0.39,0.39-1.03,0-1.41c-0.39-0.39-1.03-0.39-1.41,0l-1.06,1.06 c-0.39,0.39-0.39,1.03,0,1.41s1.03,0.39,1.41,0L7.05,18.36z"></path></svg>
                    <span>Light</span>
                </div>
                <div class="dark">
                    <svg viewBox="0 0 24 24" width="24" height="24" class="darkIcon"><path fill="' . $color_var . '" d="M9.37,5.51C9.19,6.15,9.1,6.82,9.1,7.5c0,4.08,3.32,7.4,7.4,7.4c0.68,0,1.35-0.09,1.99-0.27C17.45,17.19,14.93,19,12,19 c-3.86,0-7-3.14-7-7C5,9.07,6.81,6.55,9.37,5.51z M12,3c-4.97,0-9,4.03-9,9s4.03,9,9,9s9-4.03,9-9c0-0.46-0.04-0.92-0.1-1.36 c-0.98,1.37-2.58,2.26-4.4,2.26c-2.98,0-5.4-2.42-5.4-5.4c0-1.81,0.89-3.42,2.26-4.4C12.92,3.04,12.46,3,12,3L12,3z"></path></svg>
                    <span>Dark</span>
                </div>
                <div class="system">
					<svg width="16" height="16" viewBox="0 0 22 18" fill="none" xmlns="http://www.w3.org/2000/svg" class="systemIcon">
						<path d="M20 13V4.2C20 3.0799 20 2.51984 19.782 2.09202C19.5903 1.71569 19.2843 1.40973 18.908 1.21799C18.4802 1 17.9201 1 16.8 1H5.2C4.07989 1 3.51984 1 3.09202 1.21799C2.71569 1.40973 2.40973 1.71569 2.21799 2.09202C2 2.51984 2 3.0799 2 4.2V13M3.66667 17H18.3333C18.9533 17 19.2633 17 19.5176 16.9319C20.2078 16.7469 20.7469 16.2078 20.9319 15.5176C21 15.2633 21 14.9533 21 14.3333C21 14.0233 21 13.8683 20.9659 13.7412C20.8735 13.3961 20.6039 13.1265 20.2588 13.0341C20.1317 13 19.9767 13 19.6667 13H2.33333C2.02334 13 1.86835 13 1.74118 13.0341C1.39609 13.1265 1.12654 13.3961 1.03407 13.7412C1 13.8683 1 14.0233 1 14.3333C1 14.9533 1 15.2633 1.06815 15.5176C1.25308 16.2078 1.79218 16.7469 2.48236 16.9319C2.73669 17 3.04669 17 3.66667 17Z" stroke="' . $color_var . '" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
					<span>System</span>
                </div>
            </div>
		</div>';
        return $adminbar_switcher_icon;
    }

    public function initialize() {
        $admin_bar_position = ( !empty( $this->options['admin_bar_position'] ) ? $this->options['admin_bar_position'] : 'top' );
        if ( is_admin() ) {
            if ( $admin_bar_position == 'top' || $admin_bar_position == 'bottom' ) {
                if ( !empty( $this->adminify_ui ) ) {
                    //TODO: not need to adminify ui. alternative use iframe topbar
                    // 	add_action('admin_init', [$this, 'jltwp_adminify_add_admin_bar']);
                    // } else {
                    add_action( 'admin_head', [$this, 'jltwp_adminify_legacy_admin_bar_positon'] );
                }
            }
            add_action( 'admin_enqueue_scripts', [$this, 'jltwp_adminify_admin_scripts'], 100 );
            // Remove Unnecessary Menus from Admin bar
            // add_action('wp_before_admin_bar_render', [$this, 'jltma_adminify_remove_admin_bar_menus'], 0);
            // add_filter('admin_body_class', [$this, 'admin_bar_body_class']);
            // add_action('admin_head', [$this, 'jltwp_adminify_admin_bar_css'], 999);
            add_action( 'wp_ajax_adminify_all_search', [$this, 'adminify_all_search'] );
            add_action( 'wp_ajax_wp_adminify_color_mode', [$this, 'wp_adminify_color_mode'] );
            // Screen Option and Help Tab
            add_action(
                'admin_head',
                [$this, 'jltwp_adminify_remove_screen_options'],
                10,
                3
            );
            add_action( 'admin_head', [$this, 'jltwp_adminify_remove_help_tab'] );
        } else {
            // Admin bar Frontend settings
            $frontend_admin = ( !empty( $this->options['admin_bar_hide_frontend'] ) ? $this->options['admin_bar_hide_frontend'] : 'show' );
            // if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            // 	if ($frontend_admin == 'show') {
            // 		$frontend_admin_roles = (!empty($this->options['admin_bar_hide_frontend_user_roles'])) ? $this->options['admin_bar_hide_frontend_user_roles'] : [];
            // 		if (Utils::restricted_for($frontend_admin_roles)) {
            // 			$frontend_admin = 'hide';
            // 		}
            // 	}
            // }
            $this->jltwp_adminify_front_bar();
            if ( $admin_bar_position == 'bottom' ) {
                add_action( 'init', [$this, 'admin_bar_front_style_init'] );
            }
            if ( $frontend_admin == 'hide' ) {
                add_filter( 'show_admin_bar', '__return_false' );
            }
        }
    }

    public function before_secondary_menu() {
        ?>
		<div class="wp-adminify-top-header--search--form">
			<form class="top-header--search--form" action="$">
				<span class="adminify-search-expand"><i class="icon-magnifier icons"></i></span>
				<input id="top-header-search-input" class="top-header-search-input" type="search" placeholder="Search here">
			</form>

			<div id="top-header-search-results" class=" top-header-search-results" style="display: none;">
				<div class="top-header-results-wrapper">
				</div>
			</div>
		</div>
	<?php 
    }

    public function load_wp_admin_bar_class() {
        return 'WPAdminify\\Inc\\Classes\\Adminify_Admin_Bar';
    }

    public function jltwp_adminify_front_bar() {
        add_action( 'admin_init', [$this, 'jltwp_adminify_add_admin_bar'] );
        add_filter( 'body_class', [$this, 'admin_bar_body_class'] );
    }

    public function admin_bar_front_style_init() {
        add_filter( 'wp_enqueue_scripts', [$this, 'admin_bar_front_style'] );
    }

    // Frontend Admin bar style
    public function admin_bar_front_style() {
        $admin_bar_css = '';
        $admin_bar_css .= '.admin-bar-position-bottom #wpadminbar{
            top:auto;
            bottom: 0;
        }
        .admin-bar-position-bottom  #wpadminbar .menupop .ab-sub-wrapper{
            bottom: 32px;
        }
        @media all and (max-width:600px){
            body.logged-in.admin-bar-position-bottom {
                position: relative;
            }
        }';
        wp_add_inline_style( 'admin-bar', $admin_bar_css );
    }

    // Remove Screen Options
    public function jltwp_adminify_remove_screen_options() {
        $enable_screen_tab = Utils::get_user_preference( 'screen_options_tab' );
        if ( $enable_screen_tab ) {
            add_filter( 'screen_options_show_screen', '__return_false' );
        }
    }

    // Contextual Help Tab Remove
    public function jltwp_adminify_remove_help_tab() {
        $enable_screen_tab = Utils::get_user_preference( 'adminify_help_tab' );
        if ( $enable_screen_tab ) {
            $screen = get_current_screen();
            $screen->remove_help_tabs();
        }
    }

    // Get All registered WP Admin Menus
    public static function get_wp_admin_menus( $thismenu, $thissubmenu ) {
        $options = [];
        if ( !empty( $thismenu ) && is_array( $thismenu ) ) {
            foreach ( $thismenu as $item ) {
                if ( !empty( $item[0] ) ) {
                    // the preg_replace removes "Comments" & "Plugins" menu spans.
                    $options[$item[2]] = preg_replace( '/\\<span.*?>.*?\\<\\/span><\\/span>/s', '', $item[0] );
                }
            }
        }
        if ( !empty( $thissubmenu ) && is_array( $thissubmenu ) ) {
            foreach ( $thissubmenu as $items ) {
                foreach ( $items as $item ) {
                    if ( !empty( $item[0] ) ) {
                        $options[$item[1]] = preg_replace( '/\\<span.*?>.*?\\<\\/span><\\/span>/s', '', $item[0] );
                    }
                }
            }
        }
        return $options;
    }

    public function jltwp_adminify_admin_scripts() {
        global $pagenow;
        if ( $pagenow == 'wp-login.php' || $pagenow == 'wp-register.php' || $pagenow == 'customize.php' ) {
            return;
        }
        // Commented on: 9-6-24
        if ( !empty( $this->adminify_ui ) ) {
            wp_enqueue_style( 'wp-adminify-admin-bar' );
            $this->admin_topbar_loader_css();
        }
        wp_localize_script( 'wp-adminify-admin', 'WPAdminify', $this->adminify_create_admin_bar_js_object() );
    }

    public function adminify_create_admin_bar_js_object() {
        return [
            'ajax_url'       => admin_url( 'admin-ajax.php' ),
            'security_nonce' => wp_create_nonce( 'adminify-admin-bar-security-nonce' ),
            'notice_nonce'   => wp_create_nonce( 'adminify-notice-nonce' ),
        ];
    }

    /**
     * Preloader
     *
     * @return void
     */
    public function admin_topbar_loader_css() {
        $output_css = '';
        $topbar_wireframe_img = WP_ADMINIFY_ASSETS_IMAGE . 'topbar-wireframe.svg';
        $output_css .= '.js .wp-adminify-topbar-loader{background: url(' . esc_url( $topbar_wireframe_img ) . '); }';
        echo '<style>' . wp_strip_all_tags( $output_css ) . '</style>';
        $screen = get_current_screen();
        // Get current screen details
        if ( isset( $screen->base ) && $screen->base === 'post' ) {
            ?>
        <script>
            window.addEventListener('load', function() {
                const isFullscreenMode = wp.data.select('core/edit-post').isFeatureActive('fullscreenMode');

                if (isFullscreenMode) {
                    jQuery('.adminify-top_bar').css({
                        'display': 'none !important'
                    });
                }
            });
        </script>
        <?php 
        }
    }

    /**
     * Save Color Mode by Ajax
     *
     * @return void
     */
    public function wp_adminify_color_mode() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && check_ajax_referer( 'adminify-admin-bar-security-nonce', 'security' ) > 0 ) {
            $admin_bar_mode = AdminSettings::get_instance()->get();
            if ( !empty( $_POST['key'] ) ) {
                $key = sanitize_key( $_POST['key'] );
                $value = Utils::clean_ajax_input( wp_unslash( $_POST['value'] ) );
                if ( $key == '' ) {
                    $message = __( 'No Color Mode supplied to save', 'adminify' );
                    echo Utils::ajax_error_message( $message );
                    die;
                }
            }
            // Light/Dark Mode
            if ( $key === 'color_mode' ) {
                // For User preference
                $userid = get_current_user_id();
                $current = get_user_meta( $userid, $key, true );
                if ( $current !== $value ) {
                    $state = update_user_meta( $userid, $key, $value );
                }
                die;
                $admin_bar_mode['light_dark_mode']['admin_ui_mode'] = $value;
                $this->options['schedule_dark_mode']['enable_schedule_dark_mode'] = false;
                if ( $value == 'system' ) {
                    $admin_bar_mode['schedule_dark_mode']['enable_schedule_dark_mode'] = true;
                    $admin_bar_mode['schedule_dark_mode']['schedule_dark_mode_type'] = 'system';
                }
                update_option( '_wpadminify', $admin_bar_mode );
                die;
            }
            // Screen Options, Help Tabs and WP Hide Links
            if ( $key === 'screen_options_tab' || $key === 'hide_wp_links' || $key === 'adminify_help_tab' ) {
                $userid = get_current_user_id();
                $current = get_user_meta( $userid, '_wpadminify_preferences', true );
                if ( is_array( $current ) ) {
                    $current[$key] = $value;
                } else {
                    $current = [];
                    $current[$key] = $value;
                }
                $state = update_user_meta( $userid, '_wpadminify_preferences', $current );
                if ( $state ) {
                    $returndata = [];
                    $returndata['success'] = true;
                    $returndata['message'] = __( 'Preferences saved', 'adminify' );
                    echo json_encode( $returndata );
                } else {
                    $message = __( 'Unable to save user preferences', 'adminify' );
                    echo Utils::ajax_error_message( $message );
                    die;
                }
            }
        }
    }

    /**
     * Search Everything
     *
     * @return void
     */
    public function adminify_all_search() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && check_ajax_referer( 'adminify-admin-bar-security-nonce', 'security' ) > 0 ) {
            $search_text = '';
            if ( !empty( $_POST['search'] ) ) {
                $search_text = sanitize_text_field( wp_unslash( $_POST['search'] ) );
            }
            // Error Handling For Minimum 3 Char
            if ( strlen( $search_text ) < 3 ) {
                wp_send_json_success( [
                    'message' => 'Please Enter Minimum 3 Characters',
                ] );
            }
            // Search Arguments
            $args = [
                'numberposts' => -1,
                's'           => $search_text,
                'post_status' => [
                    'publish',
                    'pending',
                    'draft',
                    'future',
                    'private',
                    'inherit'
                ],
            ];
            // All Post Types
            $post_types = $this->get_post_types();
            foreach ( $post_types as $type ) {
                $args['post_type'][] = $type->name;
            }
            // All Categories/Taxonomies
            $all_taxonomies = get_taxonomies();
            // Get Comments
            $all_comments = get_comments();
            // Get All Users
            $all_users = get_users();
            // All Users
            // $blogusers = get_users();
            // foreach ($blogusers as $type) {
            // $name = $type->user_login;
            // $id = $type->ID;
            // $args['author__in'][] = $type->ID;
            // }
            // // All Menus
            // $all_admin_menus = self::get_wp_admin_menus();
            // All Plugins
            if ( !function_exists( 'get_plugins' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $all_plugins = get_plugins();
            $foundposts = get_posts( $args );
            // $count_items = '';
            // if (count($foundposts) > 0) {
            // $count_items .= count($foundposts);
            // } elseif (count($all_plugins)) {
            // $count_items .= count($all_plugins);
            // }
            $output_data = [
                'foundposts'     => $this->set_edit_url_foundposts( $foundposts ),
                'all_taxonomies' => $this->filter_all_taxonomies( $all_taxonomies, $search_text ),
                'all_comments'   => $this->filter_all_comments( $all_comments, $search_text ),
                'all_users'      => $this->filter_all_users( $all_users, $search_text ),
                'all_plugins'    => $this->filter_all_plugins( $all_plugins, $search_text ),
            ];
            wp_send_json_success( [
                'data' => json_encode( $output_data ),
            ] );
            return;
            ob_start();
            ?>

			<p><span class="count"></span><?php 
            echo count( $foundposts ) . wp_kses_post( ' item<span>s</span> found' );
            ?></p>

			<table class="top-header-result-table" style="height:500px;">
				<thead>
					<tr class="has-text-left">
						<th><?php 
            esc_html_e( 'Title', 'adminify' );
            ?></th>
						<th><?php 
            esc_html_e( 'Type', 'adminify' );
            ?></th>
						<th><?php 
            esc_html_e( 'User', 'adminify' );
            ?></th>
						<th><?php 
            esc_html_e( 'Date', 'adminify' );
            ?></th>
					</tr>
				</thead>
				<tbody>

					<?php 
            foreach ( $foundposts as $item ) {
                $author_id = $item->post_author;
                $editurl = get_edit_post_link( $item );
                $public = get_permalink( $item );
                ?>
						<tr>
							<td><span class="table-title"><a href="<?php 
                echo esc_url( $editurl );
                ?>"><?php 
                echo wp_kses_post( get_the_title( $item ) );
                ?></a></span></td>
							<td><span class="type"><?php 
                echo wp_kses_post( get_post_type( $item ) );
                ?></span></td>
							<td><span class="user"><?php 
                echo wp_kses_post( the_author_meta( 'user_login', $author_id ) );
                ?></span></td>
							<td><span class="date"><?php 
                echo wp_kses_post( get_the_date( get_option( 'date_format' ), $item ) );
                ?></span></td>
						</tr>
					<?php 
            }
            ?>

					<?php 
            foreach ( $all_taxonomies as $tax_name ) {
                $terms = get_terms( [
                    'taxonomy'   => $tax_name,
                    'hide_empty' => 1,
                ] );
                foreach ( $terms as $cat ) {
                    if ( strpos( strtolower( $cat->name ), strtolower( $term ) ) === false ) {
                        continue;
                    }
                    // $user = get_userdata($cat->term_id);
                    ?>
							<tr>
								<td>
									<span class="table-title">
										<a href="<?php 
                    echo esc_url( get_term_link( $cat->slug, $cat->taxonomy ) );
                    ?>">
											<?php 
                    echo esc_html( $cat->name );
                    ?>
										</a>
									</span>
								</td>
								<td>
									<span class="type"><?php 
                    echo esc_html( $cat->taxonomy );
                    ?></span>
								</td>
								<td>
									<span class="user">
										<?php 
                    esc_html_e( 'N/A', 'adminify' );
                    ?>
									</span>
								</td>
								<td>
									<span class="date">
										<?php 
                    esc_html_e( 'N/A', 'adminify' );
                    ?>
									</span>
								</td>
								<td>
								</td>
							</tr>
					<?php 
                }
            }
            ?>


					<!-- Get Comments  -->
					<?php 
            foreach ( $all_comments as $comment ) {
                if ( strpos( strtolower( $comment->comment_content ), strtolower( $term ) ) === false ) {
                    continue;
                }
                ?>
						<tr>
							<td>
								<span class="table-title">
									<a href="<?php 
                echo esc_url_raw( admin_url( 'comment.php?action=editcomment&c=' . esc_attr( $comment->comment_ID ) ) );
                ?>">
										<?php 
                echo wp_kses_post( $comment->comment_content );
                ?>
									</a>
								</span>
							</td>
							<td>
								<span class="type">
									<?php 
                echo wp_kses_post( ucwords( $comment->comment_type ) );
                ?>
								</span>
							</td>
							<td>
								<span class="user">
									<?php 
                echo wp_kses_post( the_author_meta( 'display_name', $comment->user_id ) );
                ?>
								</span>
							</td>
							<td>
								<span class="date">
									<?php 
                echo esc_html( get_the_date( get_option( 'date_format' ), $comment->comment_date ) );
                ?>
								</span>
							</td>
						</tr>
					<?php 
            }
            ?>



					<!-- Get Users  -->
					<?php 
            foreach ( $all_users as $user ) {
                if ( strpos( strtolower( $user->user_login ), strtolower( $term ) ) === false ) {
                    continue;
                }
                ?>
						<tr>
							<td>
								<span class="table-title">
									<a href="<?php 
                echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) );
                ?>">
										<?php 
                echo esc_html( $user->display_name );
                ?>
									</a>
								</span>
							</td>
							<td>
								<span class="type">
									<?php 
                echo wp_kses_post( ucwords( $comment->comment_type ) );
                ?>
								</span>
							</td>
							<td>
								<span class="user">
									<?php 
                echo wp_kses_post( the_author_meta( 'display_name', $comment->user_id ) );
                ?>
								</span>
							</td>
							<td>
								<span class="date">
									<?php 
                echo wp_kses_post( get_the_date( get_option( 'date_format' ), $user->user_registered ) );
                ?>
								</span>
							</td>
						</tr>
					<?php 
            }
            ?>



					<!-- Get Plugins  -->
					<?php 
            foreach ( $all_plugins as $plugin ) {
                if ( strpos( strtolower( $plugin['Name'] ), strtolower( $term ) ) === false ) {
                    continue;
                }
                ?>
						<tr>
							<td>
								<span class="table-title">
									<a href="<?php 
                echo esc_url( admin_url( 'plugins.php' ) );
                ?>">
										<?php 
                echo esc_html( $plugin['Name'] );
                ?>
									</a>
								</span>
							</td>
							<td>
								<span class="type">
									<?php 
                esc_html_e( 'Plugin', 'adminify' );
                ?>
								</span>
							</td>
							<td>
								<span class="user">
									<?php 
                echo esc_html( $plugin['AuthorName'] );
                ?>
								</span>
							</td>
							<td>
								<span class="date">
									<?php 
                esc_html_e( 'N/A', 'adminify' );
                ?>
								</span>
							</td>
						</tr>
					<?php 
            }
            ?>


				</tbody>
			</table>


		<?php 
            // $output_data = ob_get_clean();
            // $output_data = [
            // 	'foundposts'	=> $foundposts,
            // 	'all_taxonomies' => $all_taxonomies,
            // 	'all_comments' => $all_comments,
            // 	'all_users' => $all_users,
            // 	'all_plugins' => $all_plugins,
            // ];
            // echo json_encode($output_data);
            echo json_encode( $foundposts );
            wp_send_json_success( [
                'data' => $foundposts,
            ] );
        }
        die;
    }

    /**
     * Set URL to All Post
     */
    public function set_edit_url_foundposts( $foundposts ) {
        $all_posts = [];
        foreach ( $foundposts as $key => $post ) {
            $edit_url = get_edit_post_link( $post );
            $all_posts[$key]['title'] = get_the_title( $post->ID );
            $all_posts[$key]['link'] = $edit_url;
        }
        return $all_posts;
    }

    /**
     * Search Taxonomies
     */
    public function filter_all_taxonomies( $all_taxonomies, $search_text ) {
        $find_taxonomies = [];
        foreach ( $all_taxonomies as $tax_name ) {
            $terms = get_terms( [
                'taxonomy'   => $tax_name,
                'hide_empty' => 1,
            ] );
            foreach ( $terms as $key => $cat ) {
                if ( strpos( strtolower( $cat->name ), strtolower( $search_text ) ) === false ) {
                    continue;
                }
                $find_taxonomies[$key]['name'] = $cat->name;
                $find_taxonomies[$key]['link'] = get_edit_term_link( $cat );
            }
        }
        return $find_taxonomies;
    }

    /**
     * Search Comments
     */
    public function filter_all_comments( $all_comments, $search_text ) {
        $find_comments = [];
        foreach ( $all_comments as $key => $comment ) {
            if ( strpos( strtolower( $comment->comment_content ), strtolower( $search_text ) ) === false ) {
                continue;
            }
            $find_comments[$key]['content'] = $comment->comment_content;
            $find_comments[$key]['link'] = admin_url( 'comment.php?action=editcomment&c=' . esc_attr( $comment->comment_ID ) );
        }
        return $find_comments;
    }

    /**
     * Search Users
     */
    public function filter_all_users( $all_users, $search_text ) {
        $find_users = [];
        foreach ( $all_users as $key => $user ) {
            if ( str_contains( strtolower( $user->data->user_login ), strtolower( $search_text ) ) || str_contains( strtolower( $user->data->display_name ), strtolower( $search_text ) ) ) {
                $find_users[$key]['name'] = $user->data->user_login;
                $find_users[$key]['display_name'] = $user->data->display_name;
                $find_users[$key]['link'] = admin_url( 'user-edit.php?user_id=' . $user->ID );
            }
        }
        return $find_users;
    }

    /**
     * Search Plugins
     */
    public function filter_all_plugins( $all_plugins, $search_text ) {
        $find_plugins = [];
        foreach ( $all_plugins as $key => $plugin ) {
            if ( strpos( strtolower( $plugin['Name'] ), strtolower( $search_text ) ) === false ) {
                continue;
            }
            $find_plugins[] = [
                'name' => $plugin['Name'],
                'link' => admin_url( 'plugins.php' ),
            ];
        }
        return $find_plugins;
    }

    public function jltwp_adminify_admin_bar_css() {
        $current_screen = get_current_screen();
        $admin_bar_mode = ( !empty( $admin_bar_mode['light_dark_mode']['admin_ui_mode'] ) ? $admin_bar_mode['light_dark_mode']['admin_ui_mode'] : '' );
        $output_css = '';
        $output_css .= '<style type="text/css">';
        if ( !empty( $current_screen->is_block_editor ) ) {
            $output_css .= '.wp-adminify.adminify-ui.block-editor-page:not(.wp-adminify.is-fullscreen-mode) .interface-interface-skeleton { top: 90px !important; left: 244px !important; border-top: 0 !important; border-left: 0 !important; }';
            $output_css .= '.wp-adminify.block-editor-page.is-fullscreen-mode.block-editor-page .interface-interface-skeleton { top: 0 !important; left: 0 !important; border-top: 0 !important; border-left: 0 !important; }';
        }
        $admin_bar_container = ( !empty( $this->options['admin_bar_container'] ) ? $this->options['admin_bar_container'] : 'full_container' );
        if ( $admin_bar_mode === 'light' ) {
            $light_bg_type = ( !empty( $this->options['admin_bar_light_bg'] ) ? $this->options['admin_bar_light_bg'] : 'color' );
            $light_bg_color = ( !empty( $this->options['admin_bar_light_bg_color'] ) ? $this->options['admin_bar_light_bg_color'] : '' );
            // Full Container Colors
            if ( $admin_bar_container == 'full_container' ) {
                if ( $light_bg_type === 'color' ) {
                    $output_css .= '.wp-adminify.adminify-light-mode .adminify-top_bar nav.navbar, .wp-adminify .wp-adminify-horizontal-menu { background-color:' . esc_attr( $light_bg_color ) . ' ; }';
                }
            } elseif ( $admin_bar_container == 'admin_bar_only' ) {
                // Admin Bar Colors
                if ( $light_bg_type === 'color' ) {
                    if ( !empty( $this->options['admin_bar_light_bg_color'] ) ) {
                        $output_css .= '.wp-adminify.adminify-light-mode .adminify-top_bar nav.navbar { background-color:' . esc_attr( $light_bg_color ) . ' }';
                    }
                }
            }
            if ( !empty( $admin_bar_colors['icon_color'] ) ) {
                $output_css .= '.adminify-top_bar nav.navbar .navbar-menu .topbar-icon { fill: ' . esc_attr( $admin_bar_colors['icon_color'] ) . ' }';
            }
        } elseif ( $admin_bar_mode === 'dark' ) {
            $dark_bg_type = ( isset( $this->options['admin_bar_dark_bg'] ) ? $this->options['admin_bar_dark_bg'] : 'color' );
            if ( $admin_bar_container == 'full_container' ) {
                if ( $dark_bg_type === 'color' ) {
                    $dark_bg_color = $this->options['admin_bar_dark_bg_color'];
                    $output_css .= '.wp-adminify.adminify-dark-mode .adminify-top_bar nav.navbar, .wp-adminify .wp-adminify-horizontal-menu{ background-color:' . esc_attr( $dark_bg_color ) . ' }';
                } elseif ( $dark_bg_type === 'gradient' ) {
                    $dark_gradient_bg_color = $this->options['admin_bar_dark_bg_gradient']['background-color'];
                    $dark_gradient_color = $this->options['admin_bar_dark_bg_gradient']['background-gradient-color'];
                    $dark_gradient_color_dir = $this->options['admin_bar_dark_bg_gradient']['background-gradient-direction'];
                    $output_css .= '.wp-adminify.adminify-dark-mode .adminify-top_bar nav.navbar, .wp-adminify .wp-adminify-horizontal-menu{ background-image : linear-gradient(' . esc_attr( $dark_gradient_color_dir ) . ', ' . esc_attr( $dark_gradient_bg_color ) . ' , ' . esc_attr( $dark_gradient_color ) . '); }';
                }
            } elseif ( $admin_bar_container == 'admin_bar_only' ) {
                if ( $dark_bg_type === 'color' ) {
                    $dark_bg_color = $this->options['admin_bar_dark_bg_color'];
                    $output_css .= '.wp-adminify.adminify-dark-mode .adminify-top_bar nav.navbar{ background-color:' . esc_attr( $dark_bg_color ) . ' }';
                }
            }
        }
        // "New" Button Colors
        $new_btn_colors = ( !empty( $this->options['admin_bar_link_color'] ) ? $this->options['admin_bar_link_color'] : '' );
        if ( !empty( $new_btn_colors['link_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content > .ab-item .ab-label, .wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content .ab-item .ab-icon:before { color:' . esc_attr( $new_btn_colors['link_color'] ) . ' !important; }';
        }
        if ( !empty( $new_btn_colors['hover_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content > .ab-item:hover .ab-label, .wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content .ab-item:hover .ab-icon:before{ color:' . esc_attr( $new_btn_colors['hover_color'] ) . ' !important; }';
        }
        if ( !empty( $new_btn_colors['bg_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content > .ab-item, .wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-content:hover > .ab-item { background:' . esc_attr( $new_btn_colors['bg_color'] ) . ' !important;}';
        }
        // "New" Button Hover Colors
        $new_btn_dropwon = ( !empty( $this->options['admin_bar_link_dropdown_color'] ) ? $this->options['admin_bar_link_dropdown_color'] : '' );
        if ( !empty( $new_btn_dropwon['wrapper_bg'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper, .wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper .ab-submenu, .wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper .ab-submenu .ab-item { background-color:' . esc_attr( $new_btn_dropwon['wrapper_bg'] ) . ' !important;}';
        }
        if ( !empty( $new_btn_dropwon['bg_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper .ab-submenu .ab-item:hover { background-color:' . esc_attr( $new_btn_dropwon['bg_color'] ) . ' !important;}';
        }
        if ( !empty( $new_btn_dropwon['link_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper .ab-submenu .ab-item { color:' . esc_attr( $new_btn_dropwon['link_color'] ) . ' !important }';
        }
        if ( !empty( $new_btn_dropwon['hover_color'] ) ) {
            $output_css .= '.wp-adminify #wpadminbar .ab-top-menu .ab-sub-wrapper .ab-submenu .ab-item:hover { color:' . esc_attr( $new_btn_dropwon['hover_color'] ) . ' !important }';
        }
        // Text Color
        if ( !empty( $this->options['admin_bar_text_color'] ) ) {
            $output_css .= '.adminify-top_bar nav.navbar .navbar-brand .navbar-item .wp-adminify-site-name, .adminify-top_bar nav.navbar .navbar-menu .wp-adminify-user-site-list button { color:' . esc_attr( $this->options['admin_bar_text_color'] ) . ' }';
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-post > .ab-item { color:' . esc_attr( $this->options['admin_bar_text_color'] ) . ' ; }';
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-post > .ab-item .ab-icon:before { color:' . esc_attr( $this->options['admin_bar_text_color'] ) . ' ; }';
            $output_css .= '.wp-adminify #wpadminbar #wp-admin-bar-root-default #wp-admin-bar-new-post > .ab-item .ab-label { color:' . esc_attr( $this->options['admin_bar_text_color'] ) . ' ; }';
        }
        // Icon Color
        if ( !empty( $this->options['admin_bar_icon_color'] ) ) {
            $output_css .= '.adminify-top_bar nav.navbar .navbar-menu .topbar-icon svg path { fill:' . esc_attr( $this->options['admin_bar_icon_color'] ) . ' }';
            $output_css .= '.adminify-top_bar nav.navbar .navbar-end i, .adminify-top_bar nav.navbar .navbar-burger i { color:' . esc_attr( $this->options['admin_bar_icon_color'] ) . ' }';
        }
        $output_css .= '.wp-adminify .editor-document-tools__left button.editor-document-tools__inserter-toggle {
            padding: 0 !important;
            border: 2px solid var(--adminify-btn-bg) !important;
        }';
        $output_css .= ' </style>';
        // echo Utils::wp_kses_custom($output_css);
        echo $output_css;
    }

    // Admin Bar Body Class
    public function admin_bar_body_class( $classes ) {
        if ( is_admin() ) {
            $classes .= ' wp-adminify-admin-bar';
            $admin_bar_position = ( !empty( $this->options['admin_bar_position'] ) ? $this->options['admin_bar_position'] : 'top' );
            if ( $admin_bar_position === 'top' ) {
                $classes .= ' position-top';
            } elseif ( $admin_bar_position === 'bottom' ) {
                $classes .= ' position-bottom';
            }
            if ( !empty( $this->options['enable_admin_bar'] ) ) {
                $classes .= ' topbar-disabled';
            }
        } else {
            global $pagenow;
            if ( !is_user_logged_in() && $pagenow != 'wp-login.php' ) {
                return $classes;
            }
            $classes[] = 'wp-adminify-admin-bar';
            $admin_bar_position = ( !empty( $this->options['admin_bar_position'] ) ? $this->options['admin_bar_position'] : 'top' );
            if ( $admin_bar_position === 'top' ) {
                $classes[] = 'admin-bar-position-top';
            } elseif ( $admin_bar_position === 'bottom' ) {
                $classes[] = 'admin-bar-position-bottom';
            }
            if ( !empty( $this->options['enable_admin_bar'] ) ) {
                $classes[] = 'topbar-disabled';
            }
        }
        return $classes;
    }

    public function get_post_types() {
        if ( is_array( $this->post_types ) ) {
            return $this->post_types;
        } else {
            $args = [
                'public' => true,
            ];
            $output = 'objects';
            $post_types = get_post_types( $args, $output );
            $this->post_types = $post_types;
            return $post_types;
        }
    }

    public function jltwp_adminify_legacy_admin_bar_positon() {
        if ( !empty( $this->options['admin_bar_position'] ) && $this->options['admin_bar_position'] == 'bottom' ) {
            $jltwp_adminify_thirdparty_css = '';
            $jltwp_adminify_thirdparty_css .= 'body.wp-adminify.position-bottom {
					margin-top: -28px;
					padding-bottom: 28px;
				}

				body.wp-adminify.position-bottom.admin-bar #wphead {
					padding-top: 0;
				}

				body.wp-adminify.position-bottom.admin-bar #footer {
					padding-bottom: 28px;
				}

				body.wp-adminify.position-bottom #wpadminbar {
					top: auto !important;
					bottom: 0;
				}

				body.wp-adminify.position-bottom #wpadminbar .quicklinks .ab-top-menu .menupop .ab-sub-wrapper {
					bottom: 32px;
				}

				body.wp-adminify.position-bottom #wpadminbar .quicklinks .ab-top-menu .menupop .ab-sub-wrapper .ab-submenu .menupop .ab-sub-wrapper {
					bottom: -9px;
				}';
            echo '<style>' . wp_strip_all_tags( $jltwp_adminify_thirdparty_css ) . '</style>';
        }
    }

    public function jltwp_adminify_add_admin_bar() {
        // For Testing Admin Bar on Setup Wizard
        // add_action('admin_head', [$this, 'jltwp_adminify_render_admin_bar']);
        add_action( 'in_admin_header', [$this, 'jltwp_adminify_render_admin_bar'], -999999999 );
        // on frontend area
        add_action( 'wp_head', [$this, 'jltwp_adminify_render_admin_bar'] );
    }

    public function jltwp_adminify_render_admin_bar() {
        global $pagenow;
        if ( !is_user_logged_in() && $pagenow != 'wp-login.php' ) {
            return;
        }
        // Check Gutenberg Editor Page
        // if (Utils::is_block_editor_page()) {
        // 	return;
        // }
        // if (!is_admin_bar_showing()) {
        // 	return false;
        // }
        // Remove Adminbar from Gutenberg Block Editor Page
        // if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
        // 	// The Gutenberg plugin is on.
        // 	return;
        // }
        // if (function_exists('get_current_screen')) {
        // 	$current_screen = get_current_screen();
        // 	if (!empty($current_screen->is_block_editor)) {
        // 		return;
        // 	}
        // }
        global $wp_admin_bar;
        if ( empty( $wp_admin_bar ) ) {
            return false;
        }
        $admin_bar_mode = ( empty( $admin_bar_mode['light_dark_mode']['admin_ui_mode'] ) ? 'light' : $admin_bar_mode['light_dark_mode']['admin_ui_mode'] );
        // Light/Dark Mode
        $enable_dark_mode = $admin_bar_mode != 'light';
        // Screen Option && Hide WP Links
        $enable_screen_tab = Utils::get_user_preference( 'screen_options_tab' );
        $enable_help_tab = Utils::get_user_preference( 'adminify_help_tab' );
        // $enable_hide_wp_links = Utils::get_user_preference( 'hide_wp_links' );
        // Admin Bar Position
        if ( !empty( $this->options['admin_bar_position'] ) && $this->options['admin_bar_position'] === 'top' ) {
            $admin_bar_position = 'top_bar';
        } elseif ( !empty( $this->options['admin_bar_position'] ) && $this->options['admin_bar_position'] === 'bottom' ) {
            $admin_bar_position = 'bottom_bar is-fixed-bottom';
        } else {
            $admin_bar_position = 'top_bar';
        }
        $current_user = wp_get_current_user();
        ob_start();
        // Temporarily removing Topbar
        // <div class="wp-adminify-topbar-loader"></div>
        // 17-7-23, removed opacity for Conflicting Opacity Issue
        // <div class="wp-adminify adminify-top_bar" style="opacity: 0;">
        ?>



		<div class="wp-adminify adminify-top_bar">
			<nav class="navbar adminify-top-navbar <?php 
        echo esc_attr( $admin_bar_position );
        ?>">

				<?php 
        $this->jltwp_adminify_logo();
        ?>
				<div class="adminify-admin-wrapper">
					<div class="adminify-legacy-admin">
						<div class="adminify-hamburger navbar-burger" data-target="wp-adminify-top-adminbar">
							<svg width="11" height="8" viewBox="0 0 11 8" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M7 0H0V1H7V0Z" fill="#48455F" />
								<path d="M7 7H0V8H7V7Z" fill="#48455F" />
								<path d="M4.67 3.5H0V4.5H4.67V3.5Z" fill="#48455F" />
								<path d="M9.65 7.35L6.29 4L9.65 0.65L10.35 1.35L7.71 4L10.35 6.65L9.65 7.35Z" fill="#48455F" />
							</svg>
						</div>
						<?php 
        echo wp_admin_bar_render();
        ?>
					</div>

					<div id="wp-adminify-top-adminbar" class="navbar-menu">

						<div class="navbar-end">
							<div class="field is-grouped">

								<?php 
        echo $this->jltwp_adminify_dark_mode_switcher_dom();
        ?>

								<?php 
        if ( !empty( $this->options['admin_bar_notif'] ) ) {
            ?>
									<div class="wp-adminify--top--comment">
										<button class="comment-trigger is-clickable">
											<div class="topbar-icon">
												<svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M0.25 13.75V1.75C0.25 0.921573 0.921572 0.25 1.75 0.25H12.25C13.0784 0.25 13.75 0.921573 13.75 1.75V9.25C13.75 10.0784 13.0784 10.75 12.25 10.75H4.75C4.42535 10.7494 4.10936 10.8547 3.85 11.05L0.25 13.75ZM1.75 1.75V10.75L3.3505 9.55C3.60973 9.35448 3.9258 9.24913 4.2505 9.25H12.25V1.75H1.75Z" fill="var(--adminify-menu-text-color)" />
												</svg>
											</div>
											<span class="comment-counter">
												<?php 
            $comments_count = wp_count_comments();
            echo esc_html( $comments_count->moderated );
            ?>
											</span>
										</button>
									</div>

								<?php 
        }
        ?>

								<div class="wp-adminify--preview">
									<a class="preview-trigger is-clickable" href="<?php 
        echo esc_url( get_home_url() );
        ?>" target="_blank">
										<i class="dashicons dashicons-visibility"></i>
									</a>
								</div>

								<div class="wp-adminify--user--account">
									<button class="user-avatar is-clickable">
										<div class="image is-45x45">
											<?php 
        echo get_avatar(
            $current_user->user_email,
            45,
            '',
            '',
            [
                'class' => 'is-rounded',
            ]
        );
        ?>
										</div>
										<span class="user-status tag p-0 is-rounded"></span>
									</button>

									<?php 
        if ( !empty( $this->adminify_ui ) ) {
            ?>
										<div class="wp-adminify--user--wrapper">
											<div class="wp-adminify-user-info">
												<h3 class="wp-adminify-user-name">
													<?php 
            echo esc_html( $current_user->display_name );
            ?>
												</h3>
												<span><?php 
            echo wp_kses_post( is_email( $current_user->user_email ) );
            ?></span>

												<ul>
													<hr>
													<li>
														<a href="<?php 
            echo esc_url( admin_url( 'profile.php' ) );
            ?>">
															<svg class="wp-adminify-profile-icon" width="16" height="18" viewBox="0 0 16 18" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path d="M14.6654 16.5C14.6654 15.337 14.6654 14.7555 14.5218 14.2824C14.1987 13.217 13.365 12.3834 12.2996 12.0602C11.8265 11.9167 11.245 11.9167 10.082 11.9167H5.91537C4.7524 11.9167 4.17091 11.9167 3.69775 12.0602C2.63241 12.3834 1.79873 13.217 1.47556 14.2824C1.33203 14.7555 1.33203 15.337 1.33203 16.5M11.7487 5.25C11.7487 7.32107 10.0698 9 7.9987 9C5.92763 9 4.2487 7.32107 4.2487 5.25C4.2487 3.17893 5.92763 1.5 7.9987 1.5C10.0698 1.5 11.7487 3.17893 11.7487 5.25Z" stroke="var(--adminify-primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
															</svg>
															<span><?php 
            esc_html_e( 'View Profile', 'adminify' );
            ?></span>
														</a>
													</li>
													<hr>
													<li>
														<a href="<?php 
            echo esc_url( wp_logout_url( home_url() ) );
            ?>" class="wp-adminify-logout">
															<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
																<path fill-rule="evenodd" clip-rule="evenodd" d="M5.7587 4.31292e-07L7 9.08129e-07C7.55229 9.08129e-07 8 0.447716 8 1C8 1.55229 7.55229 2 7 2H5.8C4.94342 2 4.36113 2.00078 3.91104 2.03755C3.47262 2.07337 3.24842 2.1383 3.09202 2.21799C2.7157 2.40973 2.40974 2.7157 2.21799 3.09202C2.1383 3.24842 2.07337 3.47262 2.03755 3.91104C2.00078 4.36113 2 4.94342 2 5.8V14.2C2 15.0566 2.00078 15.6389 2.03755 16.089C2.07337 16.5274 2.1383 16.7516 2.21799 16.908C2.40973 17.2843 2.7157 17.5903 3.09202 17.782C3.24842 17.8617 3.47262 17.9266 3.91104 17.9624C4.36113 17.9992 4.94342 18 5.8 18H7C7.55229 18 8 18.4477 8 19C8 19.5523 7.55229 20 7 20H5.75868C4.95372 20 4.28937 20 3.74818 19.9558C3.18608 19.9099 2.66937 19.8113 2.18404 19.564C1.43139 19.1805 0.819468 18.5686 0.435975 17.816C0.188684 17.3306 0.0901197 16.8139 0.0441946 16.2518C-2.28137e-05 15.7106 -1.23241e-05 15.0463 4.31291e-07 14.2413V5.7587C-1.23241e-05 4.95373 -2.28137e-05 4.28937 0.0441947 3.74817C0.09012 3.18608 0.188685 2.66937 0.435976 2.18404C0.81947 1.43139 1.43139 0.819468 2.18404 0.435975C2.66938 0.188684 3.18608 0.0901197 3.74818 0.0441945C4.28937 -2.28137e-05 4.95373 -1.23241e-05 5.7587 4.31292e-07ZM13.2929 4.29289C13.6834 3.90237 14.3166 3.90237 14.7071 4.29289L19.7071 9.29289C20.0976 9.68342 20.0976 10.3166 19.7071 10.7071L14.7071 15.7071C14.3166 16.0976 13.6834 16.0976 13.2929 15.7071C12.9024 15.3166 12.9024 14.6834 13.2929 14.2929L16.5858 11H7C6.44772 11 6 10.5523 6 10C6 9.44772 6.44772 9 7 9H16.5858L13.2929 5.70711C12.9024 5.31658 12.9024 4.68342 13.2929 4.29289Z" fill="var(--adminify-primary)"></path>
															</svg>
															<span><?php 
            echo esc_html__( 'Log Out', 'adminify' );
            ?></span>
														</a>
													</li>
												</ul>
											</div>
										</div>
									<?php 
        }
        ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</nav>
		</div>

	<?php 
        $output_wp_admin_bar = ob_get_clean();
        // echo Utils::wp_kses_custom($output_wp_admin_bar);
        echo $output_wp_admin_bar;
    }

    public function jltwp_adminify_logo() {
        global $wp_admin_bar;
        $adminurl = get_admin_url();
        $homeurl = $adminurl;
        // Light Logo
        $menu_layout = (array) $this->options['menu_layout_settings'];
        // Menu Layouts
        $menu_layout = ( !empty( $this->options['menu_layout_settings'] ) ? $this->options['menu_layout_settings'] : '' );
        $menu_mode = ( !empty( $menu_layout['menu_mode'] ) ? $menu_layout['menu_mode'] : 'classic' );
        $menu_layout = ( !empty( $menu_layout['layout_type'] ) ? $menu_layout['layout_type'] : 'vertical' );
        $admin_ui_logo_type = ( !empty( $this->options['light_dark_mode']['admin_ui_logo_type'] ) ? $this->options['light_dark_mode']['admin_ui_logo_type'] : 'image_logo' );
        $light_mode = ( !empty( $this->options['light_dark_mode']['admin_ui_light_mode'] ) ? $this->options['light_dark_mode']['admin_ui_light_mode'] : '' );
        $light_logo = '';
        $light_mini_logo = '';
        if ( $admin_ui_logo_type == 'image_logo' ) {
            if ( isset( $light_mode['mini_admin_ui_light_logo']['url'] ) && $light_mode['mini_admin_ui_light_logo']['url'] ) {
                $light_mini_logo = $light_mode['mini_admin_ui_light_logo']['url'];
            } else {
                $light_mini_logo = WP_ADMINIFY_ASSETS_IMAGE . 'logos/mini-logo-light.svg';
            }
            // Logo Image
            if ( !empty( $light_mode['admin_ui_light_logo']['url'] ) ) {
                $light_logo = $light_mode['admin_ui_light_logo']['url'];
            } else {
                $light_logo = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-light.svg';
            }
        } elseif ( $admin_ui_logo_type == 'text_logo' ) {
            // Text Logo
            if ( $this->options['light_dark_mode']['admin_ui_mode'] == 'light' ) {
                $text_logo = $light_mode['admin_ui_light_logo_text'];
            }
        }
        // Logo Size
        $light_width = ( !empty( $light_mode['light_logo_size']['width'] ) ? $light_mode['light_logo_size']['width'] : '120' );
        $light_height = ( !empty( $light_mode['light_logo_size']['height'] ) ? $light_mode['light_logo_size']['height'] : '32' );
        // Dark Logo
        $dark_mode = ( !empty( $this->options['light_dark_mode']['admin_ui_dark_mode'] ) ? $this->options['light_dark_mode']['admin_ui_dark_mode'] : '' );
        $dark_logo = '';
        $dark_mini_logo = '';
        if ( $admin_ui_logo_type == 'image_logo' ) {
            if ( isset( $dark_mode['mini_admin_ui_dark_logo']['url'] ) && $dark_mode['mini_admin_ui_dark_logo']['url'] ) {
                $dark_mini_logo = $dark_mode['mini_admin_ui_dark_logo']['url'];
            } else {
                $dark_mini_logo = WP_ADMINIFY_ASSETS_IMAGE . 'logos/mini-logo-dark.svg';
            }
            // Dark Logo Image
            if ( isset( $dark_mode['admin_ui_dark_logo']['url'] ) && $dark_mode['admin_ui_dark_logo']['url'] ) {
                $dark_logo = $dark_mode['admin_ui_dark_logo']['url'];
            } else {
                $dark_logo = WP_ADMINIFY_ASSETS_IMAGE . 'logos/logo-text-dark.svg';
            }
        } elseif ( $admin_ui_logo_type == 'text_logo' ) {
            // Text Logo
            if ( $this->options['light_dark_mode']['admin_ui_mode'] == 'dark' ) {
                $text_logo = $dark_mode['admin_ui_dark_logo_text'];
            }
        }
        // Dark Logo Size
        $dark_width = ( !empty( $dark_mode['dark_logo_size']['width'] ) ? $dark_mode['dark_logo_size']['width'] : '120' );
        $dark_height = ( !empty( $dark_mode['dark_logo_size']['height'] ) ? $dark_mode['dark_logo_size']['height'] : '32' );
        ?>

		<div class="navbar-brand">
			<a class="navbar-item p-0" href="<?php 
        echo esc_url( $homeurl );
        ?>">
				<?php 
        if ( $admin_ui_logo_type == 'image_logo' ) {
            ?>
					<!-- Light Logo -->
					<img alt="<?php 
            echo esc_attr( get_bloginfo( 'name' ) );
            ?>" class="logo-light" src="<?php 
            echo esc_url( $light_logo );
            ?>" width="<?php 
            echo esc_attr( $light_width );
            ?>" height="<?php 
            echo esc_attr( $light_height );
            ?>">
					<img alt="<?php 
            echo esc_attr( get_bloginfo( 'name' ) );
            ?>" class="mini-logo-light" src="<?php 
            echo esc_url( $light_mini_logo );
            ?>" width="36" height="36">
					<!-- Dark Logo -->
					<img alt="<?php 
            echo esc_attr( get_bloginfo( 'name' ) );
            ?>" class="logo-dark" src="<?php 
            echo esc_url( $dark_logo );
            ?>" width="<?php 
            echo esc_attr( $dark_width );
            ?>" height="<?php 
            echo esc_attr( $dark_height );
            ?>">
					<img alt="<?php 
            echo esc_attr( get_bloginfo( 'name' ) );
            ?>" class="mini-logo-dark" src="<?php 
            echo esc_url( $dark_mini_logo );
            ?>" width="36" height="36">
				<?php 
        } elseif ( $admin_ui_logo_type == 'text_logo' ) {
            ?>
					<span class="wp-adminify-site-name">
						<?php 
            echo esc_html( $text_logo );
            ?>
					</span>
				<?php 
        }
        ?>
			</a>

			<!-- <div class="navbar-burger" data-target="wp-adminify-top-adminbar">
				<img src="<?php 
        // echo WP_ADMINIFY_ASSETS_IMAGE . 'icons/toggol.svg';
        ?>"  alt="Toggol icon"/>
			</div> -->

		</div>

<?php 
    }

    /* Remove from the administration bar */
    public function jltma_adminify_remove_admin_bar_menus() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu( 'wp-logo' );
        $wp_admin_bar->remove_menu( 'site-name' );
        $wp_admin_bar->remove_menu( 'updates' );
        $wp_admin_bar->remove_menu( 'menu-toggle' );
    }

}
