<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lead;
use LeadsSaaS\Services\LeadService;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LeadsController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/leads', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'index' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( Routes::NAMESPACE, '/leads/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'show' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
        ] );

        // Tag management
        register_rest_route( Routes::NAMESPACE, '/leads/(?P<id>\d+)/tags', [
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'add_tag' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'remove_tag' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
        ] );
    }

    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $args = [
            'page'     => (int) $request->get_param( 'page' ) ?: 1,
            'per_page' => (int) $request->get_param( 'per_page' ) ?: 20,
            'lista_id' => (int) $request->get_param( 'lista_id' ),
            'search'   => $request->get_param( 'search' ) ?? '',
            'orderby'  => $request->get_param( 'orderby' ) ?? 'created_at',
            'order'    => $request->get_param( 'order' ) ?? 'DESC',
        ];
        $leads = Lead::all( $args );
        foreach ( $leads as &$lead ) {
            $lead['tags']        = Lead::get_tags( (int) $lead['id'] );
            $lead['campos_json'] = $lead['campos_json'] ? json_decode( $lead['campos_json'], true ) : [];
        }
        return new WP_REST_Response( [
            'items' => $leads,
            'total' => Lead::count( $args['lista_id'] ),
        ] );
    }

    public static function show( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        return new WP_REST_Response( $lead );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        if ( empty( $data['email'] ) ) {
            return new WP_REST_Response( [ 'message' => 'O campo e-mail é obrigatório.' ], 422 );
        }
        $id = LeadService::create_from_data( $data );
        if ( ! $id ) {
            return new WP_REST_Response( [ 'message' => 'Erro ao criar lead.' ], 500 );
        }
        return new WP_REST_Response( Lead::find( $id ), 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        $data = $request->get_json_params();
        Lead::update( (int) $request['id'], $data );
        return new WP_REST_Response( Lead::find( (int) $request['id'] ) );
    }

    public static function delete( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        Lead::delete( (int) $request['id'] );
        return new WP_REST_Response( [ 'message' => 'Lead excluído com sucesso.' ] );
    }

    public static function add_tag( WP_REST_Request $request ): WP_REST_Response {
        $lead = Lead::find( (int) $request['id'] );
        if ( ! $lead ) {
            return new WP_REST_Response( [ 'message' => 'Lead não encontrado.' ], 404 );
        }
        $data   = $request->get_json_params();
        $tag_id = (int) ( $data['tag_id'] ?? 0 );
        if ( ! $tag_id ) {
            return new WP_REST_Response( [ 'message' => 'tag_id é obrigatório.' ], 422 );
        }
        LeadService::attach_tag( (int) $request['id'], $tag_id );
        return new WP_REST_Response( [ 'message' => 'Tag adicionada.' ] );
    }

    public static function remove_tag( WP_REST_Request $request ): WP_REST_Response {
        $data   = $request->get_json_params();
        $tag_id = (int) ( $data['tag_id'] ?? 0 );
        Lead::remove_tag( (int) $request['id'], $tag_id );
        return new WP_REST_Response( [ 'message' => 'Tag removida.' ] );
    }
}
