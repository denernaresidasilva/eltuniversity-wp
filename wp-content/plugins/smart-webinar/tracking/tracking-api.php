<?php
namespace SmartWebinar\Tracking;

if ( ! defined( 'ABSPATH' ) ) exit;

class TrackingAPI {

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_routes' ] );
    }

    public static function register_routes(): void {
        register_rest_route( 'webinar/v1', '/track', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_track' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'webinar_id' => [ 'required' => true,  'sanitize_callback' => 'absint' ],
                'session_id' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'event_type' => [ 'required' => true,  'sanitize_callback' => 'sanitize_key' ],
                'watch_time' => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
                'percentage' => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
                'device'     => [ 'required' => false, 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ],
                'nonce'      => [ 'required' => true ],
            ],
        ] );

        register_rest_route( 'webinar/v1', '/conversion', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_conversion' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'webinar_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
                'session_id' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'amount'     => [ 'required' => false, 'default' => 0 ],
                'nonce'      => [ 'required' => true ],
            ],
        ] );

        register_rest_route( 'webinar/v1', '/chat', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_chat' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'webinar_id' => [ 'required' => true,  'sanitize_callback' => 'absint' ],
                'session_id' => [ 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'message'    => [ 'required' => true,  'sanitize_callback' => 'sanitize_textarea_field' ],
                'nonce'      => [ 'required' => true ],
            ],
        ] );

        register_rest_route( 'webinar/v1', '/chat/messages', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_chat_messages' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'webinar_id' => [ 'required' => true, 'sanitize_callback' => 'absint' ],
                'session_id' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
                'after'      => [ 'required' => false, 'default' => 0, 'sanitize_callback' => 'absint' ],
                'nonce'      => [ 'required' => true ],
            ],
        ] );
    }

    public static function handle_track( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sw_nonce' ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid nonce' ], 403 );
        }
        $data = [
            'webinar_id' => $request->get_param( 'webinar_id' ),
            'session_id' => $request->get_param( 'session_id' ),
            'event_type' => $request->get_param( 'event_type' ),
            'watch_time' => $request->get_param( 'watch_time' ),
            'percentage' => $request->get_param( 'percentage' ),
            'device'     => $request->get_param( 'device' ),
            'user_id'    => get_current_user_id(),
        ];
        $ok = Tracker::record( $data );
        return new \WP_REST_Response( [ 'success' => $ok ], $ok ? 200 : 400 );
    }

    public static function handle_conversion( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sw_nonce' ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid nonce' ], 403 );
        }
        $webinar_id = $request->get_param( 'webinar_id' );
        $session_id = $request->get_param( 'session_id' );
        $amount     = (float) $request->get_param( 'amount' );
        \SmartWebinar\Core\WebinarEngine::record_conversion( $webinar_id, $session_id, 'webhook', $amount );
        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    public static function handle_chat( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sw_nonce' ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid nonce' ], 403 );
        }
        global $wpdb;
        $user    = wp_get_current_user();
        $user_id = $user->ID;
        $name    = $user->display_name ?: __( 'Visitante', 'smart-webinar' );

        $wpdb->insert( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
            'webinar_id'   => $request->get_param( 'webinar_id' ),
            'session_id'   => $request->get_param( 'session_id' ),
            'user_id'      => $user_id,
            'message_type' => 'user',
            'author_name'  => $name,
            'message'      => $request->get_param( 'message' ),
            'show_at'      => 0,
            'sent_at'      => current_time( 'mysql' ),
        ] );

        \SmartWebinar\Events\EventDispatcher::dispatch( 'chat_message_sent', $user_id, [
            'webinar_id' => $request->get_param( 'webinar_id' ),
            'session_id' => $request->get_param( 'session_id' ),
        ] );

        return new \WP_REST_Response( [ 'success' => true, 'id' => $wpdb->insert_id ], 200 );
    }

    public static function get_chat_messages( \WP_REST_Request $request ): \WP_REST_Response {
        if ( ! wp_verify_nonce( $request->get_param( 'nonce' ), 'sw_nonce' ) ) {
            return new \WP_REST_Response( [ 'error' => 'Invalid nonce' ], 403 );
        }
        global $wpdb;
        $webinar_id = $request->get_param( 'webinar_id' );
        $after      = $request->get_param( 'after' );

        $messages = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
            "SELECT id, author_name, author_avatar, message, message_type, show_at, sent_at
             FROM {$wpdb->prefix}webinar_chat
             WHERE webinar_id = %d AND (message_type IN ('recorded','scheduled') OR session_id != '')
             AND id > %d
             ORDER BY show_at ASC, sent_at ASC
             LIMIT 50",
            $webinar_id,
            $after
        ) );

        return new \WP_REST_Response( $messages, 200 );
    }
}
