<?php
/**
 * Uninstall script – runs when the plugin is deleted from WP admin.
 *
 * @package AISalesEngine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$tables = [
    'ai_leads',
    'ai_lists',
    'ai_list_leads',
    'ai_tags',
    'ai_lead_tags',
    'ai_events',
    'ai_jobs',
    'ai_agents',
    'ai_agent_knowledge',
    'ai_pipelines',
    'ai_pipeline_stages',
    'ai_pipeline_leads',
    'ai_automations',
];

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}{$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
}

delete_option( 'ai_sales_engine_version' );
delete_option( 'ai_sales_engine_settings' );

wp_clear_scheduled_hook( 'ai_sales_engine_process_queue' );
