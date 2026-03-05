<?php
namespace ZapWA;

class Variables {

    public static function parse($text, $payload) {

        $user_id = $payload['user_id'] ?? 0;
        $user = $user_id ? get_userdata($user_id) : null;

        $course_id = $payload['context']['course_id'] ?? 0;
        $course_author_id = $course_id ? get_post_field('post_author', $course_id) : 0;

        $vars = [
            '{user_name}'       => $user ? $user->display_name : '',
            '{user_email}'      => $user ? $user->user_email : '',
            '{user_phone}'      => $payload['phone'] ?? '',
            '{course_name}'     => $course_id ? get_the_title($course_id) : '',
            '{course_progress}' => $payload['context']['progress'] ?? '',
            '{course_author}'   => $course_author_id ? get_the_author_meta('display_name', $course_author_id) : '',
            '{course_url}'      => $course_id ? get_permalink($course_id) : '',
            '{site_name}'       => get_bloginfo('name'),
            '{site_url}'        => get_home_url(),
            '{current_date}'    => current_time('d/m/Y'),
            '{last_login}'      => isset($payload['context']['last_login'])
                                    ? (($ts = strtotime($payload['context']['last_login'])) !== false
                                        ? date_i18n('d/m/Y H:i', $ts)
                                        : '')
                                    : '',
            '{days_inactive}'   => $payload['context']['days_inactive'] ?? '',
            '{event}'           => sanitize_text_field($payload['event'] ?? ''),
            '{event_email}'     => sanitize_email(
                                    $payload['email']
                                    ?? $payload['user_email']
                                    ?? $payload['context']['email']
                                    ?? $payload['context']['user_email']
                                    ?? $payload['context']['recipient_email']
                                    ?? ($user ? $user->user_email : '')
                                  ),
        ];

        return str_replace(array_keys($vars), array_values($vars), (string) ($text ?? ''));
    }
}
