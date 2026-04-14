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

    /**
     * Chaves sensíveis que não devem ser armazenadas no payload.
     */
    private const SENSITIVE_KEYS = [ 'password', 'senha', 'token', 'secret', 'api_key', 'apikey', 'access_token', 'card', 'cvv', 'credit' ];

    public static function register_routes(): void {
        $args = [ 'token' => [ 'sanitize_callback' => 'sanitize_text_field' ] ];

        // GET / HEAD – usado por plataformas para verificar se a URL está ativa.
        register_rest_route( Routes::NAMESPACE, '/webhook/(?P<token>[a-zA-Z0-9]{16,64})', [
            'methods'             => [ 'GET', 'HEAD' ],
            'callback'            => [ self::class, 'validate' ],
            'permission_callback' => '__return_true',
            'args'                => $args,
        ] );

        // POST – recebe eventos/leads das plataformas (Eduzz, Hotmart, etc.).
        register_rest_route( Routes::NAMESPACE, '/webhook/(?P<token>[a-zA-Z0-9]{16,64})', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'receive' ],
            'permission_callback' => '__return_true',
            'args'                => $args,
        ] );
    }

    /**
     * Handler de validação (GET/HEAD).
     * Retorna 200 para token válido ou 404 para token inválido.
     */
    public static function validate( WP_REST_Request $request ): WP_REST_Response {
        $token = $request['token'];
        $lista = Lista::find_by_webhook_key( $token );

        if ( ! $lista ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[LeadsSaaS] Webhook validate: token inválido.' );
            }
            return new WP_REST_Response( [ 'message' => 'Token inválido.' ], 404 );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook validate: token válido para lista #' . (int) $lista['id'] . '.' );
        }

        return new WP_REST_Response( [ 'message' => 'Webhook ativo.' ], 200 );
    }

    /**
     * Handler de recebimento de eventos (POST).
     * Cria lead quando há e-mail válido; responde 200 quando o payload é apenas
     * um teste de plataforma (sem e-mail), para não reprovar a verificação.
     */
    public static function receive( WP_REST_Request $request ): WP_REST_Response {
        $token = $request['token'];
        $lista = Lista::find_by_webhook_key( $token );

        if ( ! $lista ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[LeadsSaaS] Webhook receive: token inválido.' );
            }
            return new WP_REST_Response( [ 'message' => 'Token inválido.' ], 404 );
        }

        $body = $request->get_json_params();
        if ( empty( $body ) ) {
            $body = $request->get_body_params();
        }
        if ( empty( $body ) ) {
            $body = [];
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook receive: payload recebido para lista #' . (int) $lista['id'] . '.' );
        }

        $email = self::extract_email( $body );

        // Sem e-mail = provavelmente teste de plataforma → retorna 200 sem criar lead.
        if ( empty( $email ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[LeadsSaaS] Webhook receive: sem e-mail no payload (possível teste de plataforma), retornando 200.' );
            }
            return new WP_REST_Response( [ 'message' => 'Webhook ativo.' ], 200 );
        }

        $nome     = self::extract_field( $body, [ 'nome', 'name', 'full_name', 'fullname', 'buyer_name', 'customer_name', 'Name' ] );
        $telefone = self::extract_field( $body, [ 'telefone', 'phone', 'celular', 'mobile', 'fone', 'Phone', 'buyer_phone', 'customer_phone' ] );

        $campos_json = self::build_campos_json( $body );

        $lead_id = LeadService::create_from_data( [
            'lista_id'    => (int) $lista['id'],
            'nome'        => sanitize_text_field( $nome ),
            'email'       => $email,
            'telefone'    => sanitize_text_field( $telefone ),
            'campos_json' => $campos_json,
            'origem'      => 'webhook',
        ], 'webhook' );

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook receive: lead #' . $lead_id . ' criado para lista #' . (int) $lista['id'] . '.' );
        }

        return new WP_REST_Response( [
            'message' => 'Lead cadastrado com sucesso.',
            'lead_id' => $lead_id,
        ], 201 );
    }

    /**
     * Extrai o e-mail do payload aceitando variações comuns de nomes de campos
     * (comparação case-insensitive).
     */
    private static function extract_email( array $body ): string {
        // Mapeamento case-insensitive: normalizar chaves para minúsculas.
        $lower_body = array_change_key_case( $body, CASE_LOWER );

        $candidates = [ 'email', 'buyer_email', 'customer_email' ];

        foreach ( $candidates as $key ) {
            if ( ! empty( $lower_body[ $key ] ) ) {
                $email = sanitize_email( (string) $lower_body[ $key ] );
                if ( $email ) {
                    return $email;
                }
            }
        }

        // Suporte a payload aninhado: customer.email, buyer.email
        foreach ( [ 'customer', 'buyer', 'subscriber', 'contact' ] as $parent ) {
            if ( ! empty( $lower_body[ $parent ] ) && is_array( $lower_body[ $parent ] ) ) {
                $nested = array_change_key_case( $lower_body[ $parent ], CASE_LOWER );
                if ( ! empty( $nested['email'] ) ) {
                    $email = sanitize_email( (string) $nested['email'] );
                    if ( $email ) {
                        return $email;
                    }
                }
            }
        }

        return '';
    }

    /**
     * Extrai o primeiro valor não vazio de uma lista de chaves candidatas.
     */
    private static function extract_field( array $body, array $keys ): string {
        foreach ( $keys as $key ) {
            if ( ! empty( $body[ $key ] ) ) {
                return (string) $body[ $key ];
            }
        }

        return '';
    }

    /**
     * Constrói o array de campos extras a partir do payload completo,
     * removendo chaves sensíveis para não armazenar dados críticos.
     *
     * @param array $body  Payload a processar.
     * @param int   $depth Profundidade atual (limite: 5 níveis).
     */
    private static function build_campos_json( array $body, int $depth = 0 ): array {
        if ( $depth > 5 ) {
            return [];
        }

        $sanitized = [];

        foreach ( $body as $key => $value ) {
            $key_lower = strtolower( (string) $key );

            // Ignorar chaves sensíveis.
            $is_sensitive = false;
            foreach ( self::SENSITIVE_KEYS as $sensitive ) {
                if ( strpos( $key_lower, $sensitive ) !== false ) {
                    $is_sensitive = true;
                    break;
                }
            }
            if ( $is_sensitive ) {
                continue;
            }

            if ( is_array( $value ) ) {
                $sanitized[ sanitize_key( $key ) ] = self::build_campos_json( $value, $depth + 1 );
            } elseif ( is_scalar( $value ) ) {
                $sanitized[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
            }
        }

        return $sanitized;
    }
}
