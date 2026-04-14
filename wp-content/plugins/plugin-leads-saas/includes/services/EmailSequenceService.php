<?php
namespace LeadsSaaS\Services;

use LeadsSaaS\Models\EmailSequence;
use LeadsSaaS\Models\Lead;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EmailSequenceService {

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    /**
     * Schedule the first step of a list's email sequence for a lead.
     * Called when a lead enters a list.
     */
    public static function schedule_for_lead( int $lead_id, int $lista_id ): void {
        if ( $lead_id <= 0 || $lista_id <= 0 ) {
            return;
        }
        $sequence = EmailSequence::get_by_lista( $lista_id );
        if ( ! $sequence ) {
            return;
        }
        $steps = EmailSequence::get_steps( (int) $sequence['id'] );
        if ( empty( $steps ) ) {
            return;
        }

        // Step 1 is immediate.
        $first_step = $steps[0];
        $token      = wp_generate_password( 32, false );
        EmailSequence::enqueue( [
            'sequence_id'  => (int) $sequence['id'],
            'step_id'      => (int) $first_step['id'],
            'lead_id'      => $lead_id,
            'scheduled_at' => gmdate( 'Y-m-d H:i:s' ),
            'open_token'   => $token,
        ] );
    }

    /**
     * Process pending queue items. Runs on WP-Cron.
     */
    public static function process_queue(): void {
        $items = EmailSequence::get_pending_queue();
        foreach ( $items as $item ) {
            self::process_item( $item );
        }
    }

    // ----------------------------------------------------------------
    // Cron setup
    // ----------------------------------------------------------------

    public static function register_cron(): void {
        add_filter( 'cron_schedules', [ self::class, 'add_cron_interval' ] );
        add_action( 'leads_saas_process_email_queue', [ self::class, 'process_queue' ] );

        if ( ! wp_next_scheduled( 'leads_saas_process_email_queue' ) ) {
            wp_schedule_event( time(), 'leads_saas_five_minutes', 'leads_saas_process_email_queue' );
        }
    }

    public static function add_cron_interval( array $schedules ): array {
        $schedules['leads_saas_five_minutes'] = [
            'interval' => 300,
            'display'  => 'Every 5 Minutes',
        ];
        return $schedules;
    }

    // ----------------------------------------------------------------
    // Queue processing (private)
    // ----------------------------------------------------------------

    private static function process_item( array $item ): void {
        // Check "wait for previous step open" gate.
        if ( ! empty( $item['prev_queue_id'] ) ) {
            $prev           = EmailSequence::get_queue_item( (int) $item['prev_queue_id'] );
            $prev_opened    = $prev && ! empty( $prev['opened_at'] );
            $max_wait_passed = ! empty( $item['wait_open_until'] ) &&
                               gmdate( 'Y-m-d H:i:s' ) >= $item['wait_open_until'];

            if ( ! $prev_opened && ! $max_wait_passed ) {
                // Not ready yet; leave as pending.
                return;
            }
        }

        $step = EmailSequence::get_step( (int) $item['step_id'] );
        if ( ! $step ) {
            EmailSequence::update_queue_status( (int) $item['id'], 'failed' );
            return;
        }

        $lead = Lead::find( (int) $item['lead_id'] );
        if ( ! $lead || empty( $lead['email'] ) ) {
            EmailSequence::update_queue_status( (int) $item['id'], 'failed' );
            return;
        }

        // Build tracking pixel URL (public, no auth).
        $tracking_url   = get_rest_url( null, 'leads/v1/email/open/' . $item['open_token'] );
        $tracking_pixel = '<img src="' . esc_url( $tracking_url ) . '" width="1" height="1" alt="" style="display:none">';

        $html_body = $step['corpo_html'] . $tracking_pixel;

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo( 'name' ) . ' <' . get_option( 'admin_email' ) . '>',
        ];

        $sent = wp_mail( $lead['email'], $step['assunto'], $html_body, $headers );

        if ( $sent ) {
            EmailSequence::update_queue_status(
                (int) $item['id'],
                'sent',
                [ 'sent_at' => gmdate( 'Y-m-d H:i:s' ) ]
            );
            self::schedule_next_step( $item, $step );
        } else {
            EmailSequence::update_queue_status( (int) $item['id'], 'failed' );
        }
    }

    private static function schedule_next_step( array $current_item, array $current_step ): void {
        $next_step = EmailSequence::get_next_step(
            (int) $current_item['sequence_id'],
            (int) $current_step['step_order']
        );
        if ( ! $next_step ) {
            return; // sequence finished
        }

        $delay_seconds = self::to_seconds( (int) $next_step['delay_value'], $next_step['delay_unit'] );
        $scheduled_at  = gmdate( 'Y-m-d H:i:s', time() + $delay_seconds );

        $prev_queue_id   = null;
        $wait_open_until = null;

        if ( (int) $current_step['wait_for_open'] ) {
            $prev_queue_id   = (int) $current_item['id'];
            $max_wait_secs   = self::to_seconds( (int) $current_step['max_wait_value'], $current_step['max_wait_unit'] );
            $wait_open_until = gmdate( 'Y-m-d H:i:s', time() + $max_wait_secs );
        }

        $token = wp_generate_password( 32, false );
        EmailSequence::enqueue( [
            'sequence_id'    => (int) $current_item['sequence_id'],
            'step_id'        => (int) $next_step['id'],
            'lead_id'        => (int) $current_item['lead_id'],
            'scheduled_at'   => $scheduled_at,
            'open_token'     => $token,
            'prev_queue_id'  => $prev_queue_id,
            'wait_open_until'=> $wait_open_until,
        ] );
    }

    private static function to_seconds( int $value, string $unit ): int {
        return $unit === 'days' ? $value * 86400 : $value * 3600;
    }
}
