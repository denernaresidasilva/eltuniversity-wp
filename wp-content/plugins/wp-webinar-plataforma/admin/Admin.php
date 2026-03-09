<?php
namespace WebinarPlataforma\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ self::class, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
    }

    public static function register_menus(): void {
        add_menu_page(
            'Plataforma de Webinars',
            'Webinars',
            'manage_options',
            'wp-webinar-plataforma',
            [ self::class, 'render_app' ],
            'dashicons-video-alt3',
            30
        );

        add_submenu_page( 'wp-webinar-plataforma', 'Dashboard',    'Dashboard',    'manage_options', 'wp-webinar-plataforma',            [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Webinars',     'Webinars',     'manage_options', 'wp-webinar-webinars',              [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Participantes','Participantes','manage_options', 'wp-webinar-participantes',         [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Chat',         'Chat',         'manage_options', 'wp-webinar-chat',                  [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Automações',   'Automações',   'manage_options', 'wp-webinar-automacoes',            [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Analytics',    'Analytics',    'manage_options', 'wp-webinar-analytics',             [ self::class, 'render_app' ] );
        add_submenu_page( 'wp-webinar-plataforma', 'Configurações','Configurações','manage_options', 'wp-webinar-configuracoes',         [ self::class, 'render_app' ] );
    }

    public static function enqueue_assets( string $hook ): void {
        $pages = [
            'toplevel_page_wp-webinar-plataforma',
            'webinars_page_wp-webinar-webinars',
            'webinars_page_wp-webinar-participantes',
            'webinars_page_wp-webinar-chat',
            'webinars_page_wp-webinar-automacoes',
            'webinars_page_wp-webinar-analytics',
            'webinars_page_wp-webinar-configuracoes',
        ];

        if ( ! in_array( $hook, $pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'wp-webinar-admin',
            WP_WEBINAR_URL . 'assets/css/admin.css',
            [],
            WP_WEBINAR_VERSION
        );

        wp_enqueue_script( 'wp-element' );

        wp_enqueue_script(
            'wp-webinar-admin',
            WP_WEBINAR_URL . 'assets/js/admin-app.js',
            [ 'wp-element' ],
            WP_WEBINAR_VERSION,
            true
        );

        wp_localize_script( 'wp-webinar-admin', 'WPWebinarConfig', [
            'apiUrl'  => esc_url_raw( rest_url( 'webinar/v1' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'siteUrl' => esc_url_raw( get_site_url() ),
            'page'    => sanitize_key( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS ) ?: 'wp-webinar-plataforma' ),
        ] );
    }

    public static function render_app(): void {
        echo '<div id="wp-webinar-app"></div>';
    }
}
