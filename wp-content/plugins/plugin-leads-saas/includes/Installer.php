<?php
namespace LeadsSaaS;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Installer {

    public static function install() {
        self::create_tables();
        update_option( 'leads_saas_db_version', LEADS_SAAS_DB_VERSION );
    }

    public static function maybe_upgrade() {
        $installed = get_option( 'leads_saas_db_version', '0.0.0' );
        if ( version_compare( $installed, LEADS_SAAS_DB_VERSION, '<' ) ) {
            self::create_tables();
            update_option( 'leads_saas_db_version', LEADS_SAAS_DB_VERSION );
        }
    }

    private static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$wpdb->prefix}lead_listas (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome        VARCHAR(191)    NOT NULL,
            descricao   TEXT            NULL,
            webhook_key VARCHAR(64)     NOT NULL DEFAULT '',
            form_schema_json LONGTEXT   NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY webhook_key (webhook_key)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}leads (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lista_id    BIGINT UNSIGNED NOT NULL,
            nome        VARCHAR(191)    NOT NULL DEFAULT '',
            email       VARCHAR(191)    NOT NULL DEFAULT '',
            telefone    VARCHAR(50)     NULL,
            campos_json LONGTEXT        NULL,
            origem      VARCHAR(100)    NOT NULL DEFAULT 'manual',
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lista_id (lista_id),
            KEY email (email),
            KEY created_at (created_at)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_tags (
            id   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome VARCHAR(100)    NOT NULL,
            cor  VARCHAR(10)     NOT NULL DEFAULT '#6366f1',
            PRIMARY KEY (id),
            UNIQUE KEY nome (nome)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_tag_relations (
            lead_id BIGINT UNSIGNED NOT NULL,
            tag_id  BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (lead_id, tag_id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_automacoes (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome            VARCHAR(191)    NOT NULL,
            trigger_key     VARCHAR(100)    NOT NULL,
            acoes_json      LONGTEXT        NULL,
            conditions_json LONGTEXT        NULL,
            nodes_json      LONGTEXT        NULL,
            ativo           TINYINT(1)      NOT NULL DEFAULT 1,
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_workflow_runs (
            id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            workflow_id  BIGINT UNSIGNED NOT NULL,
            lead_id      BIGINT UNSIGNED NOT NULL,
            current_node VARCHAR(64)     NOT NULL DEFAULT '',
            status       VARCHAR(20)     NOT NULL DEFAULT 'running',
            context_json LONGTEXT        NULL,
            resume_at    DATETIME        NULL,
            created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY workflow_id (workflow_id),
            KEY lead_id (lead_id),
            KEY status_resume (status, resume_at)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_email_sequences (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            lista_id   BIGINT UNSIGNED NOT NULL,
            nome       VARCHAR(191)    NOT NULL DEFAULT '',
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY lista_id (lista_id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_email_sequence_steps (
            id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sequence_id    BIGINT UNSIGNED NOT NULL,
            step_order     INT             NOT NULL DEFAULT 0,
            assunto        VARCHAR(255)    NOT NULL DEFAULT '',
            corpo_html     LONGTEXT        NULL,
            delay_value    INT             NOT NULL DEFAULT 0,
            delay_unit     VARCHAR(10)     NOT NULL DEFAULT 'hours',
            wait_for_open  TINYINT(1)      NOT NULL DEFAULT 0,
            max_wait_value INT             NOT NULL DEFAULT 48,
            max_wait_unit  VARCHAR(10)     NOT NULL DEFAULT 'hours',
            PRIMARY KEY (id),
            KEY sequence_id (sequence_id)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}lead_email_queue (
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sequence_id     BIGINT UNSIGNED NOT NULL,
            step_id         BIGINT UNSIGNED NOT NULL,
            lead_id         BIGINT UNSIGNED NOT NULL,
            status          VARCHAR(20)     NOT NULL DEFAULT 'pending',
            scheduled_at    DATETIME        NOT NULL,
            sent_at         DATETIME        NULL,
            open_token      VARCHAR(64)     NOT NULL DEFAULT '',
            opened_at       DATETIME        NULL,
            prev_queue_id   BIGINT UNSIGNED NULL,
            wait_open_until DATETIME        NULL,
            created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lead_id (lead_id),
            KEY status (status),
            KEY open_token (open_token)
        ) $charset;
        ";

        dbDelta( $sql );
    }
}
