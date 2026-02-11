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
        
        // Retry logic - tentar até 3 vezes
        $max_retries = 3;
        $retry_delay = 2; // segundos
        
        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            error_log('[ZapWA] Fetching QR Code - Attempt ' . $attempt . '/' . $max_retries);
            
            $response = wp_remote_get(
                rtrim($api_url, '/') . '/instance/connect/' . $instance_name,
                [
                    'headers' => ['apikey' => $api_token],
                    'timeout' => 15,
                ]
            );
            
            if (is_wp_error($response)) {
                error_log('[ZapWA] QR Code fetch error: ' . $response->get_error_message());
                if ($attempt < $max_retries) {
                    sleep($retry_delay);
                    continue;
                }
                return ['success' => false, 'error' => 'Erro de conexão: ' . $response->get_error_message()];
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            error_log('[ZapWA] QR Code Response (Status ' . $status_code . '): ' . print_r($body, true));
            
            if ($status_code === 404) {
                return ['success' => false, 'error' => 'Instância não encontrada. Por favor, crie a instância primeiro.'];
            }
            
            if ($status_code >= 400) {
                $error_msg = isset($body['message']) ? $body['message'] : 'Erro HTTP ' . $status_code;
                if ($attempt < $max_retries) {
                    sleep($retry_delay);
                    continue;
                }
                return ['success' => false, 'error' => $error_msg];
            }
            
            if (isset($body['code'])) {
                // Gerar QR Code localmente
                $qr_base64 = self::generate_base64($body['code']);
                
                error_log('[ZapWA] QR Code generated successfully');
                
                return [
                    'success' => true,
                    'qrcode_base64' => $qr_base64,
                    'code' => $body['code'],
                    'expires_in' => 120,
                ];
            }
            
            if (isset($body['qrcode']['base64'])) {
                error_log('[ZapWA] QR Code fetched successfully');
                
                return [
                    'success' => true,
                    'qrcode_base64' => $body['qrcode']['base64'],
                    'expires_in' => 120,
                ];
            }
            
            if ($attempt < $max_retries) {
                error_log('[ZapWA] QR Code not ready yet, retrying...');
                sleep($retry_delay);
            }
        }
        
        return ['success' => false, 'error' => 'QR Code não disponível após ' . $max_retries . ' tentativas'];
    }
}
