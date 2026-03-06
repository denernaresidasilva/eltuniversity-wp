<?php
namespace ZapWA\Flows;

use ZapWA\Logger;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flow Engine — Listens to existing ZapWA triggers and starts flow runs.
 */
class Flow_Engine {

    /** @var bool */
    private static $initialized = false;

    public static function init() {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        // Hook into the existing zap_evento action used by the Listener.
        add_action('zap_evento', [self::class, 'handle_event'], 20, 1);
    }

    /**
     * Called when any zap_evento fires.
     * Finds active flows whose trigger matches and starts a run for each.
     *
     * @param array $payload
     */
    public static function handle_event($payload) {
        $event_type = sanitize_text_field(trim($payload['event'] ?? ''));
        $user_id    = absint($payload['user_id'] ?? 0);

        if (!$event_type || !$user_id) {
            return;
        }

        $flows = self::get_active_flows_for_trigger($event_type);

        foreach ($flows as $flow) {
            $structure = self::get_flow_structure($flow->ID);
            if (!$structure || empty($structure['nodes'])) {
                continue;
            }

            $start_node_id = self::resolve_start_node_id($structure, $event_type);
            if (!$start_node_id) {
                continue;
            }

            $run_id = Flow_DB::create_run($flow->ID, $user_id, $start_node_id);

            if ($run_id) {
                Logger::debug('[Flows] Run iniciado', [
                    'flow_id'    => $flow->ID,
                    'user_id'    => $user_id,
                    'run_id'     => $run_id,
                    'start_node' => $start_node_id,
                ]);

                // Process immediately, matching legacy automation behaviour.
                if (class_exists(__NAMESPACE__ . '\\Flow_Runner')) {
                    Flow_Runner::process_due_runs();
                } elseif (!wp_next_scheduled('zapwa_process_flow')) {
                    wp_schedule_single_event(time(), 'zapwa_process_flow');
                }
            }
        }
    }

    /**
     * Return published automation_flow posts whose trigger matches $event_type.
     *
     * @param string $event_type
     * @return \WP_Post[]
     */
    public static function get_active_flows_for_trigger($event_type) {
        return get_posts([
            'post_type'      => 'automation_flow',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'all',
            'meta_query'     => [
                [
                    'key'   => '_flow_status',
                    'value' => 'active',
                ],
                [
                    'key'   => '_flow_trigger',
                    'value' => $event_type,
                ],
            ],
        ]);
    }

    /**
     * Decode and return the JSON structure for a flow post.
     *
     * @param int $post_id
     * @return array|null
     */
    public static function get_flow_structure($post_id) {
        $json = get_post_meta($post_id, '_flow_structure', true);
        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    private static function resolve_start_node_id(array $structure, $event_type) {
        $nodes = is_array($structure['nodes'] ?? null) ? $structure['nodes'] : [];
        $edges = is_array($structure['edges'] ?? null) ? $structure['edges'] : [];

        if (empty($nodes)) {
            return null;
        }

        // Backward compatibility: legacy flows can still contain trigger nodes.
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') !== 'trigger') {
                continue;
            }

            if (($node['data']['trigger_type'] ?? '') !== $event_type) {
                continue;
            }

            $next_node_id = self::find_first_next_node($edges, (string) ($node['id'] ?? ''));
            if ($next_node_id && self::is_non_trigger_node($nodes, $next_node_id)) {
                return $next_node_id;
            }
        }

        // New behaviour: start from the first root node that is not a trigger.
        $incoming = [];
        foreach ($edges as $edge) {
            $target = (string) ($edge['target'] ?? '');
            if ($target !== '') {
                $incoming[$target] = true;
            }
        }

        foreach ($nodes as $node) {
            $node_id = (string) ($node['id'] ?? '');
            $type    = (string) ($node['type'] ?? '');
            if ($node_id && $type !== 'trigger' && empty($incoming[$node_id])) {
                return $node_id;
            }
        }

        // Fallback: first non-trigger node available.
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') !== 'trigger' && !empty($node['id'])) {
                return (string) $node['id'];
            }
        }

        return null;
    }

    private static function find_first_next_node(array $edges, $source_id) {
        if (!$source_id) {
            return null;
        }

        foreach ($edges as $edge) {
            if ((string) ($edge['source'] ?? '') !== $source_id) {
                continue;
            }

            $target = (string) ($edge['target'] ?? '');
            if ($target) {
                return $target;
            }
        }

        return null;
    }

    private static function is_non_trigger_node(array $nodes, $node_id) {
        foreach ($nodes as $node) {
            if ((string) ($node['id'] ?? '') !== (string) $node_id) {
                continue;
            }

            return ($node['type'] ?? '') !== 'trigger';
        }

        return false;
    }
}
