<?php
/**
 * Aggregates analytics data for the dashboard.
 *
 * @package AISalesEngine\Modules\Analytics
 */

namespace AISalesEngine\Modules\Analytics;

if ( ! defined( 'ABSPATH' ) ) exit;

class Analytics {

    public static function init(): void {}

    /**
     * Build the summary payload for the dashboard.
     *
     * @return array<string,mixed>
     */
    public static function get_summary(): array {
        global $wpdb;

        $total_leads = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ai_leads"
        );

        $new_leads_today = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ai_leads WHERE DATE(created_at) = %s",
                gmdate( 'Y-m-d' )
            )
        );

        $total_events = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ai_events"
        );

        $avg_score = (float) $wpdb->get_var(
            "SELECT AVG(lead_score) FROM {$wpdb->prefix}ai_leads"
        );

        $top_sources = $wpdb->get_results(
            "SELECT source, COUNT(*) AS count
             FROM {$wpdb->prefix}ai_leads
             WHERE source != ''
             GROUP BY source
             ORDER BY count DESC
             LIMIT 5",
            ARRAY_A
        ) ?: [];

        $events_last_7_days = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) AS day, COUNT(*) AS count
                 FROM {$wpdb->prefix}ai_events
                 WHERE created_at >= %s
                 GROUP BY day
                 ORDER BY day ASC",
                gmdate( 'Y-m-d', strtotime( '-6 days' ) )
            ),
            ARRAY_A
        ) ?: [];

        $active_automations = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ai_automations WHERE status = 'active'"
        );

        $pending_jobs = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ai_jobs WHERE status = 'pending'"
        );

        return [
            'total_leads'        => $total_leads,
            'new_leads_today'    => $new_leads_today,
            'total_events'       => $total_events,
            'avg_score'          => round( $avg_score, 1 ),
            'top_sources'        => $top_sources,
            'events_last_7_days' => $events_last_7_days,
            'active_automations' => $active_automations,
            'pending_jobs'       => $pending_jobs,
        ];
    }
}
