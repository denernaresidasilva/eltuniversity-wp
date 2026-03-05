<?php
namespace ZapWA\Flows;

use ZapWA\Logger;
use ZapWA\EmailNotifier;
use ZapWA\EvolutionAPI;
use ZapWA\Variables;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flow Runner — Processes pending flow runs via WP Cron.
 */
class Flow_Runner {

    public static function init() {
        add_action('zapwa_process_flow', [self::class, 'process_due_runs']);
    }

    /**
     * Cron callback: fetch and process all due runs.
     */
    public static function process_due_runs() {
        $runs = Flow_DB::get_due_runs(20);

        foreach ($runs as $run) {
            self::process_run($run);
        }

        // Re-schedule if there are still pending runs.
        $remaining = Flow_DB::get_due_runs(1);
        if (!empty($remaining)) {
            if (!wp_next_scheduled('zapwa_process_flow')) {
                wp_schedule_single_event(time() + 30, 'zapwa_process_flow');
            }
        }
    }

    /**
     * Process a single run: execute the current node and advance.
     *
     * @param object $run  DB row from zapwa_flow_runs.
     */
    private static function process_run($run) {
        $structure = Flow_Engine::get_flow_structure((int) $run->flow_id);

        if (!$structure || empty($structure['nodes'])) {
            Flow_DB::update_run($run->id, ['status' => 'completed']);
            return;
        }

        $nodes = $structure['nodes'];
        $edges = $structure['edges'] ?? [];

        $current_node = self::find_node_by_id($nodes, $run->current_node_id);
        if (!$current_node) {
            Flow_DB::update_run($run->id, ['status' => 'completed']);
            return;
        }

        // Build a simple context for variable substitution.
        $user_id = (int) $run->contact_id;
        $user    = get_userdata($user_id);
        $context = [
            'user_id'    => $user_id,
            'user_email' => $user ? $user->user_email : '',
            'user_name'  => $user ? $user->display_name : '',
        ];

        $result = self::execute_node($current_node, $context, $run);

        if ($result === 'stop') {
            Flow_DB::update_run($run->id, ['status' => 'completed']);
            return;
        }

        // Find the next node(s) via edges.
        $next_node_id = self::resolve_next_node($edges, $run->current_node_id, $result);

        if (!$next_node_id) {
            // No more edges: the flow is complete.
            Flow_DB::update_run($run->id, ['status' => 'completed']);
            return;
        }

        $next_node = self::find_node_by_id($nodes, $next_node_id);

        if (!$next_node) {
            Flow_DB::update_run($run->id, ['status' => 'completed']);
            return;
        }

        // If next node is a delay, schedule for later.
        if ($next_node['type'] === 'delay') {
            $seconds = self::delay_to_seconds($next_node['data'] ?? []);

            // Advance past the delay node immediately by pointing to it.
            // The runner will execute it next iteration, respecting next_execution.
            Flow_DB::update_run($run->id, [
                'current_node_id' => $next_node_id,
                'next_execution'  => wp_date('Y-m-d H:i:s', time() + $seconds),
            ]);

            wp_schedule_single_event(time() + $seconds, 'zapwa_process_flow');
            return;
        }

        // Move to next node and process immediately in next cron tick.
        Flow_DB::update_run($run->id, [
            'current_node_id' => $next_node_id,
            'next_execution'  => current_time('mysql'),
        ]);

        if (!wp_next_scheduled('zapwa_process_flow')) {
            wp_schedule_single_event(time() + 5, 'zapwa_process_flow');
        }
    }

    /**
     * Execute a single node.
     *
     * @param array  $node
     * @param array  $context
     * @param object $run
     * @return string 'ok' | 'stop' | 'condition_true' | 'condition_false'
     */
    private static function execute_node(array $node, array $context, $run) {
        $type = $node['type'] ?? '';
        $data = $node['data'] ?? [];

        switch ($type) {
            case 'trigger':
                // Trigger nodes are entry points; just move on.
                return 'ok';

            case 'send_whatsapp':
                return self::execute_send_whatsapp($data, $context);

            case 'send_email':
                return self::execute_send_email($data, $context);

            case 'delay':
                // Delay nodes are handled by scheduling; mark done.
                return 'ok';

            case 'condition':
                return self::execute_condition($data, $context);

            case 'end':
                return 'stop';

            default:
                Logger::debug('[Flows] Tipo de nó desconhecido: ' . $type);
                return 'ok';
        }
    }

    // -------------------------------------------------------------------------
    // Node executors
    // -------------------------------------------------------------------------

    private static function execute_send_whatsapp(array $data, array $context) {
        $user_id = (int) ($context['user_id'] ?? 0);
        if (!$user_id) {
            return 'ok';
        }

        $phone = \ZapWA\Helpers::get_user_phone($user_id);
        if (!$phone) {
            Logger::debug('[Flows] send_whatsapp: usuário sem telefone', ['user_id' => $user_id]);
            return 'ok';
        }

        $message = sanitize_textarea_field($data['message'] ?? '');
        if (!$message) {
            return 'ok';
        }

        // Replace simple placeholders.
        $message = str_replace(
            ['{user_name}', '{user_email}', '{site_name}'],
            [
                $context['user_name'] ?? '',
                $context['user_email'] ?? '',
                get_bloginfo('name'),
            ],
            $message
        );

        $result = EvolutionAPI::send_message($phone, $message);

        Logger::debug('[Flows] send_whatsapp result', [
            'user_id' => $user_id,
            'success' => $result['success'] ?? false,
        ]);

        return 'ok';
    }

    private static function execute_send_email(array $data, array $context) {
        $to = sanitize_email($context['user_email'] ?? '');
        if (!$to) {
            return 'ok';
        }

        $subject = sanitize_text_field($data['subject'] ?? '');
        $body    = wp_kses_post($data['body'] ?? '');

        if (!$subject || !$body) {
            return 'ok';
        }

        // Replace simple placeholders.
        $find    = ['{user_name}', '{user_email}', '{site_name}'];
        $replace = [
            $context['user_name'] ?? '',
            $context['user_email'] ?? '',
            get_bloginfo('name'),
        ];

        $subject = str_replace($find, $replace, $subject);
        $body    = str_replace($find, $replace, $body);

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        $sent    = wp_mail($to, $subject, $body, $headers);

        Logger::debug('[Flows] send_email result', [
            'to'   => $to,
            'sent' => $sent,
        ]);

        return 'ok';
    }

    private static function execute_condition(array $data, array $context) {
        $condition_type = sanitize_text_field($data['condition_type'] ?? 'has_tag');
        $value          = sanitize_text_field($data['value'] ?? '');
        $user_id        = (int) ($context['user_id'] ?? 0);

        if (!$user_id) {
            return 'condition_false';
        }

        switch ($condition_type) {
            case 'has_tag':
                $tags = get_user_meta($user_id, 'zapwa_tags', true);
                if (!is_array($tags)) {
                    $tags = array_map('trim', explode(',', (string) $tags));
                }
                $result = in_array($value, $tags, true);
                break;

            case 'has_purchased_course':
                // Check if user is enrolled in a course (Tutor LMS compatible).
                $enrolled = get_user_meta($user_id, '_tutor_enrolled_course_ids', true);
                $enrolled = is_array($enrolled) ? $enrolled : [];
                $result   = in_array((int) $value, array_map('intval', $enrolled), true);
                break;

            default:
                $result = false;
        }

        return $result ? 'condition_true' : 'condition_false';
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private static function find_node_by_id(array $nodes, $id) {
        foreach ($nodes as $node) {
            if (($node['id'] ?? '') === $id) {
                return $node;
            }
        }
        return null;
    }

    /**
     * Resolve the ID of the next node given the current node ID and result.
     *
     * Edges with a "label" of "true"/"false" are used for condition branching.
     *
     * @param array  $edges
     * @param string $current_id
     * @param string $result
     * @return string|null
     */
    private static function resolve_next_node(array $edges, $current_id, $result) {
        $matching_edges = [];

        foreach ($edges as $edge) {
            if (($edge['source'] ?? '') !== $current_id) {
                continue;
            }

            $label = strtolower(trim($edge['label'] ?? ''));

            if ($result === 'condition_true' && $label === 'false') {
                continue;
            }
            if ($result === 'condition_false' && $label === 'true') {
                continue;
            }

            $matching_edges[] = $edge;
        }

        if (empty($matching_edges)) {
            return null;
        }

        return $matching_edges[0]['target'] ?? null;
    }

    private static function delay_to_seconds(array $data) {
        $amount = max(1, (int) ($data['delay_amount'] ?? 1));
        $unit   = sanitize_text_field($data['delay_unit'] ?? 'minutes');

        $multipliers = [
            'seconds' => 1,
            'minutes' => MINUTE_IN_SECONDS,
            'hours'   => HOUR_IN_SECONDS,
            'days'    => DAY_IN_SECONDS,
        ];

        return $amount * ($multipliers[$unit] ?? MINUTE_IN_SECONDS);
    }
}
