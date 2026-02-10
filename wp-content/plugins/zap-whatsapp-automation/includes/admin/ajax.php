<?php
namespace ZapWA\Admin;

use ZapWA\EvolutionAPI;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax {

    public static function init() {
        add_action('wp_ajax_zapwa_send_test', [__CLASS__, 'send_test']);
    }

    public static function send_test() {

        // Segurança
        check_ajax_referer('zapwa_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (!$phone || !$message) {
            wp_send_json_error('Dados inválidos');
        }

        // Call static method directly instead of instantiating
        $result = EvolutionAPI::send_message($phone, $message);

        if (!$result['success']) {
            wp_send_json_error($result['error']);
        }

        wp_send_json_success('Mensagem enviada com sucesso');
    }
}
