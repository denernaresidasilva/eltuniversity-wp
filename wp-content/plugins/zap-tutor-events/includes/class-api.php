<?php
/**
 * REST API Class
 * 
 * Provides REST API endpoints for event access
 * 
 * @package ZapTutorEvents
 * @since 1.1.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class API {

    /**
     * API namespace
     */
    const NAMESPACE = 'zap-events/v1';

    /**
     * Initialize API
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    /**
     * Register REST routes
     */
    public static function register_routes() {
        
        // GET /wp-json/zap-events/v1/logs
        register_rest_route(self::NAMESPACE, '/logs', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_logs'],
            'permission_callback' => [self::class, 'check_permission'],
            'args'                => [
                'per_page'   => [
                    'default'           => 50,
                    'sanitize_callback' => 'absint',
                ],
                'page'       => [
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ],
                'event_key'  => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'user_id'    => [
                    'sanitize_callback' => 'absint',
                ],
                'date_from'  => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'date_to'    => [
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);

        // GET /wp-json/zap-events/v1/stats
        register_rest_route(self::NAMESPACE, '/stats', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_stats'],
            'permission_callback' => [self::class, 'check_permission'],
            'args'                => [
                'days' => [
                    'default'           => 30,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // POST /wp-json/zap-events/v1/test
        register_rest_route(self::NAMESPACE, '/test', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'test_event'],
            'permission_callback' => [self::class, 'check_permission'],
            'args'                => [
                'user_id' => [
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /wp-json/zap-events/v1/events
        register_rest_route(self::NAMESPACE, '/events', [
            'methods'             => 'GET',
            'callback'            => [self::class, 'get_event_types'],
            'permission_callback' => [self::class, 'check_permission'],
        ]);
    }

    /**
     * Check API permission
     * 
     * @param \WP_REST_Request $request
     * @return bool|WP_Error
     */
    public static function check_permission($request) {
        
        // Check for API key in header
        $api_key = $request->get_header('X-API-Key');
        $stored_key = get_option('zap_events_api_key', '');

        if (!empty($stored_key) && $api_key === $stored_key) {
            return true;
        }

        // Fallback to WordPress authentication
        if (current_user_can('manage_options')) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __('Você não tem permissão para acessar esta API.'),
            ['status' => 401]
        );
    }

    /**
     * Get logs endpoint
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_logs($request) {
        
        $filters = [];
        
        if ($request->get_param('event_key')) {
            $filters['event_key'] = $request->get_param('event_key');
        }
        
        if ($request->get_param('user_id')) {
            $filters['user_id'] = $request->get_param('user_id');
        }
        
        if ($request->get_param('date_from')) {
            $filters['date_from'] = $request->get_param('date_from');
        }
        
        if ($request->get_param('date_to')) {
            $filters['date_to'] = $request->get_param('date_to');
        }

        $per_page = $request->get_param('per_page');
        $page = $request->get_param('page');

        $logs = Logger::get_logs($filters, $per_page, $page);
        $total = Logger::get_count($filters);

        $response_data = [
            'logs'       => $logs,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $per_page,
            'total_pages' => ceil($total / $per_page),
        ];

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * Get statistics endpoint
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_stats($request) {
        
        $days = $request->get_param('days');

        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $event_stats = $wpdb->get_results($wpdb->prepare(
            "SELECT event_key, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY event_key 
             ORDER BY count DESC",
            $date_limit
        ));

        $webhook_stats = Webhook::get_stats($days);

        $total_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            $date_limit
        ));

        $response_data = [
            'period'        => "{$days} days",
            'total_events'  => (int) $total_events,
            'events_by_type' => $event_stats,
            'webhook_stats' => $webhook_stats,
        ];

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * Test event endpoint
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function test_event($request) {
        
        $user_id = $request->get_param('user_id');

        $context = [
            'source'      => 'rest_api_test',
            'description' => 'Test event triggered via REST API',
            'timestamp'   => current_time('mysql'),
            'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        ];

        Dispatcher::dispatch('zap_test_event', $user_id, $context);

        $response_data = [
            'success' => true,
            'message' => 'Test event dispatched successfully',
            'event'   => [
                'event_key' => 'zap_test_event',
                'user_id'   => $user_id,
                'context'   => $context,
            ],
        ];

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * Get event types endpoint
     * 
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public static function get_event_types($request) {
        
        $events = Events::registry();

        $response_data = [
            'events' => $events,
            'count'  => count($events),
        ];

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * Generate new API key
     * 
     * @return string API key
     */
    public static function generate_api_key() {
        $key = wp_generate_password(32, false);
        update_option('zap_events_api_key', $key);
        return $key;
    }

    /**
     * Get current API key
     * 
     * @return string API key
     */
    public static function get_api_key() {
        $key = get_option('zap_events_api_key', '');
        
        if (empty($key)) {
            $key = self::generate_api_key();
        }
        
        return $key;
    }
}
