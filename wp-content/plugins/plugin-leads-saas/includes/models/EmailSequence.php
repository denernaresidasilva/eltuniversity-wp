<?php
namespace LeadsSaaS\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EmailSequence {

    private static function table_seq(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_email_sequences';
    }

    private static function table_steps(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_email_sequence_steps';
    }

    private static function table_queue(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_email_queue';
    }

    // ----------------------------------------------------------------
    // Sequences
    // ----------------------------------------------------------------

    public static function get_or_create_by_lista( int $lista_id ): array {
        global $wpdb;
        $t   = self::table_seq();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $t WHERE lista_id = %d", $lista_id ),
            ARRAY_A
        );
        if ( ! $row ) {
            $wpdb->insert( $t, [
                'lista_id'   => $lista_id,
                'nome'       => '',
                'created_at' => gmdate( 'Y-m-d H:i:s' ),
            ] );
            $row = [ 'id' => (int) $wpdb->insert_id, 'lista_id' => $lista_id, 'nome' => '' ];
        }
        return $row;
    }

    public static function get_by_lista( int $lista_id ): ?array {
        global $wpdb;
        $t   = self::table_seq();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $t WHERE lista_id = %d", $lista_id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    // ----------------------------------------------------------------
    // Steps
    // ----------------------------------------------------------------

    public static function get_steps( int $sequence_id ): array {
        global $wpdb;
        $t = self::table_steps();
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $t WHERE sequence_id = %d ORDER BY step_order ASC",
                $sequence_id
            ),
            ARRAY_A
        ) ?: [];
    }

    public static function save_steps( int $sequence_id, array $steps ): void {
        global $wpdb;
        $t = self::table_steps();
        $wpdb->delete( $t, [ 'sequence_id' => $sequence_id ] );
        foreach ( $steps as $order => $step ) {
            $delay_unit     = in_array( $step['delay_unit']     ?? 'hours', [ 'hours', 'days' ], true ) ? $step['delay_unit']     : 'hours';
            $max_wait_unit  = in_array( $step['max_wait_unit']  ?? 'hours', [ 'hours', 'days' ], true ) ? $step['max_wait_unit']  : 'hours';
            $wpdb->insert( $t, [
                'sequence_id'    => $sequence_id,
                'step_order'     => (int) $order,
                'assunto'        => sanitize_text_field( $step['assunto']        ?? '' ),
                'corpo_html'     => wp_kses_post( $step['corpo_html']            ?? '' ),
                'delay_value'    => (int) ( $step['delay_value']                 ?? 0 ),
                'delay_unit'     => $delay_unit,
                'wait_for_open'  => empty( $step['wait_for_open'] ) ? 0 : 1,
                'max_wait_value' => (int) ( $step['max_wait_value']              ?? 48 ),
                'max_wait_unit'  => $max_wait_unit,
            ] );
        }
    }

    public static function get_step( int $step_id ): ?array {
        global $wpdb;
        $t   = self::table_steps();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $step_id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function get_next_step( int $sequence_id, int $current_order ): ?array {
        global $wpdb;
        $t   = self::table_steps();
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $t WHERE sequence_id = %d AND step_order > %d ORDER BY step_order ASC LIMIT 1",
                $sequence_id,
                $current_order
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    // ----------------------------------------------------------------
    // Queue
    // ----------------------------------------------------------------

    /**
     * Add an item to the send queue.
     *
     * @param array{sequence_id:int,step_id:int,lead_id:int,scheduled_at:string,open_token:string,prev_queue_id?:int|null,wait_open_until?:string|null} $data
     */
    public static function enqueue( array $data ): int {
        global $wpdb;
        $t = self::table_queue();
        $wpdb->insert( $t, [
            'sequence_id'    => (int) $data['sequence_id'],
            'step_id'        => (int) $data['step_id'],
            'lead_id'        => (int) $data['lead_id'],
            'status'         => 'pending',
            'scheduled_at'   => $data['scheduled_at'],
            'open_token'     => $data['open_token'],
            'prev_queue_id'  => isset( $data['prev_queue_id'] ) ? (int) $data['prev_queue_id'] : null,
            'wait_open_until'=> $data['wait_open_until'] ?? null,
            'created_at'     => gmdate( 'Y-m-d H:i:s' ),
        ] );
        return (int) $wpdb->insert_id;
    }

    /** @return array<int,array> */
    public static function get_pending_queue(): array {
        global $wpdb;
        $t   = self::table_queue();
        $now = gmdate( 'Y-m-d H:i:s' );
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $t WHERE status = 'pending' AND scheduled_at <= %s LIMIT 50", $now ),
            ARRAY_A
        ) ?: [];
    }

    public static function get_queue_item( int $id ): ?array {
        global $wpdb;
        $t   = self::table_queue();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $t WHERE id = %d", $id ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public static function update_queue_status( int $id, string $status, array $extra = [] ): void {
        global $wpdb;
        $t    = self::table_queue();
        $data = array_merge( [ 'status' => $status ], $extra );
        $wpdb->update( $t, $data, [ 'id' => $id ] );
    }

    public static function record_open( string $token ): bool {
        if ( '' === $token ) {
            return false;
        }
        global $wpdb;
        $t   = self::table_queue();
        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $t WHERE open_token = %s AND status = 'sent'", $token ),
            ARRAY_A
        );
        if ( ! $row ) {
            return false;
        }
        if ( $row['opened_at'] ) {
            return true; // already recorded
        }
        $wpdb->update( $t, [ 'opened_at' => gmdate( 'Y-m-d H:i:s' ) ], [ 'id' => $row['id'] ] );
        return true;
    }
}
