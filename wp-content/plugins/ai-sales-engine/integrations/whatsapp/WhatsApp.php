<?php
/**
 * WhatsApp integration via Meta Cloud API.
 *
 * @package AISalesEngine\Integrations\WhatsApp
 */

namespace AISalesEngine\Integrations\WhatsApp;

if ( ! defined( 'ABSPATH' ) ) exit;

class WhatsApp {

    public static function init(): void {
        add_action( 'ai_sales_engine_job_whatsapp_message', [ self::class, 'handle_message_job' ] );
    }

    /**
     * Send a WhatsApp text message.
     *
     * @param string $to   Recipient phone in E.164 format (e.g. +5511999999999).
     * @param string $text Message body.
     * @return bool
     */
    public static function send_message( string $to, string $text ): bool {
        $settings = get_option( 'ai_sales_engine_settings', [] );
        $token    = $settings['whatsapp_token']    ?? '';
        $phone_id = $settings['whatsapp_phone_id'] ?? '';

        if ( ! $token || ! $phone_id ) {
            return false;
        }

        $url = "https://graph.facebook.com/v19.0/{$phone_id}/messages";

        $response = wp_remote_post(
            $url,
            [
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'body' => wp_json_encode( [
                    'messaging_product' => 'whatsapp',
                    'to'                => $to,
                    'type'              => 'text',
                    'text'              => [ 'body' => $text ],
                ] ),
                'data_format' => 'body',
            ]
        );

        return ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200;
    }

    /**
     * Handle queued whatsapp_message job.
     *
     * @param array $payload { to, text }.
     */
    public static function handle_message_job( array $payload ): void {
        self::send_message(
            $payload['to']   ?? '',
            $payload['text'] ?? ''
        );
    }
}
