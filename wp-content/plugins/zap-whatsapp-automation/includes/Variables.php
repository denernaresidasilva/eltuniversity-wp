<?php
namespace ZapWA;

class Variables {

    public static function parse($text, $payload) {

        $user = get_userdata($payload['user_id']);

        $vars = [
            '{user_name}'   => $user->display_name ?? '',
            '{user_email}'  => $user->user_email ?? '',
            '{user_phone}'  => $payload['phone'],
            '{course_name}' => isset($payload['context']['course_id'])
                ? get_the_title($payload['context']['course_id'])
                : '',
            '{course_progress}' => $payload['context']['progress'] ?? '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}
