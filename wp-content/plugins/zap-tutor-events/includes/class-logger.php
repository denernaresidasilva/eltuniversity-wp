<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) exit;

class Logger {

    public static function log($event_key, $user_id, $context = []) {

        global $wpdb;

        $table = $wpdb->prefix . 'zap_event_logs';

        // Verificar se a tabela existe antes de tentar inserir
        $table_exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) === $table;
        
        if (!$table_exists) {
            error_log('ZAP Events Logger: Database table does not exist');
            return false;
        }

        $result = $wpdb->insert(
            $table,
            [
                'event_key'  => sanitize_text_field($event_key),
                'user_id'    => absint($user_id),
                'context'    => wp_json_encode($context),
                'created_at' => current_time('mysql'),
            ],
            [
                '%s',
                '%d',
                '%s',
                '%s',
            ]
        );

        if ($result === false) {
            error_log('ZAP Events Logger: Erro ao inserir evento - ' . $wpdb->last_error);
            return false;
        }

        return true;
    }
}
