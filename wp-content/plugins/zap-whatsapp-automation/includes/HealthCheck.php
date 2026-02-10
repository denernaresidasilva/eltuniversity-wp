<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class HealthCheck {

    public static function init() {
        add_action('rest_api_init', [__CLASS__, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('zapwa/v1', '/health', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'check_health'],
            'permission_callback' => '__return_true'
        ]);
    }

    public static function check_health() {
        global $wpdb;
        
        $status = [
            'status' => 'ok',
            'timestamp' => current_time('mysql'),
            'checks' => []
        ];

        // 1. Verificar se tabelas existem
        $tables_exist = true;
        $tables = ['zap_wa_logs', 'zapwa_queue'];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
            
            $status['checks']['table_' . $table] = $exists;
            
            if (!$exists) {
                $tables_exist = false;
            }
        }

        // 2. Verificar cron ativo
        $cron_scheduled = wp_next_scheduled('zapwa_process_queue');
        $status['checks']['cron_scheduled'] = (bool) $cron_scheduled;
        
        if ($cron_scheduled) {
            $status['checks']['next_cron_run'] = date('Y-m-d H:i:s', $cron_scheduled);
        }

        // 3. Verificar fila pendente
        $pending_count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}zapwa_queue WHERE status = 'pending'"
        );
        $status['checks']['pending_queue'] = (int) $pending_count;

        // 4. Verificar Evolution API configurada
        $api_url = get_option('zapwa_api_url');
        $api_token = get_option('zapwa_api_token');
        $status['checks']['api_configured'] = !empty($api_url) && !empty($api_token);

        // 5. Verificar Listener ativo
        $status['checks']['listener_registered'] = has_action('zap_evento');

        // 6. Status geral
        if (!$tables_exist || !$cron_scheduled) {
            $status['status'] = 'error';
        } elseif ($pending_count > 100) {
            $status['status'] = 'warning';
            $status['message'] = 'Fila com muitos itens pendentes';
        }

        return $status;
    }
}
