<?php
/**
 * Manages sales pipelines, stages, and lead placements.
 *
 * @package AISalesEngine\Modules\Pipelines
 */

namespace AISalesEngine\Modules\Pipelines;

if ( ! defined( 'ABSPATH' ) ) exit;

class PipelineManager {

    public static function init(): void {}

    /**
     * Return all pipelines with their stages.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function list_all(): array {
        global $wpdb;

        $pipelines = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ai_pipelines ORDER BY created_at DESC",
            ARRAY_A
        ) ?: [];

        foreach ( $pipelines as &$pipeline ) {
            $pipeline['stages'] = self::get_stages( (int) $pipeline['id'] );
        }

        return $pipelines;
    }

    /**
     * Create a pipeline.
     *
     * @param string $name        Pipeline name.
     * @param string $description Optional description.
     * @return int|false
     */
    public static function create( string $name, string $description = '' ): int|false {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_pipelines',
            [
                'name'        => sanitize_text_field( $name ),
                'description' => sanitize_textarea_field( $description ),
            ]
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Add a stage to a pipeline.
     *
     * @param int    $pipeline_id Pipeline ID.
     * @param string $name        Stage name.
     * @param int    $position    Display order.
     * @return int|false
     */
    public static function add_stage( int $pipeline_id, string $name, int $position = 0 ): int|false {
        global $wpdb;

        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_pipeline_stages',
            [
                'pipeline_id' => $pipeline_id,
                'name'        => sanitize_text_field( $name ),
                'position'    => $position,
            ]
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Move a lead to a pipeline stage (upsert).
     *
     * @param int $lead_id     Lead ID.
     * @param int $pipeline_id Pipeline ID.
     * @param int $stage_id    Target stage ID.
     */
    public static function move_lead( int $lead_id, int $pipeline_id, int $stage_id ): void {
        global $wpdb;

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}ai_pipeline_leads
                 WHERE pipeline_id = %d AND lead_id = %d",
                $pipeline_id,
                $lead_id
            )
        );

        if ( $existing ) {
            $wpdb->update(
                $wpdb->prefix . 'ai_pipeline_leads',
                [ 'stage_id' => $stage_id ],
                [ 'id' => (int) $existing ]
            );
        } else {
            $wpdb->insert(
                $wpdb->prefix . 'ai_pipeline_leads',
                [
                    'pipeline_id' => $pipeline_id,
                    'stage_id'    => $stage_id,
                    'lead_id'     => $lead_id,
                ]
            );
        }
    }

    /**
     * Get leads in a pipeline, grouped by stage.
     *
     * @param int $pipeline_id Pipeline ID.
     * @return array<int,array<string,mixed>>
     */
    public static function get_board( int $pipeline_id ): array {
        global $wpdb;

        $stages = self::get_stages( $pipeline_id );

        foreach ( $stages as &$stage ) {
            $stage['leads'] = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT l.* FROM {$wpdb->prefix}ai_leads l
                     INNER JOIN {$wpdb->prefix}ai_pipeline_leads pl ON pl.lead_id = l.id
                     WHERE pl.pipeline_id = %d AND pl.stage_id = %d",
                    $pipeline_id,
                    (int) $stage['id']
                ),
                ARRAY_A
            ) ?: [];
        }

        return $stages;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function get_stages( int $pipeline_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_pipeline_stages
                 WHERE pipeline_id = %d ORDER BY position ASC",
                $pipeline_id
            ),
            ARRAY_A
        ) ?: [];
    }
}
