<?php
namespace SmartWebinar\Integrations;

if ( ! defined( 'ABSPATH' ) ) exit;

class YouTubeAPI {

    public static function init(): void {
        add_action( 'wp_ajax_sw_load_yt_comments',        [ __CLASS__, 'ajax_load_comments' ] );
        add_action( 'wp_ajax_nopriv_sw_load_yt_comments', [ __CLASS__, 'ajax_load_comments' ] );
    }

    public static function get_comments( string $video_id, int $max = 50 ): array {
        $settings = get_option( 'smart_webinar_settings', [] );
        $api_key  = $settings['youtube_api_key'] ?? '';
        if ( ! $api_key || ! $video_id ) return [];

        $cache_key = 'sw_yt_comments_' . md5( $video_id );
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return (array) $cached;

        $url = add_query_arg( [
            'part'       => 'snippet',
            'videoId'    => rawurlencode( $video_id ),
            'maxResults' => min( $max, 100 ),
            'key'        => rawurlencode( $api_key ),
            'order'      => 'time',
        ], 'https://www.googleapis.com/youtube/v3/commentThreads' );

        $response = wp_remote_get( $url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $response ) ) return [];

        $body  = wp_remote_retrieve_body( $response );
        $data  = json_decode( $body, true );
        $items = $data['items'] ?? [];

        $comments = [];
        foreach ( $items as $item ) {
            $snippet   = $item['snippet']['topLevelComment']['snippet'] ?? [];
            $comments[] = [
                'author_name'   => sanitize_text_field( $snippet['authorDisplayName'] ?? 'User' ),
                'author_avatar' => esc_url_raw( $snippet['authorProfileImageUrl'] ?? '' ),
                'message'       => sanitize_textarea_field( $snippet['textDisplay'] ?? '' ),
                'published_at'  => sanitize_text_field( $snippet['publishedAt'] ?? '' ),
            ];
        }

        set_transient( $cache_key, $comments, 6 * HOUR_IN_SECONDS );
        return $comments;
    }

    public static function ajax_load_comments(): void {
        check_ajax_referer( 'sw_nonce', 'nonce' );
        $video_id = sanitize_text_field( wp_unslash( $_POST['video_id'] ?? '' ) );
        $comments = self::get_comments( $video_id );
        wp_send_json_success( $comments );
    }
}
