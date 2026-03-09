<?php
namespace SmartWebinar\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

class Player {

    private static function normalize_video_url( string $video_url ): string {
        $video_url = trim( $video_url );
        if ( '' === $video_url ) {
            return '';
        }

        $parts = wp_parse_url( $video_url );
        if ( empty( $parts['host'] ) ) {
            return $video_url;
        }

        $host = strtolower( $parts['host'] );
        $path = $parts['path'] ?? '';

        if ( false === strpos( $host, 'youtube.com' ) && false === strpos( $host, 'youtu.be' ) ) {
            return $video_url;
        }

        if ( str_contains( $path, '/embed/' ) ) {
            return $video_url;
        }

        parse_str( $parts['query'] ?? '', $query );
        $video_id = $query['v'] ?? '';

        if ( ! $video_id && false !== strpos( $host, 'youtu.be' ) ) {
            $video_id = trim( $path, '/' );
        }

        if ( ! $video_id && str_contains( $path, '/live/' ) ) {
            $chunks = explode( '/', trim( $path, '/' ) );
            $video_id = end( $chunks );
        }

        if ( ! $video_id ) {
            return $video_url;
        }

        return 'https://www.youtube.com/embed/' . rawurlencode( $video_id );
    }

    public static function init(): void {
        add_shortcode( 'smart_webinar', [ __CLASS__, 'render_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function enqueue_assets(): void {
        global $post;
        // Enqueue only when the shortcode is present on the page
        if ( ! $post || ! has_shortcode( $post->post_content, 'smart_webinar' ) ) return;

        wp_enqueue_style( 'sw-webinar', SMART_WEBINAR_URL . 'assets/css/webinar.css', [], SMART_WEBINAR_VERSION );
        wp_enqueue_script( 'sw-countdown', SMART_WEBINAR_URL . 'assets/js/countdown.js', [], SMART_WEBINAR_VERSION, true );
        wp_enqueue_script( 'sw-tracker',   SMART_WEBINAR_URL . 'assets/js/tracker.js',   [], SMART_WEBINAR_VERSION, true );
        wp_enqueue_script( 'sw-chat',      SMART_WEBINAR_URL . 'assets/js/chat.js',      [], SMART_WEBINAR_VERSION, true );
        wp_enqueue_script( 'sw-player',    SMART_WEBINAR_URL . 'assets/js/player.js',    [ 'sw-countdown', 'sw-tracker', 'sw-chat' ], SMART_WEBINAR_VERSION, true );
    }

    public static function render_shortcode( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'smart_webinar' );
        $webinar_id = absint( $atts['id'] );
        if ( ! $webinar_id ) return '';

        $webinar = \SmartWebinar\Core\WebinarEngine::get( $webinar_id );
        if ( ! $webinar ) return '';

        $offer      = \SmartWebinar\Core\WebinarEngine::get_offer( $webinar_id );
        $user_id    = get_current_user_id();
        $session_id = \SmartWebinar\Core\SessionEngine::create( $webinar_id, $user_id );
        $nonce      = wp_create_nonce( 'sw_nonce' );

        // Fire registered event (only once per user per webinar via transient)
        $reg_key = 'sw_registered_' . $user_id . '_' . $webinar_id;
        if ( $user_id && ! get_transient( $reg_key ) ) {
            \SmartWebinar\Events\EventDispatcher::dispatch( 'webinar_registered', $user_id, [
                'webinar_id' => $webinar_id,
                'session_id' => $session_id,
            ] );
            set_transient( $reg_key, 1, 30 * DAY_IN_SECONDS );
        }

        $video_url        = self::normalize_video_url( (string) ( $webinar->video_url ?? '' ) );
        $thumbnail_url    = esc_url( (string) ( $webinar->thumbnail_url ?? '' ) );
        $scheduled_at_ts  = ! empty( $webinar->scheduled_at ) ? strtotime( $webinar->scheduled_at ) : 0;
        $start_in_seconds = max( 0, (int) ( $webinar->countdown_delay ?? 0 ) );

        if ( $scheduled_at_ts ) {
            $start_in_seconds = max( 0, $scheduled_at_ts - current_time( 'timestamp' ) );
        }

        ob_start();
        ?>
        <div id="sw-webinar-<?php echo absint( $webinar_id ); ?>"
             class="sw-webinar-wrapper"
             data-webinar-id="<?php echo absint( $webinar_id ); ?>"
             data-session-id="<?php echo esc_attr( $session_id ); ?>"
             data-mode="<?php echo esc_attr( $webinar->mode ); ?>"
             data-duration="<?php echo absint( $webinar->duration ); ?>"
             data-nonce="<?php echo esc_attr( $nonce ); ?>"
             data-rest-url="<?php echo esc_url( rest_url( 'webinar/v1/' ) ); ?>"
             data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">

            <!-- Countdown -->
            <?php if ( $webinar->mode !== 'live' ) : ?>
            <div class="sw-countdown-wrapper" id="sw-countdown-<?php echo absint( $webinar_id ); ?>" <?php if ( $thumbnail_url ) : ?>style="background-image:url('<?php echo $thumbnail_url; ?>')"<?php endif; ?>>
                <div class="sw-countdown-inner">
                    <p class="sw-countdown-label">
                        <?php echo esc_html( $webinar->countdown_text ?? __( 'O webinar começa em:', 'smart-webinar' ) ); ?>
                    </p>
                    <div class="sw-countdown-timer"
                         data-delay="<?php echo absint( $start_in_seconds ); ?>">
                        <span class="sw-cd-block"><span class="sw-cd-num" id="sw-cd-h">00</span><small><?php esc_html_e( 'horas', 'smart-webinar' ); ?></small></span>
                        <span class="sw-cd-sep">:</span>
                        <span class="sw-cd-block"><span class="sw-cd-num" id="sw-cd-m">00</span><small><?php esc_html_e( 'min', 'smart-webinar' ); ?></small></span>
                        <span class="sw-cd-sep">:</span>
                        <span class="sw-cd-block"><span class="sw-cd-num" id="sw-cd-s">00</span><small><?php esc_html_e( 'seg', 'smart-webinar' ); ?></small></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Player -->
            <div class="sw-player-wrapper" id="sw-player-wrapper-<?php echo absint( $webinar_id ); ?>"
                 <?php if ( $webinar->mode !== 'live' ) : ?>style="display:none"<?php endif; ?>>
                <div class="sw-player-container">
                    <?php if ( $video_url ) : ?>
                    <iframe
                        id="sw-video-<?php echo absint( $webinar_id ); ?>"
                        class="sw-video-iframe"
                        src="<?php echo esc_url( $video_url ); ?>?enablejsapi=1&autoplay=0&controls=<?php echo $webinar->mode === 'simulated' ? '0' : '1'; ?>&rel=0"
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                    <?php else : ?>
                    <div class="sw-no-video"><?php esc_html_e( 'Nenhum vídeo configurado.', 'smart-webinar' ); ?></div>
                    <?php endif; ?>
                    <?php if ( $webinar->mode === 'simulated' ) : ?>
                    <div class="sw-simulated-overlay" id="sw-simulated-overlay-<?php echo absint( $webinar_id ); ?>"></div>
                    <?php endif; ?>
                </div>

                <!-- Offer Button -->
                <?php if ( $offer && $offer->active ) : ?>
                <div class="sw-offer-btn-wrapper sw-offer-pos-<?php echo esc_attr( $offer->button_position ); ?>"
                     id="sw-offer-<?php echo absint( $webinar_id ); ?>"
                     style="display:none"
                     data-show-at="<?php echo absint( $offer->show_at_seconds ); ?>"
                     data-hide-at="<?php echo absint( $offer->hide_at_seconds ); ?>">
                    <a href="<?php echo esc_url( $offer->offer_url ); ?>"
                       class="sw-offer-btn sw-anim-<?php echo esc_attr( $offer->animation ); ?>"
                       style="background:<?php echo esc_attr( $offer->bg_color ); ?>;color:<?php echo esc_attr( $offer->text_color ); ?>"
                       <?php if ( $offer->open_new_tab ) : ?>target="_blank" rel="noopener noreferrer"<?php endif; ?>
                       data-offer-url="<?php echo esc_url( $offer->offer_url ); ?>">
                        <?php echo esc_html( $offer->button_text ); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- Chat -->
            <div class="sw-chat-section" id="sw-chat-<?php echo absint( $webinar_id ); ?>">
                <button type="button" class="sw-chat-toggle" data-target="sw-chat-box-<?php echo absint( $webinar_id ); ?>">
                    <?php esc_html_e( 'Ocultar chat', 'smart-webinar' ); ?>
                </button>
                <div id="sw-chat-box-<?php echo absint( $webinar_id ); ?>">
                <?php
                $chat = new Chat();
                echo $chat->render( $webinar_id, $session_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof SmartWebinar !== 'undefined') {
                SmartWebinar.init(<?php echo wp_json_encode( [
                    'webinarId'  => $webinar_id,
                    'sessionId'  => $session_id,
                    'mode'       => $webinar->mode,
                    'duration'   => (int) $webinar->duration,
                    'nonce'      => $nonce,
                    'restUrl'    => rest_url( 'webinar/v1/' ),
                    'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
                    'countdownDelay' => $start_in_seconds,
                    'offer'      => $offer ? [
                        'showAt'    => (int) $offer->show_at_seconds,
                        'hideAt'    => (int) $offer->hide_at_seconds,
                        'offerUrl'  => $offer->offer_url,
                    ] : null,
                ] ); ?>);
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
