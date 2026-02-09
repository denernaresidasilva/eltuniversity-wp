<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public static function init() {

        // Garante que a classe de teste esteja carregada
        $test_file = ZAP_EVENTS_PATH . 'includes/class-admin-test.php';
        if (file_exists($test_file)) {
            require_once $test_file;
        }

        add_action('admin_menu', [self::class, 'menu']);
    }

    public static function menu() {

        // Fallback de segurança
        if (!class_exists(__NAMESPACE__ . '\\Admin_Test')) {
            return;
        }

        add_menu_page(
            'ZAP Tutor Events',
            'ZAP Tutor Events',
            'manage_options',
            'zap-tutor-events',
            [Admin_Test::class, 'render'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'zap-tutor-events',
            'Teste de Eventos',
            'Teste de Eventos',
            'manage_options',
            'zap-tutor-events',
            [Admin_Test::class, 'render']
        );

        add_submenu_page(
            'zap-tutor-events',
            'Logs',
            'Logs',
            'manage_options',
            'zap-tutor-events-logs',
            [self::class, 'logs_page']
        );
    }

    public static function logs_page() {
        global $wpdb;

        $table = $wpdb->prefix . 'zap_event_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");

        echo '<div class="wrap"><h1>Logs de Eventos</h1>';

        if (empty($logs)) {
            echo '<p>Nenhum evento registrado ainda.</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>
                <th>ID</th>
                <th>Evento</th>
                <th>User ID</th>
                <th>Data</th>
                <th>Contexto</th>
              </tr></thead><tbody>';

        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->id) . '</td>';
            echo '<td><code>' . esc_html($log->event_key) . '</code></td>';
            echo '<td>' . esc_html($log->user_id) . '</td>';
            echo '<td>' . esc_html($log->created_at) . '</td>';
            echo '<td><pre style="max-width:400px;white-space:pre-wrap;">' . esc_html($log->context) . '</pre></td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }
}
