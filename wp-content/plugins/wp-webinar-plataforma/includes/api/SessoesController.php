<?php
namespace WebinarPlataforma\Api;

use WebinarPlataforma\Services\LeadsSaaSIntegration;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST controller for webinar sessions (horários/sessões).
 * Each session has its own Lead SaaS list; max 10 active sessions per webinar.
 */
class SessoesController {

    const MAX_SESSOES_ATIVAS = 10;

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        // List / create sessions for a webinar
        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/sessoes', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'list_sessoes' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'create_sessao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        // Single session operations
        register_rest_route( $ns, '/sessoes/(?P<id>\d+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_sessao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ self::class, 'update_sessao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ self::class, 'delete_sessao' ],
                'permission_callback' => [ Routes::class, 'auth_callback' ],
            ],
        ] );

        // Finalize session: move leads to destination list, confirm empty, delete
        register_rest_route( $ns, '/sessoes/(?P<id>\d+)/finalizar', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'finalizar_sessao' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        // Mark replay as sent for a session (evergreen replay, sent only once)
        register_rest_route( $ns, '/sessoes/(?P<id>\d+)/replay', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'marcar_replay' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        // Participants of a session
        register_rest_route( $ns, '/sessoes/(?P<id>\d+)/participantes', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'list_participantes' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        // Trigger message sequence for all participants in a session
        register_rest_route( $ns, '/sessoes/(?P<id>\d+)/sequencia', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'disparar_sequencia' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        // Available Lead SaaS lists & tags (for UI dropdowns)
        register_rest_route( $ns, '/leadsaas/listas', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_leadsaas_listas' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        register_rest_route( $ns, '/leadsaas/tags', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_leadsaas_tags' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );
    }

    /* ──────────────────────────────────────────────────────────
       LIST sessions
    ────────────────────────────────────────────────────────── */
    public static function list_sessoes( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $table      = $wpdb->prefix . 'webinar_sessoes';

        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM `{$table}` WHERE webinar_id = %d ORDER BY inicio_em ASC", $webinar_id ),
            ARRAY_A
        ) ?: [];

        // Enrich with participant counts
        $part_table = $wpdb->prefix . 'webinar_participantes';
        foreach ( $rows as &$row ) {
            $row['total_participantes'] = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT COUNT(*) FROM `{$part_table}` WHERE sessao_id = %d", $row['id'] )
            );
            if ( LeadsSaaSIntegration::is_available() && $row['leadsaas_lista_id'] ) {
                $row['leadsaas_total'] = LeadsSaaSIntegration::count_leads_in_list( (int) $row['leadsaas_lista_id'] );
            } else {
                $row['leadsaas_total'] = null;
            }
        }
        unset( $row );

        return new \WP_REST_Response( $rows, 200 );
    }

    /* ──────────────────────────────────────────────────────────
       GET single session
    ────────────────────────────────────────────────────────── */
    public static function get_sessao( \WP_REST_Request $request ): \WP_REST_Response {
        $sessao = self::find_sessao( (int) $request->get_param( 'id' ) );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }
        return new \WP_REST_Response( $sessao, 200 );
    }

    /* ──────────────────────────────────────────────────────────
       CREATE session
    ────────────────────────────────────────────────────────── */
    public static function create_sessao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $inicio_em  = sanitize_text_field( $request->get_param( 'inicio_em' ) ?: '' );
        $tipo       = sanitize_key( $request->get_param( 'tipo' ) ?: 'evergreen' );

        if ( ! $webinar_id || ! $inicio_em ) {
            return new \WP_REST_Response( [ 'message' => 'webinar_id e inicio_em são obrigatórios.' ], 400 );
        }

        if ( ! in_array( $tipo, [ 'ao_vivo', 'evergreen' ], true ) ) {
            $tipo = 'evergreen';
        }

        // Validate webinar exists
        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, nome FROM `{$wpdb->prefix}webinars` WHERE id = %d",
            $webinar_id
        ) );
        if ( ! $webinar ) {
            return new \WP_REST_Response( [ 'message' => 'Webinar não encontrado.' ], 404 );
        }

        // Enforce limit of MAX_SESSOES_ATIVAS active sessions per webinar
        $table = $wpdb->prefix . 'webinar_sessoes';
        $active = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE webinar_id = %d AND status = 'ativa'",
            $webinar_id
        ) );

        if ( $active >= self::MAX_SESSOES_ATIVAS ) {
            return new \WP_REST_Response( [
                'message' => sprintf(
                    'Limite de %d sessões ativas atingido. Finalize uma sessão existente para criar uma nova.',
                    self::MAX_SESSOES_ATIVAS
                ),
            ], 422 );
        }

        // Create the corresponding Lead SaaS list
        $lista_nome = sprintf(
            'Webinar: %s — Sessão %s',
            $webinar->nome,
            date_i18n( 'd/m/Y H:i', strtotime( $inicio_em ) )
        );
        $leadsaas_lista_id = LeadsSaaSIntegration::create_list( $lista_nome );

        $wpdb->insert(
            $table,
            [
                'webinar_id'       => $webinar_id,
                'tipo'             => $tipo,
                'inicio_em'        => $inicio_em,
                'status'           => 'ativa',
                'leadsaas_lista_id'=> $leadsaas_lista_id ?: null,
                'created_at'       => current_time( 'mysql' ),
            ],
            [ '%d', '%s', '%s', '%s', '%d', '%s' ]
        );

        $id = (int) $wpdb->insert_id;

        if ( ! $id ) {
            return new \WP_REST_Response( [ 'message' => 'Erro ao criar sessão.' ], 500 );
        }

        return new \WP_REST_Response( self::find_sessao( $id ), 201 );
    }

    /* ──────────────────────────────────────────────────────────
       UPDATE session
    ────────────────────────────────────────────────────────── */
    public static function update_sessao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $sessao = self::find_sessao( $id );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }

        $data    = [];
        $formats = [];

        if ( null !== $request->get_param( 'inicio_em' ) ) {
            $data['inicio_em'] = sanitize_text_field( (string) $request->get_param( 'inicio_em' ) );
            $formats[]         = '%s';
        }
        if ( null !== $request->get_param( 'tipo' ) ) {
            $data['tipo'] = sanitize_key( (string) $request->get_param( 'tipo' ) );
            $formats[]    = '%s';
        }

        if ( empty( $data ) ) {
            return new \WP_REST_Response( [ 'message' => 'Nenhum campo para atualizar.' ], 400 );
        }

        $wpdb->update( $wpdb->prefix . 'webinar_sessoes', $data, [ 'id' => $id ], $formats, [ '%d' ] );

        return new \WP_REST_Response( self::find_sessao( $id ), 200 );
    }

    /* ──────────────────────────────────────────────────────────
       DELETE session (only when status=ativa and no participants)
    ────────────────────────────────────────────────────────── */
    public static function delete_sessao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $sessao = self::find_sessao( $id );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }

        $table      = $wpdb->prefix . 'webinar_sessoes';
        $part_table = $wpdb->prefix . 'webinar_participantes';

        $total_p = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$part_table}` WHERE sessao_id = %d",
            $id
        ) );

        if ( $total_p > 0 ) {
            return new \WP_REST_Response( [
                'message' => 'Não é possível excluir uma sessão com participantes. Finalize a sessão primeiro.',
            ], 422 );
        }

        // Delete the associated Lead SaaS list if empty
        if ( $sessao['leadsaas_lista_id'] ) {
            LeadsSaaSIntegration::delete_list( (int) $sessao['leadsaas_lista_id'] );
        }

        $wpdb->delete( $table, [ 'id' => $id ], [ '%d' ] );

        return new \WP_REST_Response( [ 'message' => 'Sessão excluída.' ], 200 );
    }

    /* ──────────────────────────────────────────────────────────
       FINALIZE session: move leads → confirm empty → delete list → delete session
    ────────────────────────────────────────────────────────── */
    public static function finalizar_sessao( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id              = (int) $request->get_param( 'id' );
        $dest_lista_id   = (int) ( $request->get_param( 'lista_destino_id' ) ?: 0 );

        $sessao = self::find_sessao( $id );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }

        if ( $sessao['status'] === 'finalizada' ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão já está finalizada.' ], 422 );
        }

        $table = $wpdb->prefix . 'webinar_sessoes';
        $moved = 0;

        // Move all Lead SaaS leads to destination list (if configured)
        if ( $dest_lista_id > 0 && $sessao['leadsaas_lista_id'] ) {
            $moved = LeadsSaaSIntegration::move_all_leads( (int) $sessao['leadsaas_lista_id'], $dest_lista_id );
        }

        // Mark session as finalized
        $wpdb->update(
            $table,
            [ 'status' => 'finalizada' ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        // Confirm the original list is empty and delete it
        $lista_deletada = false;
        if ( $sessao['leadsaas_lista_id'] ) {
            $remaining = LeadsSaaSIntegration::count_leads_in_list( (int) $sessao['leadsaas_lista_id'] );
            if ( $remaining === 0 ) {
                $lista_deletada = LeadsSaaSIntegration::delete_list( (int) $sessao['leadsaas_lista_id'] );
                if ( $lista_deletada ) {
                    $wpdb->update( $table, [ 'leadsaas_lista_id' => null ], [ 'id' => $id ], [ null ], [ '%d' ] );
                }
            }
        }

        return new \WP_REST_Response( [
            'message'        => 'Sessão finalizada.',
            'leads_movidos'  => $moved,
            'lista_deletada' => $lista_deletada,
        ], 200 );
    }

    /* ──────────────────────────────────────────────────────────
       MARK replay as sent (evergreen, only once)
    ────────────────────────────────────────────────────────── */
    public static function marcar_replay( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $sessao = self::find_sessao( $id );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }

        if ( $sessao['replay_enviado_em'] ) {
            return new \WP_REST_Response( [
                'message'          => 'Replay já foi enviado para esta sessão.',
                'replay_enviado_em'=> $sessao['replay_enviado_em'],
            ], 422 );
        }

        $now = current_time( 'mysql' );
        $wpdb->update(
            $wpdb->prefix . 'webinar_sessoes',
            [ 'replay_enviado_em' => $now ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        return new \WP_REST_Response( [ 'message' => 'Replay marcado como enviado.', 'replay_enviado_em' => $now ], 200 );
    }

    /* ──────────────────────────────────────────────────────────
       LIST participants of a session
    ────────────────────────────────────────────────────────── */
    public static function list_participantes( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $sessao_id = (int) $request->get_param( 'id' );
        $page      = max( 1, (int) ( $request->get_param( 'page' ) ?: 1 ) );
        $per_page  = min( 100, max( 1, (int) ( $request->get_param( 'per_page' ) ?: 20 ) ) );
        $offset    = ( $page - 1 ) * $per_page;

        $table = $wpdb->prefix . 'webinar_participantes';

        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM `{$table}` WHERE sessao_id = %d",
            $sessao_id
        ) );
        $rows  = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$table}` WHERE sessao_id = %d ORDER BY data_registro DESC LIMIT %d OFFSET %d",
            $sessao_id,
            $per_page,
            $offset
        ), ARRAY_A ) ?: [];

        return new \WP_REST_Response( [
            'data'  => $rows,
            'total' => $total,
            'pages' => (int) ceil( max( 1, $total ) / $per_page ),
            'page'  => $page,
        ], 200 );
    }

    /* ──────────────────────────────────────────────────────────
       TRIGGER message sequence for all participants in a session
    ────────────────────────────────────────────────────────── */
    public static function disparar_sequencia( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $id     = (int) $request->get_param( 'id' );
        $sessao = self::find_sessao( $id );
        if ( ! $sessao ) {
            return new \WP_REST_Response( [ 'message' => 'Sessão não encontrada.' ], 404 );
        }

        // Retrieve the webinar's configuracoes_json for sequence definitions
        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT configuracoes_json FROM `{$wpdb->prefix}webinars` WHERE id = %d",
            $sessao['webinar_id']
        ) );

        $config = $webinar && $webinar->configuracoes_json
            ? json_decode( $webinar->configuracoes_json, true )
            : [];

        $sequencias = $config['sequencias'] ?? [];

        if ( empty( $sequencias ) ) {
            return new \WP_REST_Response( [ 'message' => 'Nenhuma sequência configurada para este webinar.' ], 422 );
        }

        // Get all participants of the session
        $part_table    = $wpdb->prefix . 'webinar_participantes';
        $participantes = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM `{$part_table}` WHERE sessao_id = %d",
            $id
        ), ARRAY_A ) ?: [];

        $sessao_inicio = strtotime( $sessao['inicio_em'] );
        $agendados     = 0;

        foreach ( $participantes as $p ) {
            foreach ( $sequencias as $seq ) {
                $offset_seconds = (int) ( $seq['offset_segundos'] ?? 0 );
                $send_at        = $sessao_inicio + $offset_seconds;

                if ( $send_at <= time() ) {
                    // Past time: schedule immediately via WP-Cron
                    $send_at = time() + 60;
                }

                wp_schedule_single_event( $send_at, 'ww_send_sequence_message', [
                    'participante_id' => (int) $p['id'],
                    'sessao_id'       => $id,
                    'sequencia'       => $seq,
                ] );
                $agendados++;
            }
        }

        return new \WP_REST_Response( [
            'message'   => 'Sequência agendada para todos os participantes.',
            'agendados' => $agendados,
        ], 200 );
    }

    /* ──────────────────────────────────────────────────────────
       Lead SaaS helper endpoints (for UI dropdowns)
    ────────────────────────────────────────────────────────── */
    public static function get_leadsaas_listas( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( LeadsSaaSIntegration::get_all_lists(), 200 );
    }

    public static function get_leadsaas_tags( \WP_REST_Request $request ): \WP_REST_Response {
        return new \WP_REST_Response( LeadsSaaSIntegration::get_all_tags(), 200 );
    }

    /* ──────────────────────────────────────────────────────────
       Private helpers
    ────────────────────────────────────────────────────────── */
    private static function find_sessao( int $id ): ?array {
        global $wpdb;
        $table = $wpdb->prefix . 'webinar_sessoes';
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM `{$table}` WHERE id = %d", $id ),
            ARRAY_A
        ) ?: null;
    }
}
