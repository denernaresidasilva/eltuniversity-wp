<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Automacao;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AutomacoesController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/automacoes', [
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

        register_rest_route( Routes::NAMESPACE, '/automacoes/(?P<id>\d+)', [
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

    public static function index(): WP_REST_Response {
        $automacoes = Automacao::all();
        foreach ( $automacoes as &$a ) {
            $a['acoes_json'] = $a['acoes_json'] ? json_decode( $a['acoes_json'], true ) : [];
        }
        return new WP_REST_Response( $automacoes );
    }

    public static function show( WP_REST_Request $request ): WP_REST_Response {
        $a = Automacao::find( (int) $request['id'] );
        if ( ! $a ) {
            return new WP_REST_Response( [ 'message' => 'Automação não encontrada.' ], 404 );
        }
        return new WP_REST_Response( $a );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        if ( empty( $data['nome'] ) || empty( $data['trigger'] ) ) {
            return new WP_REST_Response( [ 'message' => 'Nome e trigger são obrigatórios.' ], 422 );
        }
        $id = Automacao::create( $data );
        return new WP_REST_Response( Automacao::find( $id ), 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response {
        $a = Automacao::find( (int) $request['id'] );
        if ( ! $a ) {
            return new WP_REST_Response( [ 'message' => 'Automação não encontrada.' ], 404 );
        }
        $data = $request->get_json_params();
        Automacao::update( (int) $request['id'], $data );
        return new WP_REST_Response( Automacao::find( (int) $request['id'] ) );
    }

    public static function delete( WP_REST_Request $request ): WP_REST_Response {
        $a = Automacao::find( (int) $request['id'] );
        if ( ! $a ) {
            return new WP_REST_Response( [ 'message' => 'Automação não encontrada.' ], 404 );
        }
        Automacao::delete( (int) $request['id'] );
        return new WP_REST_Response( [ 'message' => 'Automação excluída.' ] );
    }
}
