<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Supermembros Carousel Widget
 */
class Supermembros_Carousel_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name() {
        return 'supermembros-carousel';
    }

    /**
     * Get widget title.
     */
    public function get_title() {
        return esc_html__('Carrossel Supermembros', 'supermembros-carousel');
    }

    /**
     * Get widget icon.
     */
    public function get_icon() {
        return 'eicon-slides';
    }

    /**
     * Get widget categories.
     */
    public function get_categories() {
        return ['supermembros'];
    }

    /**
     * Get widget keywords.
     */
    public function get_keywords() {
        return ['carousel', 'slider', 'supermembros', 'courses'];
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Configurações do Carrossel', 'supermembros-carousel'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Repeater for courses
        $repeater = new \Elementor\Repeater();

        // Get courses
        $courses = $this->get_tutor_courses();

        $repeater->add_control(
            'course_id',
            [
                'label' => esc_html__('Selecionar Curso', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $courses,
                'default' => '',
            ]
        );

        $this->add_control(
            'courses',
            [
                'label' => esc_html__('Cursos', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => esc_html__('Curso', 'supermembros-carousel'),
            ]
        );

        $this->end_controls_section();

        // Settings Section
        $this->start_controls_section(
            'settings_section',
            [
                'label' => esc_html__('Configurações', 'supermembros-carousel'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_lock_icon',
            [
                'label' => esc_html__('Mostrar Ícone de Cadeado', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sim', 'supermembros-carousel'),
                'label_off' => esc_html__('Não', 'supermembros-carousel'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'apply_grayscale',
            [
                'label' => esc_html__('Aplicar Escala de Cinza em Bloqueados', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sim', 'supermembros-carousel'),
                'label_off' => esc_html__('Não', 'supermembros-carousel'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'progress_filter_info',
            [
                'label' => esc_html__('Filtro de Progresso', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('Este carrossel exibe apenas cursos com progresso entre 1% e 99% (cursos em andamento). No mobile, deslize horizontalmente para navegar entre os cards.', 'supermembros-carousel'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->add_control(
            'mobile_swipe_info',
            [
                'label' => esc_html__('Navegação Mobile', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('📱 <strong>Mobile:</strong> Deslize horizontalmente para navegar<br>🖱️ <strong>Desktop:</strong> Use a roda do mouse para rolar', 'supermembros-carousel'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-success',
            ]
        );

        $this->add_control(
            'show_swipe_hint',
            [
                'label' => esc_html__('Mostrar Dica de Deslize', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sim', 'supermembros-carousel'),
                'label_off' => esc_html__('Não', 'supermembros-carousel'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Exibe uma dica sutil "Deslize para navegar" no mobile por 3 segundos.', 'supermembros-carousel'),
            ]
        );

        $this->end_controls_section();

        // Autoplay Section
        $this->start_controls_section(
            'autoplay_section',
            [
                'label' => esc_html__('Autoplay', 'supermembros-carousel'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'enable_autoplay',
            [
                'label' => esc_html__('Habilitar Autoplay', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => esc_html__('Sim', 'supermembros-carousel'),
                'label_off' => esc_html__('Não', 'supermembros-carousel'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => esc_html__('Ativa o scroll automático quando há mais de 2 cards.', 'supermembros-carousel'),
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label' => esc_html__('Velocidade do Autoplay (segundos)', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 10,
                'step' => 0.5,
                'default' => 2,
                'condition' => [
                    'enable_autoplay' => 'yes',
                ],
                'description' => esc_html__('Intervalo entre cada movimento automático.', 'supermembros-carousel'),
            ]
        );

        $this->add_control(
            'autoplay_info',
            [
                'label' => esc_html__('Como Funciona o Autoplay', 'supermembros-carousel'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('🔄 <strong>Autoplay:</strong> Rola automaticamente pelos cards<br>⏸️ <strong>Pausa:</strong> Durante hover (desktop) ou swipe (mobile)<br>🔁 <strong>Loop:</strong> Volta ao início quando chega no fim<br>👁️ <strong>Visibilidade:</strong> Pausa quando a aba não está ativa', 'supermembros-carousel'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
                'condition' => [
                    'enable_autoplay' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Get Tutor LMS Courses
     */
    protected function get_tutor_courses() {
        $courses = ['' => esc_html__('Selecione um curso', 'supermembros-carousel')];
        
        $args = [
            'post_type' => 'courses',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ];
        
        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $courses[get_the_ID()] = get_the_title();
            }
            wp_reset_postdata();
        }

        return $courses;
    }

    /**
     * Check if user is enrolled in course
     */
    protected function is_user_enrolled($course_id) {
        if (!function_exists('tutor_utils')) {
            return false;
        }
        
        $user_id = get_current_user_id();
        return tutor_utils()->is_enrolled($course_id, $user_id);
    }

    /**
     * Get course progress percentage
     */
    protected function get_course_progress($course_id) {
        if (!function_exists('tutor_utils')) {
            return 0;
        }
        
        $user_id = get_current_user_id();
        
        // Check if user is enrolled first
        if (!$this->is_user_enrolled($course_id)) {
            return 0;
        }
        
        // Get course completion percentage
        $progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
        
        return floatval($progress);
    }

    /**
     * Check if course should be displayed (progress between 1-99%)
     */
    protected function should_display_course($course_id) {
        $progress = $this->get_course_progress($course_id);
        return ($progress >= 1 && $progress <= 99);
    }

    /**
     * Get course first lesson URL
     */
    protected function get_first_lesson_url($course_id) {
        if (!function_exists('tutor_utils')) {
            return get_permalink($course_id);
        }
        
        $lesson_url = tutor_utils()->get_course_first_lesson($course_id);
        return $lesson_url ? $lesson_url : get_permalink($course_id);
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        if (empty($settings['courses'])) {
            echo '<p>' . esc_html__('Nenhum curso selecionado.', 'supermembros-carousel') . '</p>';
            return;
        }

        // Filter courses to show only those with progress between 1-99%
        $courses_to_display = [];
        foreach ($settings['courses'] as $course) {
            if (empty($course['course_id'])) {
                continue;
            }
            
            if ($this->should_display_course($course['course_id'])) {
                $courses_to_display[] = $course;
            }
        }

        // Check if there are any courses to display
        if (empty($courses_to_display)) {
            echo '<p>' . esc_html__('', 'supermembros-carousel') . '</p>';
            return;
        }

        echo '<div class="supermembros-carousel-wrapper">';
        echo '<div class="supermembros-carousel" ';
        echo 'data-total-items="' . esc_attr(count($courses_to_display)) . '" ';
        echo 'data-show-swipe-hint="' . esc_attr($settings['show_swipe_hint'] ?? 'yes') . '" ';
        echo 'data-enable-autoplay="' . esc_attr($settings['enable_autoplay'] ?? 'yes') . '" ';
        echo 'data-autoplay-speed="' . esc_attr(($settings['autoplay_speed'] ?? 2) * 1000) . '">';
        
        echo '<!-- Debug: Total courses = ' . count($courses_to_display) . ', Autoplay = ' . ($settings['enable_autoplay'] ?? 'yes') . ', Speed = ' . (($settings['autoplay_speed'] ?? 2) * 1000) . 'ms -->';

        foreach ($courses_to_display as $course) {
            $course_id = $course['course_id'];
            $course_image = get_the_post_thumbnail_url($course_id, 'full');
            $course_title = get_the_title($course_id);
            $is_enrolled = $this->is_user_enrolled($course_id);
            $progress = $this->get_course_progress($course_id);
            
            // Determine course link
            if ($is_enrolled) {
                $course_link = $this->get_first_lesson_url($course_id);
            } else {
                $course_link = get_permalink($course_id);
            }

            // CSS classes
            $item_classes = ['carousel-item'];
            if (!$is_enrolled && $settings['apply_grayscale'] === 'yes') {
                $item_classes[] = 'grayscale';
            }

            echo '<div class="' . esc_attr(implode(' ', $item_classes)) . '" data-progress="' . esc_attr($progress) . '">';
            
            // IMPORTANTE: Link com atributos para garantir que funcione no mobile
            echo '<a href="' . esc_url($course_link) . '" class="course-link" rel="nofollow" data-course-id="' . esc_attr($course_id) . '">';
            
            if ($course_image) {
                echo '<img src="' . esc_url($course_image) . '" alt="' . esc_attr($course_title) . '" loading="lazy">';
            } else {
                echo '<div class="no-image">' . esc_html($course_title) . '</div>';
            }

            // Show progress indicator
            echo '<div class="progress-indicator">';
            echo '<div class="progress-bar" style="width: ' . esc_attr($progress) . '%"></div>';
            echo '<span class="progress-text">' . esc_html($progress) . '%</span>';
            echo '</div>';

            // Show lock icon if not enrolled
            if (!$is_enrolled && $settings['show_lock_icon'] === 'yes') {
                echo '<span class="lock-icon"><i class="fas fa-lock"></i></span>';
            }

            echo '</a>';
            echo '</div>'; // carousel-item
        }

        echo '</div>'; // .supermembros-carousel

        echo '</div>'; // .supermembros-carousel-wrapper
    }

    /**
     * Render widget output in the editor.
     */
    protected function content_template() {
        ?>
        <#
        if (settings.courses.length) {
            #>
            <div class="supermembros-carousel-wrapper">
                <div class="supermembros-carousel" 
                     data-total-items="{{{ settings.courses.length }}}" 
                     data-show-swipe-hint="{{{ settings.show_swipe_hint || 'yes' }}}" 
                     data-enable-autoplay="{{{ settings.enable_autoplay || 'yes' }}}" 
                     data-autoplay-speed="{{{ (settings.autoplay_speed || 2) * 1000 }}}">
                    <!-- Debug: Autoplay = {{{ settings.enable_autoplay || 'yes' }}}, Speed = {{{ (settings.autoplay_speed || 2) * 1000 }}}ms -->>
                    <# _.each( settings.courses, function( course ) { 
                        if (course.course_id) {
                            var itemClasses = ['carousel-item'];
                            
                            if (settings.apply_grayscale === 'yes') {
                                itemClasses.push('grayscale');
                            }
                    #>
                        <div class="{{{ itemClasses.join(' ') }}}">
                            <div class="course-link">
                                <div class="placeholder-image">
                                    Curso ID: {{{ course.course_id }}}
                                    <br><small>Progresso: 1-99% (Em andamento)</small>
                                </div>
                                <div class="progress-indicator">
                                    <div class="progress-bar" style="width: 50%"></div>
                                    <span class="progress-text">50% (exemplo)</span>
                                </div>
                                <# if (settings.show_lock_icon === 'yes') { #>
                                    <span class="lock-icon"><i class="fas fa-lock"></i></span>
                                <# } #>
                            </div>
                        </div>
                    <# 
                        }
                    }); #>
                </div>
            </div>
            <#
        } else {
            #>
            <p><?php echo esc_html__('Nenhum curso selecionado.', 'supermembros-carousel'); ?></p>
            <#
        }
        #>
        <div style="margin-top: 10px; padding: 10px; background: #e8f5e8; border-left: 4px solid #4caf50; font-size: 12px;">
            <strong>📱 Navegação Mobile:</strong> Deslize horizontalmente para navegar entre os cards<br>
            <strong>🖱️ Desktop:</strong> Use a roda do mouse para rolar<br>
            <small><strong>Filtro:</strong> Apenas cursos com progresso entre 1% e 99% são exibidos</small><br>
            <# if ((settings.show_swipe_hint || 'yes') === 'yes') { #>
                <small>💡 <strong>Dica de deslize:</strong> Ativada (3 segundos no mobile)</small><br>
            <# } else { #>
                <small>💡 <strong>Dica de deslize:</strong> Desativada</small><br>
            <# } #>
            <# if ((settings.enable_autoplay || 'yes') === 'yes' && settings.courses.length > 2) { #>
                <small>🔄 <strong>Autoplay:</strong> Ativo ({{{ settings.autoplay_speed || 2 }}}s por card)</small>
            <# } else if ((settings.enable_autoplay || 'yes') === 'yes') { #>
                <small>🔄 <strong>Autoplay:</strong> Aguardando mais de 2 cards</small>
            <# } else { #>
                <small>🔄 <strong>Autoplay:</strong> Desabilitado</small>
            <# } #>
        </div>
        <?php
    }
}