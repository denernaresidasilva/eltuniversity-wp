<?php
namespace ZapWA\Admin;

use ZapWA\Broadcast;

if (!defined('ABSPATH')) exit;

class Actions {

    public static function init() {

        add_action(
            'admin_post_zap_send_broadcast',
            [self::class, 'send_broadcast']
        );

        add_action(
            'admin_post_zapwa_delete_all_logs',
            [self::class, 'delete_all_logs']
        );

        // Adicionar botão "Disparar" na lista de posts do tipo zapwa_message
        add_filter('post_row_actions', [self::class, 'add_broadcast_row_action'], 10, 2);
        add_action('admin_notices', [self::class, 'broadcast_sent_notice']);
    }

    public static function send_broadcast() {

        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão');
        }

        if (
            !isset($_GET['message_id'], $_GET['_wpnonce']) ||
            !wp_verify_nonce($_GET['_wpnonce'], 'zap_send_broadcast')
        ) {
            wp_die('Requisição inválida');
        }

        $message_id = (int) $_GET['message_id'];

        Broadcast::dispatch($message_id);

        wp_redirect(
            add_query_arg('broadcast', 'sent', wp_get_referer() ?: admin_url('edit.php?post_type=zapwa_message'))
        );
        exit;
    }

    /**
     * Adiciona o botão "Disparar" na coluna de ações da lista de mensagens.
     */
    public static function add_broadcast_row_action($actions, $post) {

        if ($post->post_type !== 'zapwa_message') {
            return $actions;
        }

        if ($post->post_status !== 'publish') {
            return $actions;
        }

        $type = get_post_meta($post->ID, '_zapwa_type', true);
        if ($type !== 'broadcast') {
            return $actions;
        }

        $url = wp_nonce_url(
            admin_url('admin-post.php?action=zap_send_broadcast&message_id=' . $post->ID),
            'zap_send_broadcast'
        );

        $actions['zap_dispatch'] = sprintf(
            '<a href="%s" style="color:#25d366;font-weight:bold;" onclick="return confirm(\'Disparar broadcast para todos os destinatários?\')">📢 Disparar</a>',
            esc_url($url)
        );

        return $actions;
    }

    /**
     * Exibe aviso de sucesso após disparar um broadcast.
     */
    public static function broadcast_sent_notice() {

        if (!isset($_GET['broadcast']) || sanitize_key($_GET['broadcast']) !== 'sent') {
            return;
        }

        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Broadcast adicionado à fila e será enviado em breve!</strong></p></div>';
    }

    /**
     * Exclui todos os logs de envio do WhatsApp.
     */
    public static function delete_all_logs() {

        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão');
        }

        check_admin_referer('zapwa_delete_all_logs', 'zapwa_delete_logs_nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'zap_wa_logs';
        $wpdb->query("TRUNCATE TABLE {$table}");

        wp_redirect(add_query_arg([
            'page'    => 'zap-wa-logs',
            'deleted' => 'all',
        ], admin_url('admin.php')));
        exit;
    }
}
