<?php
/**
 * Uninstall script
 * Removes all plugin data when uninstalled
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Remove event logs table
$table = $wpdb->prefix . 'zap_event_logs';
$wpdb->query("DROP TABLE IF EXISTS {$table}");

// Remove webhook logs table
$webhook_table = $wpdb->prefix . 'zap_webhook_logs';
$wpdb->query("DROP TABLE IF EXISTS {$webhook_table}");

// Remove all plugin options
delete_option('zap_events_webhook_url');
delete_option('zap_events_webhook_events');
delete_option('zap_events_webhook_timeout');
delete_option('zap_events_webhook_headers');
delete_option('zap_events_webhook_logging');
delete_option('zap_events_log_enabled');
delete_option('zap_events_log_retention_days');
delete_option('zap_events_api_key');
delete_option('zap_events_use_queue');
delete_option('zap_events_queue');

// Clear scheduled cron jobs
wp_clear_scheduled_hook('zap_events_daily_cleanup');
wp_clear_scheduled_hook('zap_events_process_queue');
