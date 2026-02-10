<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class QRCodeGenerator {
    
    /**
     * Generate QR Code as Base64 PNG using chillerlan/php-qrcode
     */
    public static function generate_base64($data) {
        // Load composer autoloader
        $autoload = plugin_dir_path(__FILE__) . '../vendor/autoload.php';
        if (!file_exists($autoload)) {
            return null;
        }
        require_once $autoload;
        
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
            return ['success' => false, 'error' => 'Configuração incompleta'];
        }
        
        $response = wp_remote_get(
            rtrim($api_url, '/') . '/instance/connect/' . $instance_name,
            [
                'headers' => ['apikey' => $api_token],
                'timeout' => 15,
            ]
        );
        
        if (is_wp_error($response)) {
            return ['success' => false, 'error' => $response->get_error_message()];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['code'])) {
            // Gerar QR Code localmente
            $qr_base64 = self::generate_base64($body['code']);
            
            return [
                'success' => true,
                'qrcode_base64' => $qr_base64,
                'code' => $body['code'],
                'expires_in' => 120,
            ];
        }
        
        if (isset($body['qrcode']['base64'])) {
            return [
                'success' => true,
                'qrcode_base64' => $body['qrcode']['base64'],
                'expires_in' => 120,
            ];
        }
        
        return ['success' => false, 'error' => 'QR Code não disponível'];
    }
}
