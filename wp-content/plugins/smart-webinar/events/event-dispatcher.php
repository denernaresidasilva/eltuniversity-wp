<?php
namespace SmartWebinar\Events;

if ( ! defined( 'ABSPATH' ) ) exit;

class EventDispatcher {

    /**
     * All valid event keys.
     */
    private const VALID_EVENTS = [
        'webinar_registered',
        'webinar_clicked',
        'webinar_entered',
        'webinar_countdown_started',
        'webinar_live_started',
        'webinar_video_started',
        'webinar_watched_25',
        'webinar_watched_50',
        'webinar_watched_75',
        'webinar_watched_90',
        'webinar_watched_100',
        'webinar_left_early',
        'webinar_offer_shown',
        'webinar_offer_clicked',
        'webinar_offer_converted',
        'webinar_replay_available',
        'webinar_replay_watched',
        'webinar_no_show',
        'chat_message_sent',
    ];

    public static function init(): void {
        // Nothing to hook on init; dispatch is called statically from other modules.
    }

    /**
     * Dispatch an event.
     *
     * @param string $event      One of the VALID_EVENTS keys.
     * @param int    $user_id    A valid WordPress user ID (or 0 for guests).
     * @param array  $context    Must contain webinar_id and session_id.
     */
    public static function dispatch( string $event, int $user_id, array $context = [] ): void {
        if ( ! in_array( $event, self::VALID_EVENTS, true ) ) {
            return;
        }

        $webinar_id = isset( $context['webinar_id'] ) ? absint( $context['webinar_id'] ) : 0;
        $session_id = isset( $context['session_id'] ) ? sanitize_text_field( $context['session_id'] ) : '';

        $payload = [
            'event'     => $event,
            'user_id'   => $user_id,
            'context'   => array_merge( $context, [
                'webinar_id' => $webinar_id,
                'session_id' => $session_id,
            ] ),
            'timestamp' => time(),
        ];

        /**
         * Internal Smart Webinar event.
         *
         * @param array $payload { event, user_id, context, timestamp }
         */
        do_action( 'smart_webinar_event', $payload );

        /**
         * ZapWA global WhatsApp automation hook.
         *
         * @param array $payload { event, user_id, context, timestamp }
         */
        if ( $user_id > 0 && $webinar_id > 0 ) {
            do_action( 'zap_evento', $payload );
        }
    }

    public static function get_valid_events(): array {
        return self::VALID_EVENTS;
    }
}
