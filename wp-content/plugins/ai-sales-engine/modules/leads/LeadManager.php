<?php
/**
 * CRUD operations for leads.
 *
 * @package AISalesEngine\Modules\Leads
 */

namespace AISalesEngine\Modules\Leads;

if ( ! defined( 'ABSPATH' ) ) exit;

class LeadManager {

    public static function init(): void {
        // Nothing to hook on init.
    }

    /**
     * Create a new lead.
     *
     * @param array $data Lead fields.
     * @return int|false New lead ID or false.
     */
    public static function create( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_leads',
            [
                'name'         => sanitize_text_field( $data['name']         ?? '' ),
                'email'        => sanitize_email(      $data['email']        ?? '' ),
                'phone'        => sanitize_text_field( $data['phone']        ?? '' ),
                'whatsapp'     => sanitize_text_field( $data['whatsapp']     ?? '' ),
                'instagram'    => sanitize_text_field( $data['instagram']    ?? '' ),
                'source'       => sanitize_text_field( $data['source']       ?? '' ),
                'utm_source'   => sanitize_text_field( $data['utm_source']   ?? '' ),
                'utm_medium'   => sanitize_text_field( $data['utm_medium']   ?? '' ),
                'utm_campaign' => sanitize_text_field( $data['utm_campaign'] ?? '' ),
                'status'       => 'active',
                'lead_score'   => 0,
            ]
        );

        if ( ! $result ) {
            return false;
        }

        $lead_id = (int) $wpdb->insert_id;

        \AISalesEngine\Core\EventDispatcher::dispatch( 'lead_created', $lead_id, '', $data );

        return $lead_id;
    }

    /**
     * Update an existing lead.
     *
     * @param int   $id   Lead ID.
     * @param array $data Fields to update.
     */
    public static function update( int $id, array $data ): void {
        global $wpdb;

        $allowed = [ 'name', 'email', 'phone', 'whatsapp', 'instagram', 'lead_score',
                     'source', 'utm_source', 'utm_medium', 'utm_campaign', 'status' ];

        $sanitized = [];
        foreach ( $data as $key => $value ) {
            if ( ! in_array( $key, $allowed, true ) ) {
                continue;
            }
            $sanitized[ $key ] = ( $key === 'lead_score' )
                ? (int) $value
                : sanitize_text_field( (string) $value );
        }

        if ( $sanitized ) {
            $wpdb->update( $wpdb->prefix . 'ai_leads', $sanitized, [ 'id' => $id ] );
        }

        \AISalesEngine\Core\EventDispatcher::dispatch( 'lead_updated', $id );
    }

    /**
     * Get a single lead with tags, lists and score.
     *
     * @param int $id Lead ID.
     * @return array<string,mixed>|null
     */
    public static function get( int $id ): ?array {
        global $wpdb;

        $lead = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_leads WHERE id = %d",
                $id
            ),
            ARRAY_A
        );

        if ( ! $lead ) {
            return null;
        }

        $lead['tags']  = self::get_tags( $id );
        $lead['lists'] = self::get_lists( $id );

        return $lead;
    }

    /**
     * Get a lead by email address.
     *
     * @param string $email Email address.
     * @return array<string,mixed>|null
     */
    public static function get_by_email( string $email ): ?array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_leads WHERE email = %s LIMIT 1",
                sanitize_email( $email )
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    /**
     * Paginated list with optional filters.
     *
     * @param array $args { search, per_page, page, status }.
     * @return array{ data: array, total: int, pages: int }
     */
    public static function list( array $args = [] ): array {
        global $wpdb;

        $search   = $args['search']   ?? '';
        $per_page = max( 1, (int) ( $args['per_page'] ?? 20 ) );
        $page     = max( 1, (int) ( $args['page']     ?? 1  ) );
        $status   = $args['status']   ?? '';

        $where  = 'WHERE 1=1';
        $params = [];

        if ( $search ) {
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $where   .= ' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ( $status ) {
            $where   .= ' AND status = %s';
            $params[] = $status;
        }

        $offset = ( $page - 1 ) * $per_page;

        // Count total.
        $count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}ai_leads $where";
        $total     = (int) ( $params
            ? $wpdb->get_var( $wpdb->prepare( $count_sql, ...$params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            : $wpdb->get_var( $count_sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        // Fetch rows.
        $data_sql = "SELECT * FROM {$wpdb->prefix}ai_leads $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $rows = $params
            ? $wpdb->get_results( $wpdb->prepare( $data_sql, ...array_merge( $params, [ $per_page, $offset ] ) ), ARRAY_A ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            : $wpdb->get_results( $wpdb->prepare( $data_sql, $per_page, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return [
            'data'  => $rows ?: [],
            'total' => $total,
            'pages' => (int) ceil( $total / $per_page ),
        ];
    }

    /**
     * Delete a lead and all related data.
     *
     * @param int $id Lead ID.
     */
    public static function delete( int $id ): void {
        global $wpdb;

        $wpdb->delete( $wpdb->prefix . 'ai_list_leads',    [ 'lead_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'ai_lead_tags',     [ 'lead_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'ai_events',        [ 'lead_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'ai_pipeline_leads',[ 'lead_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'ai_leads',         [ 'id'      => $id ] );
    }

    /**
     * Add a lead to a list.
     *
     * @param int $lead_id Lead ID.
     * @param int $list_id List ID.
     */
    public static function add_to_list( int $lead_id, int $list_id ): void {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT lead_id FROM {$wpdb->prefix}ai_list_leads WHERE lead_id = %d AND list_id = %d",
                $lead_id,
                $list_id
            )
        );

        if ( ! $exists ) {
            $wpdb->insert(
                $wpdb->prefix . 'ai_list_leads',
                [ 'lead_id' => $lead_id, 'list_id' => $list_id ]
            );
        }
    }

    /**
     * Get the event timeline for a lead.
     *
     * @param int $lead_id Lead ID.
     * @return array<int,array<string,mixed>>
     */
    public static function get_timeline( int $lead_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_events WHERE lead_id = %d ORDER BY created_at DESC",
                $lead_id
            ),
            ARRAY_A
        ) ?: [];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function get_tags( int $lead_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.* FROM {$wpdb->prefix}ai_tags t
                 INNER JOIN {$wpdb->prefix}ai_lead_tags lt ON lt.tag_id = t.id
                 WHERE lt.lead_id = %d",
                $lead_id
            ),
            ARRAY_A
        ) ?: [];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function get_lists( int $lead_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT l.* FROM {$wpdb->prefix}ai_lists l
                 INNER JOIN {$wpdb->prefix}ai_list_leads ll ON ll.list_id = l.id
                 WHERE ll.lead_id = %d",
                $lead_id
            ),
            ARRAY_A
        ) ?: [];
    }
}
