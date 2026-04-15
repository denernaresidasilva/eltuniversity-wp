<?php
namespace WebinarPlataforma;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Installer {

    public static function install(): void {
        self::create_tables();
        update_option( 'wp_webinar_db_version', WP_WEBINAR_DB_VERSION );
    }

    public static function maybe_upgrade(): void {
        $installed = get_option( 'wp_webinar_db_version', '0.0.0' );
        if ( version_compare( $installed, WP_WEBINAR_DB_VERSION, '<' ) ) {
            self::create_tables();
            self::run_migrations( $installed );
            update_option( 'wp_webinar_db_version', WP_WEBINAR_DB_VERSION );
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "
        CREATE TABLE {$wpdb->prefix}webinars (
            id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            nome                VARCHAR(191)    NOT NULL,
            slug                VARCHAR(191)    NOT NULL DEFAULT '',
            descricao           TEXT            NULL,
            youtube_video_id    VARCHAR(50)     NOT NULL DEFAULT '',
            tipo                ENUM('ao_vivo','evergreen') NOT NULL DEFAULT 'evergreen',
            status              ENUM('rascunho','publicado','encerrado') NOT NULL DEFAULT 'rascunho',
            data_inicio         DATETIME        NULL,
            duracao_minutos     INT UNSIGNED    NOT NULL DEFAULT 0,
            bloquear_avanco     TINYINT(1)      NOT NULL DEFAULT 0,
            simulacao_ativa     TINYINT(1)      NOT NULL DEFAULT 0,
            simulacao_contagem  INT UNSIGNED    NOT NULL DEFAULT 0,
            pagina_webinar_id   BIGINT UNSIGNED NULL,
            pagina_inscricao_id BIGINT UNSIGNED NULL,
            layout_json         LONGTEXT        NULL,
            configuracoes_json  LONGTEXT        NULL,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY status (status),
            KEY tipo (tipo),
            KEY created_at (created_at)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}webinar_participantes (
            id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id          BIGINT UNSIGNED NOT NULL,
            sessao_id           BIGINT UNSIGNED NULL,
            nome                VARCHAR(191)    NOT NULL DEFAULT '',
            email               VARCHAR(191)    NOT NULL DEFAULT '',
            telefone            VARCHAR(50)     NULL,
            data_registro       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tempo_assistido     INT UNSIGNED    NOT NULL DEFAULT 0,
            convertido          TINYINT(1)      NOT NULL DEFAULT 0,
            ip_address          VARCHAR(45)     NULL,
            user_agent          TEXT            NULL,
            leadsaas_lead_id    BIGINT UNSIGNED NULL,
            leadsaas_lista_id   BIGINT UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY sessao_id (sessao_id),
            KEY email (email),
            KEY data_registro (data_registro)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}webinar_sessoes (
            id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id          BIGINT UNSIGNED NOT NULL,
            tipo                ENUM('ao_vivo','evergreen') NOT NULL DEFAULT 'evergreen',
            inicio_em           DATETIME        NOT NULL,
            status              ENUM('ativa','finalizada') NOT NULL DEFAULT 'ativa',
            leadsaas_lista_id   BIGINT UNSIGNED NULL,
            replay_enviado_em   DATETIME        NULL,
            created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY status (status),
            KEY inicio_em (inicio_em)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}webinar_chat_mensagens (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id BIGINT UNSIGNED NOT NULL,
            tempo      INT UNSIGNED    NOT NULL DEFAULT 0,
            autor      VARCHAR(100)    NOT NULL DEFAULT '',
            mensagem   TEXT            NOT NULL,
            tipo       ENUM('programada','ao_vivo') NOT NULL DEFAULT 'programada',
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY tempo (tempo)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}webinar_automacoes (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id BIGINT UNSIGNED NOT NULL,
            nome       VARCHAR(191)    NOT NULL DEFAULT '',
            gatilho    VARCHAR(100)    NOT NULL,
            acao       VARCHAR(100)    NOT NULL,
            config     LONGTEXT        NULL,
            ordem      INT UNSIGNED    NOT NULL DEFAULT 0,
            ativo      TINYINT(1)      NOT NULL DEFAULT 1,
            created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY gatilho (gatilho)
        ) $charset;

        CREATE TABLE {$wpdb->prefix}webinar_analytics (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id BIGINT UNSIGNED NOT NULL,
            participante_id BIGINT UNSIGNED NULL,
            evento     VARCHAR(100)    NOT NULL,
            dados      LONGTEXT        NULL,
            timestamp  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY evento (evento),
            KEY timestamp (timestamp)
        ) $charset;
        ";

        dbDelta( $sql );
    }

    /**
     * Run version-specific migrations that dbDelta cannot handle (ALTER TABLE for existing tables).
     */
    private static function run_migrations( string $from_version ): void {
        global $wpdb;

        if ( version_compare( $from_version, '2.0.0', '<' ) ) {
            $table = $wpdb->prefix . 'webinar_participantes';

            // Add sessao_id column if missing
            $col = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = %s AND table_name = %s AND column_name = 'sessao_id'",
                DB_NAME,
                $table
            ) );
            if ( empty( $col ) ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `sessao_id` BIGINT UNSIGNED NULL AFTER `webinar_id`, ADD KEY `sessao_id` (`sessao_id`)" );
            }

            // Add leadsaas_lead_id column if missing
            $col = $wpdb->get_results( $wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_schema = %s AND table_name = %s AND column_name = 'leadsaas_lead_id'",
                DB_NAME,
                $table
            ) );
            if ( empty( $col ) ) {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                $wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `leadsaas_lead_id` BIGINT UNSIGNED NULL, ADD COLUMN `leadsaas_lista_id` BIGINT UNSIGNED NULL" );
            }
        }
    }
}
