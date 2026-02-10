<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Loader {

    public static function init() {

        self::load_post_types();
        self::load_core();

        add_action('init', [self::class, 'register_hooks']);

        if (is_admin()) {
            self::load_admin();
        }
    }

    public static function register_hooks() {

        if (class_exists('\ZapWA\PostTypes\Message')) {
            \ZapWA\PostTypes\Message::register();
        }

        if (class_exists('\ZapWA\Helpers')) {
            \ZapWA\Helpers::init();
        }

        if (class_exists('\ZapWA\Listener')) {
            \ZapWA\Listener::init();
        }

        if (class_exists('\ZapWA\Queue')) {
            \ZapWA\Queue::init();
        }

        if (class_exists('\ZapWA\Cron')) {
            \ZapWA\Cron::init();
        }

        if (class_exists('\ZapWA\HealthCheck')) {
            \ZapWA\HealthCheck::init();
        }
        
        // Processar eventos da tabela de logs a cada 5 minutos
        if (class_exists('\ZapWA\EventLogReader')) {
            add_action('init', function() {
                if (!wp_next_scheduled('zapwa_process_event_logs')) {
                    wp_schedule_event(time(), 'five_minutes', 'zapwa_process_event_logs');
                }
            });
            
            add_action('zapwa_process_event_logs', ['\ZapWA\EventLogReader', 'process_pending_events']);
        }
    }

    private static function load_post_types() {

        $file = plugin_dir_path(__FILE__) . 'PostTypes/Message.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }

    private static function load_core() {

        $core_files = [
            'Helpers.php',
            'Variables.php',
            'Logger.php',
            'Queue.php',
            'AntiSpam.php',
            'Broadcast.php',
            'Listener.php',
            'EventLogReader.php', // ✅ LEITOR DE EVENTOS
            'ConnectionManager.php',
            'EvolutionAPI.php',
            'QRCodeGenerator.php', // ✅ QR CODE GENERATOR
            'Cron.php',
            'Metrics.php', // ✅ MÉTRICAS
            'HealthCheck.php', // ✅ HEALTH CHECK
        ];

        foreach ($core_files as $file) {
            $path = plugin_dir_path(__FILE__) . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    private static function load_admin() {

        $admin_files = [
            'admin/AdminMenu.php',
            'admin/actions.php',
            'admin/ajax.php',
            'admin/Metaboxes/MessageSettings.php',
        ];

        foreach ($admin_files as $file) {
            $path = plugin_dir_path(__FILE__) . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }

        if (class_exists('\ZapWA\Admin\AdminMenu')) {
            \ZapWA\Admin\AdminMenu::init();
        }

        if (class_exists('\ZapWA\Admin\Actions')) {
            \ZapWA\Admin\Actions::init();
        }

        if (class_exists('\ZapWA\Admin\Ajax')) {
            \ZapWA\Admin\Ajax::init();
        }

        if (class_exists('\ZapWA\Admin\Metaboxes\MessageSettings')) {
            \ZapWA\Admin\Metaboxes\MessageSettings::init();
        }
    }
}
