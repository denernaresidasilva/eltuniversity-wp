<?php
/**
 * Logs events to wp_ai_events and fires automation triggers.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class EventDispatcher {

    public static function init(): void {
        // Nothing to hook on init; dispatch() is called by other modules.
    }

    /**
     * Log an event and trigger matching automations.
     *
     * @param string $event_name  e.g. 'page_visit', 'message_reply'.
     * @param int    $lead_id     ID of the associated lead (0 for anonymous).
     * @param string $event_value Optional scalar value for the event.
     * @param array  $metadata    Additional key/value metadata.
     */
    public static function dispatch(
        string $event_name,
        int    $lead_id,
        string $event_value = '',
        array  $metadata    = []
    ): void {
        global $wpdb;

        // Persist the event.
        $wpdb->insert(
            $wpdb->prefix . 'ai_events',
            [
                'lead_id'     => $lead_id,
                'event_name'  => sanitize_text_field( $event_name ),
                'event_value' => sanitize_text_field( $event_value ),
                'metadata'    => wp_json_encode( $metadata ),
            ]
        );

        // Update lead score via LeadScoring module.
        if ( $lead_id > 0 ) {
            \AISalesEngine\Modules\Scoring\LeadScoring::apply_event( $lead_id, $event_name );
        }

        // Let WordPress hooks receive the event.
        do_action( 'ai_sales_engine_event', $event_name, $lead_id, $event_value, $metadata );

        // Trigger automation engine.
        \AISalesEngine\Core\AutomationEngine::handle_event( $event_name, $lead_id, $metadata );
    }
}
