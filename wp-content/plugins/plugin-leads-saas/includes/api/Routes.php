<?php
namespace LeadsSaaS\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Routes {

    const NAMESPACE = 'leads/v1';

    public static function register(): void {
        ListasController::register_routes();
        LeadsController::register_routes();
        TagsController::register_routes();
        WebhookController::register_routes();
        AutomacoesController::register_routes();
        DashboardController::register_routes();
    }

    public static function auth_callback(): bool {
        return current_user_can( 'manage_options' );
    }
}
