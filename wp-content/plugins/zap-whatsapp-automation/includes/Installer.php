<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {

    public static function activate() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        // === LOGS ===
        $logs_table = $wpdb->prefix . 'zap_wa_logs';

        $sql_logs = "CREATE TABLE $logs_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            phone VARCHAR(30) NULL,
            event VARCHAR(100) NULL,
            message LONGTEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pendente',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY event (event),
            KEY status (status)
        ) $charset;";

        // === FILA ===
        $queue_table = $wpdb->prefix . 'zapwa_queue';

        $sql_queue = "CREATE TABLE $queue_table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT UNSIGNED NOT NULL,
            user_id BIGINT UNSIGNED NOT NULL,
            phone VARCHAR(30) NOT NULL,
            event VARCHAR(100) NOT NULL,
            payload LONGTEXT NOT NULL,
            attempts INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            run_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY run_at (run_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_logs);
        dbDelta($sql_queue);
        
        // Criar tabela de eventos processados
        if (class_exists('\ZapWA\EventLogReader')) {
            \ZapWA\EventLogReader::create_table();
        }

        // Set default options with standardized names
        if (get_option('zapwa_evolution_url') === false) {
            add_option('zapwa_evolution_url', '');
        }

        if (get_option('zapwa_evolution_token') === false) {
            add_option('zapwa_evolution_token', '');
        }

        if (get_option('zapwa_connection_type') === false) {
            add_option('zapwa_connection_type', 'evolution');
        }

        if (get_option('zapwa_evolution_instance') === false) {
            add_option('zapwa_evolution_instance', '');
        }

        if (get_option('zapwa_official_phone_id') === false) {
            add_option('zapwa_official_phone_id', '');
        }

        if (get_option('zapwa_official_access_token') === false) {
            add_option('zapwa_official_access_token', '');
        }

        // Register CPT and flush rewrite rules
        $cpt_file = plugin_dir_path(__FILE__) . 'PostTypes/Message.php';
        if (file_exists($cpt_file)) {
            require_once $cpt_file;
            if (class_exists('\ZapWA\PostTypes\Message')) {
                \ZapWA\PostTypes\Message::register();
            }
        }
        flush_rewrite_rules();
    }

    public static function deactivate() {
        // Clear scheduled cron hooks
        wp_clear_scheduled_hook('zapwa_process_queue');
        wp_clear_scheduled_hook('zapwa_process_event_logs');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
