<?php
/**
 * Enqueues the front-end tracker script on public pages.
 *
 * @package AISalesEngine
 */

namespace AISalesEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class PublicTracker {

    public static function init(): void {
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_tracker' ] );
    }

    /**
     * Enqueue the tracker JS when tracker_enabled option is true.
     */
    public static function enqueue_tracker(): void {
        $settings = get_option( 'ai_sales_engine_settings', [] );

        if ( empty( $settings['tracker_enabled'] ) ) {
            return;
        }

        wp_enqueue_script(
            'ai-sales-tracker',
            AI_SALES_ENGINE_URL . 'assets/js/ai-tracker.js',
            [],
            AI_SALES_ENGINE_VERSION,
            true
        );

        wp_localize_script(
            'ai-sales-tracker',
            'AISalesTracker',
            [
                'endpoint' => esc_url( rest_url( 'ai-sales/v1/event' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }
}
