<?php
namespace ZapWA\Flows;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages the wp_zapwa_flow_runs database table.
 */
class Flow_DB {

    /**
     * Create the flow_runs table using dbDelta.
     */
    public static function create_table() {
        global $wpdb;

        $table   = $wpdb->prefix . 'zapwa_flow_runs';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            flow_id BIGINT UNSIGNED NOT NULL,
            contact_id BIGINT UNSIGNED NOT NULL,
            current_node_id VARCHAR(100) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'running',
            next_execution DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY flow_id (flow_id),
            KEY contact_id (contact_id),
            KEY status (status),
            KEY next_execution (next_execution)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Insert a new flow run record.
     *
     * @param int    $flow_id
     * @param int    $contact_id
     * @param string $start_node_id
     * @return int|false Inserted row ID or false on failure.
     */
    public static function create_run($flow_id, $contact_id, $start_node_id) {
        global $wpdb;

        $table  = $wpdb->prefix . 'zapwa_flow_runs';
        $result = $wpdb->insert(
            $table,
            [
                'flow_id'        => absint($flow_id),
                'contact_id'     => absint($contact_id),
                'current_node_id' => sanitize_text_field($start_node_id),
                'status'          => 'running',
                'next_execution'  => current_time('mysql'),
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s']
        );

        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Fetch runs due for processing.
     *
     * @param int $limit
     * @return array
     */
    public static function get_due_runs($limit = 20) {
        global $wpdb;

        $table = $wpdb->prefix . 'zapwa_flow_runs';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE status = 'running'
                   AND next_execution <= %s
                 ORDER BY next_execution ASC
                 LIMIT %d",
                current_time('mysql'),
                absint($limit)
            )
        );
    }

    /**
     * Update a run record.
     *
     * @param int    $run_id
     * @param array  $data Associative array of columns to update.
     */
    public static function update_run($run_id, array $data) {
        global $wpdb;

        $table = $wpdb->prefix . 'zapwa_flow_runs';
        $data['updated_at'] = current_time('mysql');

        $formats = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['flow_id', 'contact_id'], true)) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }

        $wpdb->update($table, $data, ['id' => absint($run_id)], $formats, ['%d']);
    }

    /**
     * Get a single run by ID.
     *
     * @param int $run_id
     * @return object|null
     */
    public static function get_run($run_id) {
        global $wpdb;

        $table = $wpdb->prefix . 'zapwa_flow_runs';

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", absint($run_id))
        );
    }
}
