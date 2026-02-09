<?php
if (!defined('ABSPATH')) exit;

class ZAP_Events_Logger {

    public static function log($event_key, $user_id, $context = []) {

        global $wpdb;

        $table = $wpdb->prefix . 'zap_event_logs';

        $wpdb->insert(
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
    }
}
