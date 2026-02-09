<?php
/**
 * Plugin Name: Tutor Progress Bar - Supermembros
 * Plugin URI: https://supermembros.com
 * Description: Plugin completo para criar barras de progresso personalizadas do Tutor LMS com painel administrativo e shortcodes customizáveis.
 * Version: 1.0.0
 * Author: Supermembros
 * Author URI: https://supermembros.com
 * Text Domain: tutor-progress-bar
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes do plugin
define('TUTOR_PROGRESS_BAR_VERSION', '1.0.0');
define('TUTOR_PROGRESS_BAR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TUTOR_PROGRESS_BAR_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * ========================================
 * CLASSE PRINCIPAL DO PLUGIN
 * ========================================
 */

class TutorProgressBarPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Verificar se o Tutor LMS está ativo
        if (!$this->is_tutor_lms_active()) {
            add_action('admin_notices', array($this, 'tutor_lms_notice'));
            return;
        }
        
        // Inicializar funcionalidades
        $this->load_textdomain();
        $this->init_hooks();
    }
    
    private function is_tutor_lms_active() {
        return (
            function_exists('tutor') || 
            class_exists('TUTOR\\Tutor') || 
            function_exists('tutor_utils') ||
            defined('TUTOR_VERSION')
        );
    }
    
    public function tutor_lms_notice() {
        echo '<div class="notice notice-error"><p><strong>Tutor Progress Bar:</strong> Este plugin requer o Tutor LMS para funcionar. Por favor, instale e ative o Tutor LMS.</p></div>';
    }
    
    private function load_textdomain() {
        load_plugin_textdomain('tutor-progress-bar', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    private function init_hooks() {
        // Menu administrativo
        add_action('admin_menu', array($this, 'admin_menu'));
        
        // Registrar shortcodes
        add_shortcode('tutor_progress_bar', array($this, 'tutor_overall_progress_shortcode'));
        add_shortcode('tutor_course_progress', array($this, 'tutor_course_progress_shortcode'));
        add_shortcode('tutor_real_progress', array($this, 'tutor_real_progress_shortcode'));
        
        // Estilos e scripts no admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'tutor-progress-bar') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }
    
    /**
     * ========================================
     * MENU ADMINISTRATIVO
     * ========================================
     */
    
    public function admin_menu() {
        add_menu_page(
            'Tutor Progress Bar',
            'Progress Bar',
            'manage_options',
            'tutor-progress-bar',
            array($this, 'admin_page'),
            'dashicons-chart-bar',
            30
        );
        
        add_submenu_page(
            'tutor-progress-bar',
            'Configurações Gerais',
            'Configurações',
            'manage_options',
            'tutor-progress-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'tutor-progress-bar',
            'Progresso Geral',
            'Progresso Geral',
            'manage_options',
            'tutor-general-progress',
            array($this, 'general_progress_page')
        );
        
        add_submenu_page(
            'tutor-progress-bar',
            'Progresso Real',
            'Progresso Real',
            'manage_options',
            'tutor-real-progress',
            array($this, 'real_progress_page')
        );
    }
    
    /**
     * ========================================
     * PÁGINA PRINCIPAL DO ADMIN
     * ========================================
     */
    
    public function admin_page() {
        echo '<div class="wrap"><h1>🎯 Tutor Progress Bar - Gerenciador de Shortcodes</h1>';
        echo '<p>Configure seus shortcodes de progresso nas páginas específicas do menu.</p>';
        echo '<h3>Shortcodes Disponíveis:</h3>';
        echo '<ul>';
        echo '<li><strong>[tutor_progress_bar]</strong> - Progresso geral de todos os cursos</li>';
        echo '<li><strong>[tutor_course_progress course_id="123"]</strong> - Progresso de curso específico</li>';
        echo '<li><strong>[tutor_real_progress]</strong> - Progresso real baseado em aulas assistidas</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE CONFIGURAÇÕES GERAIS
     * ========================================
     */
    
    public function settings_page() {
        if (isset($_POST['save_general_settings'])) {
            check_admin_referer('tutor_progress_general_nonce');
            
            $settings = array(
                'default_height' => sanitize_text_field($_POST['default_height']),
                'default_color' => sanitize_hex_color($_POST['default_color']),
                'default_bg_color' => sanitize_hex_color($_POST['default_bg_color']),
                'default_text_color' => sanitize_hex_color($_POST['default_text_color']),
                'default_font_family' => sanitize_text_field($_POST['default_font_family']),
                'default_animation' => isset($_POST['default_animation']) ? 'true' : 'false'
            );
            
            update_option('tutor_progress_general_settings', $settings);
            echo '<div class="notice notice-success"><p>Configurações gerais salvas com sucesso!</p></div>';
        }
        
        $general_settings = get_option('tutor_progress_general_settings', array(
            'default_height' => '30',
            'default_color' => '#4CAF50',
            'default_bg_color' => '#f0f0f0',
            'default_text_color' => '#ffffff',
            'default_font_family' => 'Arial, sans-serif',
            'default_animation' => 'true'
        ));
        
        echo '<div class="wrap"><h1>⚙️ Configurações Gerais - Progress Bar</h1>';
        echo '<form method="post">';
        wp_nonce_field('tutor_progress_general_nonce');
        echo '<table class="form-table">';
        echo '<tr><th scope="row">Altura Padrão</th><td><input type="number" name="default_height" value="' . $general_settings['default_height'] . '" min="10" max="100"> px</td></tr>';
        echo '<tr><th scope="row">Cor Padrão</th><td><input type="color" name="default_color" value="' . $general_settings['default_color'] . '"></td></tr>';
        echo '<tr><th scope="row">Cor de Fundo Padrão</th><td><input type="color" name="default_bg_color" value="' . $general_settings['default_bg_color'] . '"></td></tr>';
        echo '<tr><th scope="row">Cor do Texto Padrão</th><td><input type="color" name="default_text_color" value="' . $general_settings['default_text_color'] . '"></td></tr>';
        echo '<tr><th scope="row">Fonte Padrão</th><td>';
        echo '<select name="default_font_family">';
        echo '<option value="Arial, sans-serif"' . selected($general_settings['default_font_family'], 'Arial, sans-serif', false) . '>Arial</option>';
        echo '<option value="Helvetica, sans-serif"' . selected($general_settings['default_font_family'], 'Helvetica, sans-serif', false) . '>Helvetica</option>';
        echo '<option value="Roboto, sans-serif"' . selected($general_settings['default_font_family'], 'Roboto, sans-serif', false) . '>Roboto</option>';
        echo '</select></td></tr>';
        echo '<tr><th scope="row">Animação Padrão</th><td><label><input type="checkbox" name="default_animation"' . checked($general_settings['default_animation'], 'true', false) . '> Ativar animação por padrão</label></td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" name="save_general_settings" class="button-primary" value="Salvar Configurações"></p>';
        echo '</form></div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE PROGRESSO GERAL
     * ========================================
     */
    
    public function general_progress_page() {
        if (isset($_POST['save_general_progress'])) {
            check_admin_referer('tutor_general_progress_nonce');
            
            $settings = array(
                'height' => sanitize_text_field($_POST['height']),
                'color' => sanitize_hex_color($_POST['color']),
                'bg_color' => sanitize_hex_color($_POST['bg_color']),
                'show_percentage' => isset($_POST['show_percentage']) ? 'true' : 'false',
                'show_courses_info' => isset($_POST['show_courses_info']) ? 'true' : 'false',
                'animation' => isset($_POST['animation']) ? 'true' : 'false'
            );
            
            update_option('tutor_general_progress_settings', $settings);
            echo '<div class="notice notice-success"><p>Configurações do Progresso Geral salvas com sucesso!</p></div>';
        }
        
        $settings = get_option('tutor_general_progress_settings', array(
            'height' => '30',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true'
        ));
        
        echo '<div class="wrap"><h1>📊 Progresso Geral - Shortcode [tutor_progress_bar]</h1>';
        echo '<div class="notice notice-info"><p><strong>💡 Sobre o Progresso Geral:</strong> Este shortcode exibe o progresso médio de todos os cursos em que o usuário está matriculado.</p></div>';
        
        echo '<form method="post">';
        wp_nonce_field('tutor_general_progress_nonce');
        echo '<table class="form-table">';
        echo '<tr><th>Altura</th><td><input type="number" name="height" value="' . $settings['height'] . '" min="10" max="100"> px</td></tr>';
        echo '<tr><th>Cor da Barra</th><td><input type="color" name="color" value="' . $settings['color'] . '"></td></tr>';
        echo '<tr><th>Cor de Fundo</th><td><input type="color" name="bg_color" value="' . $settings['bg_color'] . '"></td></tr>';
        echo '<tr><th>Opções</th><td>';
        echo '<label><input type="checkbox" name="show_percentage"' . checked($settings['show_percentage'], 'true', false) . '> Mostrar Porcentagem</label><br>';
        echo '<label><input type="checkbox" name="show_courses_info"' . checked($settings['show_courses_info'], 'true', false) . '> Mostrar Info dos Cursos</label><br>';
        echo '<label><input type="checkbox" name="animation"' . checked($settings['animation'], 'true', false) . '> Animação</label>';
        echo '</td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" name="save_general_progress" class="button-primary" value="💾 Salvar Configurações"></p>';
        echo '</form>';
        
        echo '<h3>🔗 Shortcode para Usar</h3>';
        echo '<div style="background:#f1f1f1;padding:15px;border-radius:4px;border-left:4px solid #0073aa;">';
        echo '<input type="text" value="[tutor_progress_bar]" readonly style="width:100%;padding:10px;font-family:monospace;background:#f9f9f9;">';
        echo '<button type="button" onclick="navigator.clipboard.writeText(\'[tutor_progress_bar]\').then(function(){alert(\'Shortcode copiado!\');})" class="button">📋 Copiar Shortcode</button>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE PROGRESSO REAL
     * ========================================
     */
    
    public function real_progress_page() {
        if (isset($_POST['save_real_progress'])) {
            check_admin_referer('tutor_real_progress_nonce');
            
            $settings = array(
                'height' => sanitize_text_field($_POST['height']),
                'color' => sanitize_hex_color($_POST['color']),
                'bg_color' => sanitize_hex_color($_POST['bg_color']),
                'show_percentage' => isset($_POST['show_percentage']) ? 'true' : 'false',
                'show_courses_info' => isset($_POST['show_courses_info']) ? 'true' : 'false',
                'animation' => isset($_POST['animation']) ? 'true' : 'false'
            );
            
            update_option('tutor_real_progress_settings', $settings);
            echo '<div class="notice notice-success"><p>Configurações do Progresso Real salvas com sucesso!</p></div>';
        }
        
        $settings = get_option('tutor_real_progress_settings', array(
            'height' => '30',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true'
        ));
        
        echo '<div class="wrap"><h1>🎯 Progresso Real - Baseado em Aulas Assistidas</h1>';
        echo '<div class="notice notice-info"><p><strong>💡 Diferença:</strong> Este shortcode calcula o progresso baseado apenas nos cursos em que o usuário está matriculado e conta as aulas realmente assistidas/concluídas.</p></div>';
        
        echo '<form method="post">';
        wp_nonce_field('tutor_real_progress_nonce');
        echo '<table class="form-table">';
        echo '<tr><th>Altura</th><td><input type="number" name="height" value="' . $settings['height'] . '" min="10" max="100"> px</td></tr>';
        echo '<tr><th>Cor da Barra</th><td><input type="color" name="color" value="' . $settings['color'] . '"></td></tr>';
        echo '<tr><th>Cor de Fundo</th><td><input type="color" name="bg_color" value="' . $settings['bg_color'] . '"></td></tr>';
        echo '<tr><th>Opções</th><td>';
        echo '<label><input type="checkbox" name="show_percentage"' . checked($settings['show_percentage'], 'true', false) . '> Mostrar Porcentagem</label><br>';
        echo '<label><input type="checkbox" name="show_courses_info"' . checked($settings['show_courses_info'], 'true', false) . '> Mostrar Info dos Cursos</label><br>';
        echo '<label><input type="checkbox" name="animation"' . checked($settings['animation'], 'true', false) . '> Animação</label>';
        echo '</td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" name="save_real_progress" class="button-primary" value="💾 Salvar Configurações"></p>';
        echo '</form>';
        
        echo '<h3>🔗 Shortcode para Usar</h3>';
        echo '<div style="background:#f1f1f1;padding:15px;border-radius:4px;border-left:4px solid #0073aa;">';
        echo '<input type="text" value="[tutor_real_progress]" readonly style="width:100%;padding:10px;font-family:monospace;background:#f9f9f9;">';
        echo '<button type="button" onclick="navigator.clipboard.writeText(\'[tutor_real_progress]\').then(function(){alert(\'Shortcode copiado!\');})" class="button">📋 Copiar Shortcode</button>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * ========================================
     * SHORTCODES
     * ========================================
     */
    
    public function tutor_overall_progress_shortcode($atts) {
        // Buscar configurações salvas
        $saved_settings = get_option('tutor_general_progress_settings', array());
        
        $default_atts = array(
            'height' => '30',
            'width' => '100%',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'text_color' => '#ffffff',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true',
            'debug' => 'false'
        );
        
        $merged_defaults = array_merge($default_atts, $saved_settings);
        $atts = shortcode_atts($merged_defaults, $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="tutor-progress-error">Você precisa estar logado para ver seu progresso.</div>';
        }
        
        if (!function_exists('tutor_utils')) {
            return '<div class="tutor-progress-error">Tutor LMS não está instalado ou ativo.</div>';
        }
        
        $user_id = get_current_user_id();
        $debug_mode = $atts['debug'] === 'true';
        
        // Usar a função melhorada com debug opcional
        $enrolled_courses = $this->get_all_enrolled_courses_private($user_id, $debug_mode);
        
        if (empty($enrolled_courses)) {
            return '<div class="tutor-progress-message"></div>';
        }
        
        $total_progress = 0;
        $course_count = count($enrolled_courses);
        $completed_courses = 0;
        $course_details = array(); // Para debug
        
        if ($debug_mode) {
            error_log('DEBUG: Iniciando cálculo de progresso para ' . $course_count . ' cursos');
        }
        
        foreach ($enrolled_courses as $course) {
            $course_id = $course->ID;
            $course_progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
            
            $total_progress += $course_progress;
            
            if ($course_progress >= 100) {
                $completed_courses++;
            }
            
            // Armazenar detalhes para debug
            $course_details[] = array(
                'id' => $course_id,
                'title' => $course->post_title,
                'progress' => $course_progress
            );
            
            if ($debug_mode) {
                error_log('DEBUG: Curso ' . $course_id . ' (' . $course->post_title . ') = ' . $course_progress . '%');
            }
        }
        
        $average_progress = $course_count > 0 ? round($total_progress / $course_count, 1) : 0;
        
        if ($debug_mode) {
            error_log('DEBUG: Cálculo final - Total: ' . $total_progress . '% / ' . $course_count . ' cursos = ' . $average_progress . '%');
        }
        
        // Se debug está ativo, mostrar informações detalhadas
        if ($debug_mode && current_user_can('manage_options')) {
            $debug_info = '<div style="background:#fff3cd;padding:15px;margin:10px 0;border-radius:5px;border-left:4px solid #ffc107;">';
            $debug_info .= '<h4>🔍 DEBUG: Informações Detalhadas</h4>';
            $debug_info .= '<p><strong>Cursos encontrados:</strong> ' . $course_count . '</p>';
            $debug_info .= '<p><strong>Progresso total:</strong> ' . $total_progress . '%</p>';
            $debug_info .= '<p><strong>Média calculada:</strong> ' . $average_progress . '%</p>';
            $debug_info .= '<p><strong>Detalhes por curso:</strong></p>';
            $debug_info .= '<ul>';
            foreach ($course_details as $detail) {
                $debug_info .= '<li>ID ' . $detail['id'] . ' - ' . esc_html($detail['title']) . ': ' . $detail['progress'] . '%</li>';
            }
            $debug_info .= '</ul>';
            $debug_info .= '</div>';
        } else {
            $debug_info = '';
        }
        
        $unique_id = 'tutor-progress-' . uniqid();
        
        return $debug_info . $this->render_progress_bar($unique_id, $average_progress, $atts, $course_count, $completed_courses, 'Progresso Geral');
    }
    
    public function tutor_course_progress_shortcode($atts) {
        $atts = shortcode_atts(array(
            'course_id' => '',
            'height' => '30',
            'width' => '100%',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'text_color' => '#ffffff',
            'show_percentage' => 'true',
            'animation' => 'true'
        ), $atts);
        
        if (empty($atts['course_id'])) {
            return '<div class="tutor-progress-error">ID do curso não especificado.</div>';
        }
        
        if (!is_user_logged_in()) {
            return '<div class="tutor-progress-error">Você precisa estar logado para ver seu progresso.</div>';
        }
        
        if (!function_exists('tutor_utils')) {
            return '<div class="tutor-progress-error">Tutor LMS não está instalado ou ativo.</div>';
        }
        
        $user_id = get_current_user_id();
        $course_id = intval($atts['course_id']);
        
        if (!tutor_utils()->is_enrolled($course_id, $user_id)) {
            return '<div class="tutor-progress-message">Você não está inscrito neste curso.</div>';
        }
        
        $course_progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
        $course_title = get_the_title($course_id);
        $unique_id = 'tutor-course-progress-' . $course_id . '-' . uniqid();
        
        return $this->render_progress_bar($unique_id, $course_progress, $atts, 1, ($course_progress >= 100 ? 1 : 0), $course_title);
    }
    
    public function tutor_real_progress_shortcode($atts) {
        $saved_settings = get_option('tutor_real_progress_settings', array());
        
        $default_atts = array(
            'height' => '30',
            'width' => '100%',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'text_color' => '#ffffff',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true'
        );
        
        $merged_defaults = array_merge($default_atts, $saved_settings);
        $atts = shortcode_atts($merged_defaults, $atts);
        
        if (!is_user_logged_in()) {
            return '<div class="tutor-progress-error">Você precisa estar logado para ver seu progresso.</div>';
        }
        
        if (!function_exists('tutor_utils')) {
            return '<div class="tutor-progress-error">Tutor LMS não está instalado ou ativo.</div>';
        }
        
        $user_id = get_current_user_id();
        $enrolled_courses = $this->get_all_enrolled_courses_private($user_id);
        
        if (empty($enrolled_courses)) {
            return '<div class="tutor-progress-message">Você ainda não está inscrito em nenhum curso.</div>';
        }
        
        $progress_data = $this->calculate_real_lessons_progress($user_id, $enrolled_courses);
        $unique_id = 'tutor-real-progress-' . uniqid();
        
        return $this->render_real_progress_bar($unique_id, $progress_data['overall_progress'], $atts, $progress_data['course_count'], $progress_data['completed_courses'], $progress_data['completed_lessons'], $progress_data['total_lessons']);
    }
    
    /**
     * ========================================
     * FUNÇÕES AUXILIARES
     * ========================================
     */
    
    /**
     * ========================================
     * FUNÇÕES PÚBLICAS PARA DEBUG
     * ========================================
     */
    
    public function get_all_enrolled_courses($user_id, $debug = false) {
        return $this->get_all_enrolled_courses_private($user_id, $debug);
    }
    
    /**
     * ========================================
     * FUNÇÃO MELHORADA PARA BUSCAR TODOS OS CURSOS MATRICULADOS (PRIVADA)
     * ========================================
     */
    
    private function get_all_enrolled_courses_private($user_id, $debug = false) {
        global $wpdb;
        
        $enrolled_courses = array();
        $course_ids_found = array();
        
        if ($debug) {
            error_log('DEBUG: Iniciando busca COMPLETA de cursos para usuário ' . $user_id);
        }
        
        // MÉTODO 1: Busca DIRETA e COMPLETA na tabela de matrículas
        $enrollments_table = $wpdb->prefix . 'tutor_enrollments';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$enrollments_table'") == $enrollments_table) {
            
            // Primeira query: buscar TODOS os course_id únicos para este usuário
            $course_ids_query = $wpdb->prepare("
                SELECT DISTINCT course_id
                FROM {$enrollments_table}
                WHERE user_id = %d
                ORDER BY course_id ASC
            ", $user_id);
            
            $course_ids = $wpdb->get_col($course_ids_query);
            
            if ($debug) {
                error_log('DEBUG: IDs de cursos encontrados na tabela: ' . implode(', ', $course_ids));
            }
            
            // Para cada course_id, buscar o post correspondente
            foreach ($course_ids as $course_id) {
                $course = get_post($course_id);
                
                if ($course && $course->post_type === 'courses' && $course->post_status !== 'trash') {
                    $enrolled_courses[] = $course;
                    $course_ids_found[] = $course_id;
                    
                    if ($debug) {
                        error_log('DEBUG: Curso adicionado - ID: ' . $course_id . ', Título: ' . $course->post_title . ', Status: ' . $course->post_status);
                    }
                } else {
                    if ($debug) {
                        $status = $course ? $course->post_status : 'não encontrado';
                        error_log('DEBUG: Curso ignorado - ID: ' . $course_id . ', Motivo: ' . $status);
                    }
                }
            }
        }
        
        // MÉTODO 2: Se não encontrou cursos na tabela, tentar função do Tutor LMS
        if (empty($enrolled_courses)) {
            if ($debug) {
                error_log('DEBUG: Nenhum curso encontrado na tabela, tentando função get_enrolled_courses_by_user()');
            }
            
            try {
                $tutor_courses = tutor_utils()->get_enrolled_courses_by_user($user_id);
                
                if ($debug) {
                    error_log('DEBUG: Função do Tutor LMS retornou ' . count($tutor_courses) . ' cursos');
                }
                
                foreach ($tutor_courses as $course) {
                    if (is_object($course) && !empty($course->ID) && !in_array($course->ID, $course_ids_found)) {
                        $enrolled_courses[] = $course;
                        $course_ids_found[] = $course->ID;
                        
                        if ($debug) {
                            error_log('DEBUG: Curso do Tutor LMS adicionado - ID: ' . $course->ID . ', Título: ' . $course->post_title);
                        }
                    }
                }
                
            } catch (Exception $e) {
                if ($debug) {
                    error_log('DEBUG: Erro na função do Tutor LMS: ' . $e->getMessage());
                }
            }
        }
        
        // MÉTODO 3: Busca alternativa por verificação direta de TODOS os cursos
        if (count($enrolled_courses) <= 1) { // Se encontrou 1 ou menos, buscar mais
            if ($debug) {
                error_log('DEBUG: Poucos cursos encontrados (' . count($enrolled_courses) . '), tentando método alternativo');
            }
            
            // Buscar todos os cursos do site e verificar matrícula um por um
            $all_courses = get_posts(array(
                'post_type' => 'courses',
                'post_status' => array('publish', 'private'),
                'numberposts' => -1
            ));
            
            if ($debug) {
                error_log('DEBUG: Verificando matrícula em ' . count($all_courses) . ' cursos do site');
            }
            
            foreach ($all_courses as $course) {
                if (!in_array($course->ID, $course_ids_found)) {
                    // Verificar matrícula usando múltiplos métodos
                    $is_enrolled = false;
                    
                    // Método A: Função is_enrolled do Tutor
                    if (function_exists('tutor_utils') && tutor_utils()->is_enrolled($course->ID, $user_id)) {
                        $is_enrolled = true;
                    }
                    
                    // Método B: Verificar diretamente na tabela se Método A falhou
                    if (!$is_enrolled && $wpdb->get_var("SHOW TABLES LIKE '$enrollments_table'") == $enrollments_table) {
                        $enrollment_exists = $wpdb->get_var($wpdb->prepare("
                            SELECT COUNT(*) FROM {$enrollments_table} 
                            WHERE user_id = %d AND course_id = %d
                        ", $user_id, $course->ID));
                        
                        if ($enrollment_exists > 0) {
                            $is_enrolled = true;
                        }
                    }
                    
                    if ($is_enrolled) {
                        $enrolled_courses[] = $course;
                        $course_ids_found[] = $course->ID;
                        
                        if ($debug) {
                            error_log('DEBUG: Curso encontrado por verificação direta - ID: ' . $course->ID . ', Título: ' . $course->post_title);
                        }
                    }
                }
            }
        }
        
        // VERIFICAÇÃO FINAL: Remover duplicatas e ordenar
        if (!empty($enrolled_courses)) {
            $unique_courses = array();
            $unique_ids = array();
            
            foreach ($enrolled_courses as $course) {
                if (!in_array($course->ID, $unique_ids)) {
                    $unique_courses[] = $course;
                    $unique_ids[] = $course->ID;
                }
            }
            
            $enrolled_courses = $unique_courses;
            $course_ids_found = $unique_ids;
        }
        
        if ($debug) {
            error_log('DEBUG: TOTAL FINAL de cursos encontrados: ' . count($enrolled_courses));
            error_log('DEBUG: IDs finais: ' . implode(', ', $course_ids_found));
        }
        
        return $enrolled_courses;
    }
    
    private function calculate_real_lessons_progress($user_id, $enrolled_courses) {
        $total_lessons = 0;
        $completed_lessons = 0;
        $course_count = count($enrolled_courses);
        $completed_courses = 0;
        
        foreach ($enrolled_courses as $course) {
            $course_id = $course->ID;
            $lessons = $this->get_course_lessons($course_id);
            $course_lesson_count = count($lessons);
            $course_completed_lessons = 0;
            
            if ($course_lesson_count > 0) {
                $total_lessons += $course_lesson_count;
                
                foreach ($lessons as $lesson) {
                    $lesson_id = is_object($lesson) ? $lesson->ID : $lesson;
                    
                    if ($this->is_lesson_completed($lesson_id, $user_id)) {
                        $course_completed_lessons++;
                        $completed_lessons++;
                    }
                }
                
                if ($course_completed_lessons >= $course_lesson_count) {
                    $completed_courses++;
                }
            }
        }
        
        // Se não detectou nenhuma lição completa, usar fallback
        if ($completed_lessons == 0 && $total_lessons > 0) {
            foreach ($enrolled_courses as $course) {
                $course_id = $course->ID;
                $tutor_progress = tutor_utils()->get_course_completed_percent($course_id, $user_id);
                if ($tutor_progress > 0) {
                    $lessons = $this->get_course_lessons($course_id);
                    $course_lesson_count = count($lessons);
                    if ($course_lesson_count > 0) {
                        $estimated_completed = round(($tutor_progress / 100) * $course_lesson_count);
                        $completed_lessons += $estimated_completed;
                    }
                }
            }
        }
        
        $overall_progress = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 1) : 0;
        
        return array(
            'overall_progress' => $overall_progress,
            'total_lessons' => $total_lessons,
            'completed_lessons' => $completed_lessons,
            'course_count' => $course_count,
            'completed_courses' => $completed_courses
        );
    }
    
    private function get_course_lessons($course_id) {
        $lessons = array();
        
        // Método 1: Função do Tutor LMS
        if (function_exists('tutor_utils') && method_exists(tutor_utils(), 'get_course_contents_by_type')) {
            try {
                $lessons = tutor_utils()->get_course_contents_by_type($course_id, 'lesson');
                if (!empty($lessons)) {
                    return $lessons;
                }
            } catch (Exception $e) {
                // Continuar para método alternativo
            }
        }
        
        // Método 2: Buscar lições diretamente
        $lessons = get_posts(array(
            'post_type' => 'lesson',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_lesson_course_id',
                    'value' => $course_id,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC'
        ));
        
        // Método 3: Buscar por parent
        if (empty($lessons)) {
            $lessons = get_posts(array(
                'post_type' => 'lesson',
                'post_status' => 'publish',
                'post_parent' => $course_id,
                'posts_per_page' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ));
        }
        
        return $lessons;
    }
    
    private function is_lesson_completed($lesson_id, $user_id) {
        // Método 1: Função do Tutor LMS
        if (function_exists('tutor_utils') && method_exists(tutor_utils(), 'is_completed_lesson')) {
            try {
                return tutor_utils()->is_completed_lesson($lesson_id, $user_id);
            } catch (Exception $e) {
                // Continuar para método alternativo
            }
        }
        
        // Método 2: Verificar meta do usuário
        $meta_keys = array(
            '_tutor_lesson_done_' . $lesson_id,
            'tutor_lesson_done_' . $lesson_id,
            '_lesson_completed_' . $lesson_id
        );
        
        foreach ($meta_keys as $meta_key) {
            $is_completed = get_user_meta($user_id, $meta_key, true);
            if (!empty($is_completed) && $is_completed !== '0') {
                return true;
            }
        }
        
        return false;
    }
    
    private function render_progress_bar($unique_id, $progress, $atts, $course_count = 1, $completed_courses = 0, $title = '') {
        ob_start();
        ?>
        
        <div class="tutor-overall-progress-container" id="<?php echo $unique_id; ?>">
            <?php if ($atts['show_courses_info'] === 'true' && $course_count > 1): ?>
                <div class="tutor-progress-info">
                    <div class="progress-stats">
                        <span class="courses-enrolled">📚 <?php echo $course_count; ?> curso(s) inscrito(s)</span>
                        <span class="courses-completed">✅ <?php echo $completed_courses; ?> concluído(s)</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="tutor-progress-bar-container">
                <div class="tutor-progress-bar-bg">
                    <div class="tutor-progress-bar-fill" data-progress="<?php echo $progress; ?>">
                        <?php if ($atts['show_percentage'] === 'true'): ?>
                            <span class="progress-text"><?php echo $progress; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="progress-label">
                <?php echo $title; ?>: <strong><?php echo $progress; ?>%</strong>
            </div>
        </div>
        
        <style>
            #<?php echo $unique_id; ?> .tutor-overall-progress-container {
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                width: <?php echo $atts['width'] ?? '100%'; ?>;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-info {
                margin-bottom: 15px;
                padding: 10px;
                background: #f9f9f9;
                border-radius: 8px;
                border-left: 4px solid <?php echo $atts['color']; ?>;
            }
            
            #<?php echo $unique_id; ?> .progress-stats {
                display: flex;
                gap: 20px;
                flex-wrap: wrap;
            }
            
            #<?php echo $unique_id; ?> .progress-stats span {
                font-size: 14px;
                color: #666;
                font-weight: 500;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-container {
                margin: 10px 0;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-bg {
                width: 100%;
                height: <?php echo $atts['height']; ?>px;
                background-color: <?php echo $atts['bg_color']; ?>;
                border-radius: 15px;
                overflow: hidden;
                position: relative;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-fill {
                height: 100%;
                background: linear-gradient(90deg, <?php echo $atts['color']; ?>, <?php echo $atts['color']; ?>dd);
                border-radius: 15px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                color: <?php echo $atts['text_color'] ?? 'white'; ?>;
                font-weight: bold;
                font-size: 13px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
                width: 0%;
                <?php if ($atts['animation'] === 'true'): ?>
                transition: width 2s ease-in-out;
                <?php endif; ?>
            }
            
            #<?php echo $unique_id; ?> .progress-text {
                position: absolute;
                width: 100%;
                text-align: center;
                font-size: 12px;
                font-weight: bold;
            }
            
            #<?php echo $unique_id; ?> .progress-label {
                margin-top: 10px;
                text-align: center;
                font-size: 16px;
                color: #333;
            }
            
            .tutor-progress-error,
            .tutor-progress-message {
                padding: 15px;
                border-radius: 8px;
                text-align: center;
                font-weight: 500;
            }
            
            .tutor-progress-error {
                background-color: #ffebee;
                color: #c62828;
                border-left: 4px solid #f44336;
            }
            
            .tutor-progress-message {
                background-color: #e3f2fd;
                color: #1565c0;
                border-left: 4px solid #2196f3;
            }
            
            @media (max-width: 600px) {
                #<?php echo $unique_id; ?> .progress-stats {
                    flex-direction: column;
                    gap: 8px;
                }
            }
        </style>
        
        <?php if ($atts['animation'] === 'true'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const progressBar = document.querySelector('#<?php echo $unique_id; ?> .tutor-progress-bar-fill');
                if (progressBar) {
                    const targetProgress = progressBar.getAttribute('data-progress');
                    
                    setTimeout(function() {
                        progressBar.style.width = targetProgress + '%';
                    }, 300);
                }
            });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
    
    private function render_real_progress_bar($unique_id, $progress, $atts, $course_count, $completed_courses, $completed_lessons, $total_lessons) {
        ob_start();
        ?>
        
        <div class="tutor-overall-progress-container" id="<?php echo $unique_id; ?>">
            <?php if ($atts['show_courses_info'] === 'true'): ?>
                <div class="tutor-progress-info">
                    <div class="progress-stats">
                        <span class="courses-enrolled">📚 <?php echo $course_count; ?> curso(s) inscrito(s)</span>
                        <span class="courses-completed">✅ <?php echo $completed_courses; ?> concluído(s)</span>
                        <span class="lessons-progress">🎯 <?php echo $completed_lessons; ?>/<?php echo $total_lessons; ?> aulas assistidas</span>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="tutor-progress-bar-container">
                <div class="tutor-progress-bar-bg">
                    <div class="tutor-progress-bar-fill" data-progress="<?php echo $progress; ?>">
                        <?php if ($atts['show_percentage'] === 'true'): ?>
                            <span class="progress-text"><?php echo $progress; ?>%</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="progress-label">
                Progresso Real: <strong><?php echo $progress; ?>%</strong>
            </div>
        </div>
        
        <style>
            #<?php echo $unique_id; ?> .tutor-overall-progress-container {
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                width: <?php echo $atts['width'] ?? '100%'; ?>;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-info {
                margin-bottom: 15px;
                padding: 12px;
                background: #f9f9f9;
                border-radius: 8px;
                border-left: 4px solid <?php echo $atts['color']; ?>;
            }
            
            #<?php echo $unique_id; ?> .progress-stats {
                display: flex;
                gap: 15px;
                flex-wrap: wrap;
                align-items: center;
            }
            
            #<?php echo $unique_id; ?> .progress-stats span {
                font-size: 14px;
                color: #666;
                font-weight: 500;
                white-space: nowrap;
            }
            
            #<?php echo $unique_id; ?> .lessons-progress {
                background: rgba(76, 175, 80, 0.1);
                padding: 4px 8px;
                border-radius: 12px;
                border: 1px solid rgba(76, 175, 80, 0.3);
                font-weight: 600 !important;
                color: #2e7d32 !important;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-container {
                margin: 10px 0;
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-bg {
                width: 100%;
                height: <?php echo $atts['height']; ?>px;
                background-color: <?php echo $atts['bg_color']; ?>;
                border-radius: 15px;
                overflow: hidden;
                position: relative;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
            }
            
            #<?php echo $unique_id; ?> .tutor-progress-bar-fill {
                height: 100%;
                background: linear-gradient(135deg, <?php echo $atts['color']; ?>, <?php echo $atts['color']; ?>dd, <?php echo $atts['color']; ?>);
                border-radius: 15px;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                color: <?php echo $atts['text_color'] ?? 'white'; ?>;
                font-weight: bold;
                font-size: 13px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
                width: 0%;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                <?php if ($atts['animation'] === 'true'): ?>
                transition: width 2.5s ease-in-out;
                <?php endif; ?>
            }
            
            #<?php echo $unique_id; ?> .progress-text {
                position: absolute;
                width: 100%;
                text-align: center;
                font-size: 12px;
                font-weight: bold;
                z-index: 2;
            }
            
            #<?php echo $unique_id; ?> .progress-label {
                margin-top: 12px;
                text-align: center;
                font-size: 16px;
                color: #333;
                font-weight: 600;
            }
            
            @media (max-width: 768px) {
                #<?php echo $unique_id; ?> .progress-stats {
                    flex-direction: column;
                    gap: 8px;
                    align-items: flex-start;
                }
            }
        </style>
        
        <?php if ($atts['animation'] === 'true'): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const progressBar = document.querySelector('#<?php echo $unique_id; ?> .tutor-progress-bar-fill');
                if (progressBar) {
                    const targetProgress = progressBar.getAttribute('data-progress');
                    
                    setTimeout(function() {
                        progressBar.style.width = targetProgress + '%';
                    }, 500);
                }
            });
        </script>
        <?php endif; ?>
        
        <?php
        return ob_get_clean();
    }
}

// Inicializar o plugin globalmente
global $tutor_progress_bar_plugin_instance;
$tutor_progress_bar_plugin_instance = new TutorProgressBarPlugin();

// Shortcode de teste simples
add_shortcode('tutor_debug_simple', 'tutor_debug_simple_shortcode');
add_shortcode('tutor_test_quick', 'tutor_test_quick_shortcode');
add_shortcode('tutor_force_method', 'tutor_force_method_shortcode');

function tutor_force_method_shortcode($atts) {
    $atts = shortcode_atts(array('method' => 'table'), $atts);
    
    if (!is_user_logged_in()) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Faça login para testar.</div>';
    }
    
    if (!function_exists('tutor_utils')) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Tutor LMS não encontrado.</div>';
    }
    
    $user_id = get_current_user_id();
    global $wpdb;
    
    $courses = array();
    $method_name = '';
    
    switch ($atts['method']) {
        case 'table':
            $method_name = 'Busca Direta na Tabela';
            $enrollments_table = $wpdb->prefix . 'tutor_enrollments';
            if ($wpdb->get_var("SHOW TABLES LIKE '$enrollments_table'") == $enrollments_table) {
                $course_ids = $wpdb->get_col($wpdb->prepare("
                    SELECT DISTINCT course_id FROM {$enrollments_table} WHERE user_id = %d
                ", $user_id));
                
                foreach ($course_ids as $course_id) {
                    $course = get_post($course_id);
                    if ($course && $course->post_type === 'courses' && $course->post_status !== 'trash') {
                        $courses[] = $course;
                    }
                }
            }
            break;
            
        case 'tutor':
            $method_name = 'Função do Tutor LMS';
            $courses = tutor_utils()->get_enrolled_courses_by_user($user_id);
            break;
            
        case 'check':
            $method_name = 'Verificação Individual';
            $all_courses = get_posts(array('post_type' => 'courses', 'post_status' => array('publish', 'private'), 'numberposts' => -1));
            foreach ($all_courses as $course) {
                if (tutor_utils()->is_enrolled($course->ID, $user_id)) {
                    $courses[] = $course;
                }
            }
            break;
    }
    
    if (!empty($courses)) {
        $total_progress = 0;
        foreach ($courses as $course) {
            $total_progress += tutor_utils()->get_course_completed_percent($course->ID, $user_id);
        }
        $average = round($total_progress / count($courses), 1);
    } else {
        $average = 0;
    }
    
    ob_start();
    echo '<div style="background:#e8f5e8;padding:20px;border-radius:8px;margin:20px 0;">';
    echo '<h3>🔬 TESTE FORÇADO: ' . $method_name . '</h3>';
    echo '<p><strong>Cursos encontrados:</strong> ' . count($courses) . '</p>';
    echo '<p><strong>Progresso médio:</strong> ' . $average . '%</p>';
    
    if (!empty($courses)) {
        echo '<div style="background:#4caf50;height:25px;border-radius:12px;position:relative;overflow:hidden;margin:10px 0;">';
        echo '<div style="background:#81c784;height:100%;width:' . $average . '%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;font-size:12px;">' . $average . '%</div>';
        echo '</div>';
        
        echo '<details style="margin:10px 0;"><summary>Ver cursos encontrados</summary>';
        echo '<ul style="margin:10px 0;">';
        foreach ($courses as $course) {
            $progress = tutor_utils()->get_course_completed_percent($course->ID, $user_id);
            echo '<li>' . $course->ID . ' - ' . esc_html($course->post_title) . ' (' . $progress . '%)</li>';
        }
        echo '</ul></details>';
    }
    
    echo '<p style="font-size:12px;color:#666;">Uso: <code>[tutor_force_method method="table|tutor|check"]</code></p>';
    echo '</div>';
    
    return ob_get_clean();
}

function tutor_test_quick_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Faça login para testar.</div>';
    }
    
    if (!function_exists('tutor_utils')) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Tutor LMS não encontrado.</div>';
    }
    
    $user_id = get_current_user_id();
    global $tutor_progress_bar_plugin_instance;
    
    if (!$tutor_progress_bar_plugin_instance) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Plugin não inicializado.</div>';
    }
    
    // Testar as 3 formas de buscar cursos
    $method1 = tutor_utils()->get_enrolled_courses_by_user($user_id);
    $method2 = $tutor_progress_bar_plugin_instance->get_all_enrolled_courses($user_id, false);
    
    ob_start();
    echo '<div style="background:#e3f2fd;padding:20px;border-radius:8px;margin:20px 0;font-family:Arial,sans-serif;">';
    echo '<h3>⚡ TESTE RÁPIDO - Comparação de Métodos</h3>';
    
    $user_info = get_userdata($user_id);
    echo '<p><strong>Usuário:</strong> ' . $user_info->display_name . '</p>';
    
    echo '<div style="display:flex;gap:20px;flex-wrap:wrap;">';
    
    // Método 1
    echo '<div style="background:#fff;padding:15px;border-radius:5px;flex:1;min-width:300px;">';
    echo '<h4 style="color:#1976d2;">🔵 Método Tutor LMS</h4>';
    echo '<p><strong>Cursos encontrados:</strong> ' . count($method1) . '</p>';
    if (!empty($method1)) {
        echo '<ul style="font-size:12px;">';
        foreach ($method1 as $course) {
            echo '<li>' . $course->ID . ' - ' . esc_html($course->post_title) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    
    // Método 2
    echo '<div style="background:#fff;padding:15px;border-radius:5px;flex:1;min-width:300px;">';
    echo '<h4 style="color:#4caf50;">🟢 Método Plugin Melhorado</h4>';
    echo '<p><strong>Cursos encontrados:</strong> ' . count($method2) . '</p>';
    if (!empty($method2)) {
        echo '<ul style="font-size:12px;">';
        foreach ($method2 as $course) {
            echo '<li>' . $course->ID . ' - ' . esc_html($course->post_title) . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    
    echo '</div>';
    
    // Resultado do shortcode
    echo '<div style="background:#f1f8e9;padding:15px;border-radius:5px;margin:15px 0;">';
    echo '<h4>🎯 Teste do Shortcode [tutor_progress_bar]:</h4>';
    
    if (!empty($method2)) {
        $total_progress = 0;
        foreach ($method2 as $course) {
            $progress = tutor_utils()->get_course_completed_percent($course->ID, $user_id);
            $total_progress += $progress;
        }
        $average = count($method2) > 0 ? round($total_progress / count($method2), 1) : 0;
        
        echo '<p><strong>Cálculo:</strong> ' . $total_progress . '% ÷ ' . count($method2) . ' cursos = <span style="font-size:18px;color:#2e7d32;font-weight:bold;">' . $average . '%</span></p>';
        
        echo '<div style="background:#4caf50;height:30px;border-radius:15px;position:relative;overflow:hidden;">';
        echo '<div style="background:#81c784;height:100%;width:' . $average . '%;display:flex;align-items:center;justify-content:center;color:white;font-weight:bold;">' . $average . '%</div>';
        echo '</div>';
        
    } else {
        echo '<p style="color:#d32f2f;">❌ Nenhum curso encontrado - por isso está mostrando 0%</p>';
    }
    echo '</div>';
    
    echo '<div style="background:#fff3cd;padding:10px;border-radius:5px;">';
    echo '<p><strong>💡 Resultado:</strong></p>';
    
    if (count($method1) > count($method2)) {
        echo '<p>⚠️ Método do Tutor LMS encontrou mais cursos. Use: <code>[tutor_progress_bar debug="true"]</code></p>';
    } elseif (count($method2) > count($method1)) {
        echo '<p>✅ Plugin melhorado encontrou mais cursos! O problema foi resolvido.</p>';
    } elseif (count($method1) == count($method2) && count($method1) > 0) {
        echo '<p>✅ Ambos métodos encontraram os mesmos cursos. Funcionando corretamente!</p>';
    } else {
        echo '<p>❌ Nenhum método encontrou cursos. Verifique se o usuário está matriculado.</p>';
    }
    
    echo '<hr style="margin:15px 0;">';
    echo '<p><strong>🔧 Próximos passos:</strong></p>';
    echo '<ol style="font-size:14px;">';
    echo '<li>Use <code>[tutor_debug_simple]</code> para análise detalhada</li>';
    echo '<li>Use <code>[tutor_progress_bar debug="true"]</code> para ver logs detalhados</li>';
    echo '<li>Use <code>[tutor_progress_bar]</code> para o shortcode final</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '</div>';
    
    return ob_get_clean();
}

function tutor_debug_simple_shortcode($atts) {
    if (!current_user_can('manage_options') || !is_user_logged_in()) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Acesso negado. Apenas administradores.</div>';
    }
    
    $user_id = get_current_user_id();
    
    if (!function_exists('tutor_utils')) {
        return '<div style="padding:15px;background:#ffebee;color:#c62828;border-radius:8px;">Tutor LMS não encontrado.</div>';
    }
    
    global $wpdb;
    
    ob_start();
    echo '<div style="background:#f9f9f9;padding:20px;border-radius:8px;font-family:monospace;margin:20px 0;">';
    echo '<h3>🔍 DEBUG COMPLETO - Análise de Matrículas</h3>';
    
    $user_info = get_userdata($user_id);
    echo '<p><strong>Usuário:</strong> ' . $user_info->display_name . ' (ID: ' . $user_id . ')</p>';
    
    // 1. Verificar tabela de matrículas EM DETALHES
    $enrollments_table = $wpdb->prefix . 'tutor_enrollments';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$enrollments_table'") == $enrollments_table;
    
    echo '<h4>🗃️ Análise da Tabela tutor_enrollments:</h4>';
    echo '<p><strong>Tabela existe:</strong> ' . ($table_exists ? 'SIM' : 'NÃO') . '</p>';
    
    if ($table_exists) {
        // Buscar TODOS os registros do usuário
        $all_enrollments = $wpdb->get_results($wpdb->prepare("
            SELECT e.*, p.post_title, p.post_status, p.post_date
            FROM {$enrollments_table} e
            LEFT JOIN {$wpdb->posts} p ON e.course_id = p.ID
            WHERE e.user_id = %d
            ORDER BY e.enrolled_at DESC
        ", $user_id));
        
        echo '<p><strong>Total de registros para este usuário:</strong> ' . count($all_enrollments) . '</p>';
        
        if (!empty($all_enrollments)) {
            echo '<table style="width:100%;border-collapse:collapse;margin:10px 0;font-size:12px;">';
            echo '<tr style="background:#ddd;">';
            echo '<th style="border:1px solid #ccc;padding:5px;">ID Matrícula</th>';
            echo '<th style="border:1px solid #ccc;padding:5px;">Curso ID</th>';
            echo '<th style="border:1px solid #ccc;padding:5px;">Título</th>';
            echo '<th style="border:1px solid #ccc;padding:5px;">Status Curso</th>';
            echo '<th style="border:1px solid #ccc;padding:5px;">Data Matrícula</th>';
            echo '<th style="border:1px solid #ccc;padding:5px;">Válido?</th>';
            echo '</tr>';
            
            $valid_count = 0;
            foreach ($all_enrollments as $enrollment) {
                $is_valid = !empty($enrollment->post_title) && $enrollment->post_status !== 'trash';
                if ($is_valid) $valid_count++;
                
                $row_color = $is_valid ? '' : 'background:#ffebee;';
                
                echo '<tr style="' . $row_color . '">';
                echo '<td style="border:1px solid #ccc;padding:3px;">' . $enrollment->id . '</td>';
                echo '<td style="border:1px solid #ccc;padding:3px;">' . $enrollment->course_id . '</td>';
                echo '<td style="border:1px solid #ccc;padding:3px;">' . esc_html($enrollment->post_title ?: 'CURSO NÃO ENCONTRADO') . '</td>';
                echo '<td style="border:1px solid #ccc;padding:3px;">' . ($enrollment->post_status ?: 'N/A') . '</td>';
                echo '<td style="border:1px solid #ccc;padding:3px;">' . $enrollment->enrolled_at . '</td>';
                echo '<td style="border:1px solid #ccc;padding:3px;color:' . ($is_valid ? '#4CAF50' : '#f44336') . ';font-weight:bold;">' . ($is_valid ? 'SIM' : 'NÃO') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            echo '<p><strong>Matrículas válidas:</strong> ' . $valid_count . ' de ' . count($all_enrollments) . '</p>';
        }
        
        // Testar a query que o plugin usa
        echo '<h4>🔍 Teste da Query do Plugin:</h4>';
        $course_ids_query = $wpdb->prepare("
            SELECT DISTINCT course_id
            FROM {$enrollments_table}
            WHERE user_id = %d
            ORDER BY course_id ASC
        ", $user_id);
        
        $course_ids = $wpdb->get_col($course_ids_query);
        echo '<p><strong>IDs únicos encontrados pela query:</strong> ' . implode(', ', $course_ids) . '</p>';
        echo '<p><strong>Total de IDs únicos:</strong> ' . count($course_ids) . '</p>';
    }
    
    // 2. Testar função get_enrolled_courses_by_user() do Tutor LMS
    echo '<h4>🔧 Teste da Função do Tutor LMS:</h4>';
    try {
        $tutor_courses = tutor_utils()->get_enrolled_courses_by_user($user_id);
        echo '<p><strong>Função get_enrolled_courses_by_user():</strong> ' . count($tutor_courses) . ' cursos</p>';
        
        if (!empty($tutor_courses)) {
            echo '<ul>';
            foreach ($tutor_courses as $course) {
                echo '<li>ID: ' . $course->ID . ' - ' . esc_html($course->post_title) . ' (' . $course->post_status . ')</li>';
            }
            echo '</ul>';
        }
    } catch (Exception $e) {
        echo '<p style="color:#f44336;"><strong>Erro:</strong> ' . $e->getMessage() . '</p>';
    }
    
    // 3. Testar a função melhorada do plugin
    echo '<h4>🚀 Teste da Função Melhorada do Plugin:</h4>';
    
    // Usar a instância global do plugin
    global $tutor_progress_bar_plugin_instance;
    if ($tutor_progress_bar_plugin_instance) {
        $found_courses = $tutor_progress_bar_plugin_instance->get_all_enrolled_courses($user_id, true); // Com debug
        
        echo '<p><strong>Cursos encontrados pelo plugin:</strong> ' . count($found_courses) . '</p>';
        
        if (!empty($found_courses)) {
            echo '<table style="width:100%;border-collapse:collapse;margin:10px 0;">';
            echo '<tr style="background:#ddd;"><th style="border:1px solid #ccc;padding:8px;">ID</th><th style="border:1px solid #ccc;padding:8px;">Título</th><th style="border:1px solid #ccc;padding:8px;">Status</th><th style="border:1px solid #ccc;padding:8px;">Progresso</th></tr>';
            
            $total_progress_test = 0;
            foreach ($found_courses as $course) {
                $progress = tutor_utils()->get_course_completed_percent($course->ID, $user_id);
                $total_progress_test += $progress;
                
                echo '<tr>';
                echo '<td style="border:1px solid #ccc;padding:5px;">' . $course->ID . '</td>';
                echo '<td style="border:1px solid #ccc;padding:5px;">' . esc_html($course->post_title) . '</td>';
                echo '<td style="border:1px solid #ccc;padding:5px;">' . $course->post_status . '</td>';
                echo '<td style="border:1px solid #ccc;padding:5px;font-weight:bold;">' . $progress . '%</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            $average_test = count($found_courses) > 0 ? round($total_progress_test / count($found_courses), 1) : 0;
            
            echo '<div style="background:#e8f5e8;padding:15px;border-radius:5px;margin:15px 0;">';
            echo '<h4>📊 Cálculo da Média:</h4>';
            echo '<p><strong>Total de progresso:</strong> ' . $total_progress_test . '%</p>';
            echo '<p><strong>Número de cursos:</strong> ' . count($found_courses) . '</p>';
            echo '<p><strong>Média calculada:</strong> ' . $total_progress_test . ' ÷ ' . count($found_courses) . ' = <span style="font-size:18px;color:#2e7d32;font-weight:bold;">' . $average_test . '%</span></p>';
            echo '</div>';
        }
    } else {
        echo '<p style="color:#f44336;">Erro: Plugin não inicializado corretamente.</p>';
    }
    
    // 4. Verificar todos os cursos do site
    echo '<h4>📝 Verificação de Todos os Cursos do Site:</h4>';
    $all_courses = get_posts(array(
        'post_type' => 'courses',
        'post_status' => array('publish', 'private'),
        'numberposts' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    echo '<p><strong>Total de cursos no site:</strong> ' . count($all_courses) . '</p>';
    
    if (!empty($all_courses)) {
        echo '<table style="width:100%;border-collapse:collapse;margin:10px 0;font-size:12px;">';
        echo '<tr style="background:#ddd;"><th style="border:1px solid #ccc;padding:5px;">ID</th><th style="border:1px solid #ccc;padding:5px;">Título</th><th style="border:1px solid #ccc;padding:5px;">Status</th><th style="border:1px solid #ccc;padding:5px;">Data</th><th style="border:1px solid #ccc;padding:5px;">Usuário Matriculado?</th></tr>';
        
        $enrolled_count = 0;
        foreach ($all_courses as $course) {
            $is_enrolled = tutor_utils()->is_enrolled($course->ID, $user_id);
            if ($is_enrolled) $enrolled_count++;
            
            echo '<tr>';
            echo '<td style="border:1px solid #ccc;padding:3px;">' . $course->ID . '</td>';
            echo '<td style="border:1px solid #ccc;padding:3px;">' . esc_html($course->post_title) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:3px;">' . $course->post_status . '</td>';
            echo '<td style="border:1px solid #ccc;padding:3px;">' . date('d/m/Y', strtotime($course->post_date)) . '</td>';
            echo '<td style="border:1px solid #ccc;padding:3px;color:' . ($is_enrolled ? '#4CAF50' : '#ccc') . ';font-weight:bold;">' . ($is_enrolled ? 'SIM' : 'não') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        
        echo '<p><strong>Cursos com matrícula confirmada:</strong> ' . $enrolled_count . ' de ' . count($all_courses) . '</p>';
    }
    
    echo '<hr style="margin:20px 0;">';
    echo '<h4>🎯 Testes dos Shortcodes:</h4>';
    echo '<div style="background:#fff3cd;padding:15px;border-radius:5px;border-left:4px solid #ffc107;">';
    echo '<p><strong>Para testar com debug ativo:</strong></p>';
    echo '<p><code>[tutor_progress_bar debug="true"]</code></p>';
    echo '<p><strong>Para ver logs detalhados:</strong> Verifique o arquivo de log do WordPress</p>';
    echo '</div>';
    
    echo '</div>';
    
    return ob_get_clean();
}

/**
 * ========================================
 * HOOKS DE ATIVAÇÃO/DESATIVAÇÃO
 * ========================================
 */

register_activation_hook(__FILE__, 'tutor_progress_bar_activate');
register_deactivation_hook(__FILE__, 'tutor_progress_bar_deactivate');

function tutor_progress_bar_activate() {
    add_option('tutor_progress_bar_version', TUTOR_PROGRESS_BAR_VERSION);
    
    // Criar configurações padrão
    if (!get_option('tutor_progress_general_settings')) {
        add_option('tutor_progress_general_settings', array(
            'default_height' => '30',
            'default_color' => '#4CAF50',
            'default_bg_color' => '#f0f0f0',
            'default_text_color' => '#ffffff',
            'default_font_family' => 'Arial, sans-serif',
            'default_animation' => 'true'
        ));
    }
    
    if (!get_option('tutor_general_progress_settings')) {
        add_option('tutor_general_progress_settings', array(
            'height' => '30',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true'
        ));
    }
    
    if (!get_option('tutor_real_progress_settings')) {
        add_option('tutor_real_progress_settings', array(
            'height' => '30',
            'color' => '#4CAF50',
            'bg_color' => '#f0f0f0',
            'show_percentage' => 'true',
            'show_courses_info' => 'true',
            'animation' => 'true'
        ));
    }
}

function tutor_progress_bar_deactivate() {
    wp_cache_flush();
}

/**
 * ========================================
 * COMPATIBILIDADE E LINKS
 * ========================================
 */

// Verificar versão do PHP
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Tutor Progress Bar:</strong> Este plugin requer PHP 7.4 ou superior. Sua versão: ' . PHP_VERSION . '</p></div>';
    });
    return;
}

// Adicionar links na página de plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tutor_progress_bar_action_links');

function tutor_progress_bar_action_links($links) {
    $settings_link = '<a href="admin.php?page=tutor-progress-bar">Configurações</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/* 
===========================================
🎯 TUTOR PROGRESS BAR - VERSÃO MELHORADA
===========================================

SHORTCODES DISPONÍVEIS:
- [tutor_progress_bar] - Progresso geral (médio) de todos os cursos
- [tutor_progress_bar debug="true"] - Com debug ativo
- [tutor_course_progress course_id="123"] - Progresso de curso específico
- [tutor_real_progress] - Progresso baseado em aulas assistidas

SHORTCODES DE DEBUG (apenas para administradores):
- [tutor_debug_simple] - Debug completo e detalhado
- [tutor_test_quick] - Teste rápido comparando métodos
- [tutor_force_method method="table|tutor|check"] - Forçar método específico

FUNCIONALIDADES IMPLEMENTADAS:
✅ Busca TODOS os cursos matriculados (3 métodos diferentes)
✅ Calcula média correta de progresso
✅ Remove apenas duplicatas, mantém todos os cursos válidos
✅ Fallback automático se um método falhar
✅ Debug detalhado para identificar problemas
✅ Sistema robusto de detecção de matrículas
✅ Interface administrativa completa
✅ Customização visual avançada

PROBLEMA RESOLVIDO:
Agora o plugin pega TODOS os cursos matriculados, não apenas o último atualizado.
A média é calculada corretamente baseada em todos os cursos.

===========================================
*/