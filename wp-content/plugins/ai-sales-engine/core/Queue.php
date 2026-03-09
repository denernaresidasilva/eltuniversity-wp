<?php
/**
 * Async job queue backed by wp_ai_jobs.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Queue {

    const CRON_HOOK     = 'ai_sales_engine_process_queue';
    const CRON_SCHEDULE = 'ai_sales_every_minute';

    public static function init(): void {
        add_filter( 'cron_schedules', [ self::class, 'add_cron_schedule' ] );
        add_action( self::CRON_HOOK, [ self::class, 'process' ] );

        if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
            wp_schedule_event( time(), self::CRON_SCHEDULE, self::CRON_HOOK );
        }
    }

    /**
     * Register a one-minute cron interval.
     *
     * @param array $schedules Existing WP cron schedules.
     * @return array
     */
    public static function add_cron_schedule( array $schedules ): array {
        $schedules[ self::CRON_SCHEDULE ] = [
            'interval' => 60,
            'display'  => __( 'Every Minute', 'ai-sales-engine' ),
        ];
        return $schedules;
    }

    /**
     * Dispatch a job by inserting it into the queue.
     *
     * @param string          $type    Job handler identifier.
     * @param array           $payload Arbitrary job data.
     * @param \DateTimeInterface|string|null $run_at When to run (default: now).
     */
    public static function dispatch( string $type, array $payload, $run_at = null ): int|false {
        global $wpdb;

        if ( $run_at instanceof \DateTimeInterface ) {
            $run_at_str = $run_at->format( 'Y-m-d H:i:s' );
        } elseif ( is_string( $run_at ) && $run_at !== '' ) {
            $run_at_str = $run_at;
        } else {
            $run_at_str = current_time( 'mysql' );
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_jobs',
            [
                'type'    => sanitize_text_field( $type ),
                'payload' => wp_json_encode( $payload ),
                'status'  => 'pending',
                'run_at'  => $run_at_str,
            ]
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Process pending jobs – called by WP-Cron.
     */
    public static function process(): void {
        global $wpdb;

        $jobs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_jobs
                 WHERE status = 'pending' AND run_at <= %s
                 ORDER BY run_at ASC LIMIT 20",
                current_time( 'mysql' )
            ),
            ARRAY_A
        );

        if ( empty( $jobs ) ) {
            return;
        }

        foreach ( $jobs as $job ) {
            // Mark as processing.
            $wpdb->update(
                $wpdb->prefix . 'ai_jobs',
                [ 'status' => 'processing' ],
                [ 'id' => (int) $job['id'] ]
            );

            try {
                $payload = json_decode( $job['payload'], true ) ?: [];
                do_action( 'ai_sales_engine_job_' . $job['type'], $payload, $job );

                $wpdb->update(
                    $wpdb->prefix . 'ai_jobs',
                    [ 'status' => 'done' ],
                    [ 'id' => (int) $job['id'] ]
                );
            } catch ( \Throwable $e ) {
                $attempts = (int) $job['attempts'] + 1;
                $status   = $attempts >= 3 ? 'failed' : 'pending';

                $wpdb->update(
                    $wpdb->prefix . 'ai_jobs',
                    [
                        'status'   => $status,
                        'attempts' => $attempts,
                    ],
                    [ 'id' => (int) $job['id'] ]
                );
            }
        }
    }
}
