<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = [
    $wpdb->prefix . 'lead_listas',
    $wpdb->prefix . 'leads',
    $wpdb->prefix . 'lead_tags',
    $wpdb->prefix . 'lead_tag_relations',
    $wpdb->prefix . 'lead_automacoes',
];

foreach ( $tables as $table ) {
    $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' );
}

delete_option( 'leads_saas_db_version' );
