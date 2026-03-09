<?php
/**
 * AI Agent Engine – handles agent invocations.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class AgentEngine {

    public static function init(): void {
        add_action( 'ai_sales_engine_job_agent_call', [ self::class, 'handle_agent_call' ] );
    }

    /**
     * Handle an agent_call job dispatched by the AutomationEngine.
     *
     * @param array $payload { agent_id, lead_id, config }.
     */
    public static function handle_agent_call( array $payload ): void {
        global $wpdb;

        $agent_id = (int) ( $payload['agent_id'] ?? 0 );
        $lead_id  = (int) ( $payload['lead_id']  ?? 0 );

        if ( ! $agent_id || ! $lead_id ) {
            return;
        }

        $agent = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_agents WHERE id = %d",
                $agent_id
            ),
            ARRAY_A
        );

        if ( ! $agent ) {
            return;
        }

        // Allow external integrations to handle the actual AI call.
        do_action( 'ai_sales_engine_agent_invoke', $agent, $lead_id, $payload['config'] ?? [] );
    }

    /**
     * Retrieve an agent by ID.
     *
     * @param int $agent_id Agent ID.
     * @return array<string,mixed>|null
     */
    public static function get( int $agent_id ): ?array {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_agents WHERE id = %d",
                $agent_id
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    /**
     * List all agents.
     *
     * @return array<int,array<string,mixed>>
     */
    public static function list_agents(): array {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ai_agents ORDER BY created_at DESC",
            ARRAY_A
        ) ?: [];
    }

    /**
     * Create a new agent.
     *
     * @param array $data Agent fields.
     * @return int|false New agent ID or false on failure.
     */
    public static function create( array $data ): int|false {
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_agents',
            [
                'name'            => sanitize_text_field( $data['name']            ?? '' ),
                'role'            => sanitize_text_field( $data['role']            ?? '' ),
                'goal'            => sanitize_textarea_field( $data['goal']        ?? '' ),
                'personality'     => sanitize_textarea_field( $data['personality'] ?? '' ),
                'training_prompt' => wp_kses_post( $data['training_prompt']        ?? '' ),
                'voice_enabled'   => (int) ( $data['voice_enabled']  ?? 0 ),
                'image_enabled'   => (int) ( $data['image_enabled']  ?? 0 ),
            ]
        );
        return $result ? (int) $wpdb->insert_id : false;
    }
}
