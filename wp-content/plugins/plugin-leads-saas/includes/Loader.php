<?php
namespace LeadsSaaS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Loader {

    public static function init() {

        // Core files
        require_once LEADS_SAAS_DIR . 'includes/models/Lista.php';
        require_once LEADS_SAAS_DIR . 'includes/models/Lead.php';
        require_once LEADS_SAAS_DIR . 'includes/models/Tag.php';
        require_once LEADS_SAAS_DIR . 'includes/models/Automacao.php';
        require_once LEADS_SAAS_DIR . 'includes/services/LeadService.php';
        require_once LEADS_SAAS_DIR . 'includes/services/AutomacaoService.php';
        require_once LEADS_SAAS_DIR . 'includes/services/WebhookService.php';
        require_once LEADS_SAAS_DIR . 'includes/api/Routes.php';
        require_once LEADS_SAAS_DIR . 'includes/api/ListasController.php';
        require_once LEADS_SAAS_DIR . 'includes/api/LeadsController.php';
        require_once LEADS_SAAS_DIR . 'includes/api/TagsController.php';
        require_once LEADS_SAAS_DIR . 'includes/api/WebhookController.php';
        require_once LEADS_SAAS_DIR . 'includes/api/AutomacoesController.php';
        require_once LEADS_SAAS_DIR . 'includes/api/DashboardController.php';

        // Admin
        require_once LEADS_SAAS_DIR . 'admin/Admin.php';

        // Public
        require_once LEADS_SAAS_DIR . 'public/shortcodes/FormShortcode.php';

        // Maybe run DB upgrade
        add_action( 'plugins_loaded', [ Installer::class, 'maybe_upgrade' ] );

        // Register REST routes
        add_action( 'rest_api_init', [ Api\Routes::class, 'register' ] );

        // Boot admin
        if ( is_admin() ) {
            Admin\Admin::init();
        }

        // Boot public
        add_action( 'init', [ Public_\FormShortcode::class, 'init' ] );

        // Elementor integration
        add_action( 'elementor/widgets/register', function( $manager ) {
            if ( ! defined( 'ELEMENTOR_VERSION' ) ) {
                return;
            }
            require_once LEADS_SAAS_DIR . 'integrations/elementor/LeadFormWidget.php';
            $manager->register( new Integrations\Elementor\LeadFormWidget() );
        } );
    }
}
