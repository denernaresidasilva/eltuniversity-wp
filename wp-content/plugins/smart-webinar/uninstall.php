<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

global $wpdb;

$tables = [
    $wpdb->prefix . 'webinars',
    $wpdb->prefix . 'webinar_sessions',
    $wpdb->prefix . 'webinar_tracking',
    $wpdb->prefix . 'webinar_chat',
    $wpdb->prefix . 'webinar_offers',
    $wpdb->prefix . 'webinar_conversions',
];

foreach ( $tables as $table ) {
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
}

delete_option( 'smart_webinar_schema_version' );
delete_option( 'smart_webinar_settings' );
