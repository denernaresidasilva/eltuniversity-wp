<?php
namespace SmartWebinar;

if ( ! defined( 'ABSPATH' ) ) exit;

class Installer {

    const SCHEMA_VERSION = '1.0.0';

    public static function activate(): void {
        self::create_tables();
        self::set_defaults();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // wp_webinars
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinars (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title         VARCHAR(255) NOT NULL DEFAULT '',
            slug          VARCHAR(255) NOT NULL DEFAULT '',
            description   LONGTEXT,
            mode          ENUM('live','simulated','replay') NOT NULL DEFAULT 'simulated',
            video_url     VARCHAR(2083) NOT NULL DEFAULT '',
            youtube_id    VARCHAR(50) DEFAULT NULL,
            status        ENUM('draft','scheduled','live','ended') NOT NULL DEFAULT 'draft',
            scheduled_at  DATETIME DEFAULT NULL,
            duration      INT(11) UNSIGNED NOT NULL DEFAULT 0,
            thumbnail_url VARCHAR(2083) DEFAULT NULL,
            created_by    BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug),
            KEY status (status)
        ) $charset;" );

        // wp_webinar_sessions
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinar_sessions (
            session_id    VARCHAR(36)  NOT NULL,
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            webinar_id    BIGINT(20) UNSIGNED NOT NULL,
            session_start DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            session_end   DATETIME DEFAULT NULL,
            watch_time    INT(11) UNSIGNED NOT NULL DEFAULT 0,
            progress      TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            no_show       TINYINT(1) NOT NULL DEFAULT 0,
            ip            VARCHAR(45) DEFAULT NULL,
            device        VARCHAR(100) DEFAULT NULL,
            PRIMARY KEY  (session_id),
            KEY user_id (user_id),
            KEY webinar_id (webinar_id)
        ) $charset;" );

        // wp_webinar_tracking
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinar_tracking (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            webinar_id    BIGINT(20) UNSIGNED NOT NULL,
            session_id    VARCHAR(36) NOT NULL DEFAULT '',
            event_type    VARCHAR(60) NOT NULL DEFAULT '',
            watch_time    INT(11) UNSIGNED NOT NULL DEFAULT 0,
            percentage    TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            device        VARCHAR(100) DEFAULT NULL,
            ip            VARCHAR(45) DEFAULT NULL,
            timestamp     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY webinar_id (webinar_id),
            KEY session_id (session_id),
            KEY event_type (event_type)
        ) $charset;" );

        // wp_webinar_chat
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinar_chat (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id    BIGINT(20) UNSIGNED NOT NULL,
            session_id    VARCHAR(36) NOT NULL DEFAULT '',
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            message_type  ENUM('recorded','scheduled','user') NOT NULL DEFAULT 'user',
            author_name   VARCHAR(100) NOT NULL DEFAULT '',
            author_avatar VARCHAR(2083) DEFAULT NULL,
            message       TEXT NOT NULL,
            show_at       INT(11) UNSIGNED NOT NULL DEFAULT 0,
            sent_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY webinar_id (webinar_id),
            KEY session_id (session_id),
            KEY message_type (message_type),
            KEY show_at (show_at)
        ) $charset;" );

        // wp_webinar_offers
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinar_offers (
            id                 BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id         BIGINT(20) UNSIGNED NOT NULL,
            active             TINYINT(1) NOT NULL DEFAULT 1,
            button_text        VARCHAR(255) NOT NULL DEFAULT 'Quero Agora!',
            offer_url          VARCHAR(2083) NOT NULL DEFAULT '',
            show_at_seconds    INT(11) UNSIGNED NOT NULL DEFAULT 1800,
            hide_at_seconds    INT(11) UNSIGNED NOT NULL DEFAULT 0,
            button_position    ENUM('bottom-left','bottom-center','bottom-right','top-left','top-center','top-right') NOT NULL DEFAULT 'bottom-center',
            animation          ENUM('none','bounce','pulse','shake','slide') NOT NULL DEFAULT 'pulse',
            open_new_tab       TINYINT(1) NOT NULL DEFAULT 1,
            bg_color           VARCHAR(20) NOT NULL DEFAULT '#e74c3c',
            text_color         VARCHAR(20) NOT NULL DEFAULT '#ffffff',
            PRIMARY KEY  (id),
            KEY webinar_id (webinar_id)
        ) $charset;" );

        // wp_webinar_conversions
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinar_conversions (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id    BIGINT(20) UNSIGNED NOT NULL,
            session_id    VARCHAR(36) NOT NULL DEFAULT '',
            user_id       BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            source        ENUM('querystring','webhook','script') NOT NULL DEFAULT 'script',
            amount        DECIMAL(10,2) DEFAULT NULL,
            ip            VARCHAR(45) DEFAULT NULL,
            converted_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY webinar_id (webinar_id),
            KEY session_id (session_id),
            KEY user_id (user_id)
        ) $charset;" );

        update_option( 'smart_webinar_schema_version', self::SCHEMA_VERSION );
    }

    private static function set_defaults(): void {
        if ( ! get_option( 'smart_webinar_settings' ) ) {
            update_option( 'smart_webinar_settings', [
                'youtube_api_key' => '',
                'default_mode'    => 'simulated',
            ] );
        }
    }
}
