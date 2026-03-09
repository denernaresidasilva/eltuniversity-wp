<?php
/**
 * Calculates and updates lead scores based on events.
 *
 * @package AISalesEngine\Modules\Scoring
 */

namespace AISalesEngine\Modules\Scoring;

if ( ! defined( 'ABSPATH' ) ) exit;

class LeadScoring {

    /**
     * Default scoring rules (points per event).
     *
     * @var array<string,int>
     */
    private static array $default_rules = [
        'page_visit'         => 10,
        'message_reply'      => 20,
        'webinar_completed'  => 50,
        'purchase_completed' => 100,
    ];

    public static function init(): void {}

    /**
     * Apply scoring for an event to a specific lead.
     *
     * @param int    $lead_id    Lead ID.
     * @param string $event_name Event name.
     */
    public static function apply_event( int $lead_id, string $event_name ): void {
        $rules  = self::get_rules();
        $points = $rules[ $event_name ] ?? 0;

        if ( $points === 0 ) {
            return;
        }

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}ai_leads
                 SET lead_score = lead_score + %d
                 WHERE id = %d",
                $points,
                $lead_id
            )
        );
    }

    /**
     * Return the active scoring rules (merges option overrides with defaults).
     *
     * @return array<string,int>
     */
    public static function get_rules(): array {
        $settings = get_option( 'ai_sales_engine_settings', [] );
        $saved    = $settings['scoring_rules'] ?? [];

        return array_merge( self::$default_rules, (array) $saved );
    }

    /**
     * Recalculate the score for a lead from scratch.
     *
     * @param int $lead_id Lead ID.
     */
    public static function recalculate( int $lead_id ): void {
        global $wpdb;
        $rules = self::get_rules();

        $events = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_name FROM {$wpdb->prefix}ai_events WHERE lead_id = %d",
                $lead_id
            ),
            ARRAY_A
        ) ?: [];

        $total = 0;
        foreach ( $events as $event ) {
            $total += $rules[ $event['event_name'] ] ?? 0;
        }

        $wpdb->update(
            $wpdb->prefix . 'ai_leads',
            [ 'lead_score' => $total ],
            [ 'id' => $lead_id ]
        );
    }
}
