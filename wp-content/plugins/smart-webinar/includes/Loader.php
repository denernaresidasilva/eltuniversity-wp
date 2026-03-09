<?php
namespace SmartWebinar;

if ( ! defined( 'ABSPATH' ) ) exit;

class Loader {

    public static function init(): void {
        // Core
        require_once SMART_WEBINAR_PATH . 'core/webinar-engine.php';
        require_once SMART_WEBINAR_PATH . 'core/session-engine.php';
        require_once SMART_WEBINAR_PATH . 'core/automation-engine.php';

        // Tracking
        require_once SMART_WEBINAR_PATH . 'tracking/tracker.php';
        require_once SMART_WEBINAR_PATH . 'tracking/tracking-api.php';

        // Events
        require_once SMART_WEBINAR_PATH . 'events/event-dispatcher.php';

        // Integrations
        require_once SMART_WEBINAR_PATH . 'integrations/zap-events.php';
        require_once SMART_WEBINAR_PATH . 'integrations/youtube-api.php';

        // Public
        require_once SMART_WEBINAR_PATH . 'public/chat.php';
        require_once SMART_WEBINAR_PATH . 'public/player.php';

        // Admin
        if ( is_admin() ) {
            require_once SMART_WEBINAR_PATH . 'admin/dashboard.php';
            require_once SMART_WEBINAR_PATH . 'admin/webinar-editor.php';
        }

        // Boot all modules
        \SmartWebinar\Core\WebinarEngine::init();
        \SmartWebinar\Core\SessionEngine::init();
        \SmartWebinar\Core\AutomationEngine::init();
        \SmartWebinar\Tracking\Tracker::init();
        \SmartWebinar\Tracking\TrackingAPI::init();
        \SmartWebinar\Events\EventDispatcher::init();
        \SmartWebinar\Integrations\ZapEvents::init();
        \SmartWebinar\Integrations\YouTubeAPI::init();
        \SmartWebinar\Frontend\Player::init();
        \SmartWebinar\Frontend\Chat::init();

        if ( is_admin() ) {
            \SmartWebinar\Admin\Dashboard::init();
            \SmartWebinar\Admin\WebinarEditor::init();
        }
    }
}
