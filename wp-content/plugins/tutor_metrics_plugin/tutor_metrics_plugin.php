<?php
/**
 * Plugin Name: Tutor LMS Metrics Shortcodes
 * Description: Plugin que gera vários shortcodes para exibir métricas do Tutor LMS 3.5
 * Version: 1.0.0
 * Author: Seu Nome
 * Text Domain: tutor-metrics
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class TutorLMSMetricsShortcodes {
    
    public function __construct() {
        add_action('init', array($this, 'init_shortcodes'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }
    
    /**
     * Inicializa todos os shortcodes
     */
    public function init_shortcodes() {
        // Shortcodes disponíveis
        add_shortcode('tutor_total_students', array($this, 'total_students_shortcode'));
        add_shortcode('tutor_total_courses', array($this, 'total_courses_shortcode'));
        add_shortcode('tutor_course_students', array($this, 'course_students_shortcode'));
        add_shortcode('tutor_student_progress', array($this, 'student_progress_shortcode'));
        add_shortcode('tutor_course_progress', array($this, 'course_progress_shortcode'));
        add_shortcode('tutor_students_list', array($this, 'students_list_shortcode'));
        add_shortcode('tutor_courses_list', array($this, 'courses_list_shortcode'));
        add_shortcode('tutor_metrics_dashboard', array($this, 'metrics_dashboard_shortcode'));
        add_shortcode('tutor_lesson_progress', array($this, 'lesson_progress_shortcode'));
        add_shortcode('tutor_completion_rate', array($this, 'completion_rate_shortcode'));
    }
    
    /**
     * Adiciona estilos CSS
     */
    public function enqueue_styles() {
        wp_add_inline_style('wp-block-library', $this->get_css_styles());
    }
    
    /**
     * Estilos CSS para os shortcodes
     */
    private function get_css_styles() {
        return '
        .tutor-metrics {
            font-family: Arial, sans-serif;
            margin: 20px 0;
        }
        .tutor-metric-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 10px 0;
            text-align: center;
        }
        .tutor-metric-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #007cba;
            display: block;
        }
        .tutor-metric-label {
            font-size: 1.1em;
            color: #666;
            margin-top: 5px;
        }
        .tutor-progress-bar {
            background: #e9ecef;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
            overflow: hidden;
        }
        .tutor-progress-fill {
            background: linear-gradient(90deg, #28a745, #20c997);
            height: 100%;
            transition: width 0.3s ease;
        }
        .tutor-list {
            list-style: none;
            padding: 0;
        }
        .tutor-list li {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px 15px;
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tutor-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .tutor-student-progress {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
        }
        .tutor-student-name {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tutor-progress-text {
            font-size: 0.9em;
            color: #666;
            text-align: center;
            margin-top: 5px;
        }
        ';
    }
    
    /**
     * Shortcode: Total de estudantes
     * Uso: [tutor_total_students]
     */
    public function total_students_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_label' => 'true',
            'label' => 'Total de Estudantes'
        ), $atts);
        
        $total_students = $this->get_total_students();
        
        $output = '<div class="tutor-metrics tutor-metric-box">';
        $output .= '<span class="tutor-metric-number">' . $total_students . '</span>';
        
        if ($atts['show_label'] === 'true') {
            $output .= '<div class="tutor-metric-label">' . esc_html($atts['label']) . '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Total de cursos
     * Uso: [tutor_total_courses]
     */
    public function total_courses_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_label' => 'true',
            'label' => 'Total de Cursos'
        ), $atts);
        
        $total_courses = $this->get_total_courses();
        
        $output = '<div class="tutor-metrics tutor-metric-box">';
        $output .= '<span class="tutor-metric-number">' . $total_courses . '</span>';
        
        if ($atts['show_label'] === 'true') {
            $output .= '<div class="tutor-metric-label">' . esc_html($atts['label']) . '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Estudantes de um curso específico
     * Uso: [tutor_course_students course_id="123"]
     */
    public function course_students_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'show_label' => 'true'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>ID do curso é obrigatório.</p>';
        }
        
        $course_title = get_the_title($atts['course_id']);
        $students_count = $this->get_course_students_count($atts['course_id']);
        
        $output = '<div class="tutor-metrics tutor-metric-box">';
        $output .= '<span class="tutor-metric-number">' . $students_count . '</span>';
        
        if ($atts['show_label'] === 'true') {
            $output .= '<div class="tutor-metric-label">Estudantes em: ' . esc_html($course_title) . '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Progresso de um estudante específico
     * Uso: [tutor_student_progress user_id="123" course_id="456"]
     */
    public function student_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'course_id' => '',
            'show_percentage' => 'true'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>ID do curso é obrigatório.</p>';
        }
        
        $user_info = get_userdata($atts['user_id']);
        $course_title = get_the_title($atts['course_id']);
        $progress = $this->get_student_course_progress($atts['user_id'], $atts['course_id']);
        
        $output = '<div class="tutor-metrics tutor-student-progress">';
        $output .= '<div class="tutor-student-name">' . esc_html($user_info->display_name) . '</div>';
        $output .= '<div><strong>Curso:</strong> ' . esc_html($course_title) . '</div>';
        $output .= '<div class="tutor-progress-bar">';
        $output .= '<div class="tutor-progress-fill" style="width: ' . $progress . '%"></div>';
        $output .= '</div>';
        
        if ($atts['show_percentage'] === 'true') {
            $output .= '<div class="tutor-progress-text">' . $progress . '% concluído</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Progresso geral de um curso
     * Uso: [tutor_course_progress course_id="123"]
     */
    public function course_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'limit' => 10
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<p>ID do curso é obrigatório.</p>';
        }
        
        $course_title = get_the_title($atts['course_id']);
        $students_progress = $this->get_course_students_progress($atts['course_id'], $atts['limit']);
        
        $output = '<div class="tutor-metrics">';
        $output .= '<h3>Progresso dos Estudantes - ' . esc_html($course_title) . '</h3>';
        
        foreach ($students_progress as $student) {
            $output .= '<div class="tutor-student-progress">';
            $output .= '<div class="tutor-student-name">' . esc_html($student['name']) . '</div>';
            $output .= '<div class="tutor-progress-bar">';
            $output .= '<div class="tutor-progress-fill" style="width: ' . $student['progress'] . '%"></div>';
            $output .= '</div>';
            $output .= '<div class="tutor-progress-text">' . $student['progress'] . '% concluído</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Lista de estudantes
     * Uso: [tutor_students_list limit="10"]
     */
    public function students_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_email' => 'false'
        ), $atts);
        
        $students = $this->get_students_list($atts['limit']);
        
        $output = '<div class="tutor-metrics">';
        $output .= '<ul class="tutor-list">';
        
        foreach ($students as $student) {
            $output .= '<li>';
            $output .= '<span>' . esc_html($student['name']);
            
            if ($atts['show_email'] === 'true') {
                $output .= ' (' . esc_html($student['email']) . ')';
            }
            
            $output .= '</span>';
            $output .= '<span>' . $student['courses_count'] . ' cursos</span>';
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Lista de cursos
     * Uso: [tutor_courses_list limit="10"]
     */
    public function courses_list_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'show_students_count' => 'true'
        ), $atts);
        
        $courses = $this->get_courses_list($atts['limit']);
        
        $output = '<div class="tutor-metrics">';
        $output .= '<ul class="tutor-list">';
        
        foreach ($courses as $course) {
            $output .= '<li>';
            $output .= '<span>' . esc_html($course['title']) . '</span>';
            
            if ($atts['show_students_count'] === 'true') {
                $output .= '<span>' . $course['students_count'] . ' estudantes</span>';
            }
            
            $output .= '</li>';
        }
        
        $output .= '</ul>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Dashboard completo de métricas
     * Uso: [tutor_metrics_dashboard]
     */
    public function metrics_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_students' => 'true',
            'show_courses' => 'true',
            'show_completion' => 'true'
        ), $atts);
        
        $output = '<div class="tutor-metrics tutor-dashboard">';
        
        if ($atts['show_students'] === 'true') {
            $output .= $this->total_students_shortcode(array());
        }
        
        if ($atts['show_courses'] === 'true') {
            $output .= $this->total_courses_shortcode(array());
        }
        
        if ($atts['show_completion'] === 'true') {
            $completion_rate = $this->get_overall_completion_rate();
            $output .= '<div class="tutor-metric-box">';
            $output .= '<span class="tutor-metric-number">' . $completion_rate . '%</span>';
            $output .= '<div class="tutor-metric-label">Taxa de Conclusão Geral</div>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Progresso de uma lição específica
     * Uso: [tutor_lesson_progress lesson_id="123"]
     */
    public function lesson_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'lesson_id' => '',
            'user_id' => get_current_user_id()
        ), $atts);
        
        if (empty($atts['lesson_id'])) {
            return '<p>ID da lição é obrigatório.</p>';
        }
        
        $lesson_title = get_the_title($atts['lesson_id']);
        $is_completed = $this->is_lesson_completed($atts['user_id'], $atts['lesson_id']);
        
        $output = '<div class="tutor-metrics tutor-student-progress">';
        $output .= '<div><strong>Lição:</strong> ' . esc_html($lesson_title) . '</div>';
        $output .= '<div class="tutor-progress-bar">';
        $output .= '<div class="tutor-progress-fill" style="width: ' . ($is_completed ? 100 : 0) . '%"></div>';
        $output .= '</div>';
        $output .= '<div class="tutor-progress-text">' . ($is_completed ? 'Concluída' : 'Não concluída') . '</div>';
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Shortcode: Taxa de conclusão
     * Uso: [tutor_completion_rate course_id="123"]
     */
    public function completion_rate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'show_label' => 'true'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            $completion_rate = $this->get_overall_completion_rate();
            $label = 'Taxa de Conclusão Geral';
        } else {
            $completion_rate = $this->get_course_completion_rate($atts['course_id']);
            $course_title = get_the_title($atts['course_id']);
            $label = 'Taxa de Conclusão - ' . $course_title;
        }
        
        $output = '<div class="tutor-metrics tutor-metric-box">';
        $output .= '<span class="tutor-metric-number">' . $completion_rate . '%</span>';
        
        if ($atts['show_label'] === 'true') {
            $output .= '<div class="tutor-metric-label">' . esc_html($label) . '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    // === MÉTODOS AUXILIARES ===
    
    /**
     * Obtém o total de estudantes
     */
    private function get_total_students() {
        global $wpdb;
        
        $query = "SELECT COUNT(DISTINCT user_id) as total 
                  FROM {$wpdb->prefix}tutor_enrollments 
                  WHERE status = 'enrolled'";
        
        $result = $wpdb->get_var($query);
        return $result ? intval($result) : 0;
    }
    
    /**
     * Obtém o total de cursos
     */
    private function get_total_courses() {
        $courses = get_posts(array(
            'post_type' => 'courses',
            'post_status' => 'publish',
            'numberposts' => -1
        ));
        
        return count($courses);
    }
    
    /**
     * Obtém o número de estudantes em um curso específico
     */
    private function get_course_students_count($course_id) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) as total 
             FROM {$wpdb->prefix}tutor_enrollments 
             WHERE course_id = %d AND status = 'enrolled'",
            $course_id
        );
        
        $result = $wpdb->get_var($query);
        return $result ? intval($result) : 0;
    }
    
    /**
     * Obtém o progresso de um estudante em um curso
     */
    private function get_student_course_progress($user_id, $course_id) {
        if (function_exists('tutor_utils')) {
            $progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
            return round($progress, 2);
        }
        
        // Fallback se a função não existir
        global $wpdb;
        
        // Busca o total de lições no curso
        $total_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->posts} parent ON parent.ID = p.post_parent
             WHERE p.post_type = 'lesson' 
             AND p.post_status = 'publish'
             AND parent.ID = %d",
            $course_id
        ));
        
        if (!$total_lessons) return 0;
        
        // Busca lições completadas
        $completed_lessons = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_course_progress_logs pcl
             INNER JOIN {$wpdb->posts} p ON p.ID = pcl.topic_id
             INNER JOIN {$wpdb->posts} parent ON parent.ID = p.post_parent
             WHERE pcl.user_id = %d 
             AND parent.ID = %d
             AND pcl.event_type = 'lesson_completed'",
            $user_id, $course_id
        ));
        
        $progress = ($completed_lessons / $total_lessons) * 100;
        return round($progress, 2);
    }
    
    /**
     * Obtém o progresso de todos os estudantes de um curso
     */
    private function get_course_students_progress($course_id, $limit = 10) {
        global $wpdb;
        
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name, u.user_email
             FROM {$wpdb->users} u
             INNER JOIN {$wpdb->prefix}tutor_enrollments e ON e.user_id = u.ID
             WHERE e.course_id = %d AND e.status = 'enrolled'
             LIMIT %d",
            $course_id, $limit
        ));
        
        $progress_data = array();
        
        foreach ($students as $student) {
            $progress = $this->get_student_course_progress($student->ID, $course_id);
            $progress_data[] = array(
                'name' => $student->display_name,
                'email' => $student->user_email,
                'progress' => $progress
            );
        }
        
        return $progress_data;
    }
    
    /**
     * Obtém lista de estudantes
     */
    private function get_students_list($limit = 10) {
        global $wpdb;
        
        $students = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, u.user_email, COUNT(e.course_id) as courses_count
             FROM {$wpdb->users} u
             LEFT JOIN {$wpdb->prefix}tutor_enrollments e ON e.user_id = u.ID AND e.status = 'enrolled'
             INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID
             WHERE um.meta_key = '{$wpdb->prefix}capabilities' 
             AND um.meta_value LIKE '%%student%%'
             GROUP BY u.ID
             ORDER BY u.display_name
             LIMIT %d",
            $limit
        ));
        
        $students_data = array();
        
        foreach ($students as $student) {
            $students_data[] = array(
                'name' => $student->display_name,
                'email' => $student->user_email,
                'courses_count' => $student->courses_count
            );
        }
        
        return $students_data;
    }
    
    /**
     * Obtém lista de cursos
     */
    private function get_courses_list($limit = 10) {
        global $wpdb;
        
        $courses = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, COUNT(e.user_id) as students_count
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->prefix}tutor_enrollments e ON e.course_id = p.ID AND e.status = 'enrolled'
             WHERE p.post_type = 'courses' AND p.post_status = 'publish'
             GROUP BY p.ID
             ORDER BY p.post_title
             LIMIT %d",
            $limit
        ));
        
        $courses_data = array();
        
        foreach ($courses as $course) {
            $courses_data[] = array(
                'title' => $course->post_title,
                'students_count' => $course->students_count
            );
        }
        
        return $courses_data;
    }
    
    /**
     * Verifica se uma lição foi completada
     */
    private function is_lesson_completed($user_id, $lesson_id) {
        global $wpdb;
        
        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_course_progress_logs
             WHERE user_id = %d AND topic_id = %d AND event_type = 'lesson_completed'",
            $user_id, $lesson_id
        ));
        
        return $completed > 0;
    }
    
    /**
     * Obtém taxa de conclusão geral
     */
    private function get_overall_completion_rate() {
        global $wpdb;
        
        $total_enrollments = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrollments WHERE status = 'enrolled'"
        );
        
        $completed_courses = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrollments WHERE status = 'completed'"
        );
        
        if ($total_enrollments == 0) return 0;
        
        $rate = ($completed_courses / $total_enrollments) * 100;
        return round($rate, 2);
    }
    
    /**
     * Obtém taxa de conclusão de um curso específico
     */
    private function get_course_completion_rate($course_id) {
        global $wpdb;
        
        $total_enrollments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrollments 
             WHERE course_id = %d AND status = 'enrolled'",
            $course_id
        ));
        
        $completed_enrollments = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_enrollments 
             WHERE course_id = %d AND status = 'completed'",
            $course_id
        ));
        
        if ($total_enrollments == 0) return 0;
        
        $rate = ($completed_enrollments / $total_enrollments) * 100;
        return round($rate, 2);
    }
}

// Inicializa o plugin
new TutorLMSMetricsShortcodes();

/**
 * Função para desinstalar o plugin
 */
register_uninstall_hook(__FILE__, 'tutor_metrics_uninstall');

function tutor_metrics_uninstall() {
    // Limpar configurações se necessário
    delete_option('tutor_metrics_version');
}
?>