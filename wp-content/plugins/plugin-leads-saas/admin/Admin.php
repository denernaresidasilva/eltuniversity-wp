<?php
namespace LeadsSaaS\Admin;

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
            'Gerenciador de Leads',
            'Leads SaaS',
            'manage_options',
            'leads-saas',
            [ self::class, 'render_app' ],
            'dashicons-groups',
            25
        );

        add_submenu_page( 'leads-saas', 'Dashboard',    'Dashboard',    'manage_options', 'leads-saas',           [ self::class, 'render_app' ] );
        add_submenu_page( 'leads-saas', 'Listas',       'Listas',       'manage_options', 'leads-saas-listas',    [ self::class, 'render_app' ] );
        add_submenu_page( 'leads-saas', 'Leads',        'Leads',        'manage_options', 'leads-saas-leads',     [ self::class, 'render_app' ] );
        add_submenu_page( 'leads-saas', 'Etiquetas',    'Etiquetas',    'manage_options', 'leads-saas-tags',      [ self::class, 'render_app' ] );
        add_submenu_page( 'leads-saas', 'Automações',   'Automações',   'manage_options', 'leads-saas-automacoes',[ self::class, 'render_app' ] );
    }

    public static function enqueue_assets( string $hook ): void {
        $pages = [
            'toplevel_page_leads-saas',
            'leads-saas_page_leads-saas-listas',
            'leads-saas_page_leads-saas-leads',
            'leads-saas_page_leads-saas-tags',
            'leads-saas_page_leads-saas-automacoes',
        ];

        if ( ! in_array( $hook, $pages, true ) ) {
            return;
        }

        wp_enqueue_style(
            'leads-saas-admin',
            LEADS_SAAS_URL . 'assets/css/admin.css',
            [],
            LEADS_SAAS_VERSION
        );

        // React + ReactDOM from WordPress core
        wp_enqueue_script( 'wp-element' );

        wp_enqueue_script(
            'leads-saas-admin',
            LEADS_SAAS_URL . 'assets/js/admin-app.js',
            [ 'wp-element' ],
            LEADS_SAAS_VERSION,
            true
        );

        wp_localize_script( 'leads-saas-admin', 'LeadsSaaSConfig', [
            'apiUrl'  => esc_url_raw( rest_url( 'leads/v1' ) ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'siteUrl' => esc_url_raw( get_site_url() ),
            'page'    => sanitize_key( filter_input( INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS ) ?: 'leads-saas' ),
        ] );
    }

    public static function render_app(): void {
        echo '<div id="leads-saas-app"></div>';
    }
}
