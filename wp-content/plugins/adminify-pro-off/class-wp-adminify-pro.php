<?php

namespace WPAdminify\Pro;

use \WPAdminify\Inc\Admin\AdminSettings;
use \WPAdminify\Inc\Utils;

// no direct access allowed
if (!defined('ABSPATH')) {
    exit;
}

class Adminify_Pro
{
    public $options;

    private static $instance = null;

    public function __construct()
    {
        // $this->options = (array) AdminSettings::get_instance()->get();

        add_action('plugins_loaded', array($this, 'jltwp_adminify_includes_classes'), 0);
        add_action('plugins_loaded', array($this, 'jltwp_adminify_dependencies'), -1);

        if (!$this->is_adminify_installed() || !$this->is_adminify_activated()) {
            add_action('admin_notices', array($this, 'jltwp_adminify_notice_missing_main_plugin'));
        }

        new Filters();
    }

    public function jltwp_adminify_dependencies()
    {
        if (class_exists('\WPAdminify\Inc\Admin\AdminSettings')) {
            // Must Load First for hooks adding reason
            new OptionSettings();

            // Must load after OptionSettings
            $this->options = AdminSettings::get_instance()->get();
        }
    }

    public static function adminify_pro_activation_hook(){
        $plugin_option = AdminSettings::get_instance()->get();
        // White Label Options
        if (jltwp_adminify()->is_plan('agency')) {
            $plugin_option['white_label']['adminify']['plugin_option'] = false;
            update_option('_wpadminify', $plugin_option);
        }
    }

    /**
     * Check is Plugin Active
     *
     * @param [type] $plugin_path
     *
     * @return boolean
     */
    public function is_adminify_activated($plugin_path = 'adminify/adminify.php')
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        return is_plugin_active($plugin_path);
    }

    // Function to check if a plugin is installed
    public function is_adminify_installed($plugin_slug = 'adminify/adminify.php' )
    {
        $plugins = get_plugins();
        foreach ($plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $plugin_slug) !== false) {
                return true;
            }
        }
        return false;
    }


    /**
     * Install Required WP Adminify Core Plugin
     */
    public function jltwp_adminify_notice_missing_main_plugin()
    {

        $plugin = 'adminify/adminify.php';
        if (!$this->is_adminify_installed()) {

            if (!current_user_can('install_plugins')) {
                return;
            }

            $install_activation_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=adminify'), 'install-plugin_adminify');
            $message = /* translators: 1: strong start tag, 2: strong end tag. */ sprintf(__('<b>WP Adminify Pro</b> requires %1$s"WP Adminify"%2$s plugin to be installed and activated. Please install WP Adminify to continue.', 'adminify'), '<strong>', '</strong>');
            $button_text = __('Install WP Adminify', 'adminify');

        } elseif (!$this->is_adminify_activated($plugin)) {

            if (!current_user_can('activate_plugins')) {
                return;
            }
            $install_activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin . '&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_' . $plugin);
            $message = __('<b>WP Adminify Pro</b> requires <strong>WP Adminify</strong> plugin to be active. Please activate WP Adminify to continue.', 'adminify');
            $button_text = __('Activate WP Adminify', 'adminify');
        }

        $button = '<p><a href="' . esc_url($install_activation_url) . '" class="button-primary">' . esc_html($button_text) . '</a></p>';

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p>%2$s</div>', $message, $button);
    }


    public function jltwp_adminify_includes_classes()
    {
        if (!$this->is_adminify_activated()) {
            return;
        }

        // new JLT_Adminify_Media_Replacer();
        // new GutenbergStyle();

        if (class_exists('\\WPAdminify\\Inc\\Admin\\AdminSettings')) {
            new WhiteLabel();
            new ColoredPost();
            new Schedule_Dark_Mode();

            new Tweaks_Pro();
            // new Users_Security();
            new RedirectUrls();
        }
        if (class_exists('WPAdminify\Inc\Modules\DismissNotices\Dismiss_Admin_Notices')) {
            new DismissNotice_Pro();
        }

        if (class_exists('WPAdminify\Inc\Classes\OutputCSS_Body')) {
            new OutputCSS_Body_Pro();
        }

        if (!empty($this->options['custom_admin_columns']['enable'])) {
            new CustomAdminColumns_Pro();
        }

        if (!empty($this->options['admin_pages'])) {
            new AdminPages();
        }

        if (!empty($this->options['enable_disable_comments'])) {
            new Disable_Comments_Pro();
        }

        // Horizontal Menu
        // $layout_type = ( !empty( $this->options['menu_layout_settings']['layout_type'] ) ) ? esc_html( $this->options['menu_layout_settings']['layout_type'] ) : 'horizontal';
        // if ('horizontal' === $layout_type) {
        //     new MenuStylePro();
        // }
    }


    public static function is_premium(){
        if (jltwp_adminify()->can_use_premium_code__premium_only()) {
            return true;
        }
        return false;
    }

    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

new Adminify_Pro();
