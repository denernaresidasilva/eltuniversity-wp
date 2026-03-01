<?php
/**
 * Dispatcher Class
 * 
 * Coordinates event dispatching to multiple destinations
 * 
 * @package ZapTutorEvents
 * @since 1.0.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Dispatcher {

    /**
     * Dispatch event to all configured destinations
     * 
     * @param string $event_key Event identifier
     * @param int $user_id User ID
     * @param array $context Event context data
     * @return void
     */
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

        // 1. Trigger global WordPress action for other plugins (always runs first)
        do_action('zap_evento', $payload);
        self::debug("WordPress action 'zap_evento' triggered");

        try {
            // 2. Log to database
            if (class_exists(__NAMESPACE__ . '\\Logger')) {
                $result = Logger::log($event_key, $user_id, $context);
                if ($result === false) {
                    self::debug_error("Logger failed to insert event", [
                        'event_key' => $event_key,
                        'user_id'   => $user_id,
                    ]);
                } else {
                    self::debug("Event logged to database with ID: {$result}");
                }
            } else {
                self::debug_error("Logger class not found");
            }

            // 3. Send to webhook: always try immediate dispatch first, fallback to queue on failure
            if (class_exists(__NAMESPACE__ . '\\Webhook')) {
                $sent = Webhook::send($event_key, $user_id, $context);
                if ($sent) {
                    self::debug("Event sent to webhook successfully (immediate dispatch)");
                } else {
                    self::debug_error("Immediate webhook dispatch failed, falling back to queue");
                    // Fallback: enqueue for retry if Queue class is available
                    if (class_exists(__NAMESPACE__ . '\\Queue')) {
                        $queued = Queue::enqueue($event_key, $user_id, $context);
                        if ($queued) {
                            self::debug("Event queued for retry after immediate dispatch failure");
                        } else {
                            self::debug_error("Failed to queue event after dispatch failure");
                        }
                    }
                }
            } else {
                self::debug_error("Webhook class not found");
            }

        } catch (\Exception $e) {
            self::debug_error("Error dispatching event: " . $e->getMessage(), [
                'event_key' => $event_key,
                'user_id'   => $user_id,
                'trace'     => $e->getTraceAsString(),
            ]);
            
            // Re-lançar exceção crítica se for erro fatal
            if ($e instanceof \Error) {
                throw $e;
            }
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
     * Log debug error - SEMPRE loga erros críticos
     */
    private static function debug_error($message, $context = []) {
        // SEMPRE loga erros, independente do modo DEBUG
        $log_message = '[ZAP Events ERROR] ' . $message;
        if (!empty($context)) {
            $log_message .= ' | Context: ' . wp_json_encode($context);
        }
        error_log($log_message);
        
        // Se debug estiver ativo, loga também detalhes adicionais
        if (defined('ZAP_EVENTS_DEBUG') && ZAP_EVENTS_DEBUG) {
            if (!empty($context['trace'])) {
                error_log('[ZAP Events TRACE] ' . $context['trace']);
            }
        }
    }
}
