<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\EmailSequence;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EmailSequenceController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/email-sequences/(?P<lista_id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'show' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'lista_id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'save' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
                'args'                => [ 'lista_id' => [ 'sanitize_callback' => 'absint' ] ],
            ],
        ] );

        register_rest_route( Routes::NAMESPACE, '/email/open/(?P<token>[a-zA-Z0-9]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'track_open' ],
                'permission_callback' => '__return_true', // Public – no auth required.
            ],
        ] );
    }

    public static function show( WP_REST_Request $request ): WP_REST_Response {
        $lista_id = (int) $request['lista_id'];
        $sequence = EmailSequence::get_by_lista( $lista_id );
        if ( ! $sequence ) {
            return new WP_REST_Response( [ 'id' => null, 'lista_id' => $lista_id, 'steps' => [] ] );
        }
        $sequence['steps'] = EmailSequence::get_steps( (int) $sequence['id'] );
        return new WP_REST_Response( $sequence );
    }

    public static function save( WP_REST_Request $request ): WP_REST_Response {
        $lista_id = (int) $request['lista_id'];
        $data     = $request->get_json_params();
        $steps    = isset( $data['steps'] ) && is_array( $data['steps'] ) ? $data['steps'] : [];

        $sequence = EmailSequence::get_or_create_by_lista( $lista_id );
        EmailSequence::save_steps( (int) $sequence['id'], $steps );

        $updated          = EmailSequence::get_by_lista( $lista_id );
        $updated['steps'] = EmailSequence::get_steps( (int) $updated['id'] );
        return new WP_REST_Response( $updated );
    }

    /**
     * Public endpoint: record email open and return 1×1 transparent GIF.
     */
    public static function track_open( WP_REST_Request $request ): void {
        $token = sanitize_text_field( $request['token'] );
        EmailSequence::record_open( $token );

        // Serve a 1×1 transparent GIF so email clients don't show a broken image.
        header( 'Content-Type: image/gif' );
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00\x21\xf9\x04\x01\x00\x00\x00\x00\x2c\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3b";
        exit;
    }
}
