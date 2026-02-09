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

        // Eventos vindos do Zap Tutor Events
        $events = apply_filters('zap_tutor_events_list', []);
        ?>

        <style>
            .zapwa-row { margin-bottom:15px; }
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

        <div class="zapwa-row">
            <strong>Tipo da Mensagem</strong><br>
            <select name="zapwa_type" style="width:100%">
                <option value="trigger" <?php selected($type,'trigger'); ?>>
                    Gatilho (Evento)
                </option>
                <option value="broadcast" <?php selected($type,'broadcast'); ?>>
                    Broadcast
                </option>
            </select>
        </div>

        <div class="zapwa-row">
            <strong>Evento</strong><br>
            <select name="zapwa_event" style="width:100%">
                <option value="">Selecione um evento</option>
                <?php foreach ($events as $key => $label): ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($event,$key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Usado apenas para mensagens do tipo gatilho</small>
        </div>

        <div class="zapwa-row">
            <strong>Delay (Broadcast)</strong><br>
            <input type="number"
                   name="zapwa_delay"
                   value="<?php echo esc_attr($delay); ?>"
                   style="width:120px">
            <small>Tempo entre disparos (segundos)</small>
        </div>

        <div class="zapwa-row">
            <label>
                <input type="checkbox"
                       name="zapwa_active"
                       value="1"
                       <?php checked($active,'1'); ?>>
                Mensagem ativa
            </label>
        </div>

        <hr>

        <strong>Variáveis disponíveis</strong>
        <div class="zapwa-vars">
            <span class="zapwa-var" data-var="{user_name}">{user_name}</span>
            <span class="zapwa-var" data-var="{user_email}">{user_email}</span>
            <span class="zapwa-var" data-var="{user_phone}">{user_phone}</span>
            <span class="zapwa-var" data-var="{course_name}">{course_name}</span>
            <span class="zapwa-var" data-var="{course_progress}">{course_progress}</span>
        </div>

        <p style="margin-top:8px;">
            <small>Clique para copiar a variável</small>
        </p>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.zapwa-var').forEach(function (el) {
                el.addEventListener('click', function () {
                    const text = this.dataset.var;
                    navigator.clipboard.writeText(text).then(() => {
                        const original = this.innerText;
                        this.innerText = '✔ Copiado';
                        setTimeout(() => {
                            this.innerText = original;
                        }, 1200);
                    });
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

        update_post_meta($post_id, '_zapwa_type', sanitize_text_field($_POST['zapwa_type'] ?? ''));
        update_post_meta($post_id, '_zapwa_event', sanitize_text_field($_POST['zapwa_event'] ?? ''));
        update_post_meta($post_id, '_zapwa_delay', intval($_POST['zapwa_delay'] ?? 0));
        update_post_meta($post_id, '_zapwa_active', isset($_POST['zapwa_active']) ? '1' : '0');
    }
}
