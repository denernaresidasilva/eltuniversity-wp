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

            // Find the first trigger node.
            $start_node = self::find_trigger_node($structure['nodes'], $event_type);
            if (!$start_node) {
                continue;
            }

            $run_id = Flow_DB::create_run($flow->ID, $user_id, $start_node['id']);

            if ($run_id) {
                Logger::debug('[Flows] Run iniciado', [
                    'flow_id'    => $flow->ID,
                    'user_id'    => $user_id,
                    'run_id'     => $run_id,
                    'start_node' => $start_node['id'],
                ]);

                // Schedule immediate cron processing.
                if (!wp_next_scheduled('zapwa_process_flow')) {
                    wp_schedule_single_event(time() + 5, 'zapwa_process_flow');
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

    /**
     * Find the first trigger node that matches $event_type.
     *
     * @param array  $nodes
     * @param string $event_type
     * @return array|null
     */
    private static function find_trigger_node(array $nodes, $event_type) {
        foreach ($nodes as $node) {
            if (
                ($node['type'] ?? '') === 'trigger' &&
                ($node['data']['trigger_type'] ?? '') === $event_type
            ) {
                return $node;
            }
        }
        return null;
    }
}
