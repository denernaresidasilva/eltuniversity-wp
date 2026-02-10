<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Dispatcher {

    public static function dispatch($event_key, $user_id, $context = []) {

        if (empty($event_key) || empty($user_id)) {
            return;
        }

        if (!is_array($context)) {
            $context = [];
        }

        $payload = [
            'event'     => sanitize_text_field($event_key),
            'user_id'   => absint($user_id),
            'context'   => $context,
            'timestamp' => time(),
        ];

        // ✅ LOG INTERNO (AGORA VAI APARECER)
        if (class_exists(__NAMESPACE__ . '\\Logger')) {
            try {
                Logger::log(
                    $event_key,
                    $user_id,
                    $context
                );
            } catch (\Exception $e) {
                // Silently fails to avoid breaking the site
                error_log('ZAP Events Logger Error: ' . $e->getMessage());
            }
        }

        // 🔥 DISPARA PARA PLUGINS OUVINTES
        do_action('zap_evento', $payload);
    }
}
