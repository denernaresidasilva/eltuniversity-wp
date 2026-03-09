<?php
/**
 * Bootstraps all plugin modules.
 *
 * @package AISalesEngine
 */

namespace AISalesEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Loader {

    /**
     * Load the plugin text domain for translations.
     */
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            'ai-sales-engine',
            false,
            dirname( plugin_basename( AI_SALES_ENGINE_FILE ) ) . '/languages'
        );
    }

    /**
     * Load all files and boot each module.
     */
    public static function init(): void {
        add_action( 'init', [ self::class, 'load_textdomain' ] );
        // Core
        require_once AI_SALES_ENGINE_PATH . 'core/Database.php';
        require_once AI_SALES_ENGINE_PATH . 'core/EventDispatcher.php';
        require_once AI_SALES_ENGINE_PATH . 'core/Queue.php';
        require_once AI_SALES_ENGINE_PATH . 'core/AutomationEngine.php';
        require_once AI_SALES_ENGINE_PATH . 'core/AgentEngine.php';
        require_once AI_SALES_ENGINE_PATH . 'core/Tracker.php';
        require_once AI_SALES_ENGINE_PATH . 'core/Api.php';

        // Modules
        require_once AI_SALES_ENGINE_PATH . 'modules/leads/LeadManager.php';
        require_once AI_SALES_ENGINE_PATH . 'modules/lists/ListManager.php';
        require_once AI_SALES_ENGINE_PATH . 'modules/tags/TagManager.php';
        require_once AI_SALES_ENGINE_PATH . 'modules/pipelines/PipelineManager.php';
        require_once AI_SALES_ENGINE_PATH . 'modules/analytics/Analytics.php';
        require_once AI_SALES_ENGINE_PATH . 'modules/scoring/LeadScoring.php';

        // Integrations
        require_once AI_SALES_ENGINE_PATH . 'integrations/instagram/Instagram.php';
        require_once AI_SALES_ENGINE_PATH . 'integrations/whatsapp/WhatsApp.php';
        require_once AI_SALES_ENGINE_PATH . 'integrations/webhook/WebhookHandler.php';

        // Public
        require_once AI_SALES_ENGINE_PATH . 'public/PublicTracker.php';

        // Admin
        if ( is_admin() ) {
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Dashboard.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Leads.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Lists.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Automations.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Agents.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Pipelines.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Analytics.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/pages/Settings.php';
            require_once AI_SALES_ENGINE_PATH . 'admin/Admin.php';
        }

        // Boot core
        \AISalesEngine\Core\Database::init();
        \AISalesEngine\Core\EventDispatcher::init();
        \AISalesEngine\Core\Queue::init();
        \AISalesEngine\Core\AutomationEngine::init();
        \AISalesEngine\Core\AgentEngine::init();
        \AISalesEngine\Core\Tracker::init();
        \AISalesEngine\Core\Api::init();

        // Boot modules
        \AISalesEngine\Modules\Leads\LeadManager::init();
        \AISalesEngine\Modules\Lists\ListManager::init();
        \AISalesEngine\Modules\Tags\TagManager::init();
        \AISalesEngine\Modules\Pipelines\PipelineManager::init();
        \AISalesEngine\Modules\Analytics\Analytics::init();
        \AISalesEngine\Modules\Scoring\LeadScoring::init();

        // Boot integrations
        \AISalesEngine\Integrations\Instagram\Instagram::init();
        \AISalesEngine\Integrations\WhatsApp\WhatsApp::init();
        \AISalesEngine\Integrations\Webhook\WebhookHandler::init();

        // Boot public
        \AISalesEngine\PublicTracker::init();

        // Boot admin
        if ( is_admin() ) {
            \AISalesEngine\Admin\Admin::init();
        }
    }
}
