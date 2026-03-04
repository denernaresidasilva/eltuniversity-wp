<?php
/**
 * Webhook Handler Class
 * 
 * Sends events to external systems via HTTP POST
 * Supports retry logic, custom headers, and logging
 * 
 * @package ZapTutorEvents
 * @since 1.1.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Webhook {

    /**
     * Max retry attempts on failure
     */
    const MAX_RETRIES = 3;

    /**
     * Default timeout in seconds
     */
    const DEFAULT_TIMEOUT = 10;

    /**
     * Send event to webhook URL(s).
     *
     * Supports:
     *  - Legacy single webhook via option `zap_events_webhook_url`
     *  - New multi-webhook list via `zap_events_webhooks_list` (WebhooksPage)
     *
     * @param string $event_key Event identifier
     * @param int    $user_id   User ID
     * @param array  $context   Event context data
     * @return bool True if at least one webhook succeeded
     */
    public static function send($event_key, $user_id, $context = []) {

        $targets = [];

        // ── 1. New multi-webhook list ─────────────────────────────────────
        $webhook_list = (array) get_option( 'zap_events_webhooks_list', [] );
        foreach ( $webhook_list as $wh ) {
            if ( empty( $wh['active'] ) || empty( $wh['url'] ) ) {
                continue;
            }
            // Filter by events if configured
            if ( ! empty( $wh['events'] ) && ! in_array( $event_key, (array) $wh['events'], true ) ) {
                continue;
            }
            $targets[] = [
                'url'     => $wh['url'],
                'timeout' => absint( $wh['timeout'] ?? self::DEFAULT_TIMEOUT ),
            ];
        }

        // ── 2. Legacy single webhook (backward compatibility) ─────────────
        $legacy_url = get_option( 'zap_events_webhook_url', '' );
        if ( ! empty( $legacy_url ) ) {
            $legacy_events = get_option( 'zap_events_webhook_events', [] );
            if ( empty( $legacy_events ) || in_array( $event_key, (array) $legacy_events, true ) ) {
                $targets[] = [
                    'url'     => $legacy_url,
                    'timeout' => absint( get_option( 'zap_events_webhook_timeout', self::DEFAULT_TIMEOUT ) ),
                ];
            }
        }

        if ( empty( $targets ) ) {
            return false;
        }

        $payload = [
            'event'     => $event_key,
            'user_id'   => $user_id,
            'context'   => $context,
            'timestamp' => current_time( 'mysql' ),
            'site_url'  => get_site_url(),
        ];

        $any_success = false;
        foreach ( $targets as $target ) {
            if ( self::send_with_retry( $target['url'], $payload, 1, $target['timeout'] ) ) {
                $any_success = true;
            }
        }

        return $any_success;
    }

    /**
     * Send webhook with retry logic
     *
     * @param string $url     Webhook URL
     * @param array  $payload Data to send
     * @param int    $attempt Current attempt number
     * @param int    $timeout Request timeout in seconds (0 = use option)
     * @return bool Success status
     */
    private static function send_with_retry($url, $payload, $attempt = 1, $timeout = 0) {
        
        if ( ! $timeout ) {
            $timeout = get_option('zap_events_webhook_timeout', self::DEFAULT_TIMEOUT);
        }
        $custom_headers = get_option('zap_events_webhook_headers', []);

        $headers = array_merge([
            'Content-Type' => 'application/json',
            'User-Agent'   => 'ZapTutorEvents/1.1.0',
        ], $custom_headers);

        $args = [
            'body'        => wp_json_encode($payload),
            'headers'     => $headers,
            'timeout'     => $timeout,
            'method'      => 'POST',
            'blocking'    => true,
            'httpversion' => '1.1',
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            
            // Log failure
            self::log_webhook($url, $payload, false, $error_message, $attempt);
            
            // Retry if not max attempts
            if ($attempt < self::MAX_RETRIES) {
                // Wait before retry (exponential backoff)
                sleep(pow(2, $attempt - 1));
                return self::send_with_retry($url, $payload, $attempt + 1, $timeout);
            }
            
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $success = ($status_code >= 200 && $status_code < 300);

        // Log result
        self::log_webhook($url, $payload, $success, "HTTP {$status_code}", $attempt);

        // Retry on failure if not max attempts
        if (!$success && $attempt < self::MAX_RETRIES) {
            sleep(pow(2, $attempt - 1));
            return self::send_with_retry($url, $payload, $attempt + 1, $timeout);
        }

        return $success;
    }

    /**
     * Log webhook attempt
     * 
     * @param string $url Webhook URL
     * @param array $payload Sent data
     * @param bool $success Success status
     * @param string $message Response or error message
     * @param int $attempt Attempt number
     */
    private static function log_webhook($url, $payload, $success, $message, $attempt) {
        
        // Only log if webhook logging is enabled
        if (!get_option('zap_events_webhook_logging', true)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'zap_webhook_logs';

        // Check if table exists, if not create it
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            self::create_webhook_logs_table();
        }

        $wpdb->insert(
            $table,
            [
                'webhook_url' => $url,
                'event_key'   => $payload['event'],
                'payload'     => wp_json_encode($payload),
                'success'     => $success ? 1 : 0,
                'message'     => $message,
                'attempt'     => $attempt,
                'created_at'  => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%d', '%s']
        );
    }

    /**
     * Create webhook logs table
     */
    public static function create_webhook_logs_table() {
        global $wpdb;

        $table = $wpdb->prefix . 'zap_webhook_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webhook_url VARCHAR(500) NOT NULL,
            event_key VARCHAR(100) NOT NULL,
            payload LONGTEXT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            message TEXT NULL,
            attempt INT NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY event_key (event_key),
            KEY success (success),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Get webhook statistics
     * 
     * @param int $days Number of days to look back
     * @return array Statistics data
     */
    public static function get_stats($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_webhook_logs';

        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            return [
                'total'        => 0,
                'successful'   => 0,
                'failed'       => 0,
                'success_rate' => 0,
            ];
        }

        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s",
            $date_limit
        ));

        $successful = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE created_at >= %s AND success = 1",
            $date_limit
        ));

        $failed = $total - $successful;
        $success_rate = $total > 0 ? round(($successful / $total) * 100, 2) : 0;

        return [
            'total'        => (int) $total,
            'successful'   => (int) $successful,
            'failed'       => (int) $failed,
            'success_rate' => $success_rate,
        ];
    }
}
