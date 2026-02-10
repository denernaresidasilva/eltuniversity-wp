<?php
/**
 * Plugin Name: ZAP Events Tutor
 * Description: Camada de eventos padronizados do Tutor LMS para automações externas.
 * Version: 1.1.0
 * Author: ZAP Automação
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes do plugin
 */
define('ZAP_EVENTS_PATH', plugin_dir_path(__FILE__));
define('ZAP_EVENTS_URL', plugin_dir_url(__FILE__));
define('ZAP_EVENTS_VERSION', '1.1.0');

/**
 * Enable debug mode
 * Set to true to enable detailed logging
 */
if (!defined('ZAP_EVENTS_DEBUG')) {
    define('ZAP_EVENTS_DEBUG', true); // Mudar para false em produção
}

/**
 * Check minimum requirements
 */
function zap_events_check_requirements() {
    global $wp_version;
    
    $php_version = phpversion();
    $min_php = '7.4';
    $min_wp = '5.8';
    
    $errors = [];
    
    if (version_compare($php_version, $min_php, '<')) {
        $errors[] = sprintf(
            'ZAP Events Tutor requer PHP %s ou superior. Você está usando PHP %s.',
            $min_php,
            $php_version
        );
    }
    
    if (version_compare($wp_version, $min_wp, '<')) {
        $errors[] = sprintf(
            'ZAP Events Tutor requer WordPress %s ou superior. Você está usando WordPress %s.',
            $min_wp,
            $wp_version
        );
    }
    
    if (!empty($errors)) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            implode('<br>', $errors),
            'Requisitos não atendidos',
            ['back_link' => true]
        );
    }
}
add_action('admin_init', 'zap_events_check_requirements');

/**
 * Ativação do plugin
 * Cria a tabela de logs de eventos e webhooks
 */
register_activation_hook(__FILE__, 'zap_events_install');

function zap_events_install() {

    global $wpdb;

    // Event logs table
    $table = $wpdb->prefix . 'zap_event_logs';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        event_key VARCHAR(100) NOT NULL,
        user_id BIGINT UNSIGNED NOT NULL,
        context LONGTEXT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY event_key (event_key),
        KEY user_id (user_id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Webhook logs table
    $webhook_table = $wpdb->prefix . 'zap_webhook_logs';
    
    $webhook_sql = "CREATE TABLE {$webhook_table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        webhook_url VARCHAR(500) NOT NULL,
        event_key VARCHAR(100) NOT NULL,
        payload LONGTEXT NULL,
        success TINYINT(1) NOT NULL DEFAULT 0,
        message TEXT NULL,
        attempt INT NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY event_key (event_key),
        KEY success (success),
        KEY created_at (created_at)
    ) {$charset_collate};";
    
    dbDelta($webhook_sql);

    // Set default options
    add_option('zap_events_log_enabled', true);
    add_option('zap_events_log_retention_days', 30);
    add_option('zap_events_webhook_logging', true);
    add_option('zap_events_webhook_timeout', 10);
}

/**
 * Carrega o core do plugin
 */
if (file_exists(ZAP_EVENTS_PATH . 'includes/class-plugin.php')) {
    require_once ZAP_EVENTS_PATH . 'includes/class-plugin.php';
} else {
    wp_die('ZAP Events Tutor: arquivo includes/class-plugin.php não encontrado.');
}

/**
 * Inicialização (COM NAMESPACE CORRETO)
 */
add_action('plugins_loaded', function() {
    // Verificar se Tutor LMS está ativo
    if (!function_exists('tutor')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('ZAP Tutor Events:', 'zap-tutor-events') . '</strong> ' . esc_html__('Requer o plugin Tutor LMS ativo.', 'zap-tutor-events') . '</p></div>';
        });
        return;
    }
    
    // Inicializar plugin
    \ZapTutorEvents\Plugin::init();
});
