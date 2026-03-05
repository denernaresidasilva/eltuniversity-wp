<?php
namespace ZapWA;

if (!defined('ABSPATH')) { exit; }

class Instagram {

    const GRAPH_API_VERSION = 'v18.0';

    public static function init() {
        add_action('rest_api_init', [self::class, 'register_routes']);
    }

    public static function register_routes() {
        register_rest_route('zapwa/v1', '/instagram/webhook', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [self::class, 'verify_webhook'],
                'permission_callback' => '__return_true',
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [self::class, 'receive_webhook'],
                'permission_callback' => '__return_true',
            ],
        ]);
    }

    public static function verify_webhook($request) {
        $mode         = $request->get_param('hub_mode');
        $token        = $request->get_param('hub_verify_token');
        $challenge    = $request->get_param('hub_challenge');

        $stored_token = get_option('zapwa_instagram_verify_token', '');

        if ($mode === 'subscribe' && hash_equals($stored_token, (string) $token)) {
            return rest_ensure_response((int) $challenge);
        }

        return new \WP_Error(
            'zapwa_instagram_forbidden',
            'Webhook verification failed.',
            ['status' => 403]
        );
    }

    public static function receive_webhook($request) {
        $body = $request->get_json_params();

        if (empty($body['entry']) || !is_array($body['entry'])) {
            return rest_ensure_response(['status' => 'ok']);
        }

        foreach ($body['entry'] as $entry) {
            if (empty($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];
                $field = $change['field'] ?? '';

                if ($field === 'comments') {
                    self::handle_comment($value);
                } elseif ($field === 'messages') {
                    self::handle_dm($value);
                }
            }
        }

        return rest_ensure_response(['status' => 'ok']);
    }

    private static function handle_comment($value) {
        $comment_id = sanitize_text_field($value['id'] ?? '');
        $from       = $value['from'] ?? [];
        $instagram_user_id = sanitize_text_field($from['id'] ?? '');
        $text       = sanitize_textarea_field($value['text'] ?? '');

        if (!$instagram_user_id) return;

        $contact_id = self::resolve_contact_id($instagram_user_id);

        do_action('zap_evento', [
            'event'   => 'instagram_comment',
            'user_id' => $contact_id,
            'context' => [
                'instagram_user_id' => $instagram_user_id,
                'comment_id'        => $comment_id,
                'text'              => $text,
            ],
        ]);

        if (class_exists('\ZapWA\Event_Tracker')) {
            Event_Tracker::record(
                $contact_id,
                'instagram_comment',
                $comment_id,
                'instagram',
                [
                    'instagram_user_id' => $instagram_user_id,
                    'comment_id'        => $comment_id,
                    'text'              => $text,
                ]
            );
        }

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('Instagram: comment received.', ['comment_id' => $comment_id]);
        }
    }

    private static function handle_dm($value) {
        $messages = $value['messages'] ?? [];
        if (empty($messages) || !is_array($messages)) return;

        foreach ($messages as $message) {
            $message_id        = sanitize_text_field($message['id'] ?? '');
            $instagram_user_id = sanitize_text_field($message['from'] ?? '');
            $text              = sanitize_textarea_field($message['message']['text'] ?? '');

            if (!$instagram_user_id) continue;

            $contact_id = self::resolve_contact_id($instagram_user_id);

            do_action('zap_evento', [
                'event'   => 'instagram_dm',
                'user_id' => $contact_id,
                'context' => [
                    'instagram_user_id' => $instagram_user_id,
                    'message_id'        => $message_id,
                    'text'              => $text,
                ],
            ]);

            if (class_exists('\ZapWA\Event_Tracker')) {
                Event_Tracker::record(
                    $contact_id,
                    'instagram_dm',
                    $message_id,
                    'instagram',
                    [
                        'instagram_user_id' => $instagram_user_id,
                        'message_id'        => $message_id,
                        'text'              => $text,
                    ]
                );
            }

            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('Instagram: DM received.', ['message_id' => $message_id]);
            }
        }
    }

    public static function send_dm($instagram_user_id, $message) {
        $access_token = get_option('zapwa_instagram_access_token', '');
        $page_id      = get_option('zapwa_instagram_page_id', '');

        if (empty($access_token) || empty($page_id)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('Instagram: send_dm skipped — access token or page ID not configured.');
            }
            return false;
        }

        $url = 'https://graph.facebook.com/' . self::GRAPH_API_VERSION . "/{$page_id}/messages";

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'recipient'      => ['id' => sanitize_text_field($instagram_user_id)],
                'message'        => ['text' => sanitize_textarea_field($message)],
                'messaging_type' => 'RESPONSE',
                'access_token'   => $access_token,
            ]),
        ]);

        if (is_wp_error($response)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('Instagram: send_dm failed.', ['error' => $response->get_error_message()]);
            }
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('Instagram: send_dm response.', ['http_code' => $code]);
        }

        return $code === 200;
    }

    public static function send_comment_reply($comment_id, $message) {
        $access_token = get_option('zapwa_instagram_access_token', '');

        if (empty($access_token)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('Instagram: send_comment_reply skipped — access token not configured.');
            }
            return false;
        }

        $comment_id = sanitize_text_field($comment_id);
        $url        = 'https://graph.facebook.com/' . self::GRAPH_API_VERSION . "/{$comment_id}/replies";

        $response = wp_remote_post($url, [
            'timeout' => 15,
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'message'      => sanitize_textarea_field($message),
                'access_token' => $access_token,
            ]),
        ]);

        if (is_wp_error($response)) {
            if (class_exists('\ZapWA\Logger')) {
                Logger::debug('Instagram: send_comment_reply failed.', ['error' => $response->get_error_message()]);
            }
            return false;
        }

        $code = wp_remote_retrieve_response_code($response);

        if (class_exists('\ZapWA\Logger')) {
            Logger::debug('Instagram: send_comment_reply response.', ['http_code' => $code]);
        }

        return $code === 200;
    }

    /**
     * Resolves a WP user ID from an Instagram user ID.
     * Falls back to 0 if no mapping exists.
     */
    private static function resolve_contact_id($instagram_user_id) {
        $user = get_users([
            'meta_key'   => 'zapwa_instagram_user_id',
            'meta_value' => sanitize_text_field($instagram_user_id),
            'number'     => 1,
            'fields'     => 'ids',
        ]);

        return !empty($user) ? absint($user[0]) : 0;
    }
}
