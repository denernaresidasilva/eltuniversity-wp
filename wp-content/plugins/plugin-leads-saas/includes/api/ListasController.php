<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lista;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ListasController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/listas', [
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

        register_rest_route( Routes::NAMESPACE, '/listas/(?P<id>\d+)', [
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
    }

    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $args = [
            'page'     => (int) $request->get_param( 'page' ) ?: 1,
            'per_page' => (int) $request->get_param( 'per_page' ) ?: 50,
        ];
        return new WP_REST_Response( [
            'items' => Lista::all( $args ),
            'total' => Lista::count(),
        ] );
    }

    public static function show( WP_REST_Request $request ): WP_REST_Response {
        $lista = Lista::find( (int) $request['id'] );
        if ( ! $lista ) {
            return new WP_REST_Response( [ 'message' => 'Lista não encontrada.' ], 404 );
        }
        if ( $lista['form_schema_json'] ) {
            $lista['form_schema_json'] = json_decode( $lista['form_schema_json'], true );
        }
        return new WP_REST_Response( $lista );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        if ( empty( $data['nome'] ) ) {
            return new WP_REST_Response( [ 'message' => 'O campo nome é obrigatório.' ], 422 );
        }
        $id = Lista::create( $data );
        if ( ! $id ) {
            return new WP_REST_Response( [ 'message' => 'Erro ao criar lista.' ], 500 );
        }
        $lista = Lista::find( $id );
        if ( $lista['form_schema_json'] ) {
            $lista['form_schema_json'] = json_decode( $lista['form_schema_json'], true );
        }
        return new WP_REST_Response( $lista, 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response {
        $lista = Lista::find( (int) $request['id'] );
        if ( ! $lista ) {
            return new WP_REST_Response( [ 'message' => 'Lista não encontrada.' ], 404 );
        }
        $data = $request->get_json_params();
        Lista::update( (int) $request['id'], $data );
        $lista = Lista::find( (int) $request['id'] );
        if ( $lista['form_schema_json'] ) {
            $lista['form_schema_json'] = json_decode( $lista['form_schema_json'], true );
        }
        return new WP_REST_Response( $lista );
    }

    public static function delete( WP_REST_Request $request ): WP_REST_Response {
        $lista = Lista::find( (int) $request['id'] );
        if ( ! $lista ) {
            return new WP_REST_Response( [ 'message' => 'Lista não encontrada.' ], 404 );
        }
        Lista::delete( (int) $request['id'] );
        return new WP_REST_Response( [ 'message' => 'Lista excluída com sucesso.' ] );
    }
}
