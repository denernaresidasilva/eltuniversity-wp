<?php
/**
 * Handles plugin activation: creates DB tables and sets defaults.
 *
 * @package AISalesEngine
 */

namespace AISalesEngine;

if ( ! defined( 'ABSPATH' ) ) exit;

class Installer {

    const SCHEMA_VERSION = '1.0.0';

    /**
     * Run on plugin activation.
     */
    public static function activate(): void {
        self::create_tables();
        self::set_defaults();
        flush_rewrite_rules();
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'ai_sales_engine_process_queue' );
        flush_rewrite_rules();
    }

    /**
     * Create all required database tables using dbDelta.
     */
    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Leads
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_leads (
            id            BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name          VARCHAR(255) NOT NULL DEFAULT '',
            email         VARCHAR(255) NOT NULL DEFAULT '',
            phone         VARCHAR(50)  DEFAULT NULL,
            whatsapp      VARCHAR(50)  DEFAULT NULL,
            instagram     VARCHAR(100) DEFAULT NULL,
            lead_score    INT(11) NOT NULL DEFAULT 0,
            source        VARCHAR(100) DEFAULT NULL,
            utm_source    VARCHAR(100) DEFAULT NULL,
            utm_medium    VARCHAR(100) DEFAULT NULL,
            utm_campaign  VARCHAR(100) DEFAULT NULL,
            status        VARCHAR(50)  NOT NULL DEFAULT 'active',
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY email (email),
            KEY phone (phone),
            KEY created_at (created_at)
        ) $charset;" );

        // Lists
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_lists (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT DEFAULT NULL,
            webhook_url VARCHAR(2083) DEFAULT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // List <-> Lead pivot
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_list_leads (
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            list_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (lead_id, list_id)
        ) $charset;" );

        // Tags
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_tags (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(100) NOT NULL DEFAULT '',
            slug       VARCHAR(100) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset;" );

        // Lead <-> Tag pivot
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_lead_tags (
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            tag_id  BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (lead_id, tag_id)
        ) $charset;" );

        // Events log
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_events (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id     BIGINT(20) UNSIGNED NOT NULL,
            event_name  VARCHAR(100) NOT NULL DEFAULT '',
            event_value VARCHAR(255) DEFAULT NULL,
            metadata    LONGTEXT DEFAULT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id)
        ) $charset;" );

        // Async jobs queue
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_jobs (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type       VARCHAR(100) NOT NULL DEFAULT '',
            payload    LONGTEXT DEFAULT NULL,
            status     ENUM('pending','processing','done','failed') NOT NULL DEFAULT 'pending',
            attempts   TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
            run_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status_run_at (status, run_at)
        ) $charset;" );

        // AI Agents
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_agents (
            id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name             VARCHAR(255) NOT NULL DEFAULT '',
            role             VARCHAR(255) DEFAULT NULL,
            goal             TEXT DEFAULT NULL,
            personality      TEXT DEFAULT NULL,
            training_prompt  LONGTEXT DEFAULT NULL,
            voice_enabled    TINYINT(1) NOT NULL DEFAULT 0,
            image_enabled    TINYINT(1) NOT NULL DEFAULT 0,
            created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Agent knowledge base files
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_agent_knowledge (
            id         BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            agent_id   BIGINT(20) UNSIGNED NOT NULL,
            file_path  VARCHAR(2083) DEFAULT NULL,
            file_type  VARCHAR(50)  DEFAULT NULL,
            content    LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY agent_id (agent_id)
        ) $charset;" );

        // Pipelines
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_pipelines (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL DEFAULT '',
            description TEXT DEFAULT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // Pipeline stages
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_pipeline_stages (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pipeline_id BIGINT(20) UNSIGNED NOT NULL,
            name        VARCHAR(255) NOT NULL DEFAULT '',
            position    INT(11) UNSIGNED NOT NULL DEFAULT 0,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pipeline_id (pipeline_id)
        ) $charset;" );

        // Pipeline lead placements
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_pipeline_leads (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            pipeline_id BIGINT(20) UNSIGNED NOT NULL,
            stage_id    BIGINT(20) UNSIGNED NOT NULL,
            lead_id     BIGINT(20) UNSIGNED NOT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY pipeline_lead (pipeline_id, lead_id)
        ) $charset;" );

        // Automations
        dbDelta( "CREATE TABLE {$wpdb->prefix}ai_automations (
            id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name           VARCHAR(255) NOT NULL DEFAULT '',
            trigger_type   VARCHAR(100) NOT NULL DEFAULT '',
            trigger_config LONGTEXT DEFAULT NULL,
            flow_json      LONGTEXT DEFAULT NULL,
            status         ENUM('active','inactive') NOT NULL DEFAULT 'inactive',
            created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset;" );

        update_option( 'ai_sales_engine_version', self::SCHEMA_VERSION );
    }

    /**
     * Set sensible default options on first activation.
     */
    private static function set_defaults(): void {
        if ( ! get_option( 'ai_sales_engine_settings' ) ) {
            add_option( 'ai_sales_engine_settings', [
                'openai_key'         => '',
                'whatsapp_token'     => '',
                'instagram_token'    => '',
                'tracker_enabled'    => true,
                'scoring_rules'      => [
                    'page_visit'          => 10,
                    'message_reply'       => 20,
                    'webinar_completed'   => 50,
                    'purchase_completed'  => 100,
                ],
            ] );
        }
    }
}
