<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Helpers {

    /**
     * Busca mensagens ativas vinculadas a um evento
     */
    public static function get_messages_by_event($event_key) {

        $args = [
            'post_type'      => 'zapwa_message',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_zapwa_type',
                    'value' => 'trigger',
                ],
                [
                    'key'   => '_zapwa_event',
                    'value' => $event_key,
                ],
                [
                    'key'   => '_zapwa_active',
                    'value' => '1',
                ],
            ],
        ];

        return get_posts($args);
    }

    /**
     * Retorna telefone do usuário
     */
    public static function get_user_phone($user_id) {

        $phone = get_user_meta($user_id, 'billing_phone', true);

        if (!$phone) {
            $phone = get_user_meta($user_id, 'phone', true);
        }

        return $phone ? preg_replace('/\D/', '', $phone) : null;
    }
}
