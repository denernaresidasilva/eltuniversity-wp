<?php
namespace SmartWebinar\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class WebinarEditor {

    public static function init(): void {
        add_action( 'wp_ajax_sw_save_webinar', [ __CLASS__, 'ajax_save' ] );
        add_action( 'wp_ajax_sw_delete_webinar', [ __CLASS__, 'ajax_delete' ] );
        add_action( 'wp_ajax_sw_save_chat_messages', [ __CLASS__, 'ajax_save_chat' ] );
    }

    public static function render(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $webinar_id = absint( $_GET['webinar_id'] ?? 0 );
        $webinar    = $webinar_id ? \SmartWebinar\Core\WebinarEngine::get( $webinar_id ) : null;
        $offer      = $webinar_id ? \SmartWebinar\Core\WebinarEngine::get_offer( $webinar_id ) : null;
        ?>
        <div class="wrap sw-editor">
            <h1><?php echo $webinar ? esc_html__( 'Editar Webinar', 'smart-webinar' ) : esc_html__( 'Novo Webinar', 'smart-webinar' ); ?></h1>

            <!-- Wizard Nav -->
            <div class="sw-wizard-nav">
                <?php
                $steps = [
                    1 => __( '1. Webinar', 'smart-webinar' ),
                    2 => __( '2. Vídeo',   'smart-webinar' ),
                    3 => __( '3. Contador','smart-webinar' ),
                    4 => __( '4. Chat',    'smart-webinar' ),
                    5 => __( '5. Oferta',  'smart-webinar' ),
                    6 => __( '6. Eventos', 'smart-webinar' ),
                ];
                foreach ( $steps as $n => $label ) {
                    echo '<button type="button" class="sw-step-btn' . ( $n === 1 ? ' active' : '' ) . '" data-step="' . absint( $n ) . '">' . esc_html( $label ) . '</button>';
                }
                ?>
            </div>

            <form id="sw-editor-form" data-webinar-id="<?php echo absint( $webinar_id ); ?>">
                <?php wp_nonce_field( 'sw_save_webinar', 'sw_editor_nonce' ); ?>

                <!-- Step 1: Basic -->
                <div class="sw-step" data-step="1">
                    <h2><?php esc_html_e( 'Informações Básicas', 'smart-webinar' ); ?></h2>
                    <table class="form-table">
                        <tr><th><?php esc_html_e( 'Título', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="title" class="regular-text" required
                                value="<?php echo esc_attr( $webinar->title ?? '' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Descrição', 'smart-webinar' ); ?></th>
                            <td><textarea name="description" rows="4" class="large-text"><?php echo esc_textarea( $webinar->description ?? '' ); ?></textarea></td></tr>
                        <tr><th><?php esc_html_e( 'Status', 'smart-webinar' ); ?></th>
                            <td>
                                <select name="status">
                                    <?php foreach ( [ 'draft', 'scheduled', 'live', 'ended' ] as $s ) : ?>
                                    <option value="<?php echo esc_attr( $s ); ?>" <?php selected( $webinar->status ?? 'draft', $s ); ?>><?php echo esc_html( ucfirst( $s ) ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                        <tr><th><?php esc_html_e( 'Agendado Para', 'smart-webinar' ); ?></th>
                            <td><input type="datetime-local" name="scheduled_at"
                                value="<?php echo esc_attr( $webinar ? str_replace( ' ', 'T', $webinar->scheduled_at ?? '' ) : '' ); ?>"></td></tr>
                    </table>
                </div>

                <!-- Step 2: Video -->
                <div class="sw-step" data-step="2" style="display:none">
                    <h2><?php esc_html_e( 'Configuração do Vídeo', 'smart-webinar' ); ?></h2>
                    <table class="form-table">
                        <tr><th><?php esc_html_e( 'Modo', 'smart-webinar' ); ?></th>
                            <td>
                                <select name="mode" id="sw-mode">
                                    <?php foreach ( [ 'live' => 'Live (YouTube)', 'simulated' => 'Simulated Live', 'replay' => 'Replay' ] as $v => $l ) : ?>
                                    <option value="<?php echo esc_attr( $v ); ?>" <?php selected( $webinar->mode ?? 'simulated', $v ); ?>><?php echo esc_html( $l ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td></tr>
                        <tr><th><?php esc_html_e( 'URL do Vídeo / YouTube Embed', 'smart-webinar' ); ?></th>
                            <td>
                                <input type="url" name="video_url" class="large-text"
                                    value="<?php echo esc_attr( $webinar->video_url ?? '' ); ?>"
                                    placeholder="https://www.youtube.com/watch?v=VIDEO_ID">
                                <p class="description"><?php esc_html_e( 'Aceita links do YouTube nos formatos watch, live, youtu.be ou embed.', 'smart-webinar' ); ?></p>
                            </td></tr>
                        <tr><th><?php esc_html_e( 'YouTube Video ID (para comentários)', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="youtube_id" class="regular-text"
                                value="<?php echo esc_attr( $webinar->youtube_id ?? '' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Duração (segundos)', 'smart-webinar' ); ?></th>
                            <td><input type="number" name="duration" min="0"
                                value="<?php echo absint( $webinar->duration ?? 0 ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Thumbnail', 'smart-webinar' ); ?></th>
                            <td><input type="url" name="thumbnail_url" class="large-text"
                                value="<?php echo esc_attr( $webinar->thumbnail_url ?? '' ); ?>"></td></tr>
                    </table>
                </div>

                <!-- Step 3: Countdown -->
                <div class="sw-step" data-step="3" style="display:none">
                    <h2><?php esc_html_e( 'Configuração do Contador', 'smart-webinar' ); ?></h2>
                    <p><?php esc_html_e( 'O contador evergreen é gerado automaticamente por sessão com base no horário de entrada do usuário.', 'smart-webinar' ); ?></p>
                    <table class="form-table">
                        <tr><th><?php esc_html_e( 'Delay antes de iniciar (segundos)', 'smart-webinar' ); ?></th>
                            <td><input type="number" name="countdown_delay" min="0" value="<?php echo absint( $webinar->countdown_delay ?? 0 ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Texto do Contador', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="countdown_text" class="regular-text"
                                value="<?php echo esc_attr( $webinar->countdown_text ?? 'O webinar começa em:' ); ?>"></td></tr>
                    </table>
                </div>

                <!-- Step 4: Chat -->
                <div class="sw-step" data-step="4" style="display:none">
                    <h2><?php esc_html_e( 'Chat Simulado', 'smart-webinar' ); ?></h2>
                    <p><?php esc_html_e( 'Adicione mensagens programadas que aparecem automaticamente durante o webinar.', 'smart-webinar' ); ?></p>
                    <div id="sw-chat-messages">
                        <?php
                        if ( $webinar_id ) {
                            global $wpdb;
                            $messages = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore
                                "SELECT * FROM {$wpdb->prefix}webinar_chat WHERE webinar_id = %d AND message_type IN ('scheduled','recorded') ORDER BY show_at ASC",
                                $webinar_id
                            ) );
                            foreach ( $messages as $msg ) {
                                self::render_chat_row( $msg );
                            }
                        }
                        ?>
                    </div>
                    <button type="button" id="sw-add-chat-msg" class="button">
                        <?php esc_html_e( '+ Adicionar Mensagem', 'smart-webinar' ); ?>
                    </button>
                    <script type="text/html" id="sw-chat-row-template">
                        <?php self::render_chat_row(); ?>
                    </script>
                </div>

                <!-- Step 5: Offer -->
                <div class="sw-step" data-step="5" style="display:none">
                    <h2><?php esc_html_e( 'Configuração da Oferta', 'smart-webinar' ); ?></h2>
                    <table class="form-table">
                        <tr><th><?php esc_html_e( 'Ativar Oferta', 'smart-webinar' ); ?></th>
                            <td><input type="checkbox" name="offer_active" value="1" <?php checked( $offer->active ?? 1, 1 ); ?>></td></tr>
                        <tr><th><?php esc_html_e( 'Texto do Botão', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="offer_button_text" class="regular-text"
                                value="<?php echo esc_attr( $offer->button_text ?? 'Quero Agora!' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'URL da Oferta', 'smart-webinar' ); ?></th>
                            <td><input type="url" name="offer_url" class="large-text"
                                value="<?php echo esc_attr( $offer->offer_url ?? '' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Exibir após (segundos)', 'smart-webinar' ); ?></th>
                            <td><input type="number" name="offer_show_at" min="0"
                                value="<?php echo absint( $offer->show_at_seconds ?? 1800 ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Ocultar após (segundos, 0 = nunca)', 'smart-webinar' ); ?></th>
                            <td><input type="number" name="offer_hide_at" min="0"
                                value="<?php echo absint( $offer->hide_at_seconds ?? 0 ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Posição', 'smart-webinar' ); ?></th>
                            <td>
                                <select name="offer_position">
                                    <?php
                                    $positions = [ 'bottom-left', 'bottom-center', 'bottom-right', 'top-left', 'top-center', 'top-right' ];
                                    foreach ( $positions as $p ) {
                                        echo '<option value="' . esc_attr( $p ) . '" ' . selected( $offer->button_position ?? 'bottom-center', $p, false ) . '>' . esc_html( $p ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </td></tr>
                        <tr><th><?php esc_html_e( 'Animação', 'smart-webinar' ); ?></th>
                            <td>
                                <select name="offer_animation">
                                    <?php foreach ( [ 'none', 'bounce', 'pulse', 'shake', 'slide' ] as $a ) {
                                        echo '<option value="' . esc_attr( $a ) . '" ' . selected( $offer->animation ?? 'pulse', $a, false ) . '>' . esc_html( $a ) . '</option>';
                                    } ?>
                                </select>
                            </td></tr>
                        <tr><th><?php esc_html_e( 'Cor do Botão', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="offer_bg_color" class="sw-color-picker"
                                value="<?php echo esc_attr( $offer->bg_color ?? '#e74c3c' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Cor do Texto', 'smart-webinar' ); ?></th>
                            <td><input type="text" name="offer_text_color" class="sw-color-picker"
                                value="<?php echo esc_attr( $offer->text_color ?? '#ffffff' ); ?>"></td></tr>
                        <tr><th><?php esc_html_e( 'Abrir em Nova Aba', 'smart-webinar' ); ?></th>
                            <td><input type="checkbox" name="offer_new_tab" value="1" <?php checked( $offer->open_new_tab ?? 1, 1 ); ?>></td></tr>
                    </table>
                </div>

                <!-- Step 6: Events -->
                <div class="sw-step" data-step="6" style="display:none">
                    <h2><?php esc_html_e( 'Eventos de Automação', 'smart-webinar' ); ?></h2>
                    <p><?php esc_html_e( 'Os eventos abaixo são disparados automaticamente via', 'smart-webinar' ); ?> <code>do_action('zap_evento', [...])</code>:</p>
                    <ul class="sw-events-list">
                        <?php foreach ( \SmartWebinar\Events\EventDispatcher::get_valid_events() as $ev ) : ?>
                        <li><code><?php echo esc_html( $ev ); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                    <p><?php esc_html_e( 'Configure as automações no plugin ZapWA para responder a estes eventos.', 'smart-webinar' ); ?></p>
                </div>

                <div class="sw-editor-footer">
                    <button type="button" id="sw-prev-step" class="button" style="display:none">
                        <?php esc_html_e( '← Anterior', 'smart-webinar' ); ?>
                    </button>
                    <button type="button" id="sw-next-step" class="button button-primary">
                        <?php esc_html_e( 'Próximo →', 'smart-webinar' ); ?>
                    </button>
                    <button type="button" id="sw-save-webinar" class="button button-primary" style="display:none">
                        <?php esc_html_e( 'Salvar Webinar', 'smart-webinar' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_chat_row( ?object $msg = null ): void {
        $id   = $msg ? absint( $msg->id )          : 0;
        $type = $msg ? esc_attr( $msg->message_type ) : 'scheduled';
        $name = $msg ? esc_attr( $msg->author_name )  : '';
        $text = $msg ? esc_textarea( $msg->message )  : '';
        $time = $msg ? absint( $msg->show_at )        : 0;
        ?>
        <div class="sw-chat-row" data-id="<?php echo $id; ?>">
            <select name="chat_type[]">
                <option value="scheduled" <?php selected( $type, 'scheduled' ); ?>><?php esc_html_e( 'Programada', 'smart-webinar' ); ?></option>
                <option value="recorded"  <?php selected( $type, 'recorded' );  ?>><?php esc_html_e( 'Gravada', 'smart-webinar' ); ?></option>
            </select>
            <input type="text" name="chat_author[]" placeholder="<?php esc_attr_e( 'Nome', 'smart-webinar' ); ?>" value="<?php echo $name; ?>">
            <input type="number" name="chat_show_at[]" placeholder="<?php esc_attr_e( 'Exibir em (seg)', 'smart-webinar' ); ?>" value="<?php echo $time; ?>" min="0">
            <textarea name="chat_message[]" rows="2" placeholder="<?php esc_attr_e( 'Mensagem', 'smart-webinar' ); ?>"><?php echo $text; ?></textarea>
            <button type="button" class="button sw-remove-chat-row">✕</button>
        </div>
        <?php
    }

    // ── AJAX Save ─────────────────────────────────────────────────────────────

    public static function ajax_save(): void {
        check_ajax_referer( 'sw_save_webinar', 'sw_editor_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $webinar_data = [
            'title'           => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'description'     => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'mode'            => sanitize_key( wp_unslash( $_POST['mode'] ?? 'simulated' ) ),
            'video_url'       => esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) ),
            'youtube_id'      => sanitize_text_field( wp_unslash( $_POST['youtube_id'] ?? '' ) ),
            'duration'        => absint( wp_unslash( $_POST['duration'] ?? 0 ) ),
            'thumbnail_url'   => esc_url_raw( wp_unslash( $_POST['thumbnail_url'] ?? '' ) ),
            'status'          => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ),
            'scheduled_at'    => sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ?? '' ) ),
            'countdown_delay' => absint( wp_unslash( $_POST['countdown_delay'] ?? 0 ) ),
            'countdown_text'  => sanitize_text_field( wp_unslash( $_POST['countdown_text'] ?? '' ) ),
        ];
        $webinar_data['scheduled_at'] = $webinar_data['scheduled_at']
            ? str_replace( 'T', ' ', $webinar_data['scheduled_at'] )
            : null;

        $webinar_id = absint( wp_unslash( $_POST['webinar_id'] ?? 0 ) );

        if ( $webinar_id ) {
            \SmartWebinar\Core\WebinarEngine::update( $webinar_id, $webinar_data );
        } else {
            $webinar_id = \SmartWebinar\Core\WebinarEngine::create( $webinar_data );
            if ( ! $webinar_id ) wp_send_json_error( 'Could not create webinar' );
        }

        // Save offer
        $offer_data = [
            'active'           => absint( wp_unslash( $_POST['offer_active'] ?? 0 ) ),
            'button_text'      => sanitize_text_field( wp_unslash( $_POST['offer_button_text'] ?? 'Quero Agora!' ) ),
            'offer_url'        => esc_url_raw( wp_unslash( $_POST['offer_url'] ?? '' ) ),
            'show_at_seconds'  => absint( wp_unslash( $_POST['offer_show_at'] ?? 1800 ) ),
            'hide_at_seconds'  => absint( wp_unslash( $_POST['offer_hide_at'] ?? 0 ) ),
            'button_position'  => sanitize_text_field( wp_unslash( $_POST['offer_position'] ?? 'bottom-center' ) ),
            'animation'        => sanitize_key( wp_unslash( $_POST['offer_animation'] ?? 'pulse' ) ),
            'bg_color'         => sanitize_hex_color( wp_unslash( $_POST['offer_bg_color'] ?? '#e74c3c' ) ) ?: '#e74c3c',
            'text_color'       => sanitize_hex_color( wp_unslash( $_POST['offer_text_color'] ?? '#ffffff' ) ) ?: '#ffffff',
            'open_new_tab'     => absint( wp_unslash( $_POST['offer_new_tab'] ?? 0 ) ),
        ];
        \SmartWebinar\Core\WebinarEngine::save_offer( $webinar_id, $offer_data );

        // Save chat messages inline (same request as the main save)
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
            'webinar_id'   => $webinar_id,
            'message_type' => 'scheduled',
        ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
            'webinar_id'   => $webinar_id,
            'message_type' => 'recorded',
        ] );

        $chat_types    = array_map( 'sanitize_key',            (array) ( $_POST['chat_type']    ?? [] ) );
        $chat_authors  = array_map( 'sanitize_text_field',     array_map( 'wp_unslash', (array) ( $_POST['chat_author']   ?? [] ) ) );
        $chat_times    = array_map( 'absint',                  (array) ( $_POST['chat_show_at'] ?? [] ) );
        $chat_messages = array_map( 'sanitize_textarea_field', array_map( 'wp_unslash', (array) ( $_POST['chat_message']  ?? [] ) ) );

        foreach ( $chat_messages as $i => $chat_msg ) {
            if ( ! $chat_msg ) continue;
            $wpdb->insert( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
                'webinar_id'   => $webinar_id,
                'session_id'   => '',
                'user_id'      => 0,
                'message_type' => $chat_types[ $i ] ?? 'scheduled',
                'author_name'  => $chat_authors[ $i ] ?? 'Host',
                'message'      => $chat_msg,
                'show_at'      => $chat_times[ $i ] ?? 0,
            ] );
        }

        wp_send_json_success( [ 'webinar_id' => $webinar_id ] );
    }

    public static function ajax_delete(): void {
        check_ajax_referer( 'sw_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );
        $webinar_id = absint( wp_unslash( $_POST['webinar_id'] ?? 0 ) );
        $ok = $webinar_id ? \SmartWebinar\Core\WebinarEngine::delete( $webinar_id ) : false;
        $ok ? wp_send_json_success() : wp_send_json_error( 'Not found' );
    }

    public static function ajax_save_chat(): void {
        check_ajax_referer( 'sw_save_webinar', 'sw_editor_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        global $wpdb;
        $webinar_id = absint( wp_unslash( $_POST['webinar_id'] ?? 0 ) );
        if ( ! $webinar_id ) wp_send_json_error( 'Missing webinar_id' );

        // Delete existing scheduled/recorded messages for this webinar
        $wpdb->delete( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
            'webinar_id'   => $webinar_id,
            'message_type' => 'scheduled',
        ] );
        $wpdb->delete( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
            'webinar_id'   => $webinar_id,
            'message_type' => 'recorded',
        ] );

        $types    = array_map( 'sanitize_key',             (array) ( $_POST['chat_type']    ?? [] ) );
        $authors  = array_map( 'sanitize_text_field',      array_map( 'wp_unslash', (array) ( $_POST['chat_author']   ?? [] ) ) );
        $times    = array_map( 'absint',                   (array) ( $_POST['chat_show_at'] ?? [] ) );
        $messages = array_map( 'sanitize_textarea_field',  array_map( 'wp_unslash', (array) ( $_POST['chat_message']  ?? [] ) ) );

        foreach ( $messages as $i => $msg ) {
            if ( ! $msg ) continue;
            $wpdb->insert( $wpdb->prefix . 'webinar_chat', [ // phpcs:ignore
                'webinar_id'   => $webinar_id,
                'session_id'   => '',
                'user_id'      => 0,
                'message_type' => $types[ $i ] ?? 'scheduled',
                'author_name'  => $authors[ $i ] ?? 'Host',
                'message'      => $msg,
                'show_at'      => $times[ $i ] ?? 0,
            ] );
        }
        wp_send_json_success();
    }
}
