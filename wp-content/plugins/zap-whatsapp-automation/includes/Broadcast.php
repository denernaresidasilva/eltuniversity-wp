<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Broadcast {

    public static function dispatch($message_id) {

        $interval = (int) get_post_meta($message_id, '_zap_interval', true);
        if ($interval <= 0) {
            $interval = 30;
        }

        $users = get_users([
            'meta_key'     => 'zap_phone',
            'meta_compare' => '!=',
            'meta_value'   => '',
            'fields'       => ['ID']
        ]);

        $delay = 0;

        foreach ($users as $user) {

            $phone = get_user_meta($user->ID, 'zap_phone', true);
            if (!$phone) continue;

            Queue::add([
                'type'       => 'broadcast',
                'user_id'    => $user->ID,
                'phone'      => $phone,
                'message_id' => $message_id,
                'event'      => 'broadcast',
                'context'    => [],
                'delay'      => $delay,
            ]);

            $delay += $interval;
        }
    }
}
