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
     *
     * Payloads suportados (exemplos):
     *
     * Genérico:
     *   {"email":"user@example.com","name":"João","phone":"11999999999"}
     *
     * Eduzz:
     *   {"buyer":{"email":"user@example.com","name":"João","cellphone":"+5511999999999"},
     *    "student":{"email":"user@example.com","name":"João","cellphone":"+5511999999999"}}
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

        // 1. Tentar get_json_params() (Content-Type: application/json).
        $body     = $request->get_json_params();
        $strategy = 'json_params';

        // 2. Fallback: form-urlencoded.
        if ( empty( $body ) ) {
            $body     = $request->get_body_params();
            $strategy = 'body_params';
        }

        // 3. Fallback: leitura do raw body e json_decode.
        if ( empty( $body ) ) {
            $raw = $request->get_body();
            if ( ! empty( $raw ) ) {
                $decoded = json_decode( $raw, true );
                if ( is_array( $decoded ) && ! empty( $decoded ) ) {
                    $body     = $decoded;
                    $strategy = 'raw_json_decode';
                } elseif ( strpos( $raw, '=' ) !== false ) {
                    // 4. Fallback final: application/x-www-form-urlencoded como string.
                    wp_parse_str( $raw, $parsed );
                    if ( ! empty( $parsed ) ) {
                        $body     = $parsed;
                        $strategy = 'raw_wp_parse_str';
                    }
                }
            }
        }

        if ( empty( $body ) ) {
            $body = [];
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook receive: payload recebido para lista #' . (int) $lista['id'] . ' (estratégia: ' . $strategy . ').' );
        }

        $email_info = self::extract_email_with_path( $body );
        $email      = $email_info['email'];

        // Sem e-mail = provavelmente teste de plataforma → retorna 200 sem criar lead.
        if ( empty( $email ) ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( '[LeadsSaaS] Webhook receive: sem e-mail no payload (estratégia: ' . $strategy . ', possível teste de plataforma), retornando 200.' );
            }
            return new WP_REST_Response( [ 'message' => 'Webhook ativo.' ], 200 );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook receive: e-mail encontrado via "' . $email_info['path'] . '".' );
        }

        $nome     = self::extract_field_nested( $body, [ 'nome', 'name', 'full_name', 'fullname', 'buyer_name', 'customer_name' ] );
        $telefone = self::extract_field_nested( $body, [ 'cellphone', 'telefone', 'phone', 'celular', 'mobile', 'fone', 'buyer_phone', 'customer_phone' ] );

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
            error_log( '[LeadsSaaS] Webhook receive: lead #' . (int) $lead_id . ' criado para lista #' . (int) $lista['id'] . '.' );
        }

        return new WP_REST_Response( [
            'message' => 'Lead cadastrado com sucesso.',
            'lead_id' => $lead_id,
        ], 201 );
    }

    /**
     * Extrai o e-mail do payload usando busca recursiva case-insensitive até
     * 5 níveis de profundidade. Retorna um array com 'email' e 'path' (para log).
     *
     * @param array  $body  Payload a pesquisar.
     * @param string $path  Caminho acumulado (para depuração).
     * @param int    $depth Profundidade atual.
     * @return array{email: string, path: string}
     */
    private static function extract_email_with_path( array $body, string $path = '', int $depth = 0 ): array {
        if ( $depth > 5 ) {
            return [ 'email' => '', 'path' => '' ];
        }

        // Chaves diretas prioritárias (sem aninhamento) verificadas primeiro.
        $top_candidates = [ 'email', 'buyer_email', 'customer_email', 'user_email', 'contact_email' ];
        $lower_body     = array_change_key_case( $body, CASE_LOWER );

        foreach ( $top_candidates as $key ) {
            if ( ! empty( $lower_body[ $key ] ) && is_string( $lower_body[ $key ] ) ) {
                $email = sanitize_email( $lower_body[ $key ] );
                if ( $email ) {
                    return [ 'email' => $email, 'path' => ltrim( $path . '.' . $key, '.' ) ];
                }
            }
        }

        // Busca em sub-arrays (recursiva): qualquer chave cujo valor seja array.
        foreach ( $lower_body as $key => $value ) {
            if ( is_array( $value ) ) {
                $sub_path = ltrim( $path . '.' . $key, '.' );
                // Verificar chave "email" no nível imediato do sub-array.
                $nested = array_change_key_case( $value, CASE_LOWER );
                if ( ! empty( $nested['email'] ) && is_string( $nested['email'] ) ) {
                    $email = sanitize_email( $nested['email'] );
                    if ( $email ) {
                        return [ 'email' => $email, 'path' => $sub_path . '.email' ];
                    }
                }
                // Recursão para sub-níveis.
                $result = self::extract_email_with_path( $value, $sub_path, $depth + 1 );
                if ( ! empty( $result['email'] ) ) {
                    return $result;
                }
            }
        }

        return [ 'email' => '', 'path' => '' ];
    }

    /**
     * Extrai o e-mail do payload aceitando variações comuns de nomes de campos
     * (comparação case-insensitive). Mantida por compatibilidade interna.
     *
     * @deprecated Usar extract_email_with_path() diretamente.
     */
    private static function extract_email( array $body ): string {
        return self::extract_email_with_path( $body )['email'];
    }

    /**
     * Extrai o primeiro valor não vazio para uma lista de chaves candidatas,
     * pesquisando também em sub-arrays de 1º nível (ex.: buyer.name, student.name).
     *
     * @param array    $body Payload.
     * @param string[] $keys Chaves candidatas (comparação case-insensitive).
     */
    private static function extract_field_nested( array $body, array $keys ): string {
        $lower_body = array_change_key_case( $body, CASE_LOWER );
        $lower_keys = array_map( 'strtolower', $keys );

        // Busca direta no 1º nível.
        foreach ( $lower_keys as $key ) {
            if ( ! empty( $lower_body[ $key ] ) && is_scalar( $lower_body[ $key ] ) ) {
                return (string) $lower_body[ $key ];
            }
        }

        // Busca em sub-arrays de 1º nível (ex.: buyer.name, student.name).
        foreach ( $lower_body as $value ) {
            if ( is_array( $value ) ) {
                $nested = array_change_key_case( $value, CASE_LOWER );
                foreach ( $lower_keys as $key ) {
                    if ( ! empty( $nested[ $key ] ) && is_scalar( $nested[ $key ] ) ) {
                        return (string) $nested[ $key ];
                    }
                }
            }
        }

        return '';
    }

    /**
     * Extrai o primeiro valor não vazio de uma lista de chaves candidatas.
     *
     * @deprecated Usar extract_field_nested() para suporte a campos aninhados.
     */
    private static function extract_field( array $body, array $keys ): string {
        return self::extract_field_nested( $body, $keys );
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
