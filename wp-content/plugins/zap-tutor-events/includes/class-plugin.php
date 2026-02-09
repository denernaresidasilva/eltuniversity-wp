<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin {

    /**
     * Bootstrap do plugin
     */
    public static function init() {

        /*
         * ===============================
         * CORE
         * ===============================
         */

        // Logger (responsável pelo registro de eventos)
        $logger = ZAP_EVENTS_PATH . 'includes/class-logger.php';
        if (file_exists($logger)) {
            require_once $logger;
        }

        // Dispatcher (responsável por emitir o evento global)
        $dispatcher = ZAP_EVENTS_PATH . 'includes/class-dispatcher.php';
        if (file_exists($dispatcher)) {
            require_once $dispatcher;
        }

        // Events (onde estão os hooks do Tutor LMS)
        $events = ZAP_EVENTS_PATH . 'includes/class-events.php';
        if (file_exists($events)) {
            require_once $events;
        }

        // 🔥 GARANTIA ABSOLUTA DE INICIALIZAÇÃO
        if (class_exists(__NAMESPACE__ . '\\Events')) {
            Events::init();
        }

        /*
         * ===============================
         * ADMIN
         * ===============================
         */
        if (is_admin()) {

            $admin_file = ZAP_EVENTS_PATH . 'includes/class-admin.php';

            if (file_exists($admin_file)) {
                require_once $admin_file;

                if (class_exists(__NAMESPACE__ . '\\Admin')) {
                    Admin::init();
                }
            }
        }
    }
}
