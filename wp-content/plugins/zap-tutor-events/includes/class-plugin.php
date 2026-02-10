<?php
/**
 * Main Plugin Class
 * 
 * Bootstrap and initialization for ZAP Tutor Events plugin
 * 
 * @package ZapTutorEvents
 * @since 1.0.0
 */

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
         * CORE CLASSES
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

        // Webhook (envia eventos para URLs externas)
        $webhook = ZAP_EVENTS_PATH . 'includes/class-webhook.php';
        if (file_exists($webhook)) {
            require_once $webhook;
        }

        // Queue (processamento em background)
        $queue = ZAP_EVENTS_PATH . 'includes/class-queue.php';
        if (file_exists($queue)) {
            require_once $queue;
            if (class_exists(__NAMESPACE__ . '\\Queue')) {
                Queue::init();
            }
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

        // REST API
        $api = ZAP_EVENTS_PATH . 'includes/class-api.php';
        if (file_exists($api)) {
            require_once $api;
            if (class_exists(__NAMESPACE__ . '\\API')) {
                API::init();
            }
        }

        /*
         * ===============================
         * ADMIN CLASSES
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

            // Settings page
            $settings_file = ZAP_EVENTS_PATH . 'includes/class-settings.php';
            if (file_exists($settings_file)) {
                require_once $settings_file;
                if (class_exists(__NAMESPACE__ . '\\Settings')) {
                    Settings::init();
                }
            }

            // Dashboard
            $dashboard_file = ZAP_EVENTS_PATH . 'includes/class-dashboard.php';
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
                if (class_exists(__NAMESPACE__ . '\\Dashboard')) {
                    Dashboard::init();
                }
            }
        }

        /*
         * ===============================
         * CRON JOBS
         * ===============================
         */
        self::setup_cron();
    }

    /**
     * Setup cron jobs for automatic cleanup
     */
    private static function setup_cron() {
        
        // Schedule daily log cleanup if not already scheduled
        if (!wp_next_scheduled('zap_events_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'zap_events_daily_cleanup');
        }

        add_action('zap_events_daily_cleanup', [self::class, 'run_daily_cleanup']);
    }

    /**
     * Run daily cleanup tasks
     */
    public static function run_daily_cleanup() {
        
        // Clean old event logs
        if (class_exists(__NAMESPACE__ . '\\Logger')) {
            Logger::cleanup_old_logs();
        }

        // Clean old webhook logs (if table exists)
        $retention_days = get_option('zap_events_log_retention_days', 30);
        
        if ($retention_days > 0) {
            global $wpdb;
            $webhook_table = $wpdb->prefix . 'zap_webhook_logs';
            
            if ($wpdb->get_var("SHOW TABLES LIKE '{$webhook_table}'") == $webhook_table) {
                $date_limit = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$webhook_table} WHERE created_at < %s",
                    $date_limit
                ));
            }
        }
    }
}
