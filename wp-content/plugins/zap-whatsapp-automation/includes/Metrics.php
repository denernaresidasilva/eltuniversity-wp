<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Metrics {

    public static function summary() {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_wa_logs';

        return [
            'total'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table"),
            'sent'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='enviado'"),
            'error'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='erro'"),
            'pending'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status='pendente'"),
        ];
    }

    public static function events() {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_wa_logs';

        return $wpdb->get_results("
            SELECT event, COUNT(*) as total
            FROM $table
            GROUP BY event
            ORDER BY total DESC
        ");
    }

    public static function last_logs($limit = 20) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_wa_logs';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
                $limit
            )
        );
    }
}
