<?php
// Salve este arquivo como diagnostic.php na raiz do seu plugin

if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto
}

class TutorRedirectDiagnostic {
    public static function run() {
        // Adiciona um filtro para exibir informações de diagnóstico no rodapé
        add_action('wp_footer', [self::class, 'display_debug_info']);
        
        // Adiciona um filtro para registrar tentativas de redirecionamento
        add_action('template_redirect', [self::class, 'log_redirect_attempt'], 0);
    }
    
    public static function display_debug_info() {
        // Somente mostrar para administradores
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Exibe as informações apenas se estivermos em uma página de curso ou lição
        $post_type = get_post_type();
        if ($post_type !== 'courses' && $post_type !== 'lesson') {
            return;
        }
        
        echo '<div style="background: #f8f8f8; border: 1px solid #ddd; padding: 15px; margin: 20px 0; font-family: monospace; position: fixed; bottom: 0; right: 0; max-width: 500px; max-height: 400px; overflow: auto; z-index: 9999;">';
        echo '<h3>Diagnóstico do Redirecionamento</h3>';
        
        $post_id = get_the_ID();
        $course_id = $post_id;
        
        if ($post_type === 'lesson' && function_exists('tutor_utils')) {
            $course_id = tutor_utils()->get_course_id_by_content($post_id);
        }
        
        echo '<p><strong>Post ID:</strong> ' . $post_id . '</p>';
        echo '<p><strong>Post Type:</strong> ' . $post_type . '</p>';
        
        if ($course_id) {
            echo '<p><strong>Course ID:</strong> ' . $course_id . '</p>';
            echo '<p><strong>Course Title:</strong> ' . get_the_title($course_id) . '</p>';
            echo '<p><strong>Course Slug:</strong> ' . get_post($course_id)->post_name . '</p>';
            
            // Verifica as configurações de redirecionamento
            $is_redirect_enabled = get_post_meta($course_id, '_is_redirect_enabled', true);
            $redirect_type = get_post_meta($course_id, '_redirect_type', true);
            $redirect_page_id = get_post_meta($course_id, '_redirect_page', true);
            $redirect_url = get_post_meta($course_id, '_redirect_url', true);
            
            echo '<p><strong>Redirect Enabled:</strong> ' . ($is_redirect_enabled ? 'Yes' : 'No') . '</p>';
            echo '<p><strong>Redirect Type:</strong> ' . ($redirect_type ?: 'page (default)') . '</p>';
            
            if ($redirect_type === 'url' || empty($redirect_type)) {
                echo '<p><strong>Redirect URL:</strong> ' . ($redirect_url ?: 'Not set') . '</p>';
            } else {
                echo '<p><strong>Redirect Page ID:</strong> ' . ($redirect_page_id ?: 'Not set') . '</p>';
                if ($redirect_page_id) {
                    echo '<p><strong>Redirect Page URL:</strong> ' . get_permalink($redirect_page_id) . '</p>';
                }
            }
            
            // Verifica se o usuário está matriculado
            $user_logged_in = is_user_logged_in();
            $user_enrolled = false;
            
            if ($user_logged_in && function_exists('tutor_utils')) {
                $user_enrolled = tutor_utils()->is_enrolled($course_id);
            }
            
            echo '<p><strong>User Logged In:</strong> ' . ($user_logged_in ? 'Yes' : 'No') . '</p>';
            echo '<p><strong>User Enrolled:</strong> ' . ($user_enrolled ? 'Yes' : 'No') . '</p>';
            
            // Verifica as condições de redirecionamento
            $should_redirect = $is_redirect_enabled && !$user_enrolled;
            echo '<p><strong>Should Redirect:</strong> ' . ($should_redirect ? 'Yes' : 'No') . '</p>';
            
            if ($should_redirect) {
                $redirect_destination = '';
                
                if ($redirect_type === 'url') {
                    $redirect_destination = $redirect_url;
                } else {
                    if ($redirect_page_id) {
                        $redirect_destination = get_permalink($redirect_page_id);
                    }
                }
                
                echo '<p><strong>Redirect Destination:</strong> ' . ($redirect_destination ?: 'None - Missing URL or Page ID') . '</p>';
            }
        }
        
        echo '<p><strong>Request URI:</strong> ' . $_SERVER['REQUEST_URI'] . '</p>';
        echo '<p><strong>Plugin Version:</strong> ' . (defined('TUTOR_LMS_REDIRECT_VERSION') ? TUTOR_LMS_REDIRECT_VERSION : 'Unknown') . '</p>';
        
        // Exibe as últimas 10 linhas do log
        $log_file = plugin_dir_path(dirname(__FILE__)) . 'logs/plugin.log';
        if (file_exists($log_file)) {
            echo '<h4>Últimas Entradas do Log</h4>';
            echo '<pre style="max-height: 150px; overflow: auto; background: #333; color: #fff; padding: 5px;">';
            
            $logs = file($log_file);
            $logs = array_slice($logs, -10); // Últimas 10 linhas
            
            foreach ($logs as $log_entry) {
                echo htmlspecialchars($log_entry);
            }
            
            echo '</pre>';
        }
        
        echo '</div>';
    }
    
    public static function log_redirect_attempt() {
        // Adiciona registro de tentativa de redirecionamento
        $post_id = get_the_ID();
        if (!$post_id) return;
        
        $post_type = get_post_type($post_id);
        if ($post_type !== 'courses' && $post_type !== 'lesson') return;
        
        $course_id = $post_id;
        if ($post_type === 'lesson' && function_exists('tutor_utils')) {
            $course_id = tutor_utils()->get_course_id_by_content($post_id);
        }
        
        if (!$course_id) return;
        
        // Registra a tentativa em um arquivo de log específico para diagnóstico
        $log_file = plugin_dir_path(dirname(__FILE__)) . 'logs/redirect_diagnose.log';
        $timestamp = date("Y-m-d H:i:s");
        
        $user_id = get_current_user_id();
        $is_enrolled = (function_exists('tutor_utils') && $user_id) ? tutor_utils()->is_enrolled($course_id, $user_id) : 'Unknown';
        $is_redirect_enabled = get_post_meta($course_id, '_is_redirect_enabled', true) ? 'Yes' : 'No';
        $redirect_type = get_post_meta($course_id, '_redirect_type', true) ?: 'page (default)';
        
        $log_message = sprintf(
            "%s - Course ID: %d, User ID: %d, Is Enrolled: %s, Redirect Enabled: %s, Redirect Type: %s, URI: %s\n",
            $timestamp,
            $course_id,
            $user_id,
            $is_enrolled ? 'Yes' : 'No',
            $is_redirect_enabled,
            $redirect_type,
            $_SERVER['REQUEST_URI']
        );
        
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

// Executa o diagnóstico
TutorRedirectDiagnostic::run();