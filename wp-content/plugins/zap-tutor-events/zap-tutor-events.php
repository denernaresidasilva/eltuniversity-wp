<?php
/**
 * Plugin Name: ZAP Events Tutor
 * Description: Camada de eventos padronizados do Tutor LMS para automações externas.
 * Version: 1.0.0
 * Author: ZAP Automação
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes do plugin
 */
define('ZAP_EVENTS_PATH', plugin_dir_path(__FILE__));
define('ZAP_EVENTS_URL', plugin_dir_url(__FILE__));
define('ZAP_EVENTS_VERSION', '1.0.0');

/**
 * Ativação do plugin
 * Cria a tabela de logs de eventos
 */
register_activation_hook(__FILE__, 'zap_events_install');

function zap_events_install() {

    global $wpdb;

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
