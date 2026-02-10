public static function dispatch($event_key, $user_id, $context = []) {

        if (empty($event_key) || empty($user_id)) {
            self::debug("Dispatch skipped: empty event_key or user_id", [
                'event_key' => $event_key,
                'user_id'   => $user_id,
            ]);
            return;
        }

        if (!is_array($context)) {
            $context = [];
        }

        $payload = [
            'event'     => sanitize_text_field($event_key),
            'user_id'   => absint($user_id),
            'context'   => $context,
            'timestamp' => time(),
        ];

        self::debug("Dispatching event: {$event_key}", $payload);

        try {
            // 1. Log to database
            if (class_exists(__NAMESPACE__ . '\\Logger')) {
                Logger::log($event_key, $user_id, $context);
                self::debug("Event logged to database");
            }

            // 2. Send to webhook (async via WP Cron if Queue enabled)
            if (class_exists(__NAMESPACE__ . '\\Webhook')) {
                $use_queue = get_option('zap_events_use_queue', false);
                
                if ($use_queue && class_exists(__NAMESPACE__ . '\\Queue')) {
                    Queue::enqueue($event_key, $user_id, $context);
                    self::debug("Event queued for webhook processing");
                } else {
                    Webhook::send($event_key, $user_id, $context);
                    self::debug("Event sent to webhook");
                }
            }

            // 3. Trigger global WordPress action for other plugins
            do_action('zap_evento', $payload);
            self::debug("WordPress action 'zap_evento' triggered");

        } catch (\Exception $e) {
            self::debug_error("Error dispatching event: " . $e->getMessage(), [
                'event_key' => $event_key,
                'user_id'   => $user_id,
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Log debug message
     */
    private static function debug($message, $context = []) {
        if (!defined('ZAP_EVENTS_DEBUG') || !ZAP_EVENTS_DEBUG) {
            return;
        }

        $log_message = '[ZAP Events Debug] ' . $message;
        if (!empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }
        error_log($log_message);
    }

    /**
     * Log debug error
     */
    private static function debug_error($message, $context = []) {
        if (!defined('ZAP_EVENTS_DEBUG') || !ZAP_EVENTS_DEBUG) {
            return;
        }

        $log_message = '[ZAP Events ERROR] ' . $message;
        if (!empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }
        error_log($log_message);
    }