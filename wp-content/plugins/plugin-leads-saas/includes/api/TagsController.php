<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Tag;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TagsController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/tags', [
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

        register_rest_route( Routes::NAMESPACE, '/tags/(?P<id>\d+)', [
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
        return new WP_REST_Response( Tag::all() );
    }

    public static function create( WP_REST_Request $request ): WP_REST_Response {
        $data = $request->get_json_params();
        if ( empty( $data['nome'] ) ) {
            return new WP_REST_Response( [ 'message' => 'O campo nome é obrigatório.' ], 422 );
        }
        $id = Tag::create( $data );
        return new WP_REST_Response( Tag::find( $id ), 201 );
    }

    public static function update( WP_REST_Request $request ): WP_REST_Response {
        $tag = Tag::find( (int) $request['id'] );
        if ( ! $tag ) {
            return new WP_REST_Response( [ 'message' => 'Tag não encontrada.' ], 404 );
        }
        $data = $request->get_json_params();
        Tag::update( (int) $request['id'], $data );
        return new WP_REST_Response( Tag::find( (int) $request['id'] ) );
    }

    public static function delete( WP_REST_Request $request ): WP_REST_Response {
        $tag = Tag::find( (int) $request['id'] );
        if ( ! $tag ) {
            return new WP_REST_Response( [ 'message' => 'Tag não encontrada.' ], 404 );
        }
        Tag::delete( (int) $request['id'] );
        return new WP_REST_Response( [ 'message' => 'Tag excluída.' ] );
    }
}
