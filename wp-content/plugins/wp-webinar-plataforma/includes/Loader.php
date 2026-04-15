<?php
namespace WebinarPlataforma;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Loader {

    public static function init(): void {

        // Services
        require_once WP_WEBINAR_DIR . 'includes/services/LeadsSaaSIntegration.php';

        // API
        require_once WP_WEBINAR_DIR . 'includes/api/Routes.php';
        require_once WP_WEBINAR_DIR . 'includes/api/WebinarsController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/ParticipantesController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/ChatController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/AutomacoesController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/AnalyticsController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/DashboardController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/SessoesController.php';

        // Admin
        require_once WP_WEBINAR_DIR . 'admin/Admin.php';

        // Public
        require_once WP_WEBINAR_DIR . 'public/GeradorPaginas.php';
        require_once WP_WEBINAR_DIR . 'public/FormInscricao.php';
        require_once WP_WEBINAR_DIR . 'public/RenderWebinar.php';

        // Maybe run DB upgrade
        add_action( 'plugins_loaded', [ Installer::class, 'maybe_upgrade' ] );

        // Register REST routes
        add_action( 'rest_api_init', [ Api\Routes::class, 'register' ] );

        // Boot admin
        if ( is_admin() ) {
            Admin\Admin::init();
        }

        // Boot public
        add_action( 'init', [ Public_\GeradorPaginas::class, 'init' ] );
        add_action( 'init', [ Public_\FormInscricao::class, 'init' ] );
        add_action( 'init', [ Public_\RenderWebinar::class, 'init' ] );

        // Register WP-Cron events
        self::register_cron();

        // Hook: send sequence message
        add_action( 'ww_send_sequence_message', [ self::class, 'handle_sequence_message' ], 10, 3 );

        // Hook: tag "não compareceu" for non-attending participants
        add_action( 'ww_check_nao_compareceu', [ self::class, 'handle_nao_compareceu' ] );
    }

    private static function register_cron(): void {
        // Schedule cron if not already scheduled
        if ( ! wp_next_scheduled( 'ww_check_nao_compareceu' ) ) {
            wp_schedule_event( time(), 'hourly', 'ww_check_nao_compareceu' );
        }
    }

    /**
     * Cron handler: tag participants as "nao_compareceu" if the session started
     * more than 30 minutes ago and they never watched anything (tempo_assistido = 0).
     */
    public static function handle_nao_compareceu(): void {
        global $wpdb;

        $sess_table  = $wpdb->prefix . 'webinar_sessoes';
        $part_table  = $wpdb->prefix . 'webinar_participantes';
        $webinar_tab = $wpdb->prefix . 'webinars';

        // Find active sessions that started > 30 minutes ago
        $cutoff = gmdate( 'Y-m-d H:i:s', time() - 30 * 60 );

        $sessoes = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, w.configuracoes_json
             FROM `{$sess_table}` s
             INNER JOIN `{$webinar_tab}` w ON w.id = s.webinar_id
             WHERE s.status = 'ativa' AND s.inicio_em <= %s",
            $cutoff
        ), ARRAY_A ) ?: [];

        foreach ( $sessoes as $sessao ) {
            // Find participants who never watched anything
            $not_attended = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM `{$part_table}` WHERE sessao_id = %d AND tempo_assistido = 0",
                $sessao['id']
            ), ARRAY_A ) ?: [];

            foreach ( $not_attended as $p ) {
                if ( ! empty( $p['leadsaas_lead_id'] ) ) {
                    \WebinarPlataforma\Services\LeadsSaaSIntegration::attach_tag_by_name(
                        (int) $p['leadsaas_lead_id'],
                        'nao_compareceu'
                    );
                }
            }
        }
    }

    /**
     * WP-Cron handler: send a single sequence message to a participant.
     *
     * @param int   $participante_id
     * @param int   $sessao_id
     * @param array $sequencia  Sequence config: { tipo, assunto, corpo, offset_segundos }
     */
    public static function handle_sequence_message( int $participante_id, int $sessao_id, array $sequencia ): void {
        global $wpdb;

        $part = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}webinar_participantes` WHERE id = %d",
            $participante_id
        ), ARRAY_A );

        if ( ! $part || ! $part['email'] ) {
            return;
        }

        $tipo   = $sequencia['tipo'] ?? 'email';
        $corpo  = $sequencia['corpo'] ?? '';
        $assunto = $sequencia['assunto'] ?? 'Mensagem do Webinar';

        if ( $tipo === 'email' ) {
            wp_mail(
                sanitize_email( $part['email'] ),
                sanitize_text_field( $assunto ),
                wp_kses_post( $corpo )
            );
        }
        // WhatsApp: stub — implement by hooking 'ww_sequence_whatsapp' with a real provider
        if ( $tipo === 'whatsapp' ) {
            do_action( 'ww_sequence_whatsapp', $part, $sequencia, $sessao_id );
        }
    }
}
