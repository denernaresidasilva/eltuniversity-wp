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
            id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            webinar_id      BIGINT UNSIGNED NOT NULL,
            nome            VARCHAR(191)    NOT NULL DEFAULT '',
            email           VARCHAR(191)    NOT NULL DEFAULT '',
            telefone        VARCHAR(50)     NULL,
            data_registro   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            tempo_assistido INT UNSIGNED    NOT NULL DEFAULT 0,
            convertido      TINYINT(1)      NOT NULL DEFAULT 0,
            ip_address      VARCHAR(45)     NULL,
            user_agent      TEXT            NULL,
            PRIMARY KEY (id),
            KEY webinar_id (webinar_id),
            KEY email (email),
            KEY data_registro (data_registro)
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
}
