<?php
/**
 * Elementor Widget: Matricular Alunos
 *
 * Renders a student enrollment form in the frontend via Elementor.
 *
 * @package WebhookPluginComplete
 */

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

class Webhook_Enroll_Elementor_Widget extends Widget_Base {

    public function get_name() {
        return 'webhook_matricular_alunos';
    }

    public function get_title() {
        return esc_html__('Matricular Alunos', 'webhook-receiver');
    }

    public function get_icon() {
        return 'eicon-user';
    }

    public function get_categories() {
        return ['general'];
    }

    public function get_keywords() {
        return ['matricula', 'aluno', 'curso', 'enrollment', 'tutor'];
    }

    protected function register_controls() {

        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Configurações', 'webhook-receiver'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'form_title',
            [
                'label'   => esc_html__('Título do Formulário', 'webhook-receiver'),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__('Matricular Aluno', 'webhook-receiver'),
            ]
        );

        $this->add_control(
            'show_phone',
            [
                'label'        => esc_html__('Exibir campo de telefone', 'webhook-receiver'),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__('Sim', 'webhook-receiver'),
                'label_off'    => esc_html__('Não', 'webhook-receiver'),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label'   => esc_html__('Texto do Botão', 'webhook-receiver'),
                'type'    => Controls_Manager::TEXT,
                'default' => esc_html__('Matricular Aluno', 'webhook-receiver'),
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings   = $this->get_settings_for_display();
        $form_title = !empty($settings['form_title']) ? $settings['form_title'] : 'Matricular Aluno';
        $show_phone = $settings['show_phone'] === 'yes';
        $btn_text   = !empty($settings['button_text']) ? $settings['button_text'] : 'Matricular Aluno';

        // Fetch all published Tutor LMS courses
        $courses = get_posts(array(
            'post_type'   => 'courses',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby'     => 'title',
            'order'       => 'ASC',
        ));

        $widget_id = 'whe-' . $this->get_id();
        $nonce     = wp_create_nonce('webhook_manual_enroll_action');
        ?>
        <div class="whe-widget" id="<?php echo esc_attr($widget_id); ?>">
            <h3 class="whe-title"><?php echo esc_html($form_title); ?></h3>

            <form class="whe-form">
                <div class="whe-row">
                    <div class="whe-col">
                        <label><?php esc_html_e('Nome Completo *', 'webhook-receiver'); ?></label>
                        <input type="text" name="enroll_name" placeholder="João Silva" required>
                    </div>
                    <div class="whe-col">
                        <label><?php esc_html_e('E-mail *', 'webhook-receiver'); ?></label>
                        <input type="email" name="enroll_email" placeholder="joao@email.com" required>
                    </div>
                </div>

                <div class="whe-row">
                    <?php if ($show_phone): ?>
                    <div class="whe-col">
                        <label><?php esc_html_e('Telefone / WhatsApp', 'webhook-receiver'); ?></label>
                        <input type="text" name="enroll_phone" placeholder="11999999999">
                    </div>
                    <?php endif; ?>
                    <div class="whe-col">
                        <label><?php esc_html_e('Senha *', 'webhook-receiver'); ?></label>
                        <input type="text" name="enroll_password" placeholder="Mínimo 6 caracteres" required minlength="6">
                    </div>
                </div>

                <div class="whe-courses">
                    <label><?php esc_html_e('Selecionar Cursos *', 'webhook-receiver'); ?></label>
                    <div class="whe-courses-list">
                        <?php if (!empty($courses)): ?>
                            <?php foreach ($courses as $course): ?>
                                <label class="whe-course-item">
                                    <input type="checkbox" name="enroll_course_ids[]" value="<?php echo esc_attr($course->ID); ?>">
                                    <?php echo esc_html($course->post_title); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php esc_html_e('Nenhum curso encontrado.', 'webhook-receiver'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <input type="hidden" name="action" value="webhook_receiver_manual_enroll">
                <input type="hidden" name="webhook_enroll_nonce" value="<?php echo esc_attr($nonce); ?>">

                <div class="whe-actions">
                    <button type="submit" class="whe-btn">
                        🎓 <?php echo esc_html($btn_text); ?>
                    </button>
                </div>
            </form>

            <div class="whe-result" style="display:none;"></div>
        </div>

        <style>
        .whe-widget { max-width: 700px; margin: 0 auto; font-family: inherit; }
        .whe-title { font-size: 1.4em; margin-bottom: 20px; }
        .whe-row { display: flex; gap: 15px; margin-bottom: 15px; flex-wrap: wrap; }
        .whe-col { flex: 1; min-width: 200px; display: flex; flex-direction: column; gap: 5px; }
        .whe-col label, .whe-courses > label { font-weight: 600; font-size: 14px; }
        .whe-col input[type="text"],
        .whe-col input[type="email"] {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .whe-courses { margin-bottom: 20px; }
        .whe-courses-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 8px;
            margin-top: 8px;
            max-height: 240px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
        }
        .whe-course-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            cursor: pointer;
        }
        .whe-actions { margin-top: 20px; }
        .whe-btn {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 11px 24px;
            border-radius: 5px;
            font-size: 15px;
            cursor: pointer;
        }
        .whe-btn:hover { background: #005177; }
        .whe-result { margin-top: 15px; padding: 12px; border-radius: 5px; }
        .whe-result.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .whe-result.error   { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        </style>

        <script>
        (function($) {
            var $widget = $('#<?php echo esc_js($widget_id); ?>');
            $widget.find('.whe-form').on('submit', function(e) {
                e.preventDefault();
                var $btn = $widget.find('.whe-btn');
                var $result = $widget.find('.whe-result');
                $btn.prop('disabled', true).text('⏳ Processando...');
                $.post(<?php echo json_encode(admin_url('admin-ajax.php')); ?>, $(this).serialize(), function(res) {
                    if (res.success) {
                        $result.removeClass('error').addClass('success')
                            .html('<strong>✅ Matrícula realizada com sucesso!</strong><ul>' +
                                res.data.details.map(function(d){ return '<li>'+d+'</li>'; }).join('') +
                            '</ul>').show();
                        $widget.find('.whe-form')[0].reset();
                    } else {
                        $result.removeClass('success').addClass('error')
                            .html('<strong>❌ Erro:</strong> ' + res.data.message).show();
                    }
                }).fail(function() {
                    $result.removeClass('success').addClass('error')
                        .html('<strong>❌ Erro de conexão.</strong>').show();
                }).always(function() {
                    $btn.prop('disabled', false).text('🎓 <?php echo esc_js($btn_text); ?>');
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    protected function content_template() {
        // Editor preview
        ?>
        <div class="whe-widget">
            <h3 class="whe-title">{{{ settings.form_title }}}</h3>
            <p><em><?php esc_html_e('Formulário de matrícula (visível no frontend)', 'webhook-receiver'); ?></em></p>
        </div>
        <?php
    }
}
