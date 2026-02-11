<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class ConnectionManager {

    /**
     * Get connection type (evolution or official)
     */
    public static function get_connection_type() {
        return get_option('zapwa_connection_type', 'evolution');
    }

    /**
     * Check if instance is connected
     */
    public static function is_connected() {
        $type = self::get_connection_type();
        
        if ($type === 'evolution') {
            return self::check_evolution_connection();
        } else {
            return self::check_official_connection();
        }
    }

    /**
     * Check Evolution API connection
     */
    public static function check_evolution_connection() {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');
        $instance_name = get_option('zapwa_evolution_instance');

        if (!$api_url || !$api_token || !$instance_name) {
            return false;
        }

        $response = wp_remote_get(
            rtrim($api_url, '/') . '/instance/connectionState/' . $instance_name,
            [
                'headers' => [
                    'apikey' => $api_token,
                ],
                'timeout' => 10,
            ]
        );

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['instance']['state']) && $body['instance']['state'] === 'open';
    }

    /**
     * Check Official WhatsApp API connection
     */
    private static function check_official_connection() {
        $phone_id = get_option('zapwa_official_phone_id');
        $access_token = get_option('zapwa_official_access_token');

        return !empty($phone_id) && !empty($access_token);
    }

    /**
     * Check if instance exists
     */
    public static function instance_exists($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');
        
        if (!$api_url || !$api_token || !$instance_name) {
            return false;
        }
        
        $response = wp_remote_get(
            rtrim($api_url, '/') . '/instance/fetchInstances',
            [
                'headers' => ['apikey' => $api_token],
                'timeout' => 10,
            ]
        );
        
        if (is_wp_error($response)) {
            error_log('[ZapWA] Error checking instance: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body) && is_array($body)) {
            foreach ($body as $instance) {
                if (isset($instance['instance']['instanceName']) && 
                    $instance['instance']['instanceName'] === $instance_name) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Create Evolution API instance
     */
    public static function create_instance($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');

        if (!$api_url || !$api_token || !$instance_name) {
            return ['success' => false, 'error' => 'Configuração incompleta: URL, Token ou Nome da instância faltando'];
        }

        // Verificar se já existe
        if (self::instance_exists($instance_name)) {
            return ['success' => true, 'message' => 'Instância já existe e está pronta'];
        }

        $response = wp_remote_post(
            rtrim($api_url, '/') . '/instance/create',
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'apikey' => $api_token,
                ],
                'body' => wp_json_encode([
                    'instanceName' => $instance_name,
                    'qrcode' => true,
                ]),
                'timeout' => 20,
            ]
        );

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => 'Erro de conexão: ' . $response->get_error_message()];
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        // Log para debug
        error_log('[ZapWA] Create Instance Response: ' . print_r($body, true));
        
        if ($status_code === 409) {
            // Instância já existe
            return ['success' => true, 'message' => 'Instância já existe'];
        }
        
        if ($status_code >= 400) {
            $error_msg = isset($body['message']) ? $body['message'] : 'Erro HTTP ' . $status_code;
            return ['success' => false, 'error' => $error_msg];
        }
        
        if (isset($body['error'])) {
            return ['success' => false, 'error' => $body['error']];
        }

        update_option('zapwa_evolution_instance', $instance_name);
        
        return ['success' => true, 'data' => $body];
    }

    /**
     * Get QR Code for Evolution API
     */
    public static function get_qr_code($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');

        if (!$api_url || !$api_token || !$instance_name) {
            return ['success' => false, 'error' => 'Missing configuration'];
        }

        $response = wp_remote_get(
            rtrim($api_url, '/') . '/instance/connect/' . $instance_name,
            [
                'headers' => [
                    'apikey' => $api_token,
                ],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['qrcode']) && isset($body['qrcode']['base64'])) {
            return ['success' => true, 'qrcode' => $body['qrcode']['base64']];
        }

        return ['success' => false, 'error' => 'QR Code not available'];
    }

    /**
     * Disconnect instance
     */
    public static function disconnect_instance($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');

        if (!$api_url || !$api_token || !$instance_name) {
            return ['success' => false, 'error' => 'Missing configuration'];
        }

        $response = wp_remote_request(
            rtrim($api_url, '/') . '/instance/logout/' . $instance_name,
            [
                'method' => 'DELETE',
                'headers' => [
                    'apikey' => $api_token,
                ],
                'timeout' => 15,
            ]
        );

        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }

        return ['success' => true];
    }
}
