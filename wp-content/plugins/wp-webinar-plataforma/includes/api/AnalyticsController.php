<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AnalyticsController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/webinars/(?P<webinar_id>\d+)/analytics', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_analytics' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );

        // Public: track event
        register_rest_route( $ns, '/analytics/track', [
            'methods'             => 'POST',
            'callback'            => [ self::class, 'track_event' ],
            'permission_callback' => [ Routes::class, 'public_auth_callback' ],
        ] );
    }

    public static function get_analytics( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id = (int) $request->get_param( 'webinar_id' );
        $table_p    = $wpdb->prefix . 'webinar_participantes';
        $table_a    = $wpdb->prefix . 'webinar_analytics';

        $total_participantes = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_p} WHERE webinar_id = %d",
            $webinar_id
        ) );

        $hoje = current_time( 'Y-m-d' );
        $participantes_hoje = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_p} WHERE webinar_id = %d AND DATE(data_registro) = %s",
            $webinar_id, $hoje
        ) );

        $tempo_medio = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(tempo_assistido) FROM {$table_p} WHERE webinar_id = %d AND tempo_assistido > 0",
            $webinar_id
        ) );

        $convertidos = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_p} WHERE webinar_id = %d AND convertido = 1",
            $webinar_id
        ) );

        $taxa_conversao = $total_participantes > 0 ? round( ( $convertidos / $total_participantes ) * 100, 2 ) : 0;

        $cliques_botao = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_a} WHERE webinar_id = %d AND evento = 'clique_botao'",
            $webinar_id
        ) );

        // Abandonment by time segments (every 5 minutes)
        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT duracao_minutos FROM {$wpdb->prefix}webinars WHERE id = %d",
            $webinar_id
        ) );
        $duracao = $webinar ? (int) $webinar->duracao_minutos : 60;

        $retencao = [];
        $segmentos = max( 1, (int) ceil( $duracao / 5 ) );
        for ( $i = 0; $i < $segmentos; $i++ ) {
            $seg_inicio = $i * 5 * 60;
            $seg_fim    = ( $i + 1 ) * 5 * 60;
            $count      = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table_p} WHERE webinar_id = %d AND tempo_assistido >= %d",
                $webinar_id, $seg_inicio
            ) );
            $retencao[] = [
                'minuto' => $i * 5,
                'count'  => $count,
            ];
        }

        // Daily registrations for the last 30 days
        $inscricoes_por_dia = $wpdb->get_results( $wpdb->prepare(
            "SELECT DATE(data_registro) as dia, COUNT(*) as total
             FROM {$table_p}
             WHERE webinar_id = %d AND data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY dia ORDER BY dia ASC",
            $webinar_id
        ) );

        return new \WP_REST_Response( [
            'total_participantes'  => $total_participantes,
            'participantes_hoje'   => $participantes_hoje,
            'tempo_medio_segundos' => round( $tempo_medio ),
            'convertidos'          => $convertidos,
            'taxa_conversao'       => $taxa_conversao,
            'cliques_botao'        => $cliques_botao,
            'retencao'             => $retencao,
            'inscricoes_por_dia'   => $inscricoes_por_dia ?: [],
        ], 200 );
    }

    public static function track_event( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $webinar_id      = (int) ( $request->get_param( 'webinar_id' ) ?: 0 );
        $participante_id = (int) ( $request->get_param( 'participante_id' ) ?: 0 );
        $evento          = sanitize_key( $request->get_param( 'evento' ) ?: '' );
        $dados           = $request->get_param( 'dados' );

        $eventos_validos = [
            'inicio_video', 'pause_video', 'fim_video',
            'clique_botao', 'clique_oferta', 'abandono',
            'inscricao', 'conversao',
        ];

        if ( ! $webinar_id || ! in_array( $evento, $eventos_validos, true ) ) {
            return new \WP_REST_Response( [ 'message' => 'Dados inválidos.' ], 400 );
        }

        $dados_json = is_array( $dados ) ? wp_json_encode( $dados ) : ( is_string( $dados ) ? wp_unslash( $dados ) : '{}' );

        $wpdb->insert(
            $wpdb->prefix . 'webinar_analytics',
            [
                'webinar_id'      => $webinar_id,
                'participante_id' => $participante_id ?: null,
                'evento'          => $evento,
                'dados'           => $dados_json,
                'timestamp'       => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s' ]
        );

        // If conversion event, mark participant
        if ( $evento === 'conversao' && $participante_id ) {
            $wpdb->update(
                $wpdb->prefix . 'webinar_participantes',
                [ 'convertido' => 1 ],
                [ 'id' => $participante_id ],
                [ '%d' ],
                [ '%d' ]
            );
        }

        return new \WP_REST_Response( [ 'message' => 'Evento registrado.' ], 201 );
    }
}
