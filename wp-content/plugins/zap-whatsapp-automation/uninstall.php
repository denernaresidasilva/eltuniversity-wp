<?php
/**
 * Uninstall script for Zap WhatsApp Automation plugin
 * This file is triggered when the plugin is uninstalled (deleted)
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete all plugin options
delete_option('zapwa_api_url');
delete_option('zapwa_api_token');

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}zap_wa_logs");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}zapwa_queue");

// Clear scheduled cron hooks
wp_clear_scheduled_hook('zapwa_process_queue');
wp_clear_scheduled_hook('zap_wa_process_queue');
