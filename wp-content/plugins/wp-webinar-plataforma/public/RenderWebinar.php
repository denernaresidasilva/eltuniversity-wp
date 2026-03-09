<?php
namespace WebinarPlataforma\Public_;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class RenderWebinar {

    public static function init(): void {
        add_shortcode( 'webinar_player', [ self::class, 'render_player' ] );
    }

    public static function render_player( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $id   = (int) $atts['id'];

        if ( ! $id ) {
            return '<p>' . esc_html__( 'ID do webinar inválido.', 'wp-webinar-plataforma' ) . '</p>';
        }

        global $wpdb;
        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d AND status = 'publicado'",
            $id
        ) );

        if ( ! $webinar ) {
            return '<p>' . esc_html__( 'Webinar não encontrado.', 'wp-webinar-plataforma' ) . '</p>';
        }

        // Load chat messages
        $chat_mensagens = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinar_chat_mensagens
             WHERE webinar_id = %d AND tipo = 'programada'
             ORDER BY tempo ASC",
            $id
        ) );

        // Load automations
        $automacoes = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinar_automacoes
             WHERE webinar_id = %d AND ativo = 1
             ORDER BY ordem ASC",
            $id
        ) );

        wp_enqueue_style( 'wp-webinar-public', WP_WEBINAR_URL . 'assets/css/webinar.css', [], WP_WEBINAR_VERSION );
        wp_enqueue_script( 'wp-webinar-player', WP_WEBINAR_URL . 'assets/js/webinar-player.js', [], WP_WEBINAR_VERSION, true );

        wp_localize_script( 'wp-webinar-player', 'WWPlayerConfig', [
            'webinarId'       => $id,
            'videoId'         => esc_attr( $webinar->youtube_video_id ),
            'tipo'            => esc_attr( $webinar->tipo ),
            'bloquearAvanco'  => (bool) $webinar->bloquear_avanco,
            'simulacaoAtiva'  => (bool) $webinar->simulacao_ativa,
            'simulacaoContagem' => (int) $webinar->simulacao_contagem,
            'chatMensagens'   => array_map( function( $m ) {
                return [
                    'id'       => (int) $m->id,
                    'tempo'    => (int) $m->tempo,
                    'autor'    => esc_html( $m->autor ),
                    'mensagem' => esc_html( $m->mensagem ),
                ];
            }, $chat_mensagens ?: [] ),
            'automacoes'      => array_map( function( $a ) {
                $config = json_decode( $a->config, true ) ?: [];
                return [
                    'id'      => (int) $a->id,
                    'gatilho' => esc_attr( $a->gatilho ),
                    'acao'    => esc_attr( $a->acao ),
                    'config'  => $config,
                ];
            }, $automacoes ?: [] ),
            'apiUrl'          => esc_url_raw( rest_url( 'webinar/v1' ) ),
            'nonce'           => wp_create_nonce( 'wp_rest' ),
        ] );

        ob_start();
        ?>
        <div class="ww-player-wrapper" id="ww-player-<?php echo esc_attr( $id ); ?>">

            <?php if ( $webinar->simulacao_ativa ) : ?>
            <div class="ww-watching-bar">
                <span class="ww-dot"></span>
                <span class="ww-watching-count" id="ww-watching-count"><?php echo (int) $webinar->simulacao_contagem; ?></span>
                <span> pessoas assistindo agora</span>
            </div>
            <?php endif; ?>

            <div class="ww-video-container">
                <div id="ww-youtube-player"></div>
                <div class="ww-controls" id="ww-controls">
                    <button class="ww-btn-play" id="ww-play-btn" aria-label="Play/Pause">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                    </button>
                    <div class="ww-progress-bar" id="ww-progress-bar">
                        <div class="ww-progress-fill" id="ww-progress-fill"></div>
                    </div>
                    <span class="ww-time" id="ww-time">0:00 / 0:00</span>
                    <button class="ww-btn-fullscreen" id="ww-fullscreen-btn" aria-label="Tela cheia">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7 14H5v5h5v-2H7v-3zm-2-4h2V7h3V5H5v5zm12 7h-3v2h5v-5h-2v3zM14 5v2h3v3h2V5h-5z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="ww-notifications" id="ww-notifications"></div>

            <div class="ww-automation-overlays" id="ww-automation-overlays"></div>

            <div class="ww-chat-wrapper" id="ww-chat-wrapper">
                <div class="ww-chat-header">
                    <span>💬 Chat ao Vivo</span>
                </div>
                <div class="ww-chat-messages" id="ww-chat-messages"></div>
                <div class="ww-chat-input-row">
                    <input type="text" id="ww-chat-input" class="ww-chat-input" placeholder="Digite uma mensagem..." maxlength="300" />
                    <button id="ww-chat-send" class="ww-btn-chat-send">Enviar</button>
                </div>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}
