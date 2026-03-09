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
        $mode       = $webinar->mode ?? 'evergreen';
        ?>
        <div class="wrap sw-editor">
            <div class="sw-editor-header">
                <h1><?php echo $webinar ? esc_html__( 'Editar Webinar', 'smart-webinar' ) : esc_html__( 'Novo Webinar', 'smart-webinar' ); ?></h1>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-webinar-list' ) ); ?>" class="sw-back-link">
                    ← <?php esc_html_e( 'Voltar para Webinars', 'smart-webinar' ); ?>
                </a>
            </div>

            <!-- Wizard Nav -->
            <div class="sw-wizard-nav">
                <?php
                $steps = [
                    1 => [ 'icon' => '📋', 'label' => __( 'Webinar', 'smart-webinar' ) ],
                    2 => [ 'icon' => '🎥', 'label' => __( 'Vídeo',   'smart-webinar' ) ],
                    3 => [ 'icon' => '⏱', 'label' => __( 'Contador','smart-webinar' ) ],
                    4 => [ 'icon' => '💬', 'label' => __( 'Chat',    'smart-webinar' ) ],
                    5 => [ 'icon' => '🛒', 'label' => __( 'Oferta',  'smart-webinar' ) ],
                    6 => [ 'icon' => '⚡', 'label' => __( 'Eventos', 'smart-webinar' ) ],
                    7 => [ 'icon' => '🎨', 'label' => __( 'Aparência','smart-webinar' ) ],
                ];
                foreach ( $steps as $n => $step ) {
                    $active_class = $n === 1 ? ' active' : '';
                    echo '<button type="button" class="sw-step-btn' . $active_class . '" data-step="' . absint( $n ) . '">';
                    echo '<span class="sw-step-icon">' . $step['icon'] . '</span>';
                    echo '<span class="sw-step-label">' . esc_html( $n . '. ' . $step['label'] ) . '</span>';
                    echo '</button>';
                }
                ?>
            </div>

            <!-- Progress bar -->
            <div class="sw-progress-wrap">
                <div class="sw-progress-bar" style="width:14.28%"></div>
            </div>

            <form id="sw-editor-form" data-webinar-id="<?php echo absint( $webinar_id ); ?>">
                <?php wp_nonce_field( 'sw_save_webinar', 'sw_editor_nonce' ); ?>

                <!-- ══ Step 1: Informações Básicas ══ -->
                <div class="sw-step sw-card" data-step="1">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">📋</span>
                        <h2><?php esc_html_e( 'Informações Básicas', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <div class="sw-field-group">
                            <label class="sw-label" for="sw-title">
                                <?php esc_html_e( 'Título do Webinar', 'smart-webinar' ); ?> <span class="sw-required">*</span>
                            </label>
                            <input type="text" id="sw-title" name="title" class="sw-input sw-input--full" required
                                placeholder="<?php esc_attr_e( 'Ex: Como Aumentar suas Vendas em 30 dias', 'smart-webinar' ); ?>"
                                value="<?php echo esc_attr( $webinar->title ?? '' ); ?>">
                        </div>
                        <div class="sw-field-group">
                            <label class="sw-label" for="sw-description">
                                <?php esc_html_e( 'Descrição', 'smart-webinar' ); ?>
                            </label>
                            <textarea id="sw-description" name="description" rows="5" class="sw-input sw-input--full"
                                placeholder="<?php esc_attr_e( 'Descreva o conteúdo deste webinar…', 'smart-webinar' ); ?>"><?php echo esc_textarea( $webinar->description ?? '' ); ?></textarea>
                        </div>
                        <div class="sw-field-group sw-field-group--half">
                            <label class="sw-label" for="sw-status">
                                <?php esc_html_e( 'Status', 'smart-webinar' ); ?>
                            </label>
                            <select id="sw-status" name="status" class="sw-select">
                                <option value="draft" <?php selected( $webinar->status ?? 'draft', 'draft' ); ?>>
                                    <?php esc_html_e( 'Rascunho', 'smart-webinar' ); ?>
                                </option>
                                <option value="published" <?php selected( $webinar->status ?? 'draft', 'published' ); ?>>
                                    <?php esc_html_e( 'Publicado', 'smart-webinar' ); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ══ Step 2: Configuração do Vídeo ══ -->
                <div class="sw-step sw-card" data-step="2" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">🎥</span>
                        <h2><?php esc_html_e( 'Configuração do Vídeo', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <!-- Mode selector -->
                        <div class="sw-field-group">
                            <label class="sw-label"><?php esc_html_e( 'Tipo de Transmissão', 'smart-webinar' ); ?></label>
                            <div class="sw-mode-cards">
                                <?php
                                $modes = [
                                    'live'      => [ 'icon' => '🔴', 'title' => __( 'Ao Vivo', 'smart-webinar' ),   'desc' => __( 'Transmissão em tempo real via YouTube Live', 'smart-webinar' ) ],
                                    'evergreen' => [ 'icon' => '♻️', 'title' => 'Evergreen',                          'desc' => __( 'Webinar recorrente automático, sempre disponível', 'smart-webinar' ) ],
                                    'ondemand'  => [ 'icon' => '▶️', 'title' => 'On Demand',                          'desc' => __( 'Sessão agendada para data e hora específica', 'smart-webinar' ) ],
                                ];
                                foreach ( $modes as $val => $m ) :
                                    $checked = $mode === $val ? 'checked' : '';
                                ?>
                                <label class="sw-mode-card <?php echo $mode === $val ? 'selected' : ''; ?>">
                                    <input type="radio" name="mode" value="<?php echo esc_attr( $val ); ?>" <?php echo $checked; ?>>
                                    <span class="sw-mode-icon"><?php echo $m['icon']; ?></span>
                                    <span class="sw-mode-title"><?php echo esc_html( $m['title'] ); ?></span>
                                    <span class="sw-mode-desc"><?php echo esc_html( $m['desc'] ); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- ── Live mode fields ── -->
                        <div class="sw-mode-fields" data-mode="live">
                            <div class="sw-section-title"><?php esc_html_e( 'Configuração do Ao Vivo', 'smart-webinar' ); ?></div>
                            <div class="sw-field-group">
                                <label class="sw-label" for="sw-live-stream-id">
                                    <?php esc_html_e( 'ID da Live / YouTube Live Stream ID', 'smart-webinar' ); ?>
                                </label>
                                <input type="text" id="sw-live-stream-id" name="live_stream_id" class="sw-input sw-input--full"
                                    placeholder="Ex: dQw4w9WgXcQ"
                                    value="<?php echo esc_attr( $webinar->live_stream_id ?? '' ); ?>">
                                <p class="sw-hint"><?php esc_html_e( 'ID do vídeo/live do YouTube para sincronizar comentários em tempo real.', 'smart-webinar' ); ?></p>
                            </div>
                            <div class="sw-field-group">
                                <label class="sw-label" for="sw-live-api-key">
                                    <?php esc_html_e( 'YouTube API Key (para comentários)', 'smart-webinar' ); ?>
                                </label>
                                <input type="text" id="sw-live-api-key" name="live_api_key" class="sw-input sw-input--full"
                                    placeholder="AIzaSy..."
                                    value="<?php echo esc_attr( $webinar->live_api_key ?? '' ); ?>">
                                <p class="sw-hint"><?php esc_html_e( 'Deixe em branco para usar a chave global das configurações.', 'smart-webinar' ); ?></p>
                            </div>
                        </div>

                        <!-- ── Shared: URL and duration (Evergreen + On Demand) ── -->
                        <div class="sw-mode-fields sw-video-url-wrap" data-mode="evergreen">
                            <div class="sw-section-title"><?php esc_html_e( 'Configuração Evergreen', 'smart-webinar' ); ?></div>
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'URL do Vídeo', 'smart-webinar' ); ?></label>
                                <input type="url" name="video_url" class="sw-input sw-input--full sw-video-url-input"
                                    placeholder="https://www.youtube.com/watch?v=VIDEO_ID"
                                    value="<?php echo esc_attr( $webinar->video_url ?? '' ); ?>">
                                <p class="sw-hint"><?php esc_html_e( 'Aceita links do YouTube (watch, live, youtu.be ou embed).', 'smart-webinar' ); ?></p>
                            </div>
                            <div class="sw-field-group sw-field-group--half">
                                <label class="sw-label" for="sw-duration-eg">
                                    <?php esc_html_e( 'Duração do Vídeo (minutos)', 'smart-webinar' ); ?>
                                </label>
                                <input type="number" id="sw-duration-eg" name="duration_minutes" min="1" class="sw-input"
                                    value="<?php
                                        $dur_mins = $webinar && $webinar->duration > 0 ? (int) round( $webinar->duration / 60 ) : 0;
                                        echo $dur_mins > 0 ? esc_attr( $dur_mins ) : '';
                                    ?>"
                                    placeholder="Ex: 90">
                                <p class="sw-hint"><?php esc_html_e( 'Duração em minutos do vídeo evergreen.', 'smart-webinar' ); ?></p>
                            </div>
                        </div>

                        <!-- ── On Demand mode fields ── -->
                        <div class="sw-mode-fields sw-video-url-wrap" data-mode="ondemand">
                            <div class="sw-section-title"><?php esc_html_e( 'Configuração On Demand', 'smart-webinar' ); ?></div>
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'URL do Vídeo', 'smart-webinar' ); ?></label>
                                <input type="url" name="video_url" class="sw-input sw-input--full sw-video-url-input"
                                    placeholder="https://www.youtube.com/watch?v=VIDEO_ID"
                                    value="<?php echo esc_attr( $webinar->video_url ?? '' ); ?>">
                                <p class="sw-hint"><?php esc_html_e( 'Aceita links do YouTube (watch, live, youtu.be ou embed).', 'smart-webinar' ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ Step 3: Contador ══ -->
                <div class="sw-step sw-card" data-step="3" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">⏱</span>
                        <h2><?php esc_html_e( 'Configuração do Contador', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <div class="sw-field-group">
                            <label class="sw-label" for="sw-countdown-text">
                                <?php esc_html_e( 'Texto do Contador', 'smart-webinar' ); ?>
                            </label>
                            <input type="text" id="sw-countdown-text" name="countdown_text" class="sw-input sw-input--full"
                                value="<?php echo esc_attr( $webinar->countdown_text ?? 'O webinar começa em:' ); ?>">
                        </div>

                        <!-- Live & On Demand: specific datetime -->
                        <div class="sw-countdown-fields sw-countdown-live sw-countdown-ondemand">
                            <div class="sw-field-group sw-field-group--half">
                                <label class="sw-label" for="sw-schedule-datetime">
                                    📅 <?php esc_html_e( 'Data e Hora da Transmissão', 'smart-webinar' ); ?>
                                </label>
                                <input type="datetime-local" id="sw-schedule-datetime" name="schedule_datetime"
                                    class="sw-input"
                                    value="<?php echo esc_attr( $webinar ? str_replace( ' ', 'T', $webinar->schedule_datetime ?? '' ) : '' ); ?>">
                                <p class="sw-hint"><?php esc_html_e( 'O contador vai regredir até esta data e hora.', 'smart-webinar' ); ?></p>
                            </div>
                        </div>

                        <!-- Evergreen: day of week + time -->
                        <div class="sw-countdown-fields sw-countdown-evergreen">
                            <div class="sw-info-box">
                                <span>♻️</span>
                                <?php esc_html_e( 'O contador evergreen começa a contar a partir da próxima ocorrência do dia e hora selecionados. Após o fim do vídeo, reinicia automaticamente para a próxima sessão.', 'smart-webinar' ); ?>
                            </div>
                            <div class="sw-field-row">
                                <div class="sw-field-group">
                                    <label class="sw-label" for="sw-schedule-day">
                                        <?php esc_html_e( 'Dia da Semana', 'smart-webinar' ); ?>
                                    </label>
                                    <select id="sw-schedule-day" name="schedule_day" class="sw-select">
                                        <?php
                                        $days = [
                                            0 => __( 'Domingo', 'smart-webinar' ),
                                            1 => __( 'Segunda-feira', 'smart-webinar' ),
                                            2 => __( 'Terça-feira', 'smart-webinar' ),
                                            3 => __( 'Quarta-feira', 'smart-webinar' ),
                                            4 => __( 'Quinta-feira', 'smart-webinar' ),
                                            5 => __( 'Sexta-feira', 'smart-webinar' ),
                                            6 => __( 'Sábado', 'smart-webinar' ),
                                        ];
                                        $saved_day = isset( $webinar->schedule_day ) && $webinar->schedule_day !== null ? (int) $webinar->schedule_day : null;
                                        foreach ( $days as $num => $name ) :
                                        ?>
                                        <option value="<?php echo absint( $num ); ?>"
                                            <?php echo ( $saved_day !== null && $saved_day === $num ) ? 'selected' : ''; ?>>
                                            <?php echo esc_html( $name ); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="sw-field-group">
                                    <label class="sw-label" for="sw-schedule-time">
                                        <?php esc_html_e( 'Horário', 'smart-webinar' ); ?>
                                    </label>
                                    <input type="time" id="sw-schedule-time" name="schedule_time" class="sw-input"
                                        value="<?php echo esc_attr( $webinar->schedule_time ?? '19:00' ); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ Step 4: Chat ══ -->
                <div class="sw-step sw-card" data-step="4" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">💬</span>
                        <h2><?php esc_html_e( 'Chat Simulado', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <p class="sw-description"><?php esc_html_e( 'Adicione mensagens programadas que aparecem automaticamente durante o webinar. Use o temporizador para definir o momento exato de cada mensagem.', 'smart-webinar' ); ?></p>
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
                        <button type="button" id="sw-add-chat-msg" class="button sw-add-btn">
                            + <?php esc_html_e( 'Adicionar Mensagem', 'smart-webinar' ); ?>
                        </button>
                        <script type="text/html" id="sw-chat-row-template">
                            <?php self::render_chat_row(); ?>
                        </script>
                    </div>
                </div>

                <!-- ══ Step 5: Oferta ══ -->
                <div class="sw-step sw-card" data-step="5" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">🛒</span>
                        <h2><?php esc_html_e( 'Configuração da Oferta', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <div class="sw-field-row">
                            <div class="sw-field-group">
                                <label class="sw-label">
                                    <input type="checkbox" name="offer_active" value="1" <?php checked( $offer->active ?? 1, 1 ); ?>>
                                    <?php esc_html_e( 'Ativar Oferta', 'smart-webinar' ); ?>
                                </label>
                            </div>
                            <div class="sw-field-group">
                                <label class="sw-label">
                                    <input type="checkbox" name="offer_new_tab" value="1" <?php checked( $offer->open_new_tab ?? 1, 1 ); ?>>
                                    <?php esc_html_e( 'Abrir em Nova Aba', 'smart-webinar' ); ?>
                                </label>
                            </div>
                        </div>
                        <div class="sw-field-group">
                            <label class="sw-label"><?php esc_html_e( 'Texto do Botão', 'smart-webinar' ); ?></label>
                            <input type="text" name="offer_button_text" class="sw-input sw-input--full"
                                value="<?php echo esc_attr( $offer->button_text ?? 'Quero Agora!' ); ?>">
                        </div>
                        <div class="sw-field-group">
                            <label class="sw-label"><?php esc_html_e( 'URL da Oferta', 'smart-webinar' ); ?></label>
                            <input type="url" name="offer_url" class="sw-input sw-input--full"
                                value="<?php echo esc_attr( $offer->offer_url ?? '' ); ?>"
                                placeholder="https://...">
                        </div>
                        <div class="sw-field-row">
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Exibir após (segundos)', 'smart-webinar' ); ?></label>
                                <input type="number" name="offer_show_at" min="0" class="sw-input"
                                    value="<?php echo absint( $offer->show_at_seconds ?? 1800 ); ?>">
                            </div>
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Ocultar após (seg, 0=nunca)', 'smart-webinar' ); ?></label>
                                <input type="number" name="offer_hide_at" min="0" class="sw-input"
                                    value="<?php echo absint( $offer->hide_at_seconds ?? 0 ); ?>">
                            </div>
                        </div>
                        <div class="sw-field-row">
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Posição', 'smart-webinar' ); ?></label>
                                <select name="offer_position" class="sw-select">
                                    <?php
                                    $positions = [ 'bottom-left', 'bottom-center', 'bottom-right', 'top-left', 'top-center', 'top-right' ];
                                    foreach ( $positions as $p ) {
                                        echo '<option value="' . esc_attr( $p ) . '" ' . selected( $offer->button_position ?? 'bottom-center', $p, false ) . '>' . esc_html( $p ) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Animação', 'smart-webinar' ); ?></label>
                                <select name="offer_animation" class="sw-select">
                                    <?php foreach ( [ 'none', 'bounce', 'pulse', 'shake', 'slide' ] as $a ) {
                                        echo '<option value="' . esc_attr( $a ) . '" ' . selected( $offer->animation ?? 'pulse', $a, false ) . '>' . esc_html( ucfirst( $a ) ) . '</option>';
                                    } ?>
                                </select>
                            </div>
                        </div>
                        <div class="sw-field-row">
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Cor do Botão', 'smart-webinar' ); ?></label>
                                <input type="text" name="offer_bg_color" class="sw-color-picker"
                                    value="<?php echo esc_attr( $offer->bg_color ?? '#e74c3c' ); ?>">
                            </div>
                            <div class="sw-field-group">
                                <label class="sw-label"><?php esc_html_e( 'Cor do Texto', 'smart-webinar' ); ?></label>
                                <input type="text" name="offer_text_color" class="sw-color-picker"
                                    value="<?php echo esc_attr( $offer->text_color ?? '#ffffff' ); ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ══ Step 6: Eventos ══ -->
                <div class="sw-step sw-card" data-step="6" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">⚡</span>
                        <h2><?php esc_html_e( 'Eventos de Automação', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <p><?php esc_html_e( 'Os eventos abaixo são disparados automaticamente via', 'smart-webinar' ); ?> <code>do_action('zap_evento', [...])</code>:</p>
                        <ul class="sw-events-list">
                            <?php foreach ( \SmartWebinar\Events\EventDispatcher::get_valid_events() as $ev ) : ?>
                            <li><code><?php echo esc_html( $ev ); ?></code></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="sw-hint"><?php esc_html_e( 'Configure as automações no plugin ZapWA para responder a estes eventos.', 'smart-webinar' ); ?></p>
                    </div>
                </div>

                <!-- ══ Step 7: Aparência da Sala ══ -->
                <div class="sw-step sw-card" data-step="7" style="display:none">
                    <div class="sw-card-header">
                        <span class="sw-card-icon">🎨</span>
                        <h2><?php esc_html_e( 'Aparência da Sala', 'smart-webinar' ); ?></h2>
                    </div>
                    <div class="sw-card-body">
                        <p class="sw-description"><?php esc_html_e( 'Configure o visual da sala de espera. A imagem ou vídeo de fundo será exibida antes do webinar iniciar, junto com o contador regressivo.', 'smart-webinar' ); ?></p>

                        <div class="sw-field-group sw-field-group--half">
                            <label class="sw-label"><?php esc_html_e( 'Cor de Fundo da Sala', 'smart-webinar' ); ?></label>
                            <input type="text" name="room_bg_color" class="sw-color-picker"
                                value="<?php echo esc_attr( $webinar->room_bg_color ?? '#1a1a2e' ); ?>">
                            <p class="sw-hint"><?php esc_html_e( 'Cor exibida quando não há imagem ou vídeo de fundo.', 'smart-webinar' ); ?></p>
                        </div>

                        <div class="sw-media-row">
                            <!-- Background Image -->
                            <div class="sw-media-block">
                                <label class="sw-label"><?php esc_html_e( 'Imagem de Fundo', 'smart-webinar' ); ?></label>
                                <div class="sw-media-preview" id="sw-bg-image-preview">
                                    <?php if ( ! empty( $webinar->room_bg_image_url ) ) : ?>
                                    <img src="<?php echo esc_url( $webinar->room_bg_image_url ); ?>" alt="">
                                    <?php else : ?>
                                    <span class="sw-media-placeholder">🖼 <?php esc_html_e( 'Nenhuma imagem selecionada', 'smart-webinar' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="room_bg_image_url" id="sw-bg-image-url"
                                    value="<?php echo esc_attr( $webinar->room_bg_image_url ?? '' ); ?>">
                                <div class="sw-media-actions">
                                    <button type="button" class="button sw-media-upload" data-target="sw-bg-image-url" data-preview="sw-bg-image-preview" data-type="image">
                                        📷 <?php esc_html_e( 'Selecionar Imagem', 'smart-webinar' ); ?>
                                    </button>
                                    <button type="button" class="button sw-media-remove" data-target="sw-bg-image-url" data-preview="sw-bg-image-preview" <?php echo empty( $webinar->room_bg_image_url ) ? 'style="display:none"' : ''; ?>>
                                        <?php esc_html_e( 'Remover', 'smart-webinar' ); ?>
                                    </button>
                                </div>
                                <p class="sw-hint"><?php esc_html_e( 'Imagem exibida como fundo da sala de espera.', 'smart-webinar' ); ?></p>
                            </div>

                            <!-- Background Video -->
                            <div class="sw-media-block">
                                <label class="sw-label"><?php esc_html_e( 'Vídeo de Fundo', 'smart-webinar' ); ?></label>
                                <div class="sw-media-preview sw-media-preview--video" id="sw-bg-video-preview">
                                    <?php if ( ! empty( $webinar->room_bg_video_url ) ) : ?>
                                    <video src="<?php echo esc_url( $webinar->room_bg_video_url ); ?>" muted loop autoplay playsinline></video>
                                    <?php else : ?>
                                    <span class="sw-media-placeholder">🎬 <?php esc_html_e( 'Nenhum vídeo selecionado', 'smart-webinar' ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <input type="hidden" name="room_bg_video_url" id="sw-bg-video-url"
                                    value="<?php echo esc_attr( $webinar->room_bg_video_url ?? '' ); ?>">
                                <div class="sw-media-actions">
                                    <button type="button" class="button sw-media-upload" data-target="sw-bg-video-url" data-preview="sw-bg-video-preview" data-type="video">
                                        🎬 <?php esc_html_e( 'Selecionar Vídeo', 'smart-webinar' ); ?>
                                    </button>
                                    <button type="button" class="button sw-media-remove" data-target="sw-bg-video-url" data-preview="sw-bg-video-preview" <?php echo empty( $webinar->room_bg_video_url ) ? 'style="display:none"' : ''; ?>>
                                        <?php esc_html_e( 'Remover', 'smart-webinar' ); ?>
                                    </button>
                                </div>
                                <p class="sw-hint"><?php esc_html_e( 'Vídeo em loop exibido como fundo (tem prioridade sobre a imagem).', 'smart-webinar' ); ?></p>
                            </div>
                        </div>

                        <!-- Live Preview -->
                        <div class="sw-room-preview-wrap">
                            <label class="sw-label"><?php esc_html_e( 'Pré-visualização da Sala', 'smart-webinar' ); ?></label>
                            <div class="sw-room-preview" id="sw-room-preview">
                                <div class="sw-room-overlay"></div>
                                <div class="sw-room-countdown-preview">
                                    <div class="sw-room-countdown-text"><?php echo esc_html( $webinar->countdown_text ?? 'O webinar começa em:' ); ?></div>
                                    <div class="sw-room-countdown-timer">00:00:00</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="sw-editor-footer">
                    <button type="button" id="sw-prev-step" class="button sw-btn-nav" style="display:none">
                        ← <?php esc_html_e( 'Anterior', 'smart-webinar' ); ?>
                    </button>
                    <button type="button" id="sw-next-step" class="button button-primary sw-btn-nav">
                        <?php esc_html_e( 'Próximo', 'smart-webinar' ); ?> →
                    </button>
                    <button type="button" id="sw-save-webinar" class="button button-primary sw-btn-save" style="display:none">
                        💾 <?php esc_html_e( 'Salvar Webinar', 'smart-webinar' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function render_chat_row( ?object $msg = null ): void {
        $id      = $msg ? absint( $msg->id )           : 0;
        $type    = $msg ? esc_attr( $msg->message_type ) : 'scheduled';
        $name    = $msg ? esc_attr( $msg->author_name )  : '';
        $text    = $msg ? esc_textarea( $msg->message )  : '';
        $seconds = $msg ? absint( $msg->show_at )        : 0;
        // Convert seconds to MM:SS for display
        $display_time = sprintf( '%02d:%02d', intdiv( $seconds, 60 ), $seconds % 60 );
        ?>
        <div class="sw-chat-row" data-id="<?php echo $id; ?>">
            <div class="sw-chat-row-top">
                <select name="chat_type[]" class="sw-select sw-chat-type">
                    <option value="scheduled" <?php selected( $type, 'scheduled' ); ?>><?php esc_html_e( 'Programada', 'smart-webinar' ); ?></option>
                    <option value="recorded"  <?php selected( $type, 'recorded' );  ?>><?php esc_html_e( 'Gravada', 'smart-webinar' ); ?></option>
                </select>
                <input type="text" name="chat_author[]" class="sw-input sw-chat-author"
                    placeholder="<?php esc_attr_e( 'Nome do autor', 'smart-webinar' ); ?>"
                    value="<?php echo $name; ?>">
                <button type="button" class="button sw-remove-chat-row" title="<?php esc_attr_e( 'Remover mensagem', 'smart-webinar' ); ?>">✕</button>
            </div>
            <div class="sw-chat-row-timer">
                <span class="sw-timer-label">⏱ <?php esc_html_e( 'Temporizador:', 'smart-webinar' ); ?></span>
                <input type="text" class="sw-timer-display" value="<?php echo esc_attr( $display_time ); ?>"
                    placeholder="MM:SS" maxlength="5" title="<?php esc_attr_e( 'Formato MM:SS (ex: 05:30 = 5 min e 30 seg)', 'smart-webinar' ); ?>">
                <input type="hidden" name="chat_show_at[]" class="sw-timer-seconds" value="<?php echo $seconds; ?>">
                <span class="sw-timer-hint"><?php esc_html_e( 'Exibir aos', 'smart-webinar' ); ?> <strong class="sw-timer-readable"><?php echo esc_html( $display_time ); ?></strong> <?php esc_html_e( 'do webinar', 'smart-webinar' ); ?></span>
            </div>
            <textarea name="chat_message[]" rows="2" class="sw-input sw-chat-message"
                placeholder="<?php esc_attr_e( 'Mensagem…', 'smart-webinar' ); ?>"><?php echo $text; ?></textarea>
        </div>
        <?php
    }

    // ── AJAX Save ─────────────────────────────────────────────────────────────

    public static function ajax_save(): void {
        check_ajax_referer( 'sw_save_webinar', 'sw_editor_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden' );

        $mode = sanitize_key( wp_unslash( $_POST['mode'] ?? 'evergreen' ) );

        // Duration: form sends minutes for evergreen/ondemand, convert to seconds
        $duration_minutes = absint( wp_unslash( $_POST['duration_minutes'] ?? 0 ) );
        $duration_seconds = $duration_minutes * 60;

        $schedule_datetime_raw = sanitize_text_field( wp_unslash( $_POST['schedule_datetime'] ?? '' ) );
        $schedule_datetime = $schedule_datetime_raw ? str_replace( 'T', ' ', $schedule_datetime_raw ) : null;

        $webinar_data = [
            'title'             => sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) ),
            'description'       => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
            'mode'              => $mode,
            'video_url'         => esc_url_raw( wp_unslash( $_POST['video_url'] ?? '' ) ),
            'youtube_id'        => sanitize_text_field( wp_unslash( $_POST['youtube_id'] ?? '' ) ),
            'live_api_key'      => sanitize_text_field( wp_unslash( $_POST['live_api_key'] ?? '' ) ),
            'live_stream_id'    => sanitize_text_field( wp_unslash( $_POST['live_stream_id'] ?? '' ) ),
            'duration'          => $duration_seconds,
            'thumbnail_url'     => esc_url_raw( wp_unslash( $_POST['thumbnail_url'] ?? '' ) ),
            'status'            => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ),
            'schedule_datetime' => $schedule_datetime,
            'schedule_day'      => isset( $_POST['schedule_day'] ) ? absint( wp_unslash( $_POST['schedule_day'] ) ) : null,
            'schedule_time'     => sanitize_text_field( wp_unslash( $_POST['schedule_time'] ?? '' ) ) ?: null,
            'countdown_text'    => sanitize_text_field( wp_unslash( $_POST['countdown_text'] ?? '' ) ),
            'room_bg_color'     => sanitize_hex_color( wp_unslash( $_POST['room_bg_color'] ?? '#1a1a2e' ) ) ?: '#1a1a2e',
            'room_bg_image_url' => esc_url_raw( wp_unslash( $_POST['room_bg_image_url'] ?? '' ) ),
            'room_bg_video_url' => esc_url_raw( wp_unslash( $_POST['room_bg_video_url'] ?? '' ) ),
        ];

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
