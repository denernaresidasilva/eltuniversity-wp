<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class AntiSpam {

    public static function can_send($phone) {

        $key   = 'zapwa_last_send_' . md5($phone);
        $last  = get_transient($key);

        if ($last) {
            return false;
        }

        // Permitir configurar via opção (padrão 30 segundos)
        $interval = (int) get_option('zapwa_antispam_interval', 30);
        set_transient($key, time(), $interval);
        return true;
    }
}
