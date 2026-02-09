<?php

if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto (Agencycoders)
}

class MetaBoxHandler {
    public function __construct() {
        // Adiciona metabox para cursos (Agencycoders)
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
    }

    public function add_meta_box() {
        add_meta_box(
            'tutor_lms_redirect_meta_box',
            __('Redirecionamento de Curso', 'tutor-lms-redirect'),
            [$this, 'render_meta_box'],
            'courses',
            'side',
            'default'
        );
    }

    public function render_meta_box($post) {
        // Recupera valores atuais (Agencycoders)
        $redirect_page = get_post_meta($post->ID, '_redirect_page', true);
        $redirect_url = get_post_meta($post->ID, '_redirect_url', true);
        $redirect_type = get_post_meta($post->ID, '_redirect_type', true);
        if (empty($redirect_type)) {
            $redirect_type = 'page'; // Padrão para compatibilidade retroativa
        }
        $is_redirect_enabled = get_post_meta($post->ID, '_is_redirect_enabled', true);

        // Tipo de redirecionamento (Agencycoders)
        echo '<div class="redirect-type-selector">';
        echo '<label><strong>' . __('Tipo de Redirecionamento:', 'tutor-lms-redirect') . '</strong></label><br>';
        echo '<label><input type="radio" name="redirect_type" value="page" ' . checked($redirect_type, 'page', false) . '> ' . __('Página do WordPress', 'tutor-lms-redirect') . '</label><br>';
        echo '<label><input type="radio" name="redirect_type" value="url" ' . checked($redirect_type, 'url', false) . '> ' . __('URL Externa', 'tutor-lms-redirect') . '</label>';
        echo '</div><br>';

        // Dropdown de páginas (visível quando redirecionamento é para página) (Agencycoders)
        echo '<div class="redirect-page-section" ' . ($redirect_type == 'url' ? 'style="display:none;"' : '') . '>';
        $pages = get_pages();
        echo '<label for="redirect_page" title="' . esc_attr__('Selecione a página para onde este curso será redirecionado.', 'tutor-lms-redirect') . '"><strong>' . __('Selecione a Página:', 'tutor-lms-redirect') . '</strong></label>';
        echo '<select name="redirect_page" id="redirect_page">';
        echo '<option value="">' . __('Selecione a Página', 'tutor-lms-redirect') . '</option>';
        foreach ($pages as $page) {
            $selected = ($redirect_page == $page->ID) ? 'selected' : '';
            echo '<option value="' . esc_attr($page->ID) . '" ' . esc_attr($selected) . '>' . esc_html($page->post_title) . '</option>';
        }
        echo '</select>';
        echo '</div>';

        // Campo de URL (visível quando redirecionamento é para URL) (Agencycoders)
        echo '<div class="redirect-url-section" ' . ($redirect_type == 'page' || empty($redirect_type) ? 'style="display:none;"' : '') . '>';
        echo '<label for="redirect_url" title="' . esc_attr__('Digite a URL completa (incluindo https://) para onde este curso será redirecionado.', 'tutor-lms-redirect') . '"><strong>' . __('URL Externa:', 'tutor-lms-redirect') . '</strong></label>';
        echo '<input type="url" name="redirect_url" id="redirect_url" value="' . esc_url($redirect_url) . '" style="width:100%;" placeholder="https://exemplo.com/pagina">';
        echo '</div>';

        // Switch para ativar redirecionamento (Agencycoders)
        $checked = ($is_redirect_enabled) ? 'checked' : '';
        echo '<br><br>';
        echo '<div class="custom-control custom-switch">';
        echo '<input type="checkbox" class="custom-control-input" id="customSwitch' . $post->ID . '" name="is_redirect_enabled" value="1" ' . esc_attr($checked) . '>';
        echo '<label class="custom-control-label" for="customSwitch' . $post->ID . '"><strong>' . __('Ativar Redirecionamento', 'tutor-lms-redirect') . '</strong></label>';
        echo '</div>';

        // JavaScript para alternar entre os campos de página e URL (Agencycoders)
        echo '<script>
            jQuery(document).ready(function($) {
                $("input[name=\'redirect_type\']").change(function() {
                    if ($(this).val() === "page") {
                        $(".redirect-page-section").show();
                        $(".redirect-url-section").hide();
                    } else {
                        $(".redirect-page-section").hide();
                        $(".redirect-url-section").show();
                    }
                });
            });
        </script>';

        // Segurança (Agencycoders)
        wp_nonce_field('tutor_lms_redirect_nonce', 'tutor_lms_redirect_nonce_field');
    }
}