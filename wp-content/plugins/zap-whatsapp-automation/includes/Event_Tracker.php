<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Event_Tracker {

    const SUPPORTED_EVENT_TYPES = [
        'link_clicked',
        'video_started',
        'video_progress',
        'video_completed',
        'tag_added',
        'tag_removed',
        'course_enrolled',
        'course_completed',
        'lesson_completed',
        'instagram_comment',
        'instagram_dm',
        'whatsapp_reply',
        'email_open',
        'email_click',
        'webhook_received',
    ];

    public static function create_table() {
        global $wpdb;

        $table    = $wpdb->prefix . 'zapwa_events';
        $charset  = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            contact_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(50) NOT NULL,
            event_value VARCHAR(255) NOT NULL DEFAULT '',
            source VARCHAR(100) NOT NULL DEFAULT '',
            metadata LONGTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY contact_id (contact_id),
            KEY event_type (event_type),
            KEY created_at (created_at)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function record($contact_id, $event_type, $event_value = '', $source = '', $metadata = []) {
        global $wpdb;

        $contact_id  = absint($contact_id);
        $event_type  = sanitize_text_field($event_type);
        $event_value = sanitize_text_field($event_value);
        $source      = sanitize_text_field($source);

        if (empty($event_type) || !in_array($event_type, self::SUPPORTED_EVENT_TYPES, true)) {
            return false;
        }

        $metadata_json = wp_json_encode(is_array($metadata) ? $metadata : []);

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'zapwa_events',
            [
                'contact_id'  => $contact_id,
                'event_type'  => $event_type,
                'event_value' => $event_value,
                'source'      => $source,
                'metadata'    => $metadata_json,
                'created_at'  => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        return $inserted ? $wpdb->insert_id : false;
    }

    public static function get_events($contact_id, $event_type = '', $limit = 50) {
        global $wpdb;

        $contact_id = absint($contact_id);
        $limit      = absint($limit) ?: 50;
        $table      = $wpdb->prefix . 'zapwa_events';

        if (!empty($event_type)) {
            $event_type = sanitize_text_field($event_type);
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE contact_id = %d AND event_type = %s ORDER BY created_at DESC LIMIT %d",
                    $contact_id,
                    $event_type,
                    $limit
                )
            );
        } else {
            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM $table WHERE contact_id = %d ORDER BY created_at DESC LIMIT %d",
                    $contact_id,
                    $limit
                )
            );
        }

        return is_array($results) ? $results : [];
    }
}
