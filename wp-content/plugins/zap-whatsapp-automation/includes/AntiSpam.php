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

        // 1 mensagem a cada 30 segundos por número
        set_transient($key, time(), 30);
        return true;
    }
}
