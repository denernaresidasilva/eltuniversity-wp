<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebinarsController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/webinars', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_webinars' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_webinar' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/webinars/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_webinar' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update_webinar' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_webinar' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/webinars/(?P<id>\d+)/publicar', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'publicar_webinar' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );
    }

    public static function list_webinars( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
        $per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ?: 20 ) );
        $status   = sanitize_key( $request->get_param( 'status' ) ?: '' );
        $search   = sanitize_text_field( $request->get_param( 'search' ) ?: '' );

        $offset = ( $page - 1 ) * $per_page;

        $where = '1=1';
        $params = [];

        if ( $status ) {
            $where   .= ' AND status = %s';
            $params[] = $status;
        }

        if ( $search ) {
            $where   .= ' AND nome LIKE %s';
            $params[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $table = $wpdb->prefix . 'webinars';

        if ( $params ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d", array_merge( $params, [ $per_page, $offset ] ) ) );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
        }

        return new \WP_REST_Response( [
            'data'  => $rows ?: [],
            'total' => $total,
            'pages' => (int) ceil( $total / $per_page ),
            'page'  => $page,
        ], 200 );
    }

    public static function get_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id  = (int) $request->get_param( 'id' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado.' ], 404 );
        }

        return new \WP_REST_Response( $row, 200 );
    }

    public static function create_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $nome             = sanitize_text_field( $request->get_param( 'nome' ) ?: '' );
        $descricao        = sanitize_textarea_field( $request->get_param( 'descricao' ) ?: '' );
        $youtube_video_id = sanitize_text_field( $request->get_param( 'youtube_video_id' ) ?: '' );
        $tipo             = sanitize_key( $request->get_param( 'tipo' ) ?: 'evergreen' );
        $data_inicio      = sanitize_text_field( $request->get_param( 'data_inicio' ) ?: '' );

        if ( ! $nome ) {
            return new \WP_REST_Response( [ 'message' => 'Nome do webinar é obrigatório.' ], 400 );
        }

        if ( ! in_array( $tipo, [ 'ao_vivo', 'evergreen' ], true ) ) {
            $tipo = 'evergreen';
        }

        $slug = self::generate_unique_slug( $nome );

        $wpdb->insert(
            $wpdb->prefix . 'webinars',
            [
                'nome'             => $nome,
                'slug'             => $slug,
                'descricao'        => $descricao,
                'youtube_video_id' => $youtube_video_id,
                'tipo'             => $tipo,
                'status'           => 'rascunho',
                'data_inicio'      => $data_inicio ?: null,
                'created_at'       => current_time( 'mysql' ),
            ],
            [ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
        );

        $id = (int) $wpdb->insert_id;

        if ( ! $id ) {
            return new \WP_REST_Response( [ 'message' => 'Erro ao criar webinar.' ], 500 );
        }

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 201 );
    }

    public static function update_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id  = (int) $request->get_param( 'id' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado.' ], 404 );
        }

        $data    = [];
        $formats = [];

        $fields = [
            'nome'                => '%s',
            'descricao'           => '%s',
            'youtube_video_id'    => '%s',
            'tipo'                => '%s',
            'status'              => '%s',
            'data_inicio'         => '%s',
            'duracao_minutos'     => '%d',
            'bloquear_avanco'     => '%d',
            'simulacao_ativa'     => '%d',
            'simulacao_contagem'  => '%d',
            'layout_json'         => '%s',
            'configuracoes_json'  => '%s',
        ];

        foreach ( $fields as $field => $fmt ) {
            $val = $request->get_param( $field );
            if ( null !== $val ) {
                if ( $fmt === '%s' ) {
                    $data[ $field ] = sanitize_text_field( (string) $val );
                } else {
                    $data[ $field ] = (int) $val;
                }
                // Store raw JSON as-is
                if ( in_array( $field, [ 'layout_json', 'configuracoes_json' ], true ) ) {
                    $data[ $field ] = wp_unslash( (string) $val );
                }
                $formats[] = $fmt;
            }
        }

        if ( empty( $data ) ) {
            return new \WP_REST_Response( [ 'message' => 'Nenhum campo para atualizar.' ], 400 );
        }

        $wpdb->update( $wpdb->prefix . 'webinars', $data, [ 'id' => $id ], $formats, [ '%d' ] );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 200 );
    }

    public static function delete_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id  = (int) $request->get_param( 'id' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado.' ], 404 );
        }

        $wpdb->delete( $wpdb->prefix . 'webinars', [ 'id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_participantes', [ 'webinar_id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_chat_mensagens', [ 'webinar_id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_automacoes', [ 'webinar_id' => $id ], [ '%d' ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_analytics', [ 'webinar_id' => $id ], [ '%d' ] );

        if ( $row->pagina_webinar_id ) {
            wp_delete_post( (int) $row->pagina_webinar_id, true );
        }
        if ( $row->pagina_inscricao_id ) {
            wp_delete_post( (int) $row->pagina_inscricao_id, true );
        }

        return new \WP_REST_Response( [ 'message' => 'Webinar excluído com sucesso.' ], 200 );
    }

    public static function publicar_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id  = (int) $request->get_param( 'id' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado.' ], 404 );
        }

        // Create WordPress pages if needed
        require_once WP_WEBINAR_DIR . 'public/GeradorPaginas.php';
        \WebinarPlataforma\Public_\GeradorPaginas::criar_paginas( $id );

        $wpdb->update(
            $wpdb->prefix . 'webinars',
            [ 'status' => 'publicado' ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d", $id ) );

        return new \WP_REST_Response( $row, 200 );
    }

    private static function generate_unique_slug( string $name ): string {
        global $wpdb;

        $slug = sanitize_title( $name );
        $base = $slug;
        $i    = 1;

        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}webinars WHERE slug = %s", $slug ) ) ) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
