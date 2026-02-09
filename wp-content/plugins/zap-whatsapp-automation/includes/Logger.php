<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Logger {

    /**
     * LOG TÉCNICO (DEBUG EM ARQUIVO)
     */
    public static function debug($message, $context = []) {

        $dir = WP_CONTENT_DIR . '/zapwa-logs';

        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }

        $file = $dir . '/debug-' . date('Y-m-d') . '.log';

        $entry = '[' . date('H:i:s') . '] ' . $message;

        if (!empty($context)) {
            $entry .= ' | ' . wp_json_encode($context);
        }

        $entry .= PHP_EOL;

        file_put_contents($file, $entry, FILE_APPEND);
    }

    /**
     * LOG DE ENVIO WHATSAPP (BANCO)
     */
    public static function log_send(
        $user_id,
        $event,
        $phone,
        $message,
        $status,
        $response = null
    ) {

        global $wpdb;

        $table = $wpdb->prefix . 'zap_wa_logs';

        $wpdb->insert(
            $table,
            [
                'user_id' => absint($user_id),
                'phone'   => sanitize_text_field($phone),
                'event'   => sanitize_text_field($event),
                'message' => wp_kses_post($message),
                'status'  => sanitize_text_field($status),
                'created_at' => current_time('mysql'),
            ]
        );
    }
}
