<?php
namespace SmartWebinar\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class WebinarEngine {

    public static function init(): void {
        add_action( 'init', [ __CLASS__, 'register_rewrite_rules' ] );
        add_filter( 'query_vars', [ __CLASS__, 'add_query_vars' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_handle_conversion' ] );
    }

    public static function register_rewrite_rules(): void {
        add_rewrite_rule(
            '^webinar/([^/]+)/?$',
            'index.php?smart_webinar_slug=$matches[1]',
            'top'
        );
    }

    public static function add_query_vars( array $vars ): array {
        $vars[] = 'smart_webinar_slug';
        return $vars;
    }

    public static function maybe_handle_conversion(): void {
        // Track conversion via query string
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['webinar_conversion'], $_GET['webinar_id'], $_GET['session_id'] ) ) {
            $webinar_id = absint( wp_unslash( $_GET['webinar_id'] ) );
            $session_id = sanitize_text_field( wp_unslash( $_GET['session_id'] ) );
            if ( $webinar_id && $session_id ) {
                self::record_conversion( $webinar_id, $session_id, 'querystring' );
            }
        }
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public static function create( array $data ): int|false {
        global $wpdb;
        $defaults = [
            'title'        => '',
            'slug'         => '',
            'description'  => '',
            'mode'         => 'simulated',
            'video_url'    => '',
            'youtube_id'   => null,
            'status'       => 'draft',
            'scheduled_at' => null,
            'duration'     => 0,
            'created_by'   => get_current_user_id(),
        ];
        $row = wp_parse_args( $data, $defaults );
        $row['slug'] = $row['slug'] ?: sanitize_title( $row['title'] );
        $row['slug'] = self::unique_slug( $row['slug'] );

        $result = $wpdb->insert( $wpdb->prefix . 'webinars', $row ); // phpcs:ignore
        return $result ? (int) $wpdb->insert_id : false;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $result = $wpdb->update( // phpcs:ignore
            $wpdb->prefix . 'webinars',
            $data,
            [ 'id' => $id ]
        );
        return false !== $result;
    }

    public static function get( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d LIMIT 1", $id )
        );
    }

    public static function get_by_slug( string $slug ): ?object {
        global $wpdb;
        return $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE slug = %s LIMIT 1", $slug )
        );
    }

    public static function get_all( array $args = [] ): array {
        global $wpdb;
        $defaults = [ 'status' => '', 'limit' => 50, 'offset' => 0, 'orderby' => 'created_at', 'order' => 'DESC' ];
        $args     = wp_parse_args( $args, $defaults );
        $where    = '';
        if ( $args['status'] ) {
            $where = $wpdb->prepare( 'WHERE status = %s', $args['status'] );
        }
        $order  = in_array( strtoupper( $args['order'] ), [ 'ASC', 'DESC' ], true ) ? $args['order'] : 'DESC';
        $orderby_map = [ 'created_at', 'title', 'scheduled_at', 'status' ];
        $orderby     = in_array( $args['orderby'], $orderby_map, true ) ? $args['orderby'] : 'created_at';
        $limit  = absint( $args['limit'] );
        $offset = absint( $args['offset'] );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (array) $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}webinars $where ORDER BY $orderby $order LIMIT $limit OFFSET $offset" );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        $result = $wpdb->delete( $wpdb->prefix . 'webinars', [ 'id' => $id ] ); // phpcs:ignore
        return false !== $result;
    }

    // ── Offer CRUD ────────────────────────────────────────────────────────────

    public static function save_offer( int $webinar_id, array $data ): bool {
        global $wpdb;
        $existing = $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}webinar_offers WHERE webinar_id = %d", $webinar_id )
        );
        $data['webinar_id'] = $webinar_id;
        if ( $existing ) {
            $result = $wpdb->update( $wpdb->prefix . 'webinar_offers', $data, [ 'id' => $existing->id ] ); // phpcs:ignore
        } else {
            $result = $wpdb->insert( $wpdb->prefix . 'webinar_offers', $data ); // phpcs:ignore
        }
        return false !== $result;
    }

    public static function get_offer( int $webinar_id ): ?object {
        global $wpdb;
        return $wpdb->get_row( // phpcs:ignore
            $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_offers WHERE webinar_id = %d LIMIT 1", $webinar_id )
        );
    }

    // ── Conversions ───────────────────────────────────────────────────────────

    public static function record_conversion( int $webinar_id, string $session_id, string $source = 'script', float $amount = 0 ): void {
        global $wpdb;
        $user_id = get_current_user_id();
        $wpdb->insert( $wpdb->prefix . 'webinar_conversions', [ // phpcs:ignore
            'webinar_id' => $webinar_id,
            'session_id' => $session_id,
            'user_id'    => $user_id,
            'source'     => $source,
            'amount'     => $amount ?: null,
            'ip'         => self::get_ip(),
        ] );

        \SmartWebinar\Events\EventDispatcher::dispatch( 'webinar_offer_converted', $user_id, [
            'webinar_id' => $webinar_id,
            'session_id' => $session_id,
            'source'     => $source,
            'amount'     => $amount,
        ] );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function unique_slug( string $slug, int $exclude_id = 0 ): string {
        global $wpdb;
        $original = $slug;
        $i        = 1;
        while ( true ) {
            if ( $exclude_id ) {
                $exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
                    "SELECT id FROM {$wpdb->prefix}webinars WHERE slug = %s AND id != %d LIMIT 1", $slug, $exclude_id
                ) );
            } else {
                $exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore
                    "SELECT id FROM {$wpdb->prefix}webinars WHERE slug = %s LIMIT 1", $slug
                ) );
            }
            if ( ! $exists ) break;
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    public static function get_ip(): string {
        foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                $ip = explode( ',', $ip )[0];
                return trim( $ip );
            }
        }
        return '';
    }
}
