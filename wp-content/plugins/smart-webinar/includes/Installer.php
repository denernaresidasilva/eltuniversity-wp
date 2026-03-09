<?php
namespace SmartWebinar;

if ( ! defined( 'ABSPATH' ) ) exit;

class Installer {

    const SCHEMA_VERSION = '2.0.0';

    public static function activate(): void {
        self::create_tables();
        self::maybe_upgrade();
        self::set_defaults();
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function maybe_upgrade(): void {
        $current = get_option( 'smart_webinar_schema_version', '0' );
        if ( version_compare( $current, self::SCHEMA_VERSION, '>=' ) ) {
            return;
        }
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Migrate mode column from ENUM to VARCHAR and add new values
        $col_mode = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}webinars LIKE 'mode'" ); // phpcs:ignore
        if ( ! empty( $col_mode ) && strpos( $col_mode[0]->Type, 'enum' ) !== false ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}webinars MODIFY mode VARCHAR(20) NOT NULL DEFAULT 'evergreen'" ); // phpcs:ignore
        }

        // Migrate status column from ENUM to VARCHAR
        $col_status = $wpdb->get_results( "SHOW COLUMNS FROM {$wpdb->prefix}webinars LIKE 'status'" ); // phpcs:ignore
        if ( ! empty( $col_status ) && strpos( $col_status[0]->Type, 'enum' ) !== false ) {
            $wpdb->query( "ALTER TABLE {$wpdb->prefix}webinars MODIFY status VARCHAR(20) NOT NULL DEFAULT 'draft'" ); // phpcs:ignore
        }

        // Add new columns if missing — check each individually for efficiency
        $new_cols = [
            'live_api_key'      => "ALTER TABLE {$wpdb->prefix}webinars ADD live_api_key VARCHAR(255) DEFAULT NULL",
            'live_stream_id'    => "ALTER TABLE {$wpdb->prefix}webinars ADD live_stream_id VARCHAR(255) DEFAULT NULL",
            'schedule_day'      => "ALTER TABLE {$wpdb->prefix}webinars ADD schedule_day TINYINT(1) DEFAULT NULL",
            'schedule_time'     => "ALTER TABLE {$wpdb->prefix}webinars ADD schedule_time TIME DEFAULT NULL",
            'schedule_datetime' => "ALTER TABLE {$wpdb->prefix}webinars ADD schedule_datetime DATETIME DEFAULT NULL",
            'room_bg_color'     => "ALTER TABLE {$wpdb->prefix}webinars ADD room_bg_color VARCHAR(20) NOT NULL DEFAULT '#1a1a2e'",
            'room_bg_image_url' => "ALTER TABLE {$wpdb->prefix}webinars ADD room_bg_image_url VARCHAR(2083) DEFAULT NULL",
            'room_bg_video_url' => "ALTER TABLE {$wpdb->prefix}webinars ADD room_bg_video_url VARCHAR(2083) DEFAULT NULL",
        ];
        foreach ( $new_cols as $col => $sql ) {
            $exists = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
                "SHOW COLUMNS FROM {$wpdb->prefix}webinars LIKE %s",
                $col
            ) );
            if ( empty( $exists ) ) {
                $wpdb->query( $sql ); // phpcs:ignore
            }
        }

        update_option( 'smart_webinar_schema_version', self::SCHEMA_VERSION );
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // wp_webinars
        dbDelta( "CREATE TABLE {$wpdb->prefix}webinars (
            id                BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title             VARCHAR(255) NOT NULL DEFAULT '',
            slug              VARCHAR(255) NOT NULL DEFAULT '',
            description       LONGTEXT,
            mode              VARCHAR(20) NOT NULL DEFAULT 'evergreen',
            video_url         VARCHAR(2083) NOT NULL DEFAULT '',
            youtube_id        VARCHAR(50) DEFAULT NULL,
            live_api_key      VARCHAR(255) DEFAULT NULL,
            live_stream_id    VARCHAR(255) DEFAULT NULL,
            status            VARCHAR(20) NOT NULL DEFAULT 'draft',
            scheduled_at      DATETIME DEFAULT NULL,
            schedule_day      TINYINT(1) DEFAULT NULL,
            schedule_time     TIME DEFAULT NULL,
            schedule_datetime DATETIME DEFAULT NULL,
            duration          INT(11) UNSIGNED NOT NULL DEFAULT 0,
            thumbnail_url     VARCHAR(2083) DEFAULT NULL,
            countdown_delay   INT(11) UNSIGNED NOT NULL DEFAULT 0,
            countdown_text    VARCHAR(255) NOT NULL DEFAULT 'O webinar começa em:',
            room_bg_color     VARCHAR(20) NOT NULL DEFAULT '#1a1a2e',
            room_bg_image_url VARCHAR(2083) DEFAULT NULL,
            room_bg_video_url VARCHAR(2083) DEFAULT NULL,
            created_by        BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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
                'default_mode'    => 'evergreen',
            ] );
        }
    }
}
