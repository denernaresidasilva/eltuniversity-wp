<?php
namespace ZapWA;

class Variables {

    public static function parse($text, $payload) {

        $user_id = $payload['user_id'] ?? 0;
        $user = $user_id ? get_userdata($user_id) : null;

        $vars = [
            '{user_name}'   => $user ? $user->display_name : '',
            '{user_email}'  => $user ? $user->user_email : '',
            '{user_phone}'  => $payload['phone'] ?? '',
            '{course_name}' => isset($payload['context']['course_id'])
                ? get_the_title($payload['context']['course_id'])
                : '',
            '{course_progress}' => $payload['context']['progress'] ?? '',
        ];

        return str_replace(array_keys($vars), array_values($vars), $text);
    }
}
