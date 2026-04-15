<?php
namespace WebinarPlataforma\Api;

use WebinarPlataforma\Services\LeadsSaaSIntegration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ParticipantesController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/participantes', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_participantes' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/participantes', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_by_webinar' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        register_rest_route( $ns, '/participantes/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_participante' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_participante' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        // Public: inscrever participante (now accepts sessao_id)
        register_rest_route( $ns, '/inscrever', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'inscrever' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );

        // Public: atualizar tempo assistido + auto-tagging on offer threshold
        register_rest_route( $ns, '/participantes/(?P<id>\d+)/tempo', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'update_tempo' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );

        // Public: track offer viewed (tag viu_oferta)
        register_rest_route( $ns, '/participantes/(?P<id>\d+)/oferta-vista', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'marcar_oferta_vista' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );
    }

    public static function list_participantes( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $page       = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );
        $per_page   = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) );
        $search     = sanitize_text_field( $request->get_param( 'search' ) ?: '' );
        $webinar_id = (int) ( $request->get_param( 'webinar_id' ) ?: 0 );
        $sessao_id  = (int) ( $request->get_param( 'sessao_id' ) ?: 0 );
        $offset     = ( $page - 1 ) * $per_page;

        $table = $wpdb->prefix . 'webinar_participantes';

        $where  = '1=1';
        $params = [];

        if ( $webinar_id ) {
            $where   .= ' AND webinar_id = %d';
            $params[] = $webinar_id;
        }

        if ( $sessao_id ) {
            $where   .= ' AND sessao_id = %d';
            $params[] = $sessao_id;
        }

        if ( $search ) {
            $where   .= ' AND (nome LIKE %s OR email LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ( $params ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", ...$params ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY data_registro DESC LIMIT %d OFFSET %d", array_merge( $params, [ $per_page, $offset ] ) ) );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY data_registro DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
        }

        return new \WP_REST_Response( [
            'data'  => $rows ?: [],
            'total' => $total,
            'pages' => (int) ceil( $total / $per_page ),
            'page'  => $page,
        ], 200 );
    }

    public static function list_by_webinar( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $page       = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );
        $per_page   = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) );
        $sessao_id  = (int) ( $request->get_param( 'sessao_id' ) ?: 0 );
        $offset     = ( $page - 1 ) * $per_page;
        $table      = $wpdb->prefix . 'webinar_participantes';

        if ( $sessao_id ) {
            $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE webinar_id = %d AND sessao_id = %d", $webinar_id, $sessao_id ) );
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE webinar_id = %d AND sessao_id = %d ORDER BY data_registro DESC LIMIT %d OFFSET %d", $webinar_id, $sessao_id, $per_page, $offset ) );
        } else {
            $total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE webinar_id = %d", $webinar_id ) );
            $rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE webinar_id = %d ORDER BY data_registro DESC LIMIT %d OFFSET %d", $webinar_id, $per_page, $offset ) );
        }

        return new \WP_REST_Response( [
            'data'  => $rows ?: [],
            'total' => $total,
            'pages' => (int) ceil( $total / $per_page ),
            'page'  => $page,
        ], 200 );
    }

    public static function get_participante( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id  = (int) $request->get_param( 'id' );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinar_participantes WHERE id = %d", $id ) );

        if ( ! $row ) {
            return new \WP_REST_Response( [ 'message' => 'Participante não encontrado.' ], 404 );
        }

        return new \WP_REST_Response( $row, 200 );
    }

    public static function delete_participante( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id = (int) $request->get_param( 'id' );
        $wpdb->delete( $wpdb->prefix . 'webinar_participantes', [ 'id' => $id ], [ '%d' ] );

        return new \WP_REST_Response( [ 'message' => 'Participante removido.' ], 200 );
    }

    public static function inscrever( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) ( $request->get_param( 'webinar_id' ) ?: 0 );
        $sessao_id  = (int) ( $request->get_param( 'sessao_id' ) ?: 0 );
        $nome       = sanitize_text_field( $request->get_param( 'nome' ) ?: '' );
        $email      = sanitize_email( $request->get_param( 'email' ) ?: '' );
        $telefone   = sanitize_text_field( $request->get_param( 'telefone' ) ?: '' );

        if ( ! $webinar_id || ! $nome || ! $email ) {
            return new \WP_REST_Response( [ 'message' => 'Campos obrigatórios: webinar_id, nome e email.' ], 400 );
        }

        if ( ! is_email( $email ) ) {
            return new \WP_REST_Response( [ 'message' => 'E-mail inválido.' ], 400 );
        }

        $webinar = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d AND status = 'publicado'", $webinar_id ) );

        if ( ! $webinar ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado ou não está publicado.' ], 404 );
        }

        // Validate session if provided
        $sessao = null;
        if ( $sessao_id ) {
            $sessao = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}webinar_sessoes WHERE id = %d AND webinar_id = %d AND status = 'ativa'",
                $sessao_id,
                $webinar_id
            ) );
            if ( ! $sessao ) {
                return new \WP_REST_Response( [ 'message' => 'Sessão inválida ou não encontrada.' ], 404 );
            }
        }

        // Check for duplicate registration in the same session (or webinar if no session)
        $where_dup = 'webinar_id = %d AND email = %s';
        $params_dup = [ $webinar_id, $email ];
        if ( $sessao_id ) {
            $where_dup  .= ' AND sessao_id = %d';
            $params_dup[] = $sessao_id;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}webinar_participantes WHERE {$where_dup}",
            ...$params_dup
        ) );

        if ( $existing ) {
            return new \WP_REST_Response( [
                'message'         => 'Você já está inscrito neste webinar.',
                'participante_id' => (int) $existing,
            ], 200 );
        }

        // Resolve Lead SaaS list: session's list or null
        $leadsaas_lista_id = $sessao ? (int) $sessao->leadsaas_lista_id : 0;
        $leadsaas_lead_id  = 0;

        // Create / upsert lead in Lead SaaS
        if ( $leadsaas_lista_id > 0 ) {
            $leadsaas_lead_id = LeadsSaaSIntegration::upsert_lead(
                $leadsaas_lista_id,
                $email,
                $nome,
                $telefone
            );
        }

        $insert_data = [
            'webinar_id'        => $webinar_id,
            'sessao_id'         => $sessao_id ?: null,
            'nome'              => $nome,
            'email'             => $email,
            'telefone'          => $telefone,
            'data_registro'     => current_time( 'mysql' ),
            'tempo_assistido'   => 0,
            'ip_address'        => self::get_client_ip(),
            'leadsaas_lead_id'  => $leadsaas_lead_id ?: null,
            'leadsaas_lista_id' => $leadsaas_lista_id ?: null,
        ];

        $wpdb->insert(
            $wpdb->prefix . 'webinar_participantes',
            $insert_data,
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d' ]
        );

        $participante_id = (int) $wpdb->insert_id;

        // Log analytics event
        $wpdb->insert(
            $wpdb->prefix . 'webinar_analytics',
            [
                'webinar_id'      => $webinar_id,
                'participante_id' => $participante_id,
                'evento'          => 'inscricao',
                'dados'           => wp_json_encode( [ 'email' => $email, 'sessao_id' => $sessao_id ] ),
                'timestamp'       => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );

        return new \WP_REST_Response( [
            'message'         => 'Inscrição realizada com sucesso!',
            'participante_id' => $participante_id,
            'redirect_url'    => get_page_link( (int) $webinar->pagina_webinar_id ),
        ], 201 );
    }

    public static function update_tempo( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id    = (int) $request->get_param( 'id' );
        $tempo = (int) ( $request->get_param( 'tempo' ) ?: 0 );

        $wpdb->update(
            $wpdb->prefix . 'webinar_participantes',
            [ 'tempo_assistido' => $tempo ],
            [ 'id' => $id ],
            [ '%d' ],
            [ '%d' ]
        );

        // Auto-tagging: check if offer threshold has been crossed
        $part = $wpdb->get_row( $wpdb->prepare(
            "SELECT p.*, w.configuracoes_json FROM {$wpdb->prefix}webinar_participantes p
             INNER JOIN {$wpdb->prefix}webinars w ON w.id = p.webinar_id
             WHERE p.id = %d",
            $id
        ) );

        if ( $part && $part->leadsaas_lead_id && $part->configuracoes_json ) {
            $config    = json_decode( $part->configuracoes_json, true );
            $oferta_em = (int) ( $config['oferta_aparece_em_segundos'] ?? 0 );

            if ( $oferta_em > 0 && $tempo >= $oferta_em ) {
                LeadsSaaSIntegration::attach_tag_by_name( (int) $part->leadsaas_lead_id, 'viu_oferta' );

                // Check webinar tag automations: tag → move to list
                $tag_automations = $config['tag_automacoes'] ?? [];
                foreach ( $tag_automations as $ta ) {
                    if ( ( $ta['tag_nome'] ?? '' ) === 'viu_oferta' && ! empty( $ta['lista_id'] ) ) {
                        LeadsSaaSIntegration::move_lead_to_list(
                            (int) $part->leadsaas_lead_id,
                            (int) $ta['lista_id']
                        );
                    }
                }
            }
        }

        return new \WP_REST_Response( [ 'message' => 'Tempo atualizado.' ], 200 );
    }

    /**
     * Explicitly mark that the participant has seen the offer (called by player when offer threshold is crossed).
     */
    public static function marcar_oferta_vista( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id = (int) $request->get_param( 'id' );

        $part = $wpdb->get_row( $wpdb->prepare(
            "SELECT p.*, w.configuracoes_json FROM {$wpdb->prefix}webinar_participantes p
             INNER JOIN {$wpdb->prefix}webinars w ON w.id = p.webinar_id
             WHERE p.id = %d",
            $id
        ) );

        if ( ! $part ) {
            return new \WP_REST_Response( [ 'message' => 'Participante não encontrado.' ], 404 );
        }

        if ( ! $part->leadsaas_lead_id ) {
            return new \WP_REST_Response( [ 'message' => 'Participante não possui lead associado.' ], 200 );
        }

        LeadsSaaSIntegration::attach_tag_by_name( (int) $part->leadsaas_lead_id, 'viu_oferta' );

        // Apply tag automations defined in webinar config
        $config          = $part->configuracoes_json ? json_decode( $part->configuracoes_json, true ) : [];
        $tag_automations = $config['tag_automacoes'] ?? [];
        foreach ( $tag_automations as $ta ) {
            if ( ( $ta['tag_nome'] ?? '' ) === 'viu_oferta' && ! empty( $ta['lista_id'] ) ) {
                LeadsSaaSIntegration::move_lead_to_list(
                    (int) $part->leadsaas_lead_id,
                    (int) $ta['lista_id']
                );
            }
        }

        // Log analytics
        $wpdb->insert(
            $wpdb->prefix . 'webinar_analytics',
            [
                'webinar_id'      => (int) $part->webinar_id,
                'participante_id' => $id,
                'evento'          => 'viu_oferta',
                'dados'           => wp_json_encode( [] ),
                'timestamp'       => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );

        return new \WP_REST_Response( [ 'message' => 'Etiqueta "viu_oferta" aplicada.' ], 200 );
    }

    private static function get_client_ip(): string {
        $raw = '';

        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $raw = (string) $_SERVER['REMOTE_ADDR'];
        }

        $ip = filter_var( $raw, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );

        // Fall back to any valid IP (includes private ranges for local dev)
        if ( ! $ip ) {
            $ip = filter_var( $raw, FILTER_VALIDATE_IP );
        }

        return $ip ? $ip : '';
    }
}
