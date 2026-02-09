<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Installer {

    public static function activate() {

        // 🔹 Registrar CPT manualmente (Loader ainda não existe aqui)
        $cpt_file = ZAP_WA_PATH . 'includes/PostTypes/Message.php';

        if (file_exists($cpt_file)) {
            require_once $cpt_file;

            if (class_exists('\ZapWA\PostTypes\Message')) {
                \ZapWA\PostTypes\Message::register();
            }
        }

        // 🔹 Flush rewrite após registrar CPT
        flush_rewrite_rules();

        // 🔹 Opções padrão
        if (get_option('zap_wa_queue') === false) {
            add_option('zap_wa_queue', []);
        }

        if (get_option('zap_wa_api_url') === false) {
            add_option('zap_wa_api_url', '');
        }

        if (get_option('zap_wa_api_key') === false) {
            add_option('zap_wa_api_key', '');
        }
    }

    public static function deactivate() {

        // Limpa cron da fila
        wp_clear_scheduled_hook('zap_wa_process_queue');

        flush_rewrite_rules();
    }
}
