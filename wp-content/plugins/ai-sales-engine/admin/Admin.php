<?php
/**
 * Registers the admin menu and enqueues admin assets.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ self::class, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
    }

    /**
     * Register the top-level menu and all sub-menus.
     */
    public static function register_menu(): void {
        add_menu_page(
            __( 'AI Sales Engine', 'ai-sales-engine' ),
            __( 'AI Sales Engine', 'ai-sales-engine' ),
            'manage_options',
            'ai-sales-engine',
            [ Dashboard::class, 'render' ],
            'dashicons-chart-line',
            30
        );

        $pages = [
            'ai-sales-engine'            => [ __( 'Dashboard',   'ai-sales-engine' ), [ Dashboard::class,   'render' ] ],
            'ai-sales-leads'             => [ __( 'Leads',        'ai-sales-engine' ), [ Leads::class,        'render' ] ],
            'ai-sales-lists'             => [ __( 'Lists',        'ai-sales-engine' ), [ Lists::class,        'render' ] ],
            'ai-sales-automations'       => [ __( 'Automations',  'ai-sales-engine' ), [ Automations::class,  'render' ] ],
            'ai-sales-agents'            => [ __( 'Agents',       'ai-sales-engine' ), [ Agents::class,       'render' ] ],
            'ai-sales-pipelines'         => [ __( 'Pipelines',    'ai-sales-engine' ), [ Pipelines::class,    'render' ] ],
            'ai-sales-analytics'         => [ __( 'Analytics',    'ai-sales-engine' ), [ Analytics::class,    'render' ] ],
            'ai-sales-settings'          => [ __( 'Settings',     'ai-sales-engine' ), [ Settings::class,     'render' ] ],
        ];

        foreach ( $pages as $slug => [ $label, $callback ] ) {
            add_submenu_page(
                'ai-sales-engine',
                $label,
                $label,
                'manage_options',
                $slug,
                $callback
            );
        }
    }

    /**
     * Enqueue CSS/JS on AI Sales Engine admin pages.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'ai-sales' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ai-sales-admin',
            AI_SALES_ENGINE_URL . 'assets/css/admin.css',
            [],
            AI_SALES_ENGINE_VERSION
        );

        wp_enqueue_script(
            'ai-sales-admin',
            AI_SALES_ENGINE_URL . 'assets/js/admin.js',
            [ 'wp-api', 'jquery' ],
            AI_SALES_ENGINE_VERSION,
            true
        );

        wp_localize_script(
            'ai-sales-admin',
            'AISalesAdmin',
            [
                'rest_url' => esc_url( rest_url( 'ai-sales/v1/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }
}
