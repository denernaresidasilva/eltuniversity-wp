<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class QRCodeGenerator {
    
    private static $autoloader_loaded = false;
    
    /**
     * Load composer autoloader once
     */
    private static function load_autoloader() {
        if (self::$autoloader_loaded) {
            return true;
        }
        
        $autoload = plugin_dir_path(__FILE__) . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            return false;
        }
        
        require_once $autoload;
        self::$autoloader_loaded = true;
        return true;
    }


    /**
     * Build authentication headers for Evolution API.
     */
    private static function get_api_headers($api_token) {
        return [
            'apikey' => $api_token,
            'Authorization' => 'Bearer ' . $api_token,
        ];
    }

    /**
     * Ensure base64 image has a valid data URI prefix.
     */
    private static function normalize_base64_image($base64) {
        if (!is_string($base64) || $base64 === '') {
            return null;
        }

        if (strpos($base64, 'data:image') === 0) {
            return $base64;
        }

        return 'data:image/png;base64,' . $base64;
    }

    /**
     * Try extracting a QR payload from several Evolution API response formats.
     */
    private static function extract_qr_payload($body) {
        if (!is_array($body)) {
            return null;
        }

        if (!empty($body['code']) && is_string($body['code'])) {
            return ['type' => 'code', 'value' => $body['code']];
        }

        $candidate_paths = [
            ['qrcode', 'base64'],
            ['qrcode', 'code'],
            ['base64'],
            ['qr'],
            ['qrCode'],
            ['qrcode'],
        ];

        foreach ($candidate_paths as $path) {
            $value = $body;
            foreach ($path as $segment) {
                if (!is_array($value) || !array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }
                $value = $value[$segment];
            }

            if (is_string($value) && $value !== '') {
                return ['type' => 'base64', 'value' => $value];
            }
        }

        return null;
    }


    /**
     * Build API URL candidates for Evolution variants.
     */
    private static function get_api_url_candidates($api_url) {
        $api_url = rtrim((string) $api_url, '/');
        if ($api_url === '') {
            return [];
        }

        $candidates = [$api_url];
        $root_url = rtrim(preg_replace('~/api(?:/v\d+)?$~', '', $api_url), '/');

        foreach (['/api', '/api/v1', '/api/v2'] as $suffix) {
            $candidates[] = $root_url . $suffix;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Call connect endpoint with GET and fallback to POST for compatibility.
     */
    private static function request_connect_endpoint($full_url, $api_token) {
        $request_options = [
            'headers' => self::get_api_headers($api_token),
            'timeout' => 20,
        ];

        $response = wp_remote_get($full_url, $request_options);

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) !== 405) {
            return $response;
        }

        return wp_remote_post($full_url, $request_options);
    }
    
    /**
     * Generate QR Code as Base64 PNG using chillerlan/php-qrcode
     */
    public static function generate_base64($data) {
        if (!self::load_autoloader()) {
            return null;
        }
        
        $options = new \chillerlan\QRCode\QROptions([
            'version'      => 5,
            'outputType'   => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'     => \chillerlan\QRCode\QRCode::ECC_L,
            'scale'        => 6,
            'imageBase64'  => true,
        ]);
        
        $qrcode = new \chillerlan\QRCode\QRCode($options);
        return $qrcode->render($data);
    }
    
    /**
     * Get QR Code from Evolution API and generate locally
     */
    public static function fetch_and_generate($instance_name) {
        $api_url = get_option('zapwa_evolution_url');
        $api_token = get_option('zapwa_evolution_token');
        
        if (!$api_url || !$api_token || !$instance_name) {
            error_log('[ZapWA] QR Code fetch failed: Configuração incompleta');
            return ['success' => false, 'error' => 'Configuração incompleta'];
        }

        $api_candidates = self::get_api_url_candidates($api_url);
        $max_retries = 6;
        $retry_delay = 2;
        $last_error = 'QR Code não disponível';

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            foreach ($api_candidates as $candidate_url) {
                $full_url = rtrim($candidate_url, '/') . '/instance/connect/' . rawurlencode($instance_name);
                error_log('[ZapWA] Fetching QR Code - Attempt ' . $attempt . '/' . $max_retries . ' URL: ' . $full_url);

                $response = self::request_connect_endpoint($full_url, $api_token);

                if (is_wp_error($response)) {
                    $last_error = 'Erro de conexão: ' . $response->get_error_message();
                    error_log('[ZapWA] QR Code fetch error: ' . $last_error);
                    continue;
                }

                $status_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                $body = json_decode($response_body, true);

                error_log('[ZapWA] QR Code response status=' . $status_code . ' body=' . $response_body);

                if ($status_code === 404) {
                    $last_error = 'Instância/endpoint não encontrado. Verifique URL da Evolution API.';
                    continue;
                }

                if ($status_code === 401 || $status_code === 403) {
                    return ['success' => false, 'error' => 'API Key inválida (HTTP ' . $status_code . ')'];
                }

                if ($status_code >= 400) {
                    $last_error = isset($body['message']) ? $body['message'] : ('Erro HTTP ' . $status_code);
                    continue;
                }

                $payload = self::extract_qr_payload($body);
                if (!$payload) {
                    $last_error = 'Resposta sem QR Code. Aguarde alguns segundos e tente novamente.';
                    continue;
                }

                if ($payload['type'] === 'code') {
                    $qr_base64 = self::generate_base64($payload['value']);
                    if (!$qr_base64) {
                        return ['success' => false, 'error' => 'Falha ao gerar QR Code localmente (dependência ausente).'];
                    }

                    return [
                        'success' => true,
                        'qrcode_base64' => $qr_base64,
                        'code' => $payload['value'],
                        'expires_in' => 120,
                    ];
                }

                $normalized_base64 = self::normalize_base64_image($payload['value']);
                if (!$normalized_base64) {
                    $last_error = 'QR Code retornado em formato inválido.';
                    continue;
                }

                return [
                    'success' => true,
                    'qrcode_base64' => $normalized_base64,
                    'expires_in' => 120,
                ];
            }

            if ($attempt < $max_retries) {
                sleep($retry_delay);
            }
        }
        
        return ['success' => false, 'error' => $last_error . ' (após ' . $max_retries . ' tentativas)'];
    }
}
