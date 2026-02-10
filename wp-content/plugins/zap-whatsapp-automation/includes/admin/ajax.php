<?php
namespace ZapWA\Admin;

use ZapWA\EvolutionAPI;
use ZapWA\QRCodeGenerator;
use ZapWA\ConnectionManager;

if (!defined('ABSPATH')) {
    exit;
}

class Ajax {

    public static function init() {
        add_action('wp_ajax_zapwa_send_test', [__CLASS__, 'send_test']);
        add_action('wp_ajax_zapwa_get_qrcode', [__CLASS__, 'get_qrcode']);
        add_action('wp_ajax_zapwa_check_connection', [__CLASS__, 'check_connection']);
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

    public static function get_qrcode() {
        // Security check
        check_ajax_referer('zapwa_qrcode', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        $instance = sanitize_text_field($_POST['instance'] ?? '');

        if (!$instance) {
            wp_send_json_error('Nome da instância não informado');
        }

        $result = QRCodeGenerator::fetch_and_generate($instance);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['error']);
        }
    }

    public static function check_connection() {
        // Security check
        check_ajax_referer('zapwa_qrcode', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permissão negada');
        }

        $is_connected = ConnectionManager::is_connected();

        wp_send_json_success(['connected' => $is_connected]);
    }
}
