<?php
/**
 * Instagram integration – handles incoming DM webhooks.
 *
 * @package AISalesEngine\Integrations\Instagram
 */

namespace AISalesEngine\Integrations\Instagram;

if ( ! defined( 'ABSPATH' ) ) exit;

class Instagram {

    public static function init(): void {
        add_action( 'ai_sales_engine_job_instagram_message', [ self::class, 'handle_message_job' ] );
    }

    /**
     * Send a direct message reply via the Instagram Graph API.
     *
     * @param string $recipient_id Instagram PSID.
     * @param string $text         Message text.
     * @return bool
     */
    public static function send_message( string $recipient_id, string $text ): bool {
        $settings = get_option( 'ai_sales_engine_settings', [] );
        $token    = $settings['instagram_token'] ?? '';

        if ( ! $token ) {
            return false;
        }

        $response = wp_remote_post(
            'https://graph.instagram.com/v19.0/me/messages',
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'body' => wp_json_encode( [
                    'recipient' => [ 'id' => $recipient_id ],
                    'message'   => [ 'text' => $text ],
                ] ),
                'data_format' => 'body',
            ]
        );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }

    /**
     * Handle queued instagram_message job.
     *
     * @param array $payload { recipient_id, text }.
     */
    public static function handle_message_job( array $payload ): void {
        self::send_message(
            $payload['recipient_id'] ?? '',
            $payload['text']         ?? ''
        );
    }
}
