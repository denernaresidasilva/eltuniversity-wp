<?php
namespace SmartWebinar\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class AutomationEngine {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'schedule_events' ] );
        add_action( 'sw_check_no_shows', [ __CLASS__, 'process_no_shows' ] );
    }

    public static function schedule_events(): void {
        if ( ! wp_next_scheduled( 'sw_check_no_shows' ) ) {
            wp_schedule_event( time(), 'hourly', 'sw_check_no_shows' );
        }
    }

    public static function process_no_shows(): void {
        global $wpdb;
        // Webinars that ended more than 1 hour ago with unresolved sessions
        $ended_webinars = $wpdb->get_results( // phpcs:ignore
            "SELECT id FROM {$wpdb->prefix}webinars 
             WHERE status = 'ended' 
             AND scheduled_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        foreach ( $ended_webinars as $webinar ) {
            // Get no-show sessions
            $sessions = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
                "SELECT session_id, user_id, webinar_id 
                 FROM {$wpdb->prefix}webinar_sessions 
                 WHERE webinar_id = %d AND watch_time = 0 AND no_show = 0",
                $webinar->id
            ) );
            foreach ( $sessions as $session ) {
                \SmartWebinar\Events\EventDispatcher::dispatch( 'webinar_no_show', (int) $session->user_id, [
                    'webinar_id' => (int) $session->webinar_id,
                    'session_id' => $session->session_id,
                ] );
            }
            SessionEngine::mark_no_show( (int) $webinar->id );
        }
    }
}
