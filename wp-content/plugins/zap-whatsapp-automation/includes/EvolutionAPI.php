<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class EvolutionAPI {

    /**
     * Send message to a specific user
     * @param int $user_id
     * @param string $message
     * @param array $payload
     */
    public static function send($user_id, $message, $payload = []) {

        $phone = get_user_meta($user_id, 'billing_phone', true);

        if (!$phone || !$message) {
            return;
        }

        $api_url   = rtrim(get_option('zapwa_api_url'), '/');
        $api_token = get_option('zapwa_api_token');

        if (!$api_url || !$api_token) {
            Logger::debug('Evolution API não configurada');
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

        Logger::debug('Mensagem WhatsApp enviada', [
            'user_id' => $user_id,
            'phone'   => $phone,
            'event'   => $payload['event'] ?? '',
            'status'  => is_wp_error($response) ? 'erro' : 'enviado'
        ]);
    }

    /**
     * Send message to a phone number
     * @param string $phone
     * @param string $text
     * @return array ['success' => bool, 'error' => string|null]
     */
    public static function send_message($phone, $text) {

        if (!$phone || !$text) {
            return ['success' => false, 'error' => 'Phone or text missing'];
        }

        $connection_type = ConnectionManager::get_connection_type();

        if ($connection_type === 'official') {
            return self::send_via_official($phone, $text);
        } else {
            return self::send_via_evolution($phone, $text);
        }
    }

    /**
     * Send message using Official WhatsApp API
     */
    private static function send_via_official($phone, $text) {
        $phone_id = get_option('zapwa_official_phone_id');
        $access_token = get_option('zapwa_official_access_token');

        if (!$phone_id || !$access_token) {
            return ['success' => false, 'error' => 'Official API not configured'];
        }

        // Format phone number (remove + and ensure country code)
        $phone = preg_replace('/\D/', '', $phone);

        $response = wp_remote_post(
            "https://graph.facebook.com/v18.0/{$phone_id}/messages",
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $access_token,
                ],
                'body' => wp_json_encode([
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => [
                        'body' => $text
                    ]
                ]),
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return ['success' => true, 'error' => null];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $error_message = $body['error']['message'] ?? 'HTTP ' . $status_code;

        return ['success' => false, 'error' => $error_message];
    }

    /**
     * Send message via Evolution API
     */
    private static function send_via_evolution($phone, $text) {
        $api_url = rtrim(get_option('zapwa_api_url'), '/');
        $api_token = get_option('zapwa_api_token');
        $instance_name = get_option('zapwa_instance_name');

        if (!$api_url || !$api_token || !$instance_name) {
            return ['success' => false, 'error' => 'Evolution API not configured'];
        }

        $response = wp_remote_post($api_url . '/message/sendText/' . $instance_name, [
            'headers' => [
                'Content-Type' => 'application/json',
                'apikey' => $api_token,
            ],
            'body' => wp_json_encode([
                'number' => $phone,
                'text' => $text,
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return ['success' => true, 'error' => null];
        }

        return ['success' => false, 'error' => 'HTTP ' . $status_code];
    }
}
