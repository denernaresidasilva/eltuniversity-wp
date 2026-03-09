<?php
namespace WebinarPlataforma\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Routes {

    const NAMESPACE = 'webinar/v1';

    public static function register(): void {
        WebinarsController::register_routes();
        ParticipantesController::register_routes();
        ChatController::register_routes();
        AutomacoesController::register_routes();
        AnalyticsController::register_routes();
        DashboardController::register_routes();
    }

    public static function auth_callback(): bool {
        return current_user_can( 'manage_options' );
    }

    public static function public_auth_callback(): bool {
        return true;
    }
}
