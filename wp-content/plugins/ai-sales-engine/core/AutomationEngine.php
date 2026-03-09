<?php
/**
 * Matches events to automation triggers and executes flow nodes.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class AutomationEngine {

    public static function init(): void {
        // Register job handlers for async flow execution.
        add_action( 'ai_sales_engine_job_automation_flow', [ self::class, 'execute_flow_job' ] );
    }

    /**
     * Called by EventDispatcher whenever an event fires.
     *
     * @param string $event_name Fired event name.
     * @param int    $lead_id    Lead ID.
     * @param array  $metadata   Event metadata.
     */
    public static function handle_event( string $event_name, int $lead_id, array $metadata = [] ): void {
        global $wpdb;

        $automations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}ai_automations
                 WHERE status = 'active' AND trigger_type = %s",
                $event_name
            ),
            ARRAY_A
        );

        foreach ( $automations as $automation ) {
            Queue::dispatch( 'automation_flow', [
                'automation_id' => (int) $automation['id'],
                'lead_id'       => $lead_id,
                'flow_json'     => $automation['flow_json'],
                'metadata'      => $metadata,
            ] );
        }
    }

    /**
     * Execute a queued automation flow job.
     *
     * @param array $payload Job payload from Queue.
     */
    public static function execute_flow_job( array $payload ): void {
        $flow_json = $payload['flow_json'] ?? '[]';
        $lead_id   = (int) ( $payload['lead_id'] ?? 0 );
        $nodes     = json_decode( $flow_json, true ) ?: [];

        foreach ( $nodes as $node ) {
            self::execute_node( $node, $lead_id );
        }
    }

    /**
     * Execute a single flow node.
     *
     * @param array $node    Node definition with 'type' and 'config'.
     * @param int   $lead_id Target lead.
     */
    private static function execute_node( array $node, int $lead_id ): void {
        $type   = $node['type']   ?? '';
        $config = $node['config'] ?? [];

        switch ( $type ) {
            case 'add_tag':
                if ( ! empty( $config['tag_id'] ) ) {
                    \AISalesEngine\Modules\Tags\TagManager::add_tag_to_lead( $lead_id, (int) $config['tag_id'] );
                }
                break;

            case 'remove_tag':
                if ( ! empty( $config['tag_id'] ) ) {
                    \AISalesEngine\Modules\Tags\TagManager::remove_tag_from_lead( $lead_id, (int) $config['tag_id'] );
                }
                break;

            case 'send_webhook':
                if ( ! empty( $config['url'] ) ) {
                    \AISalesEngine\Integrations\Webhook\WebhookHandler::send(
                        $config['url'],
                        [ 'lead_id' => $lead_id ]
                    );
                }
                break;

            case 'move_pipeline_stage':
                if ( ! empty( $config['pipeline_id'] ) && ! empty( $config['stage_id'] ) ) {
                    \AISalesEngine\Modules\Pipelines\PipelineManager::move_lead(
                        $lead_id,
                        (int) $config['pipeline_id'],
                        (int) $config['stage_id']
                    );
                }
                break;

            case 'call_agent':
                if ( ! empty( $config['agent_id'] ) ) {
                    Queue::dispatch( 'agent_call', [
                        'agent_id' => (int) $config['agent_id'],
                        'lead_id'  => $lead_id,
                        'config'   => $config,
                    ] );
                }
                break;

            case 'delay':
                // Re-queue remaining nodes with a future run_at.
                $delay_seconds = (int) ( $config['seconds'] ?? 60 );
                $run_at        = gmdate( 'Y-m-d H:i:s', time() + $delay_seconds );
                Queue::dispatch( 'automation_flow', [
                    'lead_id'  => $lead_id,
                    'flow_json'=> wp_json_encode( array_slice( $node['next'] ?? [], 0 ) ),
                ], $run_at );
                return; // Stop processing further nodes synchronously.

            default:
                do_action( 'ai_sales_engine_flow_node_' . $type, $config, $lead_id );
                break;
        }
    }
}
