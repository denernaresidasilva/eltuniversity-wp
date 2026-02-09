<?php

if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto (Agencycoders)
}

class SaveHandler {
    private $logger;

    public function __construct($logger) {
        $this->logger = $logger;
        add_action('wp_ajax_toggle_redirect', [$this, 'toggle_redirect']);
        add_action('wp_ajax_update_redirect_page', [$this, 'update_redirect_page']);
        add_action('wp_ajax_update_redirect_url', [$this, 'update_redirect_url']);
        add_action('wp_ajax_update_redirect_type', [$this, 'update_redirect_type']);
        add_action('wp_ajax_update_course_slug', [$this, 'update_course_slug']); 
        add_action('save_post_courses', [$this, 'save_course_meta'], 10, 3);
        add_action('admin_post_save_tutor_lms_redirect', [$this, 'save_redirect_settings']);
    }

    public function toggle_redirect() {
        check_ajax_referer('tutor_lms_redirect_nonce', 'security');

        $course_id = intval($_POST['course_id']);
        $enabled = boolval($_POST['enabled']);

        if ($course_id) {
            update_post_meta($course_id, '_is_redirect_enabled', $enabled ? 1 : 0);
            $this->logger->log("Redirecionamento " . ($enabled ? 'ativado' : 'desativado') . " para o curso ID: $course_id");
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function update_redirect_page() {
        check_ajax_referer('tutor_lms_redirect_nonce', 'security');

        $course_id = intval($_POST['course_id']);
        $redirect_page_id = intval($_POST['redirect_page_id']);

        if ($course_id) {
            update_post_meta($course_id, '_redirect_page', $redirect_page_id);
            update_post_meta($course_id, '_redirect_type', 'page'); // Certifica que o tipo é 'page'
            $this->logger->log("Página de redirecionamento atualizada para o curso ID: $course_id");
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function update_redirect_url() {
        check_ajax_referer('tutor_lms_redirect_nonce', 'security');

        $course_id = intval($_POST['course_id']);
        $redirect_url = esc_url_raw($_POST['redirect_url']);

        if ($course_id && $redirect_url) {
            update_post_meta($course_id, '_redirect_url', $redirect_url);
            update_post_meta($course_id, '_redirect_type', 'url'); // Certifica que o tipo é 'url'
            $this->logger->log("URL externa de redirecionamento atualizada para o curso ID: $course_id, URL: $redirect_url");
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function update_redirect_type() {
        check_ajax_referer('tutor_lms_redirect_nonce', 'security');

        $course_id = intval($_POST['course_id']);
        $redirect_type = sanitize_text_field($_POST['redirect_type']);

        if ($course_id && in_array($redirect_type, ['page', 'url'])) {
            update_post_meta($course_id, '_redirect_type', $redirect_type);
            $this->logger->log("Tipo de redirecionamento atualizado para o curso ID: $course_id, Tipo: $redirect_type");
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function update_course_slug() {
        check_ajax_referer('tutor_lms_redirect_nonce', 'security');

        $course_id = intval($_POST['course_id']);
        $selected_slug = sanitize_text_field($_POST['selected_slug']);

        if ($course_id && $selected_slug) {
            update_post_meta($course_id, '_selected_slug', $selected_slug);
            $this->logger->log("Slug do curso atualizado para o curso ID: $course_id, Slug: $selected_slug");
            wp_send_json_success();
        } else {
            wp_send_json_error();
        }
    }

    public function save_course_meta($post_id, $post, $update) {
        // Verifica se é um salvamento automático
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verifica o nonce
        if (!isset($_POST['tutor_lms_redirect_nonce_field']) || !wp_verify_nonce($_POST['tutor_lms_redirect_nonce_field'], 'tutor_lms_redirect_nonce')) {
            return;
        }

        // Verifica permissões
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Salva os dados do redirecionamento
        if (isset($_POST['redirect_type'])) {
            $redirect_type = sanitize_text_field($_POST['redirect_type']);
            update_post_meta($post_id, '_redirect_type', $redirect_type);
            
            // Salva os dados específicos com base no tipo
            if ($redirect_type === 'page' && isset($_POST['redirect_page'])) {
                update_post_meta($post_id, '_redirect_page', intval($_POST['redirect_page']));
            } elseif ($redirect_type === 'url' && isset($_POST['redirect_url'])) {
                update_post_meta($post_id, '_redirect_url', esc_url_raw($_POST['redirect_url']));
            }
            
            $this->logger->log("Tipo de redirecionamento definido como '$redirect_type' para o curso ID: $post_id");
        }

        if (isset($_POST['is_redirect_enabled'])) {
            update_post_meta($post_id, '_is_redirect_enabled', 1);
            $this->logger->log("Redirecionamento ativado para o curso ID: $post_id");
        } else {
            update_post_meta($post_id, '_is_redirect_enabled', 0);
            $this->logger->log("Redirecionamento desativado para o curso ID: $post_id");
        }
    }

    public function save_redirect_settings() {
        if (!isset($_POST['tutor_lms_redirect_nonce_field']) || !wp_verify_nonce($_POST['tutor_lms_redirect_nonce_field'], 'tutor_lms_redirect_nonce')) {
            return;
        }

        // Salva as configurações do redirecionamento
        $redirect_types = isset($_POST['redirect_type']) ? $_POST['redirect_type'] : [];
        $redirect_pages = isset($_POST['redirect_page']) ? $_POST['redirect_page'] : [];
        $redirect_urls = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : [];
        $is_redirect_enabled = isset($_POST['is_redirect_enabled']) ? $_POST['is_redirect_enabled'] : [];

        foreach ($redirect_types as $course_id => $type) {
            update_post_meta($course_id, '_redirect_type', sanitize_text_field($type));
            
            if ($type === 'page' && isset($redirect_pages[$course_id])) {
                update_post_meta($course_id, '_redirect_page', intval($redirect_pages[$course_id]));
            } elseif ($type === 'url' && isset($redirect_urls[$course_id])) {
                update_post_meta($course_id, '_redirect_url', esc_url_raw($redirect_urls[$course_id]));
            }
        }

        // Salva o estado de ativação de cada curso
        $courses = get_posts([
            'post_type' => 'courses',
            'numberposts' => -1
        ]);

        foreach ($courses as $course) {
            $enabled = isset($is_redirect_enabled[$course->ID]) ? 1 : 0;
            update_post_meta($course->ID, '_is_redirect_enabled', $enabled);
        }

        // Registra a ação no logger
        $this->logger->log("Configurações de redirecionamento salvas.");
        
        wp_redirect(admin_url('admin.php?page=tutor-lms-redirect&settings-updated=true'));
        exit;
    }
}