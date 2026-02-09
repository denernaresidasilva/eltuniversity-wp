<?php
/**
 * Plugin Name: Tutor LMS Dashboard Analytics
 * Plugin URI: https://seusite.com
 * Description: Exibe estatísticas e dados dos alunos do Tutor LMS no dashboard do WordPress
 * Version: 1.0.0
 * Author: Seu Nome
 * License: GPL v2 or later
 * Text Domain: tutor-lms-dashboard
 */

// Evita acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes do plugin
define('TUTOR_DASHBOARD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TUTOR_DASHBOARD_PLUGIN_PATH', plugin_dir_path(__FILE__));

class TutorLMSDashboard {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Verificar se o Tutor LMS está ativo
        if (!class_exists('TUTOR\Tutor')) {
            add_action('admin_notices', array($this, 'tutor_lms_missing_notice'));
            return;
        }
    }
    
    public function tutor_lms_missing_notice() {
        echo '<div class="notice notice-error"><p>';
        echo __('Tutor LMS Dashboard Analytics requer o plugin Tutor LMS para funcionar.', 'tutor-lms-dashboard');
        echo '</p></div>';
    }
    
    public function enqueue_scripts($hook) {
        if ($hook == 'index.php') {
            wp_enqueue_style('tutor-dashboard-style', TUTOR_DASHBOARD_PLUGIN_URL . 'assets/style.css');
            wp_enqueue_script('tutor-dashboard-script', TUTOR_DASHBOARD_PLUGIN_URL . 'assets/script.js', array('jquery'), '1.0.0', true);
            
            // Passar dados para JavaScript
            wp_localize_script('tutor-dashboard-script', 'tutorDashboard', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('tutor_dashboard_nonce')
            ));
        }
    }
    
    public function add_dashboard_widgets() {
        // Widget principal com estatísticas gerais
        wp_add_dashboard_widget(
            'tutor_students_overview',
            'Visão Geral dos Alunos - Tutor LMS',
            array($this, 'students_overview_widget')
        );
        
        // Widget com alunos recentes
        wp_add_dashboard_widget(
            'tutor_recent_students',
            'Alunos Recentes',
            array($this, 'recent_students_widget')
        );
        
        // Widget com cursos mais populares
        wp_add_dashboard_widget(
            'tutor_popular_courses',
            'Cursos Mais Populares',
            array($this, 'popular_courses_widget')
        );
    }
    
    public function students_overview_widget() {
        $stats = $this->get_students_statistics();
        
        echo '<div class="tutor-dashboard-overview">';
        echo '<div class="stats-grid">';
        
        // Total de alunos
        echo '<div class="stat-card">';
        echo '<div class="stat-number">' . number_format($stats['total_students']) . '</div>';
        echo '<div class="stat-label">Total de Alunos</div>';
        echo '</div>';
        
        // Alunos ativos (últimos 30 dias)
        echo '<div class="stat-card">';
        echo '<div class="stat-number">' . number_format($stats['active_students']) . '</div>';
        echo '<div class="stat-label">Ativos (30 dias)</div>';
        echo '</div>';
        
        // Novos alunos (últimos 7 dias)
        echo '<div class="stat-card">';
        echo '<div class="stat-number">' . number_format($stats['new_students']) . '</div>';
        echo '<div class="stat-label">Novos (7 dias)</div>';
        echo '</div>';
        
        // Taxa de conclusão média
        echo '<div class="stat-card">';
        echo '<div class="stat-number">' . number_format($stats['completion_rate'], 1) . '%</div>';
        echo '<div class="stat-label">Taxa de Conclusão</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    public function recent_students_widget() {
        $recent_students = $this->get_recent_students();
        
        echo '<div class="tutor-recent-students">';
        if (!empty($recent_students)) {
            echo '<ul class="students-list">';
            foreach ($recent_students as $student) {
                echo '<li class="student-item">';
                echo '<div class="student-avatar">' . get_avatar($student->ID, 32) . '</div>';
                echo '<div class="student-info">';
                echo '<strong>' . esc_html($student->display_name) . '</strong><br>';
                echo '<small>Registrado em: ' . date('d/m/Y', strtotime($student->user_registered)) . '</small>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nenhum aluno encontrado.</p>';
        }
        echo '</div>';
    }
    
    public function popular_courses_widget() {
        $popular_courses = $this->get_popular_courses();
        
        echo '<div class="tutor-popular-courses">';
        if (!empty($popular_courses)) {
            echo '<ul class="courses-list">';
            foreach ($popular_courses as $course) {
                echo '<li class="course-item">';
                echo '<div class="course-info">';
                echo '<strong>' . esc_html($course->post_title) . '</strong><br>';
                echo '<small>' . $course->student_count . ' alunos inscritos</small>';
                echo '</div>';
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>Nenhum curso encontrado.</p>';
        }
        echo '</div>';
    }
    
    private function get_students_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total de alunos (usuários com capability de student ou subscriber)
        $user_query = new WP_User_Query(array(
            'role__in' => array('subscriber', 'tutor_instructor', 'tutor_student'),
            'count_total' => true
        ));
        $stats['total_students'] = $user_query->get_total();
        
        // Alunos ativos nos últimos 30 dias
        $active_users = $wpdb->get_var("
            SELECT COUNT(DISTINCT user_id) 
            FROM {$wpdb->usermeta} 
            WHERE meta_key = 'last_activity' 
            AND meta_value > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['active_students'] = $active_users ? $active_users : 0;
        
        // Novos alunos nos últimos 7 dias
        $new_users = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->users} 
            WHERE user_registered > DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['new_students'] = $new_users ? $new_users : 0;
        
        // Taxa de conclusão média
        $completion_data = $wpdb->get_row("
            SELECT 
                AVG(completion_percent) as avg_completion
            FROM (
                SELECT 
                    user_id,
                    course_id,
                    (completed_lessons / total_lessons * 100) as completion_percent
                FROM (
                    SELECT 
                        cm.user_id,
                        cm.course_id,
                        COUNT(CASE WHEN cm.completion_mode = 'completed' THEN 1 END) as completed_lessons,
                        COUNT(*) as total_lessons
                    FROM {$wpdb->prefix}tutor_course_completed_lessons cm
                    GROUP BY cm.user_id, cm.course_id
                ) as lesson_stats
                WHERE total_lessons > 0
            ) as course_completion
        ");
        
        $stats['completion_rate'] = $completion_data && $completion_data->avg_completion ? 
                                   $completion_data->avg_completion : 0;
        
        return $stats;
    }
    
    private function get_recent_students($limit = 5) {
        $user_query = new WP_User_Query(array(
            'role__in' => array('subscriber', 'tutor_student'),
            'orderby' => 'user_registered',
            'order' => 'DESC',
            'number' => $limit
        ));
        
        return $user_query->get_results();
    }
    
    private function get_popular_courses($limit = 5) {
        global $wpdb;
        
        $courses = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.ID,
                p.post_title,
                COUNT(DISTINCT e.user_id) as student_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->prefix}tutor_enrollments e ON p.ID = e.course_id
            WHERE p.post_type = 'courses'
            AND p.post_status = 'publish'
            GROUP BY p.ID, p.post_title
            ORDER BY student_count DESC
            LIMIT %d
        ", $limit));
        
        return $courses;
    }
    
    public function activate() {
        // Criar tabelas ou configurações necessárias na ativação
        if (!get_option('tutor_dashboard_version')) {
            add_option('tutor_dashboard_version', '1.0.0');
        }
    }
    
    public function deactivate() {
        // Limpeza necessária na desativação
    }
}

// Inicializar o plugin
new TutorLMSDashboard();

// CSS inline para o dashboard
add_action('admin_head', function() {
    if (get_current_screen()->id === 'dashboard') {
        echo '<style>
        .tutor-dashboard-overview .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .tutor-dashboard-overview .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            text-align: center;
            border-left: 4px solid #0073aa;
        }
        
        .tutor-dashboard-overview .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
        }
        
        .tutor-dashboard-overview .stat-label {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .tutor-recent-students .students-list,
        .tutor-popular-courses .courses-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .tutor-recent-students .student-item,
        .tutor-popular-courses .course-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .tutor-recent-students .student-item:last-child,
        .tutor-popular-courses .course-item:last-child {
            border-bottom: none;
        }
        
        .tutor-recent-students .student-avatar {
            margin-right: 10px;
        }
        
        .tutor-recent-students .student-avatar img {
            border-radius: 50%;
        }
        
        .tutor-recent-students .student-info strong,
        .tutor-popular-courses .course-info strong {
            color: #333;
        }
        
        .tutor-recent-students .student-info small,
        .tutor-popular-courses .course-info small {
            color: #666;
        }
        
        @media (max-width: 768px) {
            .tutor-dashboard-overview .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        </style>';
    }
});

// JavaScript inline para interatividade
add_action('admin_footer', function() {
    if (get_current_screen()->id === 'dashboard') {
        echo '<script>
        jQuery(document).ready(function($) {
            // Adicionar efeitos hover nos cards de estatísticas
            $(".stat-card").hover(
                function() {
                    $(this).css("transform", "translateY(-2px)");
                    $(this).css("box-shadow", "0 4px 8px rgba(0,0,0,0.1)");
                },
                function() {
                    $(this).css("transform", "translateY(0)");
                    $(this).css("box-shadow", "none");
                }
            );
            
            // Animação de contagem para os números
            $(".stat-number").each(function() {
                var $this = $(this);
                var countTo = parseInt($this.text().replace(/,/g, ""));
                
                if (countTo > 0) {
                    $({ countNum: 0 }).animate({
                        countNum: countTo
                    }, {
                        duration: 1000,
                        easing: "swing",
                        step: function() {
                            $this.text(Math.floor(this.countNum).toLocaleString());
                        },
                        complete: function() {
                            $this.text(countTo.toLocaleString());
                        }
                    });
                }
            });
        });
        </script>';
    }
});
?>