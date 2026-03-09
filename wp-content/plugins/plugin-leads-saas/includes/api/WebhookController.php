<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lista;
use LeadsSaaS\Services\LeadService;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebhookController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/webhook/(?P<token>[a-zA-Z0-9]{16,64})', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'receive' ],
            'permission_callback' => '__return_true',
            'args'                => [ 'token' => [ 'sanitize_callback' => 'sanitize_text_field' ] ],
        ] );
    }

    public static function receive( WP_REST_Request $request ): WP_REST_Response {
        $token = $request['token'];
        $lista = Lista::find_by_webhook_key( $token );

        if ( ! $lista ) {
            return new WP_REST_Response( [ 'message' => 'Token inválido.' ], 404 );
        }

        $body = $request->get_json_params();
        if ( empty( $body ) ) {
            $body = $request->get_body_params();
        }

        $email = sanitize_email( $body['email'] ?? '' );
        if ( empty( $email ) ) {
            return new WP_REST_Response( [ 'message' => 'E-mail é obrigatório.' ], 422 );
        }

        $lead_id = LeadService::create_from_data( [
            'lista_id' => (int) $lista['id'],
            'nome'     => sanitize_text_field( $body['nome'] ?? '' ),
            'email'    => $email,
            'telefone' => sanitize_text_field( $body['telefone'] ?? '' ),
            'origem'   => 'webhook',
        ], 'webhook' );

        return new WP_REST_Response( [
            'message' => 'Lead cadastrado com sucesso.',
            'lead_id' => $lead_id,
        ], 201 );
    }
}
