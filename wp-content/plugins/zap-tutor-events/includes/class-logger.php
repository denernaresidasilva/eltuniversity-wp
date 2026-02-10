<?php
/**
 * Logger Class
 * 
 * Handles event logging to database and cleanup
 * 
 * @package ZapTutorEvents
 * @since 1.0.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) exit;

class Logger {

    /**
     * Log an event to database
     * 
     * @param string $event_key Event identifier
     * @param int $user_id User ID
     * @param array $context Event context data
     * @return int|false Insert ID on success, false on failure
     */
    public static function log($event_key, $user_id, $context = []) {

        // Check if logging is enabled
        if (!get_option('zap_events_log_enabled', true)) {
            return false;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'zap_event_logs';

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

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Clean up old logs based on retention settings
     * 
     * @return int Number of deleted rows
     */
    public static function cleanup_old_logs() {
        
        $retention_days = get_option('zap_events_log_retention_days', 30);
        
        // If set to 0 (infinite), don't delete anything
        if ($retention_days === 0 || $retention_days === '0') {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';

        $date_limit = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            $date_limit
        ));

        return $deleted ?: 0;
    }

    /**
     * Get total count of logs
     * 
     * @param array $filters Optional filters (event_key, user_id, date_from, date_to)
     * @return int Total count
     */
    public static function get_count($filters = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';

        $where = ['1=1'];
        $values = [];

        if (!empty($filters['event_key'])) {
            $where[] = 'event_key = %s';
            $values[] = $filters['event_key'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = absint($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $filters['date_to'] . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where);
        
        if (!empty($values)) {
            $query = $wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where_clause}", $values);
        } else {
            $query = "SELECT COUNT(*) FROM {$table} WHERE {$where_clause}";
        }

        return (int) $wpdb->get_var($query);
    }

    /**
     * Get logs with filters and pagination
     * 
     * @param array $filters Filters (event_key, user_id, date_from, date_to)
     * @param int $per_page Results per page
     * @param int $page Current page
     * @return array Logs
     */
    public static function get_logs($filters = [], $per_page = 50, $page = 1) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';

        $where = ['1=1'];
        $values = [];

        if (!empty($filters['event_key'])) {
            $where[] = 'event_key = %s';
            $values[] = $filters['event_key'];
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = absint($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'created_at >= %s';
            $values[] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'created_at <= %s';
            $values[] = $filters['date_to'] . ' 23:59:59';
        }

        $where_clause = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $values[] = $per_page;
        $values[] = $offset;

        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $values
        );

        return $wpdb->get_results($query);
    }
}
