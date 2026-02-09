<?php
/**
 * Plugin Name: Carrossel Supermembros
 * Description: Widget Elementor personalizado com carrossel de cursos, bloqueio, imagem customizada e comentários dinâmicos.
 * Version: 1.0
 * Author: Raul
 * Text Domain: carrossel-supermembros
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ------ 1. REGISTRA Widget do Elementor ------
add_action( 'elementor/widgets/widgets_registered', function($widgets_manager) {

    if ( !class_exists('\Elementor\Widget_Base') ) return;

    class Custom_Carousel_Widget extends \Elementor\Widget_Base {

        public function get_name() {
            return 'custom_carousel';
        }

        public function get_title() {
            return __( 'Carrossel Supermembros', 'carrossel-supermembros' );
        }

        public function get_icon() {
            return 'eicon-post-list';
        }

        public function get_categories() {
            return [ 'custom_category' ];
        }

        protected function register_controls() {
            $this->start_controls_section(
                'content_section',
                [
                    'label' => __( 'Cursos', 'carrossel-supermembros' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );

            $repeater = new \Elementor\Repeater();
            $courses  = $this->get_tutor_courses();

            $repeater->add_control(
                'course_id',
                [
                    'label'   => __( 'Selecionar Curso', 'carrossel-supermembros' ),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'options' => $courses,
                ]
            );
            $repeater->add_control(
                'use_custom_image',
                [
                    'label'       => __( 'Usar Imagem Personalizada', 'carrossel-supermembros' ),
                    'type'        => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'    => __( 'Sim', 'carrossel-supermembros' ),
                    'label_off'   => __( 'Não', 'carrossel-supermembros' ),
                    'return_value'=> 'yes',
                    'default'     => 'no',
                ]
            );
            $repeater->add_control(
                'custom_image',
                [
                    'label'     => __( 'Imagem Personalizada', 'carrossel-supermembros' ),
                    'type'      => \Elementor\Controls_Manager::MEDIA,
                    'default'   => [ 'url' => \Elementor\Utils::get_placeholder_image_src(), ],
                    'condition' => [ 'use_custom_image' => 'yes' ],
                ]
            );
            $repeater->add_control(
                'enable_comments',
                [
                    'label'       => __( 'Ativar Comentários?', 'carrossel-supermembros' ),
                    'type'        => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'    => __( 'Sim', 'carrossel-supermembros' ),
                    'label_off'   => __( 'Não', 'carrossel-supermembros' ),
                    'return_value'=> 'yes',
                    'default'     => 'no',
                ]
            );
            $this->add_control(
                'courses',
                [
                    'label'       => __( 'Cursos', 'carrossel-supermembros' ),
                    'type'        => \Elementor\Controls_Manager::REPEATER,
                    'fields'      => $repeater->get_controls(),
                    'title_field' => __( 'Curso', 'carrossel-supermembros' ),
                ]
            );

            $this->end_controls_section();

            $this->start_controls_section(
                'settings_section',
                [
                    'label' => __( 'Configurações', 'carrossel-supermembros' ),
                    'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
            );
            $this->add_control(
                'lock_all',
                [
                    'label'       => __( 'Mostrar Cadeado se Bloqueado', 'carrossel-supermembros' ),
                    'type'        => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'    => __( 'Sim', 'carrossel-supermembros' ),
                    'label_off'   => __( 'Não', 'carrossel-supermembros' ),
                    'return_value'=> 'yes',
                    'default'     => 'no',
                ]
            );
            $this->add_control(
                'grayscale_all',
                [
                    'label'       => __( 'Preto e Branco se Bloqueado', 'carrossel-supermembros' ),
                    'type'        => \Elementor\Controls_Manager::SWITCHER,
                    'label_on'    => __( 'Sim', 'carrossel-supermembros' ),
                    'label_off'   => __( 'Não', 'carrossel-supermembros' ),
                    'return_value'=> 'yes',
                    'default'     => 'no',
                ]
            );
            $this->end_controls_section();
        }

        protected function get_tutor_courses() {
            $courses = [];
            $args = [ 'post_type'=>'courses', 'posts_per_page'=>-1 ];
            $query = new WP_Query($args);
            if ( $query->have_posts() ) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $courses[get_the_ID()] = get_the_title();
                }
                wp_reset_postdata();
            }
            return $courses;
        }

        protected function get_first_lesson_url($course_id) {
            if( function_exists('tutor_utils') && method_exists(tutor_utils(), 'get_course_first_lesson') ) {
                $lesson_url = tutor_utils()->get_course_first_lesson($course_id);
                if ($lesson_url) return $lesson_url;
            }
            return false;
        }

        protected function get_enrollment_date($course_id, $user_id) {
            if( function_exists('tutor_utils') && method_exists(tutor_utils(), 'get_course_enrollments') ) {
                $enroll = tutor_utils()->get_course_enrollments($course_id, $user_id);
                if(!empty($enroll)) return strtotime($enroll[0]->created_at);
            }
            return false;
        }

        protected function get_purchase_link($course_id) {
            $redirect_url = get_post_meta($course_id, '_redirect_url', true);
            return !empty($redirect_url) ? $redirect_url : get_permalink($course_id);
        }

        protected function get_course_image($course_id, $course_settings) {
            if ( isset($course_settings['use_custom_image']) && $course_settings['use_custom_image'] === 'yes' ) {
                if ( isset($course_settings['custom_image']['url']) && !empty($course_settings['custom_image']['url']) )
                    return $course_settings['custom_image']['url'];
            }
            $thumb = get_the_post_thumbnail_url($course_id, 'full');
            return $thumb ? $thumb : (\Elementor\Utils::get_placeholder_image_src());
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $current_user_id = get_current_user_id();
            $user_data = get_userdata($current_user_id);
            if(!$user_data) return;
            $registration_date = strtotime($user_data->user_registered);
            $user_name = $user_data->first_name ? $user_data->first_name : $user_data->display_name;

            if ( !empty($settings['courses']) ) {
                echo '<div class="custom-carousel-wrapper show-left show-right">';
                echo '<button class="arrow left"><i class="fas fa-chevron-left"></i></button>';
                echo '<div class="custom-carousel">';
                foreach ($settings['courses'] as $course) {
                    $course_id = $course['course_id'];
                    $purchase_link = $this->get_purchase_link($course_id);
                    $enable_comments = isset($course['enable_comments']) && $course['enable_comments'] === 'yes' ? 'yes' : 'no';
                    $course_image = $this->get_course_image($course_id, $course);

                    // Checagem de matrícula
                    $is_enrolled = function_exists('tutor_utils') && method_exists(tutor_utils(), 'is_enrolled')
                        ? tutor_utils()->is_enrolled($course_id, $current_user_id) : false;

                    $lock_course = !$is_enrolled && $settings['lock_all'] === 'yes';
                    $grayscale_course = !$is_enrolled && $settings['grayscale_all'] === 'yes';

                    $first_lesson_url = $this->get_first_lesson_url($course_id);
                    $course_link = $is_enrolled && $first_lesson_url ? $first_lesson_url : $purchase_link;

                    echo '<div class="carousel-item'.($grayscale_course ? ' grayscale' : '').'">';
                    echo '<a id="course-link-'.esc_attr($course_id).'" href="'.esc_url($course_link).'" class="course-link" data-enable-comments="'.esc_attr($enable_comments).'" data-course-id="'.esc_attr($course_id).'">';
                    echo '<img src="'.esc_url($course_image).'" alt="">';
                    if($lock_course) {
                        echo '<span class="lock-icon icon-background"><i class="fas fa-lock icon-color"></i></span>';
                    }
                    echo '</a>';
                    echo '</div>';
                }
                echo '</div>';
                echo '<button class="arrow right"><i class="fas fa-chevron-right"></i></button>';
                echo '</div>';
            }
        }
        protected function content_template() {}
    }

    $widgets_manager->register_widget_type( new Custom_Carousel_Widget() );
});

// ------ 2. REGISTRA Categoria no Elementor ------
add_action( 'elementor/elements/categories_registered', function($elements_manager){
    $elements_manager->add_category('custom_category', [
        'title' => __( 'Supermembros', 'carrossel-supermembros' ),
        'icon' => 'fa fa-plug',
        'position' => 3,
    ]);
});

// ------ 3. EMBUTE CSS ------
add_action( 'wp_head', function(){
    ?>
    <style>
    .custom-carousel-wrapper { position: relative; width: 100%; }
    .custom-carousel { display: flex; overflow-x: auto; scroll-snap-type: x mandatory; padding: 10px 0; scroll-behavior: smooth; }
    .custom-carousel .carousel-item { flex: 0 0 calc(15% - 10px); margin-right: 10px; text-align: center; border-radius: 10px; overflow: hidden; scroll-snap-align: center; position: relative; }
    .custom-carousel .carousel-item img { max-width: 100%; height: auto; display: block; transition: transform 0.3s, filter 0.3s; }
    .custom-carousel .carousel-item:hover img { filter: brightness(0.8); transform: scale(1.05);}
    .custom-carousel .carousel-item.grayscale img { filter: grayscale(100%);}
    .custom-carousel-wrapper .arrow { position: absolute; top: 50%; transform: translateY(-50%); background-color: rgba(0,0,0,0.5); color: white; border: none; padding: 10px; cursor: pointer; z-index: 1; border-radius:8px; width:40px; height:40px; display: flex; align-items: center; justify-content: center; transition: background-color 0.3s, color 0.3s;}
    .custom-carousel-wrapper .arrow.left { left: -20px;}
    .custom-carousel-wrapper .arrow.right { right: -10px;}
    .custom-carousel-wrapper .arrow i { font-size: 20px; }
    .custom-carousel-wrapper .arrow:hover { background-color: rgba(255,255,255,0.295); color: black;}
    .custom-carousel::-webkit-scrollbar { display: none;}
    .custom-carousel .lock-icon { color: #4a4a4a; background-color: rgba(0,0,0,0.5); border-radius: 50%; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; position: absolute; top: 10px; right: 10px; font-size: 18px;}
    .hide-comments { display: none !important; }
    @media (max-width: 768px) {
        .custom-carousel .carousel-item { flex: 0 0 calc(50% - 10px);}
        .custom-carousel-wrapper .arrow.left { left: -15px;}
        .custom-carousel-wrapper .arrow.right { right: -5px;}
    }
    </style>
    <?php
});

// ------ 4. EMBUTE JS ------
add_action('wp_footer', function(){
    ?>
    <script>
    jQuery(document).ready(function ($) {
        // Salva valor enable_comments ao clicar no banner
        $('.course-link').on('click', function () {
            var enableComments = $(this).data('enable-comments');
            if (enableComments !== undefined) localStorage.setItem('enable_comments', enableComments);
            else localStorage.setItem('enable_comments', 'no');
        });
        // Esconde a aba de comentários se necessário
        $(window).on('load', function () {
            setTimeout(function() {
                var storedEnableComments = localStorage.getItem('enable_comments');
                if (storedEnableComments === 'no') {
                    var commentsTab = $('a[data-tutor-query-value="comments"]');
                    if (commentsTab.length > 0) commentsTab.addClass('hide-comments');
                }
            }, 10);
        });
        // Carrossel setas
        $('.custom-carousel').on('scroll', function () {
            const $this = $(this);
            $this.parent().toggleClass('show-left', $this.scrollLeft() > 0);
            $this.parent().toggleClass('show-right', $this.scrollLeft() + $this.innerWidth() < $this[0].scrollWidth);
        }).trigger('scroll');
        $('.arrow.left').on('click', function () {
            $(this).siblings('.custom-carousel').scrollLeft($(this).siblings('.custom-carousel').scrollLeft() - 300);
        });
        $('.arrow.right').on('click', function () {
            $(this).siblings('.custom-carousel').scrollLeft($(this).siblings('.custom-carousel').scrollLeft() + 300);
        });
    });
    </script>
    <?php
});

// (Opcional) Adiciona Font Awesome se não tiver no seu tema/site (para ícones do cadeado/setas)
add_action( 'wp_enqueue_scripts', function(){
    wp_enqueue_style('carrossel-font-awesome','https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');
});
