<?php
/**
 * Plugin Name: Continuar Assistindo
 * Plugin URI: https://example.com
 * Description: Widget de carrossel simplificado para Elementor
 * Version: 2.0.0
 * Author: Seu Nome
 * License: GPL v2 or later
 * Text Domain: supermembros-carousel
 * Elementor tested up to: 3.18.0
 * Elementor Pro tested up to: 3.18.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Main Supermembros Carousel Class
 */
final class Supermembros_Carousel {

    /**
     * Plugin Version
     */
    const VERSION = '2.0.0';

    /**
     * Minimum Elementor Version
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';

    /**
     * Minimum PHP Version
     */
    const MINIMUM_PHP_VERSION = '7.4';

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'i18n']);
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Load Textdomain
     */
    public function i18n() {
        load_plugin_textdomain('supermembros-carousel');
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_php_version']);
            return;
        }

        // Add Plugin actions
        add_action('elementor/widgets/widgets_registered', [$this, 'init_widgets']);
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Admin notice
     */
    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'supermembros-carousel'),
            '<strong>' . esc_html__('Supermembros Carousel', 'supermembros-carousel') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'supermembros-carousel') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     */
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'supermembros-carousel'),
            '<strong>' . esc_html__('Supermembros Carousel', 'supermembros-carousel') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'supermembros-carousel') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     */
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) unset($_GET['activate']);

        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'supermembros-carousel'),
            '<strong>' . esc_html__('Supermembros Carousel', 'supermembros-carousel') . '</strong>',
            '<strong>' . esc_html__('PHP', 'supermembros-carousel') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Init Widgets
     */
    public function init_widgets() {
        // Include Widget files
        require_once(__DIR__ . '/widgets/carousel-widget.php');

        // Register widget
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new \Supermembros_Carousel_Widget());
    }
    
    /**
     * Add Elementor Widget Categories
     */
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'supermembros',
            [
                'title' => esc_html__('Supermembros', 'supermembros-carousel'),
                'icon' => 'fa fa-plug',
            ]
        );
    }
    
    /**
     * Enqueue Scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('supermembros-carousel', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], self::VERSION);
        wp_enqueue_script('supermembros-carousel', plugin_dir_url(__FILE__) . 'assets/js/script.js', ['jquery'], self::VERSION, true);
    }
}

Supermembros_Carousel::instance();