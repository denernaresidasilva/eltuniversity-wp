<?php
namespace WebinarPlataforma;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Loader {

    public static function init(): void {

        // API
        require_once WP_WEBINAR_DIR . 'includes/api/Routes.php';
        require_once WP_WEBINAR_DIR . 'includes/api/WebinarsController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/ParticipantesController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/ChatController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/AutomacoesController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/AnalyticsController.php';
        require_once WP_WEBINAR_DIR . 'includes/api/DashboardController.php';

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
    }
}
