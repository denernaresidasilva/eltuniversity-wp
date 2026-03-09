<?php
namespace SmartWebinar\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class SessionEngine {

    public static function init(): void {
        add_action( 'wp_ajax_sw_create_session',        [ __CLASS__, 'ajax_create_session' ] );
        add_action( 'wp_ajax_nopriv_sw_create_session', [ __CLASS__, 'ajax_create_session' ] );
        add_action( 'wp_ajax_sw_end_session',           [ __CLASS__, 'ajax_end_session' ] );
        add_action( 'wp_ajax_nopriv_sw_end_session',    [ __CLASS__, 'ajax_end_session' ] );
    }

    // ── Session CRUD ──────────────────────────────────────────────────────────

    public static function create( int $webinar_id, int $user_id = 0 ): string {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $session_id = wp_generate_uuid4();

        $wpdb->insert( $wpdb->prefix . 'webinar_sessions', [ // phpcs:ignore
            'session_id'    => $session_id,
            'user_id'       => $user_id,
            'webinar_id'    => $webinar_id,
            'session_start' => current_time( 'mysql' ),
            'ip'            => WebinarEngine::get_ip(),
            'device'        => self::detect_device(),
        ] );

        \SmartWebinar\Events\EventDispatcher::dispatch( 'webinar_entered', $user_id, [
            'webinar_id' => $webinar_id,
            'session_id' => $session_id,
        ] );

        return $session_id;
    }

    public static function get( string $session_id ): ?object {
        global $wpdb;
        return $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_sessions WHERE session_id = %s LIMIT 1", $session_id )
        );
    }

    public static function end( string $session_id ): void {
        global $wpdb;
        $session = self::get( $session_id );
        if ( ! $session ) return;

        $watch_time = absint( $session->watch_time );
        $webinar    = WebinarEngine::get( (int) $session->webinar_id );
        $duration   = $webinar ? (int) $webinar->duration : 0;
        $progress   = $duration > 0 ? min( 100, (int) round( ( $watch_time / $duration ) * 100 ) ) : 0;

        $wpdb->update( $wpdb->prefix . 'webinar_sessions', [ // phpcs:ignore
            'session_end' => current_time( 'mysql' ),
            'progress'    => $progress,
        ], [ 'session_id' => $session_id ] );

        if ( $progress < 90 ) {
            \SmartWebinar\Events\EventDispatcher::dispatch( 'webinar_left_early', (int) $session->user_id, [
                'webinar_id' => (int) $session->webinar_id,
                'session_id' => $session_id,
                'progress'   => $progress,
            ] );
        }
    }

    public static function update_watch_time( string $session_id, int $seconds ): void {
        global $wpdb;
        $wpdb->update( $wpdb->prefix . 'webinar_sessions', // phpcs:ignore
            [ 'watch_time' => $seconds ],
            [ 'session_id' => $session_id ]
        );
    }

    public static function mark_no_show( int $webinar_id ): void {
        global $wpdb;
        // Sessions registered but never joined the live
        $wpdb->query( $wpdb->prepare( // phpcs:ignore
            "UPDATE {$wpdb->prefix}webinar_sessions SET no_show = 1 WHERE webinar_id = %d AND watch_time = 0 AND session_end IS NULL",
            $webinar_id
        ) );
    }

    // ── AJAX Handlers ─────────────────────────────────────────────────────────

    public static function ajax_create_session(): void {
        check_ajax_referer( 'sw_nonce', 'nonce' );
        $webinar_id = absint( wp_unslash( $_POST['webinar_id'] ?? 0 ) );
        if ( ! $webinar_id ) wp_send_json_error( 'Invalid webinar' );

        $session_id = self::create( $webinar_id );
        wp_send_json_success( [ 'session_id' => $session_id ] );
    }

    public static function ajax_end_session(): void {
        check_ajax_referer( 'sw_nonce', 'nonce' );
        $session_id = sanitize_text_field( wp_unslash( $_POST['session_id'] ?? '' ) );
        if ( $session_id ) self::end( $session_id );
        wp_send_json_success();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function detect_device(): string {
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        if ( preg_match( '/(tablet|ipad|playbook|silk)/i', $ua ) ) return 'tablet';
        if ( preg_match( '/(mobile|iphone|ipod|phone|android|blackberry|mini|windows\sce|palm)/i', $ua ) ) return 'mobile';
        return 'desktop';
    }
}
