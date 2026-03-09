<?php
namespace LeadsSaaS\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebhookService {

    public static function send( string $url, array $payload ): void {
        $result = wp_remote_post( esc_url_raw( $url ), [
            'timeout'     => 10,
            'headers'     => [ 'Content-Type' => 'application/json' ],
            'body'        => wp_json_encode( $payload ),
            'data_format' => 'body',
        ] );

        if ( is_wp_error( $result ) ) {
            error_log( '[LeadsSaaS] Webhook delivery failed to ' . $url . ': ' . $result->get_error_message() );
        }
    }
}
