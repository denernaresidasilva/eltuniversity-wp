<?php
/**
 * Sends outbound webhook notifications.
 *
 * @package AISalesEngine\Integrations\Webhook
 */

namespace AISalesEngine\Integrations\Webhook;

if ( ! defined( 'ABSPATH' ) ) exit;

class WebhookHandler {

    public static function init(): void {
        add_action( 'ai_sales_engine_job_webhook', [ self::class, 'handle_job' ] );
    }

    /**
     * POST JSON payload to a URL.
     *
     * @param string $url     Target URL.
     * @param array  $payload Data to send.
     * @return bool
     */
    public static function send( string $url, array $payload ): bool {
        $url = esc_url_raw( $url );
        if ( ! $url ) {
            return false;
        }

        $response = wp_remote_post(
            $url,
            [
                'headers'     => [ 'Content-Type' => 'application/json' ],
                'body'        => wp_json_encode( $payload ),
                'data_format' => 'body',
                'timeout'     => 15,
            ]
        );

        return ! is_wp_error( $response )
            && wp_remote_retrieve_response_code( $response ) >= 200
            && wp_remote_retrieve_response_code( $response ) < 300;
    }

    /**
     * Handle a queued webhook job.
     *
     * @param array $payload { url, data }.
     */
    public static function handle_job( array $payload ): void {
        self::send(
            $payload['url']  ?? '',
            $payload['data'] ?? []
        );
    }
}
