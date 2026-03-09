<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AutomacoesController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/automacoes', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_automacoes' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_automacao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/automacoes/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update_automacao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_automacao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        // Public: get automations by video time
        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/automacoes/tempo/(?P<tempo>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_por_tempo' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );
    }

    public static function list_automacoes( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $rows       = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinar_automacoes WHERE webinar_id = %d ORDER BY ordem ASC, created_at ASC",
            $webinar_id
        ) );

        return new \WP_REST_Response( $rows ?: [], 200 );
    }

    public static function create_automacao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $nome       = sanitize_text_field( $request->get_param( 'nome' ) ?: '' );
        $gatilho    = sanitize_key( $request->get_param( 'gatilho' ) ?: '' );
        $acao       = sanitize_key( $request->get_param( 'acao' ) ?: '' );
        $config     = $request->get_param( 'config' );
        $ordem      = (int) ( $request->get_param( 'ordem' ) ?: 0 );

        $gatilhos_validos = [ 'inscricao', 'inicio_video', 'tempo_especifico', 'tag_adicionada' ];
        $acoes_validas    = [ 'mostrar_botao', 'mostrar_popup', 'enviar_webhook', 'redirecionar', 'mostrar_notificacao' ];

        if ( ! in_array( $gatilho, $gatilhos_validos, true ) ) {
            return new \WP_REST_Response( [ 'message' => 'Gatilho inválido.' ], 400 );
        }

        if ( ! in_array( $acao, $acoes_validas, true ) ) {
            return new \WP_REST_Response( [ 'message' => 'Ação inválida.' ], 400 );
        }

        $config_json = is_array( $config ) ? wp_json_encode( $config ) : ( is_string( $config ) ? wp_unslash( $config ) : '{}' );

        $wpdb->insert(
            $wpdb->prefix . 'webinar_automacoes',
            [
                'webinar_id' => $webinar_id,
                'nome'       => $nome,
                'gatilho'    => $gatilho,
                'acao'       => $acao,
                'config'     => $config_json,
                'ordem'      => $ordem,
                'ativo'      => 1,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' ]
        );

        $id  = (int) $wpdb->insert_id;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_automacoes WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 201 );
    }

    public static function update_automacao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $data   = [];
        $fmt    = [];

        $fields = [ 'nome' => '%s', 'gatilho' => '%s', 'acao' => '%s', 'ordem' => '%d', 'ativo' => '%d' ];
        foreach ( $fields as $field => $f ) {
            $val = $request->get_param( $field );
            if ( null !== $val ) {
                $data[ $field ] = $f === '%s' ? sanitize_text_field( (string) $val ) : (int) $val;
                $fmt[]          = $f;
            }
        }

        $config = $request->get_param( 'config' );
        if ( null !== $config ) {
            $data['config'] = is_array( $config ) ? wp_json_encode( $config ) : wp_unslash( (string) $config );
            $fmt[]          = '%s';
        }

        if ( ! empty( $data ) ) {
            $wpdb->update( $wpdb->prefix . 'webinar_automacoes', $data, [ 'id' => $id ], $fmt, [ '%d' ] );
        }

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_automacoes WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 200 );
    }

    public static function delete_automacao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id = (int) $request->get_param( 'id' );
        $wpdb->delete( $wpdb->prefix . 'webinar_automacoes', [ 'id' => $id ], [ '%d' ] );

        return new \WP_REST_Response( [ 'message' => 'Automação excluída.' ], 200 );
    }

    public static function get_por_tempo( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id   = (int) $request->get_param( 'webinar_id' );
        $tempo        = (int) $request->get_param( 'tempo' );
        $tempo_inicio = max( 0, $tempo - 5 );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinar_automacoes
             WHERE webinar_id = %d AND ativo = 1 AND gatilho = 'tempo_especifico'
             AND JSON_EXTRACT(config, '$.tempo') >= %d AND JSON_EXTRACT(config, '$.tempo') <= %d
             ORDER BY ordem ASC",
            $webinar_id, $tempo_inicio, $tempo
        ) );

        return new \WP_REST_Response( $rows ?: [], 200 );
    }
}
