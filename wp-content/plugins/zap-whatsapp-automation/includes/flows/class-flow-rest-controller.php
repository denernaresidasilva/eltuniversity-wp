<?php
namespace ZapWA\Flows;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API controller for automation flows.
 *
 * Routes:
 *   GET    /wp-json/zapwa/v1/flows
 *   POST   /wp-json/zapwa/v1/flows
 *   GET    /wp-json/zapwa/v1/flows/{id}
 *   PUT    /wp-json/zapwa/v1/flows/{id}
 *   DELETE /wp-json/zapwa/v1/flows/{id}
 */
class Flow_REST_Controller extends WP_REST_Controller {

    /** @var string */
    protected $namespace = 'zapwa/v1';

    /** @var string */
    protected $rest_base = 'flows';

    public static function init() {
        $instance = new self();
        add_action('rest_api_init', [$instance, 'register_routes']);
    }

    public function register_routes() {

        register_rest_route($this->namespace, '/' . $this->rest_base, [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_items'],
                'permission_callback' => [$this, 'permissions_check'],
            ],
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => $this->get_item_args(),
            ],
        ]);

        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'get_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => ['id' => ['validate_callback' => 'is_numeric']],
            ],
            [
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => [$this, 'update_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => $this->get_item_args(false),
            ],
            [
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => [$this, 'delete_item'],
                'permission_callback' => [$this, 'permissions_check'],
                'args'                => ['id' => ['validate_callback' => 'is_numeric']],
            ],
        ]);
    }

    /**
     * Permission check — manage_options required.
     */
    public function permissions_check($request) {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'rest_forbidden',
                __('Você não tem permissão para gerenciar fluxos.', 'zap-whatsapp-automation'),
                ['status' => 403]
            );
        }
        return true;
    }

    // -------------------------------------------------------------------------
    // CRUD handlers
    // -------------------------------------------------------------------------

    public function get_items($request) {
        $posts = get_posts([
            'post_type'      => 'automation_flow',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $items = array_map([$this, 'prepare_item_for_response_array'], $posts);

        return rest_ensure_response($items);
    }

    public function get_item($request) {
        $post = get_post((int) $request['id']);

        if (!$post || $post->post_type !== 'automation_flow') {
            return new WP_Error('not_found', __('Fluxo não encontrado.', 'zap-whatsapp-automation'), ['status' => 404]);
        }

        return rest_ensure_response($this->prepare_item_for_response_array($post));
    }

    public function create_item($request) {
        $title     = sanitize_text_field($request->get_param('title') ?? '');
        $status    = $request->get_param('status') === 'active' ? 'publish' : 'draft';
        $structure = $this->sanitize_flow_structure($request->get_param('structure') ?? []);
        $trigger   = sanitize_text_field($request->get_param('trigger') ?? '');

        if (!$title) {
            return new WP_Error('missing_title', __('Título é obrigatório.', 'zap-whatsapp-automation'), ['status' => 400]);
        }

        $post_id = wp_insert_post([
            'post_type'   => 'automation_flow',
            'post_status' => $status,
            'post_title'  => $title,
        ]);

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        update_post_meta($post_id, '_flow_structure', wp_json_encode($structure));
        update_post_meta($post_id, '_flow_status', $status === 'publish' ? 'active' : 'inactive');
        update_post_meta($post_id, '_flow_trigger', $trigger);

        $post = get_post($post_id);

        return rest_ensure_response($this->prepare_item_for_response_array($post));
    }

    public function update_item($request) {
        $post = get_post((int) $request['id']);

        if (!$post || $post->post_type !== 'automation_flow') {
            return new WP_Error('not_found', __('Fluxo não encontrado.', 'zap-whatsapp-automation'), ['status' => 404]);
        }

        $update_data = ['ID' => $post->ID];

        $title = $request->get_param('title');
        if ($title !== null) {
            $update_data['post_title'] = sanitize_text_field($title);
        }

        $status = $request->get_param('status');
        if ($status !== null) {
            $update_data['post_status'] = ($status === 'active') ? 'publish' : 'draft';
            update_post_meta($post->ID, '_flow_status', $status === 'active' ? 'active' : 'inactive');
        }

        $trigger = $request->get_param('trigger');
        if ($trigger !== null) {
            update_post_meta($post->ID, '_flow_trigger', sanitize_text_field($trigger));
        }

        $structure = $request->get_param('structure');
        if ($structure !== null) {
            $clean = $this->sanitize_flow_structure($structure);
            update_post_meta($post->ID, '_flow_structure', wp_json_encode($clean));
        }

        if (count($update_data) > 1) {
            wp_update_post($update_data);
        }

        $post = get_post($post->ID);

        return rest_ensure_response($this->prepare_item_for_response_array($post));
    }

    public function delete_item($request) {
        $post = get_post((int) $request['id']);

        if (!$post || $post->post_type !== 'automation_flow') {
            return new WP_Error('not_found', __('Fluxo não encontrado.', 'zap-whatsapp-automation'), ['status' => 404]);
        }

        wp_delete_post($post->ID, true);

        return rest_ensure_response(['deleted' => true, 'id' => $post->ID]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function prepare_item_for_response_array($post) {
        $structure_raw = get_post_meta($post->ID, '_flow_structure', true);
        $structure     = $structure_raw ? json_decode($structure_raw, true) : ['nodes' => [], 'edges' => []];

        return [
            'id'        => $post->ID,
            'title'     => $post->post_title,
            'status'    => get_post_meta($post->ID, '_flow_status', true) ?: 'inactive',
            'trigger'   => get_post_meta($post->ID, '_flow_trigger', true) ?: '',
            'structure' => $structure,
            'created'   => $post->post_date,
            'modified'  => $post->post_modified,
        ];
    }

    /**
     * Recursively sanitize the flow structure to ensure only safe data is stored.
     *
     * @param mixed $structure
     * @return array
     */
    private function sanitize_flow_structure($structure) {
        if (is_string($structure)) {
            $decoded = json_decode($structure, true);
            $structure = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($structure)) {
            return ['nodes' => [], 'edges' => []];
        }

        $nodes = [];
        foreach ((array) ($structure['nodes'] ?? []) as $node) {
            if (!is_array($node)) {
                continue;
            }
            $nodes[] = [
                'id'       => sanitize_text_field($node['id'] ?? ''),
                'type'     => sanitize_key($node['type'] ?? 'trigger'),
                'data'     => $this->sanitize_node_data((array) ($node['data'] ?? [])),
                'position' => [
                    'x' => (float) ($node['position']['x'] ?? 0),
                    'y' => (float) ($node['position']['y'] ?? 0),
                ],
            ];
        }

        $edges = [];
        foreach ((array) ($structure['edges'] ?? []) as $edge) {
            if (!is_array($edge)) {
                continue;
            }
            $edges[] = [
                'id'     => sanitize_text_field($edge['id'] ?? ''),
                'source' => sanitize_text_field($edge['source'] ?? ''),
                'target' => sanitize_text_field($edge['target'] ?? ''),
                'label'  => sanitize_text_field($edge['label'] ?? ''),
            ];
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }

    /**
     * Sanitize per-node data fields.
     *
     * @param array $data
     * @return array
     */
    private function sanitize_node_data(array $data) {
        $safe = [];

        $text_fields = [
            'trigger_type', 'message', 'subject', 'body',
            'delay_unit', 'condition_type', 'value', 'label',
            'tag', 'url', 'method',
        ];

        foreach ($text_fields as $field) {
            if (isset($data[$field])) {
                $safe[$field] = sanitize_textarea_field($data[$field]);
            }
        }

        if (isset($data['delay_amount'])) {
            $safe['delay_amount'] = absint($data['delay_amount']);
        }

        if (isset($data['agent_id'])) {
            $safe['agent_id'] = absint($data['agent_id']);
        }

        if (isset($data['headers']) && is_array($data['headers'])) {
            $safe_headers = [];
            foreach ($data['headers'] as $k => $v) {
                $safe_headers[sanitize_text_field($k)] = sanitize_text_field($v);
            }
            $safe['headers'] = $safe_headers;
        }

        return $safe;
    }

    /**
     * Common args definition for create/update.
     */
    private function get_item_args($require_title = true) {
        return [
            'title'     => ['type' => 'string', 'required' => $require_title],
            'status'    => ['type' => 'string', 'enum' => ['active', 'inactive']],
            'trigger'   => ['type' => 'string'],
            'structure' => ['type' => ['object', 'string']],
        ];
    }
}
