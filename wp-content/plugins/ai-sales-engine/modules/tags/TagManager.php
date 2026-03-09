<?php
/**
 * Manages tags and their assignment to leads.
 *
 * @package AISalesEngine\Modules\Tags
 */

namespace AISalesEngine\Modules\Tags;

if ( ! defined( 'ABSPATH' ) ) exit;

class TagManager {

    public static function init(): void {}

    /**
     * Return all tags.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function list_all(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ai_tags ORDER BY name ASC",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Create a tag (returns existing ID if slug already exists).
     *
     * @param string $name Tag label.
     * @return int|false Tag ID or false.
     */
    public static function create( string $name ): int|false {
        global $wpdb;

        $slug = sanitize_title( $name );

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ai_tags WHERE slug = %s",
                $slug
            )
        );

        if ( $existing ) {
            return (int) $existing;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_tags',
            [
                'name' => sanitize_text_field( $name ),
                'slug' => $slug,
            ]
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Assign a tag to a lead.
     *
     * @param int $lead_id Lead ID.
     * @param int $tag_id  Tag ID.
     */
    public static function add_tag_to_lead( int $lead_id, int $tag_id ): void {
        global $wpdb;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT lead_id FROM {$wpdb->prefix}ai_lead_tags
                 WHERE lead_id = %d AND tag_id = %d",
                $lead_id,
                $tag_id
            )
        );

        if ( ! $exists ) {
            $wpdb->insert(
                $wpdb->prefix . 'ai_lead_tags',
                [ 'lead_id' => $lead_id, 'tag_id' => $tag_id ]
            );
        }
    }

    /**
     * Remove a tag from a lead.
     *
     * @param int $lead_id Lead ID.
     * @param int $tag_id  Tag ID.
     */
    public static function remove_tag_from_lead( int $lead_id, int $tag_id ): void {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'ai_lead_tags',
            [ 'lead_id' => $lead_id, 'tag_id' => $tag_id ]
        );
    }

    /**
     * Get tags assigned to a lead.
     *
     * @param int $lead_id Lead ID.
     * @return array<int,array<string,mixed>>
     */
    public static function get_for_lead( int $lead_id ): array {
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
}
