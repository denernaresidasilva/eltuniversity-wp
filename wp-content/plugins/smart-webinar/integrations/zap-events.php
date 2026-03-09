<?php
namespace SmartWebinar\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bridge between Smart Webinar events and ZapWA automation.
 *
 * The EventDispatcher already calls do_action('zap_evento', $payload).
 * This class provides additional hooks for ZapWA-specific enrichment.
 */
class ZapEvents {

    public static function init(): void {
        add_action( 'smart_webinar_event', [ __CLASS__, 'log_event' ] );
    }

    /**
     * Log the event for internal analytics.
     */
    public static function log_event( array $payload ): void {
        // Store in transient for dashboard polling (last 100 events)
        $log   = get_transient( 'sw_event_log' ) ?: [];
        $log[] = $payload;
        if ( count( $log ) > 100 ) {
            $log = array_slice( $log, -100 );
        }
        set_transient( 'sw_event_log', $log, HOUR_IN_SECONDS );
    }
}
