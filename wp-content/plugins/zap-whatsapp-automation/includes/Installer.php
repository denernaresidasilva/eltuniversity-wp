<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {

    const SCHEMA_VERSION = '1.1.0';

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

        // Event tracker table
        if (class_exists('\ZapWA\Event_Tracker')) {
            \ZapWA\Event_Tracker::create_table();
        }

        // Tag manager tables
        if (class_exists('\ZapWA\Tag_Manager')) {
            \ZapWA\Tag_Manager::create_tables();
        }

        // Link tracker table
        if (class_exists('\ZapWA\Link_Tracker')) {
            \ZapWA\Link_Tracker::create_table();
        }

        // AI Agent tables
        if (class_exists('\ZapWA\AI_Agent')) {
            \ZapWA\AI_Agent::create_tables();
        }

        // Default AI settings
        if (get_option('zapwa_openai_api_key') === false) {
            add_option('zapwa_openai_api_key', '');
        }
        if (get_option('zapwa_gemini_api_key') === false) {
            add_option('zapwa_gemini_api_key', '');
        }
        if (get_option('zapwa_elevenlabs_api_key') === false) {
            add_option('zapwa_elevenlabs_api_key', '');
        }
        if (get_option('zapwa_elevenlabs_voice_id') === false) {
            add_option('zapwa_elevenlabs_voice_id', '21m00Tcm4TlvDq8ikWAM');
        }
        if (get_option('zapwa_instagram_access_token') === false) {
            add_option('zapwa_instagram_access_token', '');
        }
        if (get_option('zapwa_instagram_page_id') === false) {
            add_option('zapwa_instagram_page_id', '');
        }
        if (get_option('zapwa_instagram_verify_token') === false) {
            add_option('zapwa_instagram_verify_token', wp_generate_password(32, false));
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

        if (get_option('zapwa_logging_enabled') === false) {
            add_option('zapwa_logging_enabled', true);
        }

        update_option('zapwa_schema_version', self::SCHEMA_VERSION, false);

        // Register CPT and flush rewrite rules
        $cpt_file = plugin_dir_path(__FILE__) . 'PostTypes/Message.php';
        if (file_exists($cpt_file)) {
            require_once $cpt_file;
            if (class_exists('\ZapWA\PostTypes\Message')) {
                \ZapWA\PostTypes\Message::register();
            }
        }

        // Register automation_flow CPT and create flow_runs table
        $flow_cpt_file = plugin_dir_path(__FILE__) . 'flows/class-flow-cpt.php';
        $flow_db_file  = plugin_dir_path(__FILE__) . 'flows/class-flow-db.php';
        if (file_exists($flow_cpt_file)) {
            require_once $flow_cpt_file;
        }
        if (file_exists($flow_db_file)) {
            require_once $flow_db_file;
            if (class_exists('\ZapWA\Flows\Flow_DB')) {
                \ZapWA\Flows\Flow_DB::create_table();
                \ZapWA\Flows\Flow_DB::create_stats_table();
            }
        }
        if (class_exists('\ZapWA\Flows\Flow_CPT')) {
            \ZapWA\Flows\Flow_CPT::register();
        }

        flush_rewrite_rules();
    }

    public static function deactivate() {
        // Clear scheduled cron hooks
        wp_clear_scheduled_hook('zapwa_process_queue');
        wp_clear_scheduled_hook('zapwa_process_event_logs');
        wp_clear_scheduled_hook('zapwa_process_flow');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Garante que todas as tabelas necessárias existam mesmo quando
     * o plugin é atualizado sem desativar/ativar.
     */
    public static function maybe_upgrade_schema() {
        $current = get_option('zapwa_schema_version', '');

        if ($current === self::SCHEMA_VERSION) {
            return;
        }

        self::activate();
        update_option('zapwa_schema_version', self::SCHEMA_VERSION, false);
    }
}
