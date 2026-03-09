<?php
/**
 * Registers all REST API endpoints for the AI Sales Engine.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Api {

    const NAMESPACE = 'ai-sales/v1';

    public static function init(): void {
        add_action( 'rest_api_init', [ self::class, 'register_routes' ] );
    }

    public static function register_routes(): void {
        $ns = self::NAMESPACE;

        // Public event intake endpoint.
        register_rest_route( $ns, '/event', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'handle_event' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'event_name' => [ 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        // Leads collection.
        register_rest_route( $ns, '/leads', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_leads' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_lead' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
        ] );

        // Single lead.
        register_rest_route( $ns, '/leads/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_lead' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update_lead' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_lead' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
        ] );

        // Lead tags.
        register_rest_route( $ns, '/leads/(?P<id>\d+)/tags', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'add_tag_to_lead' ],
            'permission_callback' => [ self::class, 'auth_check' ],
        ] );

        // Lists.
        register_rest_route( $ns, '/lists', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_lists' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_list' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
        ] );

        // Automations.
        register_rest_route( $ns, '/automations', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_automations' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_automation' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
        ] );

        // Analytics summary.
        register_rest_route( $ns, '/analytics/summary', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_analytics_summary' ],
            'permission_callback' => [ self::class, 'auth_check' ],
        ] );

        // Agents.
        register_rest_route( $ns, '/agents', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_agents' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_agent' ],
                'permission_callback' => [ self::class, 'auth_check' ],
            ],
        ] );
    }

    // -------------------------------------------------------------------------
    // Permission callbacks
    // -------------------------------------------------------------------------

    /**
     * Require manage_options capability or valid nonce.
     *
     * @param \WP_REST_Request $request Incoming request.
     */
    public static function auth_check( \WP_REST_Request $request ): bool|\WP_Error {
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }
        return new \WP_Error( 'rest_forbidden', __( 'Forbidden.', 'ai-sales-engine' ), [ 'status' => 403 ] );
    }

    // -------------------------------------------------------------------------
    // Route callbacks
    // -------------------------------------------------------------------------

    /**
     * POST /event – accept an external or front-end event.
     */
    public static function handle_event( \WP_REST_Request $request ): \WP_REST_Response {
        $event_name  = sanitize_text_field( wp_unslash( $request->get_param( 'event_name' ) ?? '' ) );
        $email       = sanitize_email( wp_unslash( $request->get_param( 'email' ) ?? '' ) );
        $event_value = sanitize_text_field( wp_unslash( $request->get_param( 'event_value' ) ?? '' ) );
        $metadata    = (array) ( $request->get_param( 'metadata' ) ?? [] );

        if ( ! $event_name ) {
            return new \WP_REST_Response( [ 'error' => 'event_name is required' ], 400 );
        }

        Tracker::track( $email, $event_name, array_merge( $metadata, [ 'event_value' => $event_value ] ) );

        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    /** GET /leads */
    public static function get_leads( \WP_REST_Request $request ): \WP_REST_Response {
        $args = [
            'search'   => sanitize_text_field( wp_unslash( $request->get_param( 'search' ) ?? '' ) ),
            'per_page' => absint( $request->get_param( 'per_page' ) ?: 20 ),
            'page'     => absint( $request->get_param( 'page' ) ?: 1 ),
            'status'   => sanitize_text_field( wp_unslash( $request->get_param( 'status' ) ?? '' ) ),
        ];
        $data = \AISalesEngine\Modules\Leads\LeadManager::list( $args );
        return new \WP_REST_Response( $data, 200 );
    }

    /** POST /leads */
    public static function create_lead( \WP_REST_Request $request ): \WP_REST_Response {
        $data = [
            'name'         => sanitize_text_field( wp_unslash( $request->get_param( 'name' )         ?? '' ) ),
            'email'        => sanitize_email( wp_unslash( $request->get_param( 'email' )              ?? '' ) ),
            'phone'        => sanitize_text_field( wp_unslash( $request->get_param( 'phone' )         ?? '' ) ),
            'whatsapp'     => sanitize_text_field( wp_unslash( $request->get_param( 'whatsapp' )      ?? '' ) ),
            'instagram'    => sanitize_text_field( wp_unslash( $request->get_param( 'instagram' )     ?? '' ) ),
            'source'       => sanitize_text_field( wp_unslash( $request->get_param( 'source' )        ?? '' ) ),
            'utm_source'   => sanitize_text_field( wp_unslash( $request->get_param( 'utm_source' )    ?? '' ) ),
            'utm_medium'   => sanitize_text_field( wp_unslash( $request->get_param( 'utm_medium' )    ?? '' ) ),
            'utm_campaign' => sanitize_text_field( wp_unslash( $request->get_param( 'utm_campaign' )  ?? '' ) ),
        ];
        $id = \AISalesEngine\Modules\Leads\LeadManager::create( $data );
        if ( ! $id ) {
            return new \WP_REST_Response( [ 'error' => 'Could not create lead.' ], 500 );
        }
        return new \WP_REST_Response( [ 'id' => $id ], 201 );
    }

    /** GET /leads/{id} */
    public static function get_lead( \WP_REST_Request $request ): \WP_REST_Response {
        $id   = absint( $request->get_param( 'id' ) );
        $lead = \AISalesEngine\Modules\Leads\LeadManager::get( $id );
        if ( ! $lead ) {
            return new \WP_REST_Response( [ 'error' => 'Not found.' ], 404 );
        }
        return new \WP_REST_Response( $lead, 200 );
    }

    /** PUT /leads/{id} */
    public static function update_lead( \WP_REST_Request $request ): \WP_REST_Response {
        $id   = absint( $request->get_param( 'id' ) );
        $data = [
            'name'      => sanitize_text_field( wp_unslash( $request->get_param( 'name' )  ?? '' ) ),
            'phone'     => sanitize_text_field( wp_unslash( $request->get_param( 'phone' ) ?? '' ) ),
            'whatsapp'  => sanitize_text_field( wp_unslash( $request->get_param( 'whatsapp' ) ?? '' ) ),
            'instagram' => sanitize_text_field( wp_unslash( $request->get_param( 'instagram' ) ?? '' ) ),
            'status'    => sanitize_text_field( wp_unslash( $request->get_param( 'status' )    ?? '' ) ),
        ];
        $data = array_filter( $data );
        \AISalesEngine\Modules\Leads\LeadManager::update( $id, $data );
        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    /** DELETE /leads/{id} */
    public static function delete_lead( \WP_REST_Request $request ): \WP_REST_Response {
        $id = absint( $request->get_param( 'id' ) );
        \AISalesEngine\Modules\Leads\LeadManager::delete( $id );
        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    /** POST /leads/{id}/tags */
    public static function add_tag_to_lead( \WP_REST_Request $request ): \WP_REST_Response {
        $lead_id = absint( $request->get_param( 'id' ) );
        $tag_id  = absint( $request->get_param( 'tag_id' ) );
        if ( ! $tag_id ) {
            return new \WP_REST_Response( [ 'error' => 'tag_id required.' ], 400 );
        }
        \AISalesEngine\Modules\Tags\TagManager::add_tag_to_lead( $lead_id, $tag_id );
        return new \WP_REST_Response( [ 'success' => true ], 200 );
    }

    /** GET /lists */
    public static function get_lists( \WP_REST_Request $request ): \WP_REST_Response {
        $lists = \AISalesEngine\Modules\Lists\ListManager::list_all();
        return new \WP_REST_Response( $lists, 200 );
    }

    /** POST /lists */
    public static function create_list( \WP_REST_Request $request ): \WP_REST_Response {
        $data = [
            'name'        => sanitize_text_field( wp_unslash( $request->get_param( 'name' )        ?? '' ) ),
            'description' => sanitize_textarea_field( wp_unslash( $request->get_param( 'description' ) ?? '' ) ),
            'webhook_url' => esc_url_raw( wp_unslash( $request->get_param( 'webhook_url' ) ?? '' ) ),
        ];
        $id = \AISalesEngine\Modules\Lists\ListManager::create( $data );
        return new \WP_REST_Response( [ 'id' => $id ], 201 );
    }

    /** GET /automations */
    public static function get_automations( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}ai_automations ORDER BY created_at DESC",
            ARRAY_A
        ) ?: [];
        return new \WP_REST_Response( $rows, 200 );
    }

    /** POST /automations */
    public static function create_automation( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;
        $result = $wpdb->insert(
            $wpdb->prefix . 'ai_automations',
            [
                'name'           => sanitize_text_field( wp_unslash( $request->get_param( 'name' )           ?? '' ) ),
                'trigger_type'   => sanitize_text_field( wp_unslash( $request->get_param( 'trigger_type' )   ?? '' ) ),
                'trigger_config' => wp_json_encode( $request->get_param( 'trigger_config' ) ?: [] ),
                'flow_json'      => wp_json_encode( $request->get_param( 'flow_json' )      ?: [] ),
                'status'         => 'inactive',
            ]
        );
        if ( ! $result ) {
            return new \WP_REST_Response( [ 'error' => 'Could not create automation.' ], 500 );
        }
        return new \WP_REST_Response( [ 'id' => (int) $wpdb->insert_id ], 201 );
    }

    /** GET /analytics/summary */
    public static function get_analytics_summary( \WP_REST_Request $request ): \WP_REST_Response {
        $summary = \AISalesEngine\Modules\Analytics\Analytics::get_summary();
        return new \WP_REST_Response( $summary, 200 );
    }

    /** GET /agents */
    public static function get_agents( \WP_REST_Request $request ): \WP_REST_Response {
        $agents = AgentEngine::list_agents();
        return new \WP_REST_Response( $agents, 200 );
    }

    /** POST /agents */
    public static function create_agent( \WP_REST_Request $request ): \WP_REST_Response {
        $data = [
            'name'            => sanitize_text_field( wp_unslash( $request->get_param( 'name' )             ?? '' ) ),
            'role'            => sanitize_text_field( wp_unslash( $request->get_param( 'role' )             ?? '' ) ),
            'goal'            => sanitize_textarea_field( wp_unslash( $request->get_param( 'goal' )         ?? '' ) ),
            'personality'     => sanitize_textarea_field( wp_unslash( $request->get_param( 'personality' )  ?? '' ) ),
            'training_prompt' => wp_kses_post( wp_unslash( $request->get_param( 'training_prompt' )         ?? '' ) ),
            'voice_enabled'   => absint( $request->get_param( 'voice_enabled' ) ),
            'image_enabled'   => absint( $request->get_param( 'image_enabled' ) ),
        ];
        $id = AgentEngine::create( $data );
        if ( ! $id ) {
            return new \WP_REST_Response( [ 'error' => 'Could not create agent.' ], 500 );
        }
        return new \WP_REST_Response( [ 'id' => $id ], 201 );
    }
}
