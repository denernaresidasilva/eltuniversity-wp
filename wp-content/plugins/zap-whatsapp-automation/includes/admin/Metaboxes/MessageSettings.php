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

        $type   = get_post_meta($post->ID, '_zapwa_type', true);
        $event  = get_post_meta($post->ID, '_zapwa_event', true);
        $delay  = get_post_meta($post->ID, '_zapwa_delay', true);
        $active = get_post_meta($post->ID, '_zapwa_active', true);
        $broadcast_target = get_post_meta($post->ID, '_zapwa_broadcast_target', true);

        // Eventos do Tutor LMS
        $events = apply_filters('zap_tutor_events_list', []);
        ?>

        <style>
            .zapwa-row { margin-bottom:15px; }
            .zapwa-type-selector {
                display: flex;
                gap: 16px;
                margin-bottom: 24px;
            }
            .zapwa-type-card {
                flex: 1;
                padding: 20px;
                border: 2px solid #ddd;
                border-radius: 8px;
                cursor: pointer;
                text-align: center;
                transition: all 0.3s;
            }
            .zapwa-type-card:hover {
                border-color: #0073aa;
                background: #f0f8ff;
            }
            .zapwa-type-card.active {
                border-color: #0073aa;
                background: #e3f2fd;
            }
            .zapwa-type-card h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
            }
            .zapwa-config-section {
                display: none;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 8px;
                margin-top: 16px;
            }
            .zapwa-config-section.active {
                display: block;
            }
            .zapwa-vars {
                display:flex;
                flex-wrap:wrap;
                gap:6px;
            }
            .zapwa-var {
                background:#f4f4f4;
                border:1px solid #ddd;
                border-radius:4px;
                padding:4px 8px;
                cursor:pointer;
                font-family:monospace;
            }
            .zapwa-var:hover {
                background:#eaeaea;
            }
        </style>

        <input type="hidden" name="zapwa_type" id="zapwa_type" value="<?php echo esc_attr($type ?: 'trigger'); ?>">

        <!-- SELETOR DE TIPO -->
        <div class="zapwa-type-selector">
            <div class="zapwa-type-card <?php echo ($type !== 'broadcast' ? 'active' : ''); ?>" data-type="trigger">
                <h3>🎯 Gatilho (Automação)</h3>
                <p>Dispara automaticamente quando um evento ocorre</p>
            </div>
            <div class="zapwa-type-card <?php echo ($type === 'broadcast' ? 'active' : ''); ?>" data-type="broadcast">
                <h3>📢 Disparo Broadcast</h3>
                <p>Envio manual para múltiplos destinatários</p>
            </div>
        </div>

        <!-- CONFIGURAÇÃO GATILHO -->
        <div class="zapwa-config-section <?php echo ($type !== 'broadcast' ? 'active' : ''); ?>" id="config-trigger">
            <div class="zapwa-row">
                <strong>Selecione o Evento Gatilho</strong><br>
                <select name="zapwa_event" style="width:100%">
                    <option value="">Selecione um evento</option>
                    <?php foreach ($events as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($event,$key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small>A mensagem será enviada automaticamente quando este evento ocorrer</small>
            </div>
        </div>

        <!-- CONFIGURAÇÃO BROADCAST -->
        <div class="zapwa-config-section <?php echo ($type === 'broadcast' ? 'active' : ''); ?>" id="config-broadcast">
            <div class="zapwa-row">
                <strong>Destinatários</strong><br>
                <select name="zapwa_broadcast_target" style="width:100%">
                    <option value="all_students" <?php selected($broadcast_target, 'all_students'); ?>>
                        👨‍🎓 Todos os Alunos
                    </option>
                    <option value="active_courses" <?php selected($broadcast_target, 'active_courses'); ?>>
                        📚 Alunos com Cursos Ativos
                    </option>
                    <option value="inactive_students" <?php selected($broadcast_target, 'inactive_students'); ?>>
                        💤 Alunos Inativos (7+ dias sem acessar)
                    </option>
                    <option value="completed_courses" <?php selected($broadcast_target, 'completed_courses'); ?>>
                        🎓 Alunos que Completaram Cursos
                    </option>
                    <option value="specific_course" <?php selected($broadcast_target, 'specific_course'); ?>>
                        🎯 Alunos de um Curso Específico
                    </option>
                </select>
            </div>

            <div class="zapwa-row">
                <strong>Delay entre Mensagens (segundos)</strong><br>
                <input type="number" 
                       name="zapwa_delay" 
                       value="<?php echo esc_attr($delay ?: 5); ?>" 
                       min="1" 
                       max="300"
                       style="width:120px">
                <small>Tempo de espera entre cada envio (recomendado: 5-10 segundos)</small>
            </div>
        </div>

        <hr>

        <div class="zapwa-row">
            <label>
                <input type="checkbox"
                       name="zapwa_active"
                       value="1"
                       <?php checked($active,'1'); ?>>
                <strong>Mensagem ativa</strong>
            </label>
        </div>

        <hr>

        <strong>Variáveis disponíveis (use com chaves {}):</strong>
        <div class="zapwa-vars">
            <span class="zapwa-var" data-var="{user_name}">{user_name}</span>
            <span class="zapwa-var" data-var="{user_email}">{user_email}</span>
            <span class="zapwa-var" data-var="{user_phone}">{user_phone}</span>
            <span class="zapwa-var" data-var="{course_name}">{course_name}</span>
            <span class="zapwa-var" data-var="{course_progress}">{course_progress}</span>
        </div>

        <p style="margin-top:8px;">
            <small>✅ Use chaves {} para as variáveis (padrão do Tutor LMS). Clique para copiar.</small>
        </p>

        <script>
        jQuery(document).ready(function($) {
            // Toggle entre Gatilho e Broadcast
            $('.zapwa-type-card').on('click', function() {
                const type = $(this).data('type');
                
                // Atualizar visual
                $('.zapwa-type-card').removeClass('active');
                $(this).addClass('active');
                
                // Atualizar campo hidden
                $('#zapwa_type').val(type);
                
                // Mostrar/ocultar seções
                $('.zapwa-config-section').removeClass('active');
                $('#config-' + type).addClass('active');
            });

            // Copiar variável ao clicar
            $('.zapwa-var').on('click', function() {
                const text = $(this).data('var');
                navigator.clipboard.writeText(text).then(() => {
                    const original = $(this).text();
                    $(this).text('✔ Copiado');
                    setTimeout(() => {
                        $(this).text(original);
                    }, 1200);
                });
            });
        });
        </script>

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
    }
}
