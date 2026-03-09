<?php
/**
 * Manages contact lists.
 *
 * @package AISalesEngine\Modules\Lists
 */

namespace AISalesEngine\Modules\Lists;

if ( ! defined( 'ABSPATH' ) ) exit;

class ListManager {

    public static function init(): void {}

    /**
     * Return all lists.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function list_all(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ai_lists ORDER BY created_at DESC",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Create a new list.
     *
     * @param array $data { name, description, webhook_url }.
     * @return int|false
     */
    public static function create( array $data ): int|false {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_lists',
            [
                'name'        => sanitize_text_field( $data['name']        ?? '' ),
                'description' => sanitize_textarea_field( $data['description'] ?? '' ),
                'webhook_url' => esc_url_raw( $data['webhook_url'] ?? '' ),
            ]
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Get a list by ID.
     *
     * @param int $id List ID.
     * @return array<string,mixed>|null
     */
    public static function get( int $id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_lists WHERE id = %d",
                $id
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * Delete a list and all its lead associations.
     *
     * @param int $id List ID.
     */
    public static function delete( int $id ): void {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'ai_list_leads', [ 'list_id' => $id ] );
        $wpdb->delete( $wpdb->prefix . 'ai_lists',      [ 'id'      => $id ] );
    }
}
