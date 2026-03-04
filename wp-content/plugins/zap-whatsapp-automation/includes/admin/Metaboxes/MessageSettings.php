<?php
namespace ZapWA\Admin\Metaboxes;

if (!defined('ABSPATH')) exit;

class MessageSettings {

    public static function init() {
        add_action('add_meta_boxes', [self::class, 'register']);
        add_action('save_post_zapwa_message', [self::class, 'save']);
    }

    public static function register() {

        add_meta_box(
            'zapwa_message_settings',
            'Configurações da Automação',
            [self::class, 'render'],
            'zapwa_message',
            'normal',
            'high'
        );
    }

    public static function render($post) {

        wp_nonce_field('zapwa_message_settings', 'zapwa_nonce');

        $type             = get_post_meta($post->ID, '_zapwa_type', true);
        $event            = get_post_meta($post->ID, '_zapwa_event', true);
        $delay            = get_post_meta($post->ID, '_zapwa_delay', true);
        $active           = get_post_meta($post->ID, '_zapwa_active', true);
        $broadcast_target = get_post_meta($post->ID, '_zapwa_broadcast_target', true);

        $email_enabled = get_post_meta($post->ID, 'zapwa_email_enabled', true);
        $email_subject = get_post_meta($post->ID, 'zapwa_email_subject', true);
        $email_body    = get_post_meta($post->ID, 'zapwa_email_body', true);
        $email_is_html = get_post_meta($post->ID, 'zapwa_email_is_html', true);

        $events       = apply_filters('zap_tutor_events_list', []);
        $is_broadcast = ($type === 'broadcast');
        ?>

        <input type="hidden" name="zapwa_type" id="zapwa_type" value="<?php echo esc_attr($type ?: 'trigger'); ?>">

        <div class="zapwa-ms-layout">

            <!-- ===== MAIN COLUMN ===== -->
            <div class="zapwa-ms-col-main">

                <!-- TYPE SELECTOR -->
                <div class="zapwa-ms-section">
                    <div class="zapwa-ms-section-head">
                        <span>⚡</span> Tipo de Mensagem
                    </div>
                    <div class="zapwa-ms-section-body">
                        <div class="zapwa-type-tabs">
                            <button type="button" class="zapwa-type-tab <?php echo !$is_broadcast ? 'active' : ''; ?>" data-type="trigger">
                                🎯 Gatilho (Automação)
                            </button>
                            <button type="button" class="zapwa-type-tab <?php echo $is_broadcast ? 'active' : ''; ?>" data-type="broadcast">
                                📢 Broadcast
                            </button>
                        </div>
                        <p class="zapwa-type-desc" id="desc-trigger"<?php echo $is_broadcast ? ' style="display:none"' : ''; ?>>
                            Dispara automaticamente quando um evento específico ocorre na plataforma.
                        </p>
                        <p class="zapwa-type-desc" id="desc-broadcast"<?php echo !$is_broadcast ? ' style="display:none"' : ''; ?>>
                            Envio manual para múltiplos destinatários cadastrados na plataforma.
                        </p>
                    </div>
                </div>

                <!-- TRIGGER CONFIG -->
                <div class="zapwa-ms-section" id="config-trigger"<?php echo $is_broadcast ? ' style="display:none"' : ''; ?>>
                    <div class="zapwa-ms-section-head">
                        <span>🎯</span> Evento Gatilho
                    </div>
                    <div class="zapwa-ms-section-body">
                        <label class="zapwa-ms-field-label">Selecione o evento que dispara a mensagem:</label>
                        <select name="zapwa_event" class="zapwa-ms-select">
                            <option value="">— Selecione um evento —</option>
                            <?php foreach ($events as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($event, $key); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="zapwa-ms-hint">A mensagem será enviada automaticamente quando este evento ocorrer.</p>
                    </div>
                </div>

                <!-- BROADCAST CONFIG -->
                <div class="zapwa-ms-section" id="config-broadcast"<?php echo !$is_broadcast ? ' style="display:none"' : ''; ?>>
                    <div class="zapwa-ms-section-head">
                        <span>📢</span> Configuração do Broadcast
                    </div>
                    <div class="zapwa-ms-section-body">
                        <div class="zapwa-ms-field-row">
                            <label class="zapwa-ms-field-label">Destinatários:</label>
                            <select name="zapwa_broadcast_target" class="zapwa-ms-select">
                                <option value="all_students" <?php selected($broadcast_target, 'all_students'); ?>>👨‍🎓 Todos os Alunos</option>
                                <option value="active_courses" <?php selected($broadcast_target, 'active_courses'); ?>>📚 Alunos com Cursos Ativos</option>
                                <option value="inactive_students" <?php selected($broadcast_target, 'inactive_students'); ?>>💤 Alunos Inativos (7+ dias sem acessar)</option>
                                <option value="completed_courses" <?php selected($broadcast_target, 'completed_courses'); ?>>🎓 Alunos que Completaram Cursos</option>
                                <option value="specific_course" <?php selected($broadcast_target, 'specific_course'); ?>>🎯 Alunos de um Curso Específico</option>
                            </select>
                        </div>
                        <div class="zapwa-ms-field-row">
                            <label class="zapwa-ms-field-label">Delay entre envios (segundos):</label>
                            <div class="zapwa-ms-inline-row">
                                <input type="number"
                                       name="zapwa_delay"
                                       value="<?php echo esc_attr($delay ?: 5); ?>"
                                       min="1"
                                       max="300"
                                       class="zapwa-ms-number-input">
                                <span class="zapwa-ms-hint">Recomendado: 5–10 s para evitar bloqueio</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- VARIABLES -->
                <div class="zapwa-ms-section">
                    <div class="zapwa-ms-section-head">
                        <span>🏷️</span> Variáveis — clique para copiar
                    </div>
                    <div class="zapwa-ms-section-body">
                        <div class="zapwa-vars">
                            <span class="zapwa-var" data-var="{user_name}">{user_name}</span>
                            <span class="zapwa-var" data-var="{user_email}">{user_email}</span>
                            <span class="zapwa-var" data-var="{user_phone}">{user_phone}</span>
                            <span class="zapwa-var" data-var="{course_name}">{course_name}</span>
                            <span class="zapwa-var" data-var="{course_progress}">{course_progress}</span>
                            <span class="zapwa-var" data-var="{course_author}">{course_author}</span>
                            <span class="zapwa-var" data-var="{course_url}">{course_url}</span>
                            <span class="zapwa-var" data-var="{site_name}">{site_name}</span>
                            <span class="zapwa-var" data-var="{site_url}">{site_url}</span>
                            <span class="zapwa-var" data-var="{current_date}">{current_date}</span>
                            <span class="zapwa-var" data-var="{last_login}">{last_login}</span>
                            <span class="zapwa-var" data-var="{days_inactive}">{days_inactive}</span>
                            <span class="zapwa-var" data-var="{event}">{event}</span>
                            <span class="zapwa-var" data-var="{event_email}">{event_email}</span>
                        </div>
                    </div>
                </div>

                <!-- EMAIL SECTION -->
                <div class="zapwa-ms-section">
                    <div class="zapwa-ms-section-head zapwa-ms-section-head--green">
                        <span>✉️</span> E-mail Opcional
                    </div>
                    <div class="zapwa-ms-section-body">
                        <div class="zapwa-ms-toggle-row">
                            <label class="zapwa-switch">
                                <input type="checkbox"
                                       name="zapwa_email_enabled"
                                       id="zapwa_email_enabled"
                                       value="1"
                                       <?php checked($email_enabled, '1'); ?>>
                                <span class="slider"></span>
                            </label>
                            <label for="zapwa_email_enabled" class="zapwa-ms-toggle-text">Enviar e-mail junto com o WhatsApp</label>
                        </div>

                        <div id="zapwa-email-fields" class="zapwa-ms-email-fields"<?php echo $email_enabled ? '' : ' style="display:none"'; ?>>
                            <div class="zapwa-ms-field-row">
                                <label for="zapwa_email_subject" class="zapwa-ms-field-label">Assunto do e-mail</label>
                                <input type="text"
                                       id="zapwa_email_subject"
                                       name="zapwa_email_subject"
                                       value="<?php echo esc_attr($email_subject); ?>"
                                       class="zapwa-ms-text-input"
                                       placeholder="Ex: Bem-vindo, {user_name}!">
                            </div>
                            <div class="zapwa-ms-field-row">
                                <label for="zapwa_email_body" class="zapwa-ms-field-label">
                                    Corpo do e-mail
                                    <span class="zapwa-ms-hint-inline"><?php esc_html_e( '— suporta HTML', 'zap-whatsapp-automation' ); ?></span>
                                </label>
                                <textarea id="zapwa_email_body"
                                          name="zapwa_email_body"
                                          rows="10"
                                          class="zapwa-ms-textarea"
                                          placeholder="Escreva o corpo do e-mail. HTML e variáveis como {user_name} são suportados."><?php echo esc_textarea($email_body); ?></textarea>
                            </div>
                            <!-- Variables below email editor -->
                            <div class="zapwa-ms-email-vars">
                                <span class="zapwa-ms-email-vars__label">🏷️ Variáveis (clique para copiar):</span>
                                <div class="zapwa-vars zapwa-vars--email">
                                    <span class="zapwa-var" data-var="{user_name}">{user_name}</span>
                                    <span class="zapwa-var" data-var="{user_email}">{user_email}</span>
                                    <span class="zapwa-var" data-var="{user_phone}">{user_phone}</span>
                                    <span class="zapwa-var" data-var="{course_name}">{course_name}</span>
                                    <span class="zapwa-var" data-var="{course_progress}">{course_progress}</span>
                                    <span class="zapwa-var" data-var="{course_url}">{course_url}</span>
                                    <span class="zapwa-var" data-var="{site_name}">{site_name}</span>
                                    <span class="zapwa-var" data-var="{site_url}">{site_url}</span>
                                    <span class="zapwa-var" data-var="{current_date}">{current_date}</span>
                                    <span class="zapwa-var" data-var="{last_login}">{last_login}</span>
                                    <span class="zapwa-var" data-var="{days_inactive}">{days_inactive}</span>
                                    <span class="zapwa-var" data-var="{event_email}">{event_email}</span>
                                </div>
                            </div>
                            <div class="zapwa-ms-toggle-row">
                                <label class="zapwa-switch">
                                    <input type="checkbox"
                                           name="zapwa_email_is_html"
                                           id="zapwa_email_is_html"
                                           value="1"
                                           <?php checked($email_is_html, '1'); ?>>
                                    <span class="slider"></span>
                                </label>
                                <label for="zapwa_email_is_html" class="zapwa-ms-toggle-text">Enviar como HTML</label>
                            </div>
                            <div class="zapwa-ms-preview-btn-row">
                                <button type="button"
                                        id="zapwa-email-preview-btn"
                                        class="zapwa-btn zapwa-btn-secondary">
                                    &#x1F441; Preview do E-mail
                                </button>
                                <span class="zapwa-ms-hint">Veja como o e-mail ficará antes de salvar</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIVE TOGGLE — always last, prominent -->
                <div class="zapwa-ms-active-row zapwa-ms-active-row--prominent">
                    <div class="zapwa-ms-active-label">
                        <span class="zapwa-ms-active-icon">✅</span>
                        <div>
                            <strong>Mensagem Ativa</strong>
                            <div class="zapwa-ms-hint">Ativa o envio automático desta mensagem</div>
                        </div>
                    </div>
                    <label class="zapwa-switch zapwa-switch--lg">
                        <input type="checkbox" name="zapwa_active" value="1" <?php checked($active, '1'); ?>>
                        <span class="slider"></span>
                    </label>
                </div>

            <!-- ===== SIDEBAR COLUMN ===== -->
            <div class="zapwa-ms-col-side">

                <!-- WhatsApp Preview -->
                <div class="zapwa-ms-preview-card" id="zapwa-bubble-wrap">
                    <div class="zapwa-ms-preview-head">
                        <span>👁</span> Preview WhatsApp
                    </div>
                    <div class="zapwa-phone-header">
                        <div class="zapwa-phone-avatar">🤖</div>
                        <div>
                            <div class="zapwa-phone-name"><?php echo esc_html(get_bloginfo('name') ?: __('Meu Site', 'zap-whatsapp-automation')); ?></div>
                            <div class="zapwa-phone-status">online</div>
                        </div>
                    </div>
                    <div class="zapwa-phone-body">
                        <div class="zapwa-bubble">
                            <div id="zapwa-bubble-text">
                                <span class="zapwa-bubble-empty">Escreva a mensagem acima para ver o preview...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips -->
                <div class="zapwa-ms-tips-card">
                    <div class="zapwa-ms-tips-head">💡 Dicas Rápidas</div>
                    <ul class="zapwa-ms-tips-list">
                        <li>Clique nas variáveis para copiá-las para a área de transferência</li>
                        <li>Use <code>{user_name}</code> para personalizar a mensagem</li>
                        <li>Mensagens curtas têm melhor taxa de engajamento</li>
                        <li>Evite enviar mensagens depois das 22h</li>
                    </ul>
                </div>

            </div><!-- /.zapwa-ms-col-side -->

        </div><!-- /.zapwa-ms-layout -->

        <?php
    }

    public static function save($post_id) {

        if (
            !isset($_POST['zapwa_nonce']) ||
            !wp_verify_nonce($_POST['zapwa_nonce'], 'zapwa_message_settings')
        ) {
            return;
        }

        update_post_meta($post_id, '_zapwa_type', sanitize_text_field($_POST['zapwa_type'] ?? 'trigger'));
        update_post_meta($post_id, '_zapwa_event', sanitize_text_field($_POST['zapwa_event'] ?? ''));
        update_post_meta($post_id, '_zapwa_delay', intval($_POST['zapwa_delay'] ?? 5));
        update_post_meta($post_id, '_zapwa_active', isset($_POST['zapwa_active']) ? '1' : '0');
        update_post_meta($post_id, '_zapwa_broadcast_target', sanitize_text_field($_POST['zapwa_broadcast_target'] ?? 'all_students'));

        // Email fields
        update_post_meta($post_id, 'zapwa_email_enabled', isset($_POST['zapwa_email_enabled']) ? '1' : '0');
        update_post_meta($post_id, 'zapwa_email_subject', sanitize_text_field($_POST['zapwa_email_subject'] ?? ''));
        update_post_meta($post_id, 'zapwa_email_body', wp_kses_post($_POST['zapwa_email_body'] ?? ''));
        update_post_meta($post_id, 'zapwa_email_is_html', isset($_POST['zapwa_email_is_html']) ? '1' : '0');
    }
}
