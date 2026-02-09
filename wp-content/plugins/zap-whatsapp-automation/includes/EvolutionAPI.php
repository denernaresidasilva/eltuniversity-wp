<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class EvolutionAPI {

    public static function send($user_id, $message, $payload = []) {

        $phone = get_user_meta($user_id, 'billing_phone', true);

        if (!$phone || !$message) {
            return;
        }

        $api_url   = rtrim(get_option('zapwa_api_url'), '/');
        $api_token = get_option('zapwa_api_token');

        if (!$api_url || !$api_token) {
            Logger::log('Evolution API não configurada');
            return;
        }

        $response = wp_remote_post($api_url . '/message/sendText', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ],
            'body' => wp_json_encode([
                'number' => $phone,
                'text'   => $message,
            ]),
            'timeout' => 20,
        ]);

        Logger::log('Mensagem WhatsApp enviada', [
            'user_id' => $user_id,
            'phone'   => $phone,
            'event'   => $payload['event'] ?? '',
            'status'  => is_wp_error($response) ? 'erro' : 'enviado'
        ]);
    }
}
