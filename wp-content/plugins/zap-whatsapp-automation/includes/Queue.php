<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Queue {

    /**
     * Get next pending item from queue
     * @return object|null
     */
    public static function next() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zapwa_queue';
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table 
                WHERE status = 'pending' 
                AND run_at <= %s 
                ORDER BY run_at ASC 
                LIMIT 1",
                current_time('mysql')
            )
        );
    }

    /**
     * Increment attempt counter for an item
     * @param int $id
     */
    public static function attempt($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zapwa_queue';
        
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table SET attempts = attempts + 1 WHERE id = %d",
                $id
            )
        );
    }

    /**
     * Mark item as completed
     * @param int $id
     */
    public static function done($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zapwa_queue';
        
        $wpdb->update(
            $table,
            ['status' => 'completed'],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Mark item as failed
     * @param int $id
     */
    public static function fail($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zapwa_queue';
        
        $wpdb->update(
            $table,
            ['status' => 'failed'],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Add item to queue
     * @param array $payload
     */
    public static function add($payload) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'zapwa_queue';
        
        $delay = (int) ($payload['delay'] ?? 0);
        $run_at = date('Y-m-d H:i:s', time() + $delay);
        
        $wpdb->insert(
            $table,
            [
                'message_id' => absint($payload['message_id'] ?? 0),
                'user_id' => absint($payload['user_id'] ?? 0),
                'phone' => sanitize_text_field($payload['phone'] ?? ''),
                'event' => sanitize_text_field($payload['event'] ?? ''),
                'payload' => wp_json_encode($payload),
                'attempts' => 0,
                'status' => 'pending',
                'run_at' => $run_at,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('zapwa_process_queue')) {
            wp_schedule_single_event(time() + 5, 'zapwa_process_queue');
        }
    }

    /**
     * Process queue via Cron for backward compatibility
     */
    public static function process() {
        // Process queue via Cron for backward compatibility
        if (class_exists('\ZapWA\Cron')) {
            \ZapWA\Cron::process();
        }
    }
}
