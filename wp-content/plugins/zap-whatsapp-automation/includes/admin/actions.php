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
            admin_url('admin.php?page=zap-wa-messages&broadcast=sent')
        );
        exit;
    }
}
