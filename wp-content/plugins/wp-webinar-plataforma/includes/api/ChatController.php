<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ChatController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/chat', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_mensagens' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_mensagem' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/chat/(?P<id>\d+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update_mensagem' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_mensagem' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        // Public: get messages by video time
        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/chat/tempo/(?P<tempo>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_por_tempo' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );

        // Public: send live message
        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/chat/ao-vivo', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'send_ao_vivo' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );
    }

    public static function list_mensagens( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $table      = $wpdb->prefix . 'webinar_chat_mensagens';

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$table} WHERE webinar_id = %d ORDER BY tempo ASC, created_at ASC",
            $webinar_id
        ) );

        return new \WP_REST_Response( $rows ?: [], 200 );
    }

    public static function create_mensagem( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $tempo      = (int) ( $request->get_param( 'tempo' ) ?: 0 );
        $autor      = sanitize_text_field( $request->get_param( 'autor' ) ?: '' );
        $mensagem   = sanitize_textarea_field( $request->get_param( 'mensagem' ) ?: '' );
        $tipo       = sanitize_key( $request->get_param( 'tipo' ) ?: 'programada' );

        if ( ! in_array( $tipo, [ 'programada', 'ao_vivo' ], true ) ) {
            $tipo = 'programada';
        }

        if ( ! $autor || ! $mensagem ) {
            return new \WP_REST_Response( [ 'message' => 'Autor e mensagem são obrigatórios.' ], 400 );
        }

        $wpdb->insert(
            $wpdb->prefix . 'webinar_chat_mensagens',
            [
                'webinar_id' => $webinar_id,
                'tempo'      => $tempo,
                'autor'      => $autor,
                'mensagem'   => $mensagem,
                'tipo'       => $tipo,
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        $id  = (int) $wpdb->insert_id;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_chat_mensagens WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 201 );
    }

    public static function update_mensagem( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $tempo  = (int) ( $request->get_param( 'tempo' ) ?: 0 );
        $autor  = sanitize_text_field( $request->get_param( 'autor' ) ?: '' );
        $msg    = sanitize_textarea_field( $request->get_param( 'mensagem' ) ?: '' );

        $wpdb->update(
            $wpdb->prefix . 'webinar_chat_mensagens',
            [ 'tempo' => $tempo, 'autor' => $autor, 'mensagem' => $msg ],
            [ 'id' => $id ],
            [ '%d', '%s', '%s' ],
            [ '%d' ]
        );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_chat_mensagens WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 200 );
    }

    public static function delete_mensagem( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id = (int) $request->get_param( 'id' );
        $wpdb->delete( $wpdb->prefix . 'webinar_chat_mensagens', [ 'id' => $id ], [ '%d' ] );

        return new \WP_REST_Response( [ 'message' => 'Mensagem excluída.' ], 200 );
    }

    public static function get_por_tempo( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id   = (int) $request->get_param( 'webinar_id' );
        $tempo        = (int) $request->get_param( 'tempo' );
        $tempo_inicio = max( 0, $tempo - 5 );

        $rows = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinar_chat_mensagens
             WHERE webinar_id = %d AND tipo = 'programada' AND tempo >= %d AND tempo <= %d
             ORDER BY tempo ASC",
            $webinar_id, $tempo_inicio, $tempo
        ) );

        return new \WP_REST_Response( $rows ?: [], 200 );
    }

    public static function send_ao_vivo( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $autor      = sanitize_text_field( $request->get_param( 'autor' ) ?: 'Anônimo' );
        $mensagem   = sanitize_textarea_field( $request->get_param( 'mensagem' ) ?: '' );

        if ( ! $mensagem ) {
            return new \WP_REST_Response( [ 'message' => 'Mensagem é obrigatória.' ], 400 );
        }

        $wpdb->insert(
            $wpdb->prefix . 'webinar_chat_mensagens',
            [
                'webinar_id' => $webinar_id,
                'tempo'      => 0,
                'autor'      => $autor,
                'mensagem'   => $mensagem,
                'tipo'       => 'ao_vivo',
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s' ]
        );

        $id  = (int) $wpdb->insert_id;
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_chat_mensagens WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 201 );
    }
}
