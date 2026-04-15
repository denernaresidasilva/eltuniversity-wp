<?php
namespace LeadsSaaS\Services;

use LeadsSaaS\Models\Automacao;
use LeadsSaaS\Models\Lead;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AutomacaoService {

    public static function fire( string $trigger, array $context = [] ): void {
        $automacoes = Automacao::find_by_trigger( $trigger );
        foreach ( $automacoes as $automacao ) {
            // Enforce conditions: if automacao has lista_id condition, context must match.
            $conditions = $automacao['conditions_json'] ?? null;
            if ( is_array( $conditions ) && isset( $conditions['lista_id'] ) ) {
                if ( ! isset( $context['lista_id'] ) || (int) $conditions['lista_id'] !== (int) $context['lista_id'] ) {
                    continue;
                }
            }

            // If workflow nodes are defined, use graph execution instead of flat actions.
            if ( ! empty( $automacao['nodes_json'] ) && is_array( $automacao['nodes_json'] ) ) {
                self::run_workflow( (int) $automacao['id'], $automacao['nodes_json'], $context );
            } else {
                self::run_acoes( $automacao['acoes_json'] ?? [], $context );
            }
        }
    }

    /**
     * Execute a node-based workflow graph for a given lead context.
     * Traverses the graph starting from the first node of type 'trigger' (or 'action' if none).
     * Nodes of type 'wait' are persisted to lead_workflow_runs for async processing via cron.
     *
     * @param int   $workflow_id  Automacao ID.
     * @param array $nodes        Decoded nodes_json array.
     * @param array $context      Runtime context (lead_id, lista_id, tag_id, etc.).
     */
    public static function run_workflow( int $workflow_id, array $nodes, array $context ): void {
        if ( empty( $nodes ) ) {
            return;
        }

        // Index nodes by id for O(1) lookup.
        $index = [];
        foreach ( $nodes as $node ) {
            if ( ! empty( $node['id'] ) ) {
                $index[ $node['id'] ] = $node;
            }
        }

        // Find the starting node: first 'trigger' node, or first node overall.
        $start = null;
        foreach ( $nodes as $node ) {
            if ( ( $node['type'] ?? '' ) === 'trigger' ) {
                $start = $node;
                break;
            }
        }
        if ( ! $start ) {
            $start = $nodes[0];
        }

        self::execute_node( $start, $index, $workflow_id, $context );
    }

    /**
     * Recursively execute a single node, then follow its edges.
     */
    private static function execute_node( array $node, array $index, int $workflow_id, array $context ): void {
        $type   = $node['type']   ?? 'action';
        $config = $node['config'] ?? [];
        $node_id = $node['id']   ?? '';

        switch ( $type ) {
            case 'trigger':
                // Trigger node is just an entry point; proceed to next node(s).
                self::follow_edges( $node['next'] ?? [], $index, $workflow_id, $context );
                break;

            case 'action':
                self::run_acoes( [ $config ], $context );
                self::follow_edges( $node['next'] ?? [], $index, $workflow_id, $context );
                break;

            case 'wait':
                // Persist a workflow run entry; async processing will continue from next node.
                global $wpdb;
                $delay_value = (int) ( $config['delay_value'] ?? 0 );
                $delay_unit  = in_array( $config['delay_unit'] ?? 'hours', [ 'hours', 'days' ], true )
                    ? $config['delay_unit'] : 'hours';
                $delay_secs  = $delay_unit === 'days' ? $delay_value * 86400 : $delay_value * 3600;
                $resume_at   = gmdate( 'Y-m-d H:i:s', time() + $delay_secs );
                $next_nodes  = $node['next'] ?? [];

                $wpdb->insert( $wpdb->prefix . 'lead_workflow_runs', [
                    'workflow_id'  => $workflow_id,
                    'lead_id'      => (int) ( $context['lead_id'] ?? 0 ),
                    'next_nodes'   => wp_json_encode( $next_nodes ),
                    'status'       => 'waiting',
                    'context_json' => wp_json_encode( $context ),
                    'resume_at'    => $resume_at,
                    'created_at'   => gmdate( 'Y-m-d H:i:s' ),
                ] );
                // Stop synchronous execution; cron will resume.
                break;

            case 'if_else':
                $condition_met = self::evaluate_condition( $config, $context );
                if ( $condition_met ) {
                    self::follow_edges( $node['next_true'] ?? [], $index, $workflow_id, $context );
                } else {
                    self::follow_edges( $node['next_false'] ?? [], $index, $workflow_id, $context );
                }
                break;

            case 'split':
                // Execute all branches in parallel (synchronously).
                self::follow_edges( $node['next'] ?? [], $index, $workflow_id, $context );
                break;

            case 'goal':
                // Check goal condition; if met, mark run as completed and stop.
                $condition_met = self::evaluate_condition( $config, $context );
                if ( ! $condition_met ) {
                    self::follow_edges( $node['next'] ?? [], $index, $workflow_id, $context );
                }
                break;

            case 'loop':
                // Execute body nodes up to max_iterations times.
                $max   = max( 1, (int) ( $config['max_iterations'] ?? 1 ) );
                $count = (int) ( $context['_loop_count_' . $node_id] ?? 0 );
                if ( $count < $max ) {
                    $context['_loop_count_' . $node_id] = $count + 1;
                    self::follow_edges( $node['next'] ?? [], $index, $workflow_id, $context );
                } else {
                    self::follow_edges( $node['next_exit'] ?? [], $index, $workflow_id, $context );
                }
                break;

            case 'end':
            default:
                // End node or unknown type: stop execution.
                break;
        }
    }

    /**
     * Follow a list of next-node IDs, executing each one.
     *
     * @param string[] $next_ids  Array of node IDs to execute next.
     * @param array    $index     Node index keyed by id.
     * @param int      $workflow_id
     * @param array    $context
     */
    private static function follow_edges( array $next_ids, array $index, int $workflow_id, array $context ): void {
        foreach ( $next_ids as $next_id ) {
            if ( isset( $index[ $next_id ] ) ) {
                self::execute_node( $index[ $next_id ], $index, $workflow_id, $context );
            }
        }
    }

    /**
     * Evaluate a condition config against the current context.
     * Supported operators: has_tag, not_has_tag, lista_is, field_equals.
     */
    private static function evaluate_condition( array $config, array $context ): bool {
        $op    = $config['op']    ?? '';
        $value = $config['value'] ?? null;
        $field = $config['field'] ?? '';

        switch ( $op ) {
            case 'has_tag':
                if ( empty( $context['lead_id'] ) ) {
                    return false;
                }
                $tags = Lead::get_tags( (int) $context['lead_id'] );
                foreach ( $tags as $tag ) {
                    if ( (int) $tag['id'] === (int) $value ) {
                        return true;
                    }
                }
                return false;

            case 'not_has_tag':
                if ( empty( $context['lead_id'] ) ) {
                    return true;
                }
                $tags = Lead::get_tags( (int) $context['lead_id'] );
                foreach ( $tags as $tag ) {
                    if ( (int) $tag['id'] === (int) $value ) {
                        return false;
                    }
                }
                return true;

            case 'lista_is':
                return isset( $context['lista_id'] ) && (int) $context['lista_id'] === (int) $value;

            case 'field_equals':
                return isset( $context[ $field ] ) && (string) $context[ $field ] === (string) $value;

            default:
                return false;
        }
    }

    /**
     * Process pending workflow runs (called by WP-Cron every 5 minutes).
     */
    public static function process_pending_runs(): void {
        global $wpdb;
        $table = $wpdb->prefix . 'lead_workflow_runs';
        $now   = gmdate( 'Y-m-d H:i:s' );

        $runs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE status = 'waiting' AND resume_at <= %s LIMIT 50",
                $now
            ),
            ARRAY_A
        ) ?: [];

        foreach ( $runs as $run ) {
            $wpdb->update( $table, [ 'status' => 'running' ], [ 'id' => $run['id'] ] );

            $workflow  = Automacao::find( (int) $run['workflow_id'] );
            $context   = $run['context_json'] ? json_decode( $run['context_json'], true ) : [];
            $next_ids  = $run['next_nodes']   ? json_decode( $run['next_nodes'],   true ) : [];

            if ( $workflow && is_array( $workflow['nodes_json'] ) && is_array( $next_ids ) ) {
                // Index nodes.
                $index = [];
                foreach ( $workflow['nodes_json'] as $node ) {
                    if ( ! empty( $node['id'] ) ) {
                        $index[ $node['id'] ] = $node;
                    }
                }
                self::follow_edges( $next_ids, $index, (int) $run['workflow_id'], $context );
            }

            $wpdb->update( $table, [ 'status' => 'done' ], [ 'id' => $run['id'] ] );
        }
    }

    private static function run_acoes( array $acoes, array $context ): void {
        foreach ( $acoes as $acao ) {
            $tipo = $acao['tipo'] ?? '';
            switch ( $tipo ) {
                case 'add_tag':
                    if ( ! empty( $context['lead_id'] ) && ! empty( $acao['tag_id'] ) ) {
                        Lead::add_tag( (int) $context['lead_id'], (int) $acao['tag_id'] );
                    }
                    break;

                case 'move_list':
                    if ( ! empty( $context['lead_id'] ) && ! empty( $acao['lista_id'] ) ) {
                        Lead::update( (int) $context['lead_id'], [ 'lista_id' => (int) $acao['lista_id'] ] );
                    }
                    break;

                case 'send_webhook':
                    if ( ! empty( $acao['url'] ) && ! empty( $context['lead_id'] ) ) {
                        $lead = Lead::find( (int) $context['lead_id'] );
                        if ( $lead ) {
                            WebhookService::send( $acao['url'], $lead );
                        }
                    }
                    break;

                default:
                    do_action( 'leads_saas_automacao_acao', $tipo, $acao, $context );
                    break;
            }
        }
    }
}
