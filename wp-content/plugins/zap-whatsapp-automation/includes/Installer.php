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
    }

    public static function deactivate() {
        // Não apagar dados
    }
}
