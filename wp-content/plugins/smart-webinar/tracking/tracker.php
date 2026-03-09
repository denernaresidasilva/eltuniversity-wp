<?php
namespace SmartWebinar\Tracking;

if ( ! defined( 'ABSPATH' ) ) exit;

class Tracker {

    public static function init(): void {
        // Nothing to hook; recording is called from the REST API.
    }

    public static function record( array $data ): bool {
        global $wpdb;

        $row = [
            'user_id'    => absint( $data['user_id'] ?? get_current_user_id() ),
            'webinar_id' => absint( $data['webinar_id'] ?? 0 ),
            'session_id' => sanitize_text_field( $data['session_id'] ?? '' ),
            'event_type' => sanitize_key( $data['event_type'] ?? '' ),
            'watch_time' => absint( $data['watch_time'] ?? 0 ),
            'percentage' => min( 100, absint( $data['percentage'] ?? 0 ) ),
            'device'     => sanitize_text_field( $data['device'] ?? '' ),
            'ip'         => \SmartWebinar\Core\WebinarEngine::get_ip(),
            'timestamp'  => current_time( 'mysql' ),
        ];

        if ( ! $row['webinar_id'] || ! $row['event_type'] ) return false;

        $result = $wpdb->insert( $wpdb->prefix . 'webinar_tracking', $row ); // phpcs:ignore
        if ( false === $result ) return false;

        // Dispatch zap_evento for video milestone events
        $milestone_map = [
            'video_25'   => 'webinar_watched_25',
            'video_50'   => 'webinar_watched_50',
            'video_75'   => 'webinar_watched_75',
            'video_90'   => 'webinar_watched_90',
            'video_100'  => 'webinar_watched_100',
            'video_start'=> 'webinar_video_started',
        ];

        if ( isset( $milestone_map[ $row['event_type'] ] ) ) {
            \SmartWebinar\Events\EventDispatcher::dispatch(
                $milestone_map[ $row['event_type'] ],
                $row['user_id'],
                [
                    'webinar_id' => $row['webinar_id'],
                    'session_id' => $row['session_id'],
                    'watch_time' => $row['watch_time'],
                    'percentage' => $row['percentage'],
                ]
            );
        }

        // Update session watch time
        if ( $row['session_id'] ) {
            \SmartWebinar\Core\SessionEngine::update_watch_time( $row['session_id'], $row['watch_time'] );
        }

        return true;
    }
}
