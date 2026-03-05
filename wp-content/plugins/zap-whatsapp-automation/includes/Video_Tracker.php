<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Video_Tracker — REST endpoint and YouTube tracking script for video events.
 *
 * Registers POST /wp-json/zapwa/v1/event and outputs the YouTube Iframe API
 * tracking script in the page footer.
 */
class Video_Tracker {

    /** Valid event types accepted by the endpoint. */
    private static $valid_event_types = [
        'video_started',
        'video_progress',
        'video_completed',
        'link_clicked',
    ];

    /**
     * Register the REST endpoint.
     */
    public static function init() {
        add_action('rest_api_init', [self::class, 'register_rest_route']);
        add_action('wp_footer', [self::class, 'enqueue_script']);
    }

    /**
     * Register POST /wp-json/zapwa/v1/event.
     */
    public static function register_rest_route() {
        register_rest_route('zapwa/v1', '/event', [
            'methods'             => 'POST',
            'callback'            => [self::class, 'handle_event_request'],
            'permission_callback' => '__return_true', // public — no auth required
            'args'                => [
                'contact_id'  => [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 0,
                ],
                'event_type'  => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                ],
                'event_value' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                    'default'           => '',
                ],
                'metadata'    => [
                    'type'    => 'object',
                    'default' => [],
                ],
            ],
        ]);
    }

    /**
     * Handle the incoming REST event request.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public static function handle_event_request($request) {
        $event_type  = sanitize_key($request->get_param('event_type'));
        $contact_id  = absint($request->get_param('contact_id'));
        $event_value = sanitize_text_field((string) $request->get_param('event_value'));
        $metadata    = (array) ($request->get_param('metadata') ?? []);

        if (!in_array($event_type, self::$valid_event_types, true)) {
            return new \WP_Error(
                'invalid_event_type',
                sprintf(
                    /* translators: %s: received event type */
                    __('Tipo de evento inválido: %s', 'zap-whatsapp-automation'),
                    esc_html($event_type)
                ),
                ['status' => 400]
            );
        }

        // Sanitize metadata values
        $clean_metadata = [];
        foreach ($metadata as $key => $value) {
            $clean_metadata[sanitize_key($key)] = sanitize_text_field((string) $value);
        }

        if (class_exists('\ZapWA\Event_Tracker')) {
            \ZapWA\Event_Tracker::record(
                $contact_id,
                $event_type,
                $event_value,
                'video_tracker',
                $clean_metadata
            );
        }

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Output the YouTube tracking inline script in the page footer.
     * Always included on front-end pages so it is available for any video
     * injected dynamically after page load.
     */
    public static function enqueue_script() {
        if (is_admin()) {
            return;
        }

        $script = self::get_youtube_tracking_script();
        if ($script) {
            echo '<script id="zapwa-video-tracker">' . "\n" . $script . "\n" . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    /**
     * Build and return the inline JavaScript for YouTube video tracking.
     *
     * @return string JavaScript source (no <script> tags).
     */
    public static function get_youtube_tracking_script() {
        $endpoint = esc_url(rest_url('zapwa/v1/event'));

        return <<<JS
(function () {
    'use strict';

    var ENDPOINT   = '{$endpoint}';
    var contactId  = (typeof window.zapwaContactId !== 'undefined') ? parseInt(window.zapwaContactId, 10) : 0;
    var sentEvents = {};   // dedup store: "videoId|eventKey" => true

    // -------------------------------------------------------------------------
    // Utility: send a tracking event via fetch
    // -------------------------------------------------------------------------
    function sendEvent(eventType, eventValue, metadata) {
        var payload = {
            contact_id:  contactId,
            event_type:  eventType,
            event_value: String(eventValue || ''),
            metadata:    metadata || {}
        };

        fetch(ENDPOINT, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify(payload)
        }).catch(function () {
            // Silently ignore network errors — tracking must never break the page.
        });
    }

    function dedupKey(videoId, key) {
        return videoId + '|' + key;
    }

    // -------------------------------------------------------------------------
    // Progress tracking per player
    // -------------------------------------------------------------------------
    function attachProgressTracker(player, videoId) {
        var milestones  = { 25: false, 50: false, 75: false, 100: false };
        var intervalRef = null;

        function tick() {
            var duration = player.getDuration();
            if (!duration || duration <= 0) { return; }

            var currentTime = player.getCurrentTime();
            var pct = Math.floor((currentTime / duration) * 100);

            if (pct >= 100 && !milestones[100]) {
                milestones[100] = true;
                var dk = dedupKey(videoId, 'completed');
                if (!sentEvents[dk]) {
                    sentEvents[dk] = true;
                    sendEvent('video_completed', videoId, { percent: 100 });
                }
                clearInterval(intervalRef);
                return;
            }

            [25, 50, 75].forEach(function (threshold) {
                if (pct >= threshold && !milestones[threshold]) {
                    milestones[threshold] = true;
                    var dk2 = dedupKey(videoId, 'progress_' + threshold);
                    if (!sentEvents[dk2]) {
                        sentEvents[dk2] = true;
                        sendEvent('video_progress', videoId, { percent: threshold });
                    }
                }
            });
        }

        intervalRef = setInterval(tick, 2000);
        return intervalRef;
    }

    // -------------------------------------------------------------------------
    // YouTube Iframe API integration
    // -------------------------------------------------------------------------
    function onPlayerReady() { /* reserved */ }

    function onPlayerStateChange(event, videoId) {
        // YT.PlayerState.PLAYING === 1
        if (event.data === 1) {
            var dk = dedupKey(videoId, 'started');
            if (!sentEvents[dk]) {
                sentEvents[dk] = true;
                sendEvent('video_started', videoId, {});
            }
        }
    }

    function initPlayers() {
        var iframes = document.querySelectorAll(
            'iframe.zapwa-track-video, iframe[data-zapwa-video-id]'
        );

        iframes.forEach(function (iframe) {
            var videoId = iframe.getAttribute('data-zapwa-video-id') || '';

            // Extract video ID from src if not set explicitly
            if (!videoId) {
                var src = iframe.src || '';
                var match = src.match(/(?:youtube\.com\/embed\/|youtu\.be\/)([A-Za-z0-9_-]{11})/);
                if (match) { videoId = match[1]; }
            }

            if (!videoId) { return; }

            // Ensure enablejsapi is set in the iframe src
            if (iframe.src && iframe.src.indexOf('enablejsapi') === -1) {
                iframe.src += (iframe.src.indexOf('?') !== -1 ? '&' : '?') + 'enablejsapi=1';
            }

            // Capture intervalRef so it can be cleared on completion
            var intervalRef = null;

            /* eslint-disable no-new */
            new window.YT.Player(iframe, {
                events: {
                    onReady: onPlayerReady,
                    onStateChange: function (ev) {
                        onPlayerStateChange(ev, videoId);
                        if (ev.data === 1 && !intervalRef) {
                            intervalRef = attachProgressTracker(ev.target, videoId);
                        }
                        // YT.PlayerState.ENDED === 0 — ensure completed fires
                        if (ev.data === 0) {
                            var dk = dedupKey(videoId, 'completed');
                            if (!sentEvents[dk]) {
                                sentEvents[dk] = true;
                                sendEvent('video_completed', videoId, { percent: 100 });
                            }
                            if (intervalRef) {
                                clearInterval(intervalRef);
                                intervalRef = null;
                            }
                        }
                    }
                }
            });
        });
    }

    // -------------------------------------------------------------------------
    // Load YouTube Iframe API (only once)
    // -------------------------------------------------------------------------
    function loadYouTubeAPI() {
        if (document.getElementById('zapwa-yt-api')) { return; }

        var tag = document.createElement('script');
        tag.id  = 'zapwa-yt-api';
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScript = document.getElementsByTagName('script')[0];
        firstScript.parentNode.insertBefore(tag, firstScript);
    }

    // YouTube API ready callback — may already be defined by other plugins
    var previousOnYTReady = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = function () {
        if (typeof previousOnYTReady === 'function') {
            previousOnYTReady();
        }
        initPlayers();
    };

    // Kick off
    loadYouTubeAPI();
}());
JS;
    }
}
