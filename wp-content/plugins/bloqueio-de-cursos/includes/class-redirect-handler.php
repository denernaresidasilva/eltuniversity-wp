<?php

if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto (Agencycoders)
}

class RedirectHandler {
    private $logger;

    public function __construct($logger) {
        $this->logger = $logger;
        add_action('template_redirect', [$this, 'check_course_access'], 1); // Prioridade 1 para executar antes de outros hooks
    }

    public function check_course_access() {
        // Obtém a URL solicitada do servidor
        $requested_url = $_SERVER['REQUEST_URI'];
        $this->logger->log("URL requisitada: $requested_url");

        // Verifica se a URL atual é uma página de curso ou lição com o slug relevante
        if ($this->is_course_or_lesson_page($requested_url)) {
            $this->logger->log("Detectada página de curso ou lição: $requested_url");
            $course_slug = $this->extract_course_slug($requested_url);
            
            // Se identificou um slug de curso, prossegue com a verificação
            if (!empty($course_slug)) {
                $this->logger->log("Slug do curso extraído: $course_slug");
                
                // Obtém o ID do curso com base no slug - tenta primeiro pelo slug da URL
                $course = get_page_by_path($course_slug, OBJECT, 'courses');
                
                // Se não encontrou com o slug da URL, tenta obter pelo post atual
                if (!$course) {
                    $post_id = get_the_ID();
                    if ($post_id) {
                        // Verifica se o post atual é um curso
                        if (get_post_type($post_id) === 'courses') {
                            $course = get_post($post_id);
                        } else if (function_exists('tutor_utils')) {
                            // Se for uma lição, tenta obter o curso pai
                            $course_id = tutor_utils()->get_course_id_by_content($post_id);
                            if ($course_id) {
                                $course = get_post($course_id);
                            }
                        }
                    }
                }
                
                if ($course) {
                    $course_id = $course->ID;
                    $this->logger->log("Curso encontrado - ID: $course_id, Título: " . $course->post_title);
                    
                    $is_redirect_enabled = get_post_meta($course_id, '_is_redirect_enabled', true);
                    $this->logger->log("Status de redirecionamento: " . ($is_redirect_enabled ? 'Ativado' : 'Desativado'));
                    
                    // Verifica se o redirecionamento está ativado para este curso
                    if ($is_redirect_enabled) {
                        // Verifica se o usuário está logado
                        $user_logged_in = is_user_logged_in();
                        $this->logger->log("Usuário está logado: " . ($user_logged_in ? 'Sim' : 'Não'));
                        
                        // Verifica se o usuário está inscrito no curso
                        $user_enrolled = false;
                        if ($user_logged_in && function_exists('tutor_utils')) {
                            $user_enrolled = tutor_utils()->is_enrolled($course_id);
                            $this->logger->log("Usuário está matriculado: " . ($user_enrolled ? 'Sim' : 'Não'));
                        }
                        
                        // Se o usuário não está matriculado, redirecionamos
                        if (!$user_enrolled) {
                            $redirect_type = get_post_meta($course_id, '_redirect_type', true);
                            if (empty($redirect_type)) {
                                $redirect_type = 'page'; // Compatibilidade retroativa
                            }
                            $this->logger->log("Tipo de redirecionamento: $redirect_type");
                            
                            $redirect_url = '';
                            
                            if ($redirect_type === 'page') {
                                $redirect_page_id = get_post_meta($course_id, '_redirect_page', true);
                                $this->logger->log("ID da página de redirecionamento: $redirect_page_id");
                                
                                if ($redirect_page_id) {
                                    $redirect_url = get_permalink($redirect_page_id);
                                }
                            } else { // $redirect_type === 'url'
                                $redirect_url = get_post_meta($course_id, '_redirect_url', true);
                                $this->logger->log("URL externa de redirecionamento: $redirect_url");
                            }
                            
                            // Se temos uma URL para redirecionar, fazemos o redirecionamento
                            if (!empty($redirect_url)) {
                                $this->logger->log("Usuário não matriculado acessando curso: $course_slug, redirecionando para: $redirect_url");
                                
                                // Deixamos um cookie para evitar loops de redirecionamento
                                setcookie('tutor_redirect_' . $course_id, '1', time() + 60, COOKIEPATH, COOKIE_DOMAIN);
                                
                                // Executa o redirecionamento de forma definitiva
                                wp_redirect($redirect_url, 302);
                                exit;
                            } else {
                                $this->logger->log("ERRO: URL de redirecionamento vazia. Não foi possível redirecionar.");
                            }
                        }
                    }
                } else {
                    $this->logger->log("ERRO: Não foi possível encontrar o curso com o slug: $course_slug");
                }
            } else {
                $this->logger->log("ERRO: Não foi possível extrair o slug do curso da URL: $requested_url");
            }
        }
    }

    private function extract_course_slug($url) {
        // Divide a URL por "/" e verifica se um dos segmentos é um slug de curso
        $url_parts = explode('/', trim($url, '/'));
        $this->logger->log("Partes da URL: " . implode(', ', $url_parts));
        
        // Identifica o slug do curso na URL
        $ignore_parts = ['courses', 'lesson', 'tutor', 'wp-content', 'wp-admin', 'wp-includes'];
        
        foreach ($url_parts as $part) {
            if (!empty($part) && !in_array($part, $ignore_parts) && !is_numeric($part)) {
                // Verifica se é um curso existente
                $check_course = get_page_by_path($part, OBJECT, 'courses');
                
                if ($check_course) {
                    $this->logger->log("Slug de curso válido encontrado: $part");
                    return $part;
                }
            }
        }
        
        // Tentativa alternativa para extrair o slug da URL quando a detecção normal falha
        $current_post_id = get_the_ID();
        if ($current_post_id) {
            $current_post_type = get_post_type($current_post_id);
            
            if ($current_post_type === 'courses') {
                $course = get_post($current_post_id);
                $this->logger->log("Curso encontrado pelo ID atual: " . $course->post_name);
                return $course->post_name;
            } elseif ($current_post_type === 'lesson' && function_exists('tutor_utils')) {
                $course_id = tutor_utils()->get_course_id_by_content($current_post_id);
                if ($course_id) {
                    $course = get_post($course_id);
                    $this->logger->log("Curso encontrado pelo ID da lição: " . $course->post_name);
                    return $course->post_name;
                }
            }
        }
        
        $this->logger->log("Nenhum slug de curso válido encontrado na URL");
        return '';
    }

    private function is_course_or_lesson_page($url) {
        // Verifica no slug padrão
        if (strpos($url, '/courses/') !== false || strpos($url, '/lesson/') !== false) {
            return true;
        }
        
        // Verifica pelo post type atual
        $current_post_id = get_the_ID();
        if ($current_post_id) {
            $post_type = get_post_type($current_post_id);
            if ($post_type === 'courses' || $post_type === 'lesson') {
                return true;
            }
        }
        
        return false;
    }
}