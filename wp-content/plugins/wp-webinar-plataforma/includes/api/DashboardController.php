<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DashboardController {

    public static function register_routes(): void {
        $ns = Routes::NAMESPACE;

        register_rest_route( $ns, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'get_dashboard' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );
    }

    public static function get_dashboard( \WP_REST_Request $request ): \WP_REST_Response {
        global $wpdb;

        $table_w = $wpdb->prefix . 'webinars';
        $table_p = $wpdb->prefix . 'webinar_participantes';
        $hoje    = current_time( 'Y-m-d' );

        $total_webinars = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_w}" );

        $total_participantes = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_p}" );

        $participantes_hoje = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_p} WHERE DATE(data_registro) = %s",
            $hoje
        ) );

        $tempo_medio = (float) $wpdb->get_var(
            "SELECT AVG(tempo_assistido) FROM {$table_p} WHERE tempo_assistido > 0"
        );

        $convertidos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table_p} WHERE convertido = 1"
        );

        $taxa_conversao = $total_participantes > 0
            ? round( ( $convertidos / $total_participantes ) * 100, 2 )
            : 0;

        $ultimos_inscritos = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.id, p.nome, p.email, p.data_registro, w.nome as webinar_nome
             FROM {$table_p} p
             LEFT JOIN {$table_w} w ON w.id = p.webinar_id
             ORDER BY p.data_registro DESC
             LIMIT %d",
            10
        ) );

        $webinars_ativos = $wpdb->get_results(
            "SELECT id, nome, slug, tipo, status, created_at,
             (SELECT COUNT(*) FROM {$table_p} WHERE webinar_id = w.id) as total_participantes
             FROM {$table_w} w
             WHERE status = 'publicado'
             ORDER BY created_at DESC
             LIMIT 5"
        );

        // Registrations per day for last 30 days
        $inscricoes_por_dia = $wpdb->get_results(
            "SELECT DATE(data_registro) as dia, COUNT(*) as total
             FROM {$table_p}
             WHERE data_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY dia ORDER BY dia ASC"
        );

        return new \WP_REST_Response( [
            'total_webinars'       => $total_webinars,
            'total_participantes'  => $total_participantes,
            'participantes_hoje'   => $participantes_hoje,
            'tempo_medio_segundos' => round( $tempo_medio ),
            'taxa_conversao'       => $taxa_conversao,
            'ultimos_inscritos'    => $ultimos_inscritos ?: [],
            'webinars_ativos'      => $webinars_ativos ?: [],
            'inscricoes_por_dia'   => $inscricoes_por_dia ?: [],
        ], 200 );
    }
}
