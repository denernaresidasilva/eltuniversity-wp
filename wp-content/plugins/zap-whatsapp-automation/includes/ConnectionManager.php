<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class ConnectionManager {

    /**
     * Test API connection
     */
    public static function test_api_connection() {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');
        
        if (!$api_url || !$api_token) {
            return [
                'success' => false, 
                'error' => 'URL ou Token não configurado'
            ];
        }
        
        // Teste 1: Verificar URL base
        $response = wp_remote_get(rtrim($api_url, '/'), ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error' => 'Erro ao acessar URL: ' . $response->get_error_message()
            ];
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($status_code !== 200) {
            $error_msg = 'API retornou status ' . $status_code;
            if (is_array($body) && isset($body['message'])) {
                $error_msg .= ': ' . $body['message'];
            }
            return [
                'success' => false,
                'error' => $error_msg
            ];
        }
        
        // Validar se body é um array válido
        if (!is_array($body)) {
            return [
                'success' => false,
                'error' => 'Resposta inválida da API'
            ];
        }
        
        // Teste 2: Verificar token listando instâncias
        $response2 = wp_remote_get(
            rtrim($api_url, '/') . '/instance/fetchInstances',
            [
                'headers' => ['apikey' => $api_token],
                'timeout' => 10,
            ]
        );
        
        if (is_wp_error($response2)) {
            return [
                'success' => false,
                'error' => 'Token inválido ou sem permissão',
                'details' => $response2->get_error_message()
            ];
        }
        
        $status_code2 = wp_remote_retrieve_response_code($response2);
        
        if ($status_code2 === 401 || $status_code2 === 403) {
            return [
                'success' => false,
                'error' => 'API Key inválida (Status ' . $status_code2 . ')'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'API funcionando corretamente!',
            'version' => isset($body['version']) ? $body['version'] : 'desconhecida',
            'api_info' => is_array($body) ? array_intersect_key($body, array_flip(['status', 'message', 'version', 'clientName', 'documentation'])) : []
        ];
    }

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
     * Normalize Evolution API URL by removing trailing instance endpoints.
     *
     * @since 1.1.0
     *
     * @param string $api_url
     * @return string Normalized URL or empty string when input is empty or whitespace-only.
     */
    private static function normalize_api_url(string $api_url) {
        $api_url = trim($api_url);
        if ($api_url === '') {
            return '';
        }

        $normalized_url = rtrim($api_url, '/');
        $endpoint_suffixes = [
            '/instance/create',
            '/instance/fetchInstances',
            '/instance/connect',
            '/instance/logout',
            '/instance',
        ];

        do {
            $made_change = false;
            foreach ($endpoint_suffixes as $suffix) {
                $suffix_length = strlen($suffix);
                if (substr_compare($normalized_url, $suffix, -$suffix_length) === 0) {
                    $normalized_url = rtrim(substr($normalized_url, 0, -$suffix_length), '/');
                    $made_change = true;
                    break;
                }
            }
        } while ($made_change);

        return $normalized_url;
    }

    /**
     * Build a log-safe URL without credentials or query strings.
     *
     * @since 1.1.0
     *
     * @param string $api_url
     * @return string
     */
    private static function get_log_safe_url(string $api_url) {
        $parts = wp_parse_url($api_url);
        if (!is_array($parts) || empty($parts['host'])) {
            return '[redacted]';
        }

        $safe_url = '';
        if (!empty($parts['scheme'])) {
            $safe_url .= $parts['scheme'] . '://';
        }
        $safe_url .= $parts['host'];
        if (!empty($parts['port'])) {
            $safe_url .= ':' . $parts['port'];
        }
        if (!empty($parts['path'])) {
            $safe_url .= $parts['path'];
        }

        return $safe_url;
    }

    /**
     * Build Evolution API base URL candidates for compatibility.
     */
    private static function get_api_url_candidates($api_url) {
        $api_url = self::normalize_api_url($api_url);
        $candidates = [$api_url];
        $root_url = rtrim(preg_replace('~/api(?:/v\\d+)?$~', '', $api_url), '/');

        foreach (['/api', '/api/v1', '/api/v2'] as $suffix) {
            $candidate = $root_url . $suffix;
            $candidates[] = $candidate;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Create Evolution API instance
     */
    public static function create_instance($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');

        if (!$api_url || !$api_token || !$instance_name) {
            return [
                'success' => false, 
                'error' => 'Configuração incompleta: URL, Token ou Nome da instância faltando'
            ];
        }

        $normalized_api_url = self::normalize_api_url($api_url);
        /**
         * Filters whether to persist the normalized Evolution API URL.
         *
         * Example:
         * add_filter(
         *   'zapwa_persist_normalized_api_url',
         *   function($should_persist, $original_url, $normalized_url) {
         *     return $should_persist && $original_url !== $normalized_url;
         *   },
         *   10,
         *   3
         * );
         *
         * @since 1.1.0
         *
         * @param bool $should_persist
         * @param string $original_url
         * @param string $normalized_url
         */
        $should_persist = apply_filters(
            'zapwa_persist_normalized_api_url',
            true,
            $api_url,
            $normalized_api_url
        );
        if ($normalized_api_url !== $api_url && $should_persist) {
            update_option('zapwa_evolution_url', $normalized_api_url);
            $log_before = self::get_log_safe_url($api_url);
            $log_after = self::get_log_safe_url($normalized_api_url);
            error_log(sprintf('[ZapWA] Normalized Evolution API URL: %s -> %s', $log_before, $log_after));
        }

        $api_candidates = self::get_api_url_candidates($normalized_api_url);
        
        error_log('[ZapWA] Tentando criar instância...');
        error_log('[ZapWA] Instance Name: ' . $instance_name);

        // Verificar se já existe
        if (self::instance_exists($instance_name)) {
            return ['success' => true, 'message' => 'Instância já existe e está pronta'];
        }

        $request_body = [
            'instanceName' => $instance_name,
            'integration' => 'WHATSAPP-BAILEYS', // Required by Evolution API v2.x - specifies WhatsApp connection type
            'qrcode' => true,
        ];
        
        error_log('[ZapWA] Request Body: ' . wp_json_encode($request_body));

        $response = null;
        $full_url = null;
        $status_code = null;
        $resolved_api_url = $normalized_api_url;
        $should_update_api_url = false;
        $last_candidate_index = count($api_candidates) - 1;

        foreach ($api_candidates as $index => $candidate_url) {
            $full_url = $candidate_url . '/instance/create';
            error_log('[ZapWA] URL: ' . $full_url);

            $response = wp_remote_post(
                $full_url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'apikey' => $api_token,
                    ],
                    'body' => wp_json_encode($request_body),
                    'timeout' => 20,
                    'sslverify' => true, // Importante para HTTPS
                ]
            );

            if (is_wp_error($response)) {
                $error_msg = $response->get_error_message();
                error_log('[ZapWA] WP Error: ' . $error_msg);
                return ['success' => false, 'error' => 'Erro de conexão: ' . $error_msg];
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 404 && $index < $last_candidate_index) {
                continue;
            }

            $resolved_api_url = $candidate_url;
            $should_update_api_url = ($status_code !== 404 && $candidate_url !== $api_url);
            break;
        }

        if ($should_update_api_url) {
            update_option('zapwa_evolution_url', $resolved_api_url);
        }

        $response_body = wp_remote_retrieve_body($response);
        $body = json_decode($response_body, true);
        
        // Validar se JSON é válido
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('[ZapWA] Invalid JSON response: ' . json_last_error_msg());
            return [
                'success' => false,
                'error' => 'Resposta inválida da API (JSON malformado)'
            ];
        }
        
        // Log completo da resposta
        error_log('[ZapWA] Status Code: ' . $status_code);
        error_log('[ZapWA] Response Body: ' . $response_body);
        
        // Verificar headers da resposta
        $headers = wp_remote_retrieve_headers($response);
        error_log('[ZapWA] Response Headers: ' . print_r($headers, true));
        
        if ($status_code === 409) {
            // Instância já existe
            return ['success' => true, 'message' => 'Instância já existe'];
        }
        
        if ($status_code === 404) {
            return [
                'success' => false,
                'error' => 'Endpoint não encontrado (404). Verifique se a URL inclui /api/v1 ou /api/v2.',
                'url_testada' => $full_url,
                'sugestao' => 'Acesse no navegador: ' . $resolved_api_url . '/instance/fetchInstances'
            ];
        }
        
        if ($status_code === 401 || $status_code === 403) {
            return [
                'success' => false,
                'error' => 'API Key inválida (Status ' . $status_code . ')'
            ];
        }
        
        if ($status_code >= 400) {
            $error_msg = isset($body['message']) ? $body['message'] : 'Erro HTTP ' . $status_code;
            return [
                'success' => false, 
                'error' => $error_msg,
                'status_code' => $status_code
            ];
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
