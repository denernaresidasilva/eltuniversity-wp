<?php
/**
 * Queue Class
 * 
 * Background job processing for high-volume events
 * Uses WordPress Cron to process events asynchronously
 * 
 * @package ZapTutorEvents
 * @since 1.1.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Queue {

    /**
     * Queue option key
     */
    const QUEUE_OPTION = 'zap_events_queue';

    /**
     * Max items to process per run
     */
    const BATCH_SIZE = 10;

    /**
     * Initialize queue system
     */
    public static function init() {
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('zap_events_process_queue')) {
            wp_schedule_event(time(), 'every_minute', 'zap_events_process_queue');
        }

        add_action('zap_events_process_queue', [self::class, 'process_queue']);
        add_filter('cron_schedules', [self::class, 'add_cron_interval']);
    }

    /**
     * Add custom cron interval
     * 
     * @param array $schedules Existing schedules
     * @return array Modified schedules
     */
    public static function add_cron_interval($schedules) {
        $schedules['every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute'),
        ];
        return $schedules;
    }

    /**
     * Enqueue an event for processing
     * 
     * @param string $event_key Event identifier
     * @param int $user_id User ID
     * @param array $context Event context
     * @param int $priority Priority (1-10, lower is higher priority)
     * @return bool Success status
     */
    public static function enqueue($event_key, $user_id, $context = [], $priority = 5) {
        
        $queue = get_option(self::QUEUE_OPTION, []);

        $item = [
            'event_key' => $event_key,
            'user_id'   => $user_id,
            'context'   => $context,
            'priority'  => min(10, max(1, $priority)),
            'queued_at' => time(),
        ];

        $queue[] = $item;

        // Sort by priority (lower number = higher priority)
        usort($queue, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return update_option(self::QUEUE_OPTION, $queue);
    }

    /**
     * Process queued events
     */
    public static function process_queue() {
        
        $queue = get_option(self::QUEUE_OPTION, []);

        if (empty($queue)) {
            return;
        }

        // Take batch of items
        $batch = array_slice($queue, 0, self::BATCH_SIZE);
        $remaining = array_slice($queue, self::BATCH_SIZE);

        foreach ($batch as $item) {
            try {
                // Send to webhook
                if (class_exists(__NAMESPACE__ . '\\Webhook')) {
                    Webhook::send(
                        $item['event_key'],
                        $item['user_id'],
                        $item['context']
                    );
                }
            } catch (\Exception $e) {
                // Log error but continue processing
                error_log('[ZAP Events Queue] Error processing item: ' . $e->getMessage());
            }
        }

        // Update queue with remaining items
        update_option(self::QUEUE_OPTION, $remaining);
    }

    /**
     * Get queue status
     * 
     * @return array Queue information
     */
    public static function get_status() {
        $queue = get_option(self::QUEUE_OPTION, []);
        
        return [
            'total_items'  => count($queue),
            'next_run'     => wp_next_scheduled('zap_events_process_queue'),
            'is_scheduled' => (bool) wp_next_scheduled('zap_events_process_queue'),
        ];
    }

    /**
     * Clear the queue
     * 
     * @return bool Success status
     */
    public static function clear_queue() {
        return delete_option(self::QUEUE_OPTION);
    }
}
