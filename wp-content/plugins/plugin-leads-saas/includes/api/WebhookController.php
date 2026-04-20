<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lista;
use LeadsSaaS\Models\Tag;
use LeadsSaaS\Services\LeadService;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebhookController {

    /**
     * WordPress option key used to store webhook debug log entries.
     */
    private const DEBUG_OPTION = 'leads_saas_webhook_debug_log';

    /**
     * Maximum number of log entries kept in the circular buffer.
     */
    private const DEBUG_MAX_ENTRIES = 100;

    /**
     * Chaves sensíveis que não devem ser armazenadas no payload.
     */
    private const SENSITIVE_KEYS = [ 'password', 'senha', 'token', 'secret', 'api_key', 'apikey', 'access_token', 'card', 'cvv', 'credit' ];

    /**
     * Profundidade máxima para busca recursiva de e-mail no payload.
     */
    private const MAX_EMAIL_SEARCH_DEPTH = 5;

    /**
     * Chaves de 1º nível verificadas prioritariamente na extração de e-mail.
     */
    private const EMAIL_TOP_CANDIDATES = [ 'email', 'buyer_email', 'customer_email', 'user_email', 'contact_email' ];

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
     * Register debug-specific REST routes (requires authentication).
     */
    public static function register_debug_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/webhook-debug/logs', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_debug_logs' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'clear_debug_logs' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
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
            self::append_debug_log( [
                'method'       => $request->get_method(),
                'token'        => $token,
                'lista_id'     => null,
                'status'       => 'invalid_token',
                'email_found'  => false,
                'email'        => '',
                'lead_id'      => null,
                'response_code'=> 404,
                'payload'      => null,
                'headers'      => self::get_sanitized_headers( $request ),
            ] );
            return new WP_REST_Response( [ 'message' => 'Token inválido.' ], 404 );
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook validate: token válido para lista #' . (int) $lista['id'] . '.' );
        }

        self::append_debug_log( [
            'method'       => $request->get_method(),
            'token'        => $token,
            'lista_id'     => (int) $lista['id'],
            'lista_nome'   => $lista['nome'] ?? '',
            'status'       => 'validation_ok',
            'email_found'  => false,
            'email'        => '',
            'lead_id'      => null,
            'response_code'=> 200,
            'payload'      => null,
            'headers'      => self::get_sanitized_headers( $request ),
        ] );

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
            self::append_debug_log( [
                'method'       => 'POST',
                'token'        => $token,
                'lista_id'     => null,
                'status'       => 'invalid_token',
                'email_found'  => false,
                'email'        => '',
                'lead_id'      => null,
                'response_code'=> 404,
                'payload'      => self::safe_truncate( $request->get_body() ),
                'headers'      => self::get_sanitized_headers( $request ),
            ] );
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
                } elseif ( JSON_ERROR_NONE !== json_last_error() && strpos( $raw, '=' ) !== false ) {
                    // 4. Fallback final: application/x-www-form-urlencoded como string
                    //    (somente quando json_decode falhou com erro real).
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
            self::append_debug_log( [
                'method'       => 'POST',
                'token'        => $token,
                'lista_id'     => (int) $lista['id'],
                'lista_nome'   => $lista['nome'] ?? '',
                'status'       => 'no_email_found',
                'strategy'     => $strategy,
                'email_found'  => false,
                'email'        => '',
                'lead_id'      => null,
                'response_code'=> 200,
                'payload'      => $body,
                'raw_body'     => self::safe_truncate( $request->get_body() ),
                'headers'      => self::get_sanitized_headers( $request ),
            ] );
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

        // Attach tags sent in the payload (array of tag names or tag IDs).
        if ( $lead_id > 0 ) {
            $raw_tags = $body['tags'] ?? ( $body['etiquetas'] ?? [] );
            if ( ! empty( $raw_tags ) && is_array( $raw_tags ) ) {
                self::attach_tags_by_name( $lead_id, $raw_tags );
            }
        }

        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( '[LeadsSaaS] Webhook receive: lead #' . (int) $lead_id . ' criado para lista #' . (int) $lista['id'] . '.' );
        }

        self::append_debug_log( [
            'method'       => 'POST',
            'token'        => $token,
            'lista_id'     => (int) $lista['id'],
            'lista_nome'   => $lista['nome'] ?? '',
            'status'       => $lead_id > 0 ? 'lead_created' : 'lead_creation_failed',
            'strategy'     => $strategy,
            'email_found'  => true,
            'email'        => $email,
            'email_path'   => $email_info['path'],
            'nome'         => $nome,
            'telefone'     => $telefone,
            'lead_id'      => $lead_id > 0 ? $lead_id : null,
            'response_code'=> 201,
            'payload'      => $body,
            'raw_body'     => self::safe_truncate( $request->get_body() ),
            'headers'      => self::get_sanitized_headers( $request ),
        ] );

        return new WP_REST_Response( [
            'message' => 'Lead cadastrado com sucesso.',
            'lead_id' => $lead_id,
        ], 201 );
    }

    /* ------------------------------------------------------------------ */
    /*  Debug log helpers                                                    */
    /* ------------------------------------------------------------------ */

    /**
     * Append one entry to the circular debug log stored as a WordPress option.
     */
    private static function append_debug_log( array $entry ): void {
        $entry['timestamp'] = gmdate( 'Y-m-d H:i:s' ) . ' UTC';
        $log = get_option( self::DEBUG_OPTION, [] );
        if ( ! is_array( $log ) ) {
            $log = [];
        }
        array_unshift( $log, $entry );                         // newest first
        $log = array_slice( $log, 0, self::DEBUG_MAX_ENTRIES ); // keep last N
        update_option( self::DEBUG_OPTION, $log, false );
    }

    /**
     * REST GET /webhook-debug/logs – returns all stored entries (admin only).
     */
    public static function get_debug_logs( WP_REST_Request $request ): WP_REST_Response {
        $log = get_option( self::DEBUG_OPTION, [] );
        return new WP_REST_Response( is_array( $log ) ? $log : [], 200 );
    }

    /**
     * REST DELETE /webhook-debug/logs – clears the log (admin only).
     */
    public static function clear_debug_logs( WP_REST_Request $request ): WP_REST_Response {
        delete_option( self::DEBUG_OPTION );
        return new WP_REST_Response( [ 'message' => 'Log limpo com sucesso.' ], 200 );
    }

    /**
     * Collect selected request headers, stripping sensitive values.
     */
    private static function get_sanitized_headers( WP_REST_Request $request ): array {
        $headers = $request->get_headers();
        $keep    = [ 'content_type', 'content-type', 'user_agent', 'user-agent', 'x_forwarded_for', 'x-forwarded-for', 'accept', 'origin' ];
        $result  = [];
        foreach ( $headers as $key => $value ) {
            if ( in_array( strtolower( $key ), $keep, true ) ) {
                $result[ $key ] = is_array( $value ) ? implode( ', ', $value ) : $value;
            }
        }
        return $result;
    }

    /**
     * Truncate a raw string to a safe display length.
     */
    private static function safe_truncate( string $str, int $max = 4000 ): string {
        return mb_strlen( $str ) > $max ? mb_substr( $str, 0, $max ) . '…' : $str;
    }

    /* ------------------------------------------------------------------ */
    /*  Email / field extraction helpers                                    */
    /* ------------------------------------------------------------------ */

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
        if ( $depth > self::MAX_EMAIL_SEARCH_DEPTH ) {
            return [ 'email' => '', 'path' => '' ];
        }

        // Chaves diretas prioritárias (sem aninhamento) verificadas primeiro.
        $lower_body = array_change_key_case( $body, CASE_LOWER );

        foreach ( self::EMAIL_TOP_CANDIDATES as $key ) {
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

    /**
     * Resolve a list of tag names (or IDs) and attach them to a lead.
     * Tags that do not exist yet are created on the fly.
     *
     * @param int   $lead_id   Lead to attach tags to.
     * @param array $raw_tags  Array of tag names (string) or IDs (int).
     */
    private static function attach_tags_by_name( int $lead_id, array $raw_tags ): void {
        foreach ( $raw_tags as $raw ) {
            if ( is_int( $raw ) || ( is_string( $raw ) && ctype_digit( $raw ) ) ) {
                // Numeric: treat as tag ID.
                $tag_id = (int) $raw;
                if ( $tag_id > 0 ) {
                    LeadService::attach_tag( $lead_id, $tag_id );
                }
                continue;
            }

            $nome = sanitize_text_field( (string) $raw );
            if ( '' === $nome ) {
                continue;
            }

            // Find existing tag by name (case-insensitive).
            $all_tags = Tag::all();
            $found    = null;
            foreach ( $all_tags as $tag ) {
                if ( strtolower( $tag['nome'] ) === strtolower( $nome ) ) {
                    $found = $tag;
                    break;
                }
            }

            $tag_id = $found ? (int) $found['id'] : Tag::create( [ 'nome' => $nome ] );
            if ( $tag_id > 0 ) {
                LeadService::attach_tag( $lead_id, $tag_id );
            }
        }
    }
}
