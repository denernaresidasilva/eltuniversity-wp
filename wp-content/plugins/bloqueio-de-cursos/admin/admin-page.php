<?php
if (!defined('ABSPATH')) {
    exit; // Bloqueia acesso direto
}

// Exibe mensagens de configuração salvas
if (isset($_GET['settings-updated'])) {
    add_settings_error('tutor_lms_redirect_messages', 'tutor_lms_redirect_message', __('Configurações salvas', 'tutor-lms-redirect'), 'updated');
}
settings_errors('tutor_lms_redirect_messages');
?>
<div class="wrap">
    <h1><?php _e('Bloqueio de Aulas', 'tutor-lms-redirect'); ?></h1>
    <p><?php _e('Configuração dos redirecionamentos de cursos para a página desejada ou URL externa.', 'tutor-lms-redirect'); ?></p>
    <form id="tutor-lms-redirect-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="save_tutor_lms_redirect">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" width="20%"><strong><?php _e('Curso (Slug)', 'tutor-lms-redirect'); ?></strong></th>
                    <th scope="col" width="15%"><strong><?php _e('Tipo', 'tutor-lms-redirect'); ?></strong></th>
                    <th scope="col" width="55%"><strong><?php _e('Destino do Redirecionamento', 'tutor-lms-redirect'); ?></strong></th>
                    <th scope="col" width="10%" class="text-center"><strong><?php _e('Ativar', 'tutor-lms-redirect'); ?></strong></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Recupera todos os cursos
                $courses = get_posts([
                    'post_type' => 'courses',
                    'numberposts' => -1
                ]);

                if (empty($courses)) {
                    echo '<tr><td colspan="4">' . __('Nenhum curso encontrado.', 'tutor-lms-redirect') . '</td></tr>';
                }

                foreach ($courses as $course) {
                    $redirect_page = get_post_meta($course->ID, '_redirect_page', true);
                    $redirect_url = get_post_meta($course->ID, '_redirect_url', true);
                    $redirect_type = get_post_meta($course->ID, '_redirect_type', true);
                    if (empty($redirect_type)) {
                        $redirect_type = 'page'; // Padrão para compatibilidade retroativa
                    }
                    $is_redirect_enabled = get_post_meta($course->ID, '_is_redirect_enabled', true);
                    $course_slug = $course->post_name; // Obtém o slug do curso diretamente
                    ?>
                    <tr class="course-row <?php echo $is_redirect_enabled ? 'highlight' : ''; ?>" data-course-id="<?php echo $course->ID; ?>">
                        <td><?php echo esc_html($course->post_title); ?> <br><small>(<?php echo esc_html($course_slug); ?>)</small></td>
                        <td>
                            <select name="redirect_type[<?php echo $course->ID; ?>]" class="redirect-type-select" data-course-id="<?php echo $course->ID; ?>">
                                <option value="page" <?php selected($redirect_type, 'page'); ?>><?php _e('Página WordPress', 'tutor-lms-redirect'); ?></option>
                                <option value="url" <?php selected($redirect_type, 'url'); ?>><?php _e('URL Externa', 'tutor-lms-redirect'); ?></option>
                            </select>
                        </td>
                        <td class="redirect-destination">
                            <!-- Seletor de página (mostrado quando o tipo é 'page') -->
                            <div class="page-select-container" <?php echo $redirect_type === 'url' ? 'style="display:none;"' : ''; ?>>
                                <select name="redirect_page[<?php echo $course->ID; ?>]" class="redirect-page-select" data-course-id="<?php echo $course->ID; ?>">
                                    <option value=""><?php _e('Selecione a Página', 'tutor-lms-redirect'); ?></option>
                                    <?php
                                    // Lista todas as páginas disponíveis para redirecionamento
                                    $pages = get_pages();
                                    foreach ($pages as $page) {
                                        $selected = ($redirect_page == $page->ID) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($page->ID) . '" ' . esc_attr($selected) . '>' . esc_html($page->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <!-- Campo de URL externa (mostrado quando o tipo é 'url') -->
                            <div class="url-input-container" <?php echo $redirect_type === 'page' || empty($redirect_type) ? 'style="display:none;"' : ''; ?>>
                                <input type="url" name="redirect_url[<?php echo $course->ID; ?>]" 
                                       class="redirect-url-input" 
                                       data-course-id="<?php echo $course->ID; ?>" 
                                       value="<?php echo esc_url($redirect_url); ?>" 
                                       placeholder="https://exemplo.com/pagina" 
                                       style="width:100%;">
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input switch-ajax" id="customSwitch<?php echo $course->ID; ?>" name="is_redirect_enabled[<?php echo $course->ID; ?>]" data-course-id="<?php echo $course->ID; ?>" value="1" <?php checked($is_redirect_enabled, 1); ?>>
                                <label class="custom-control-label" for="customSwitch<?php echo $course->ID; ?>"></label>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Salvar Alterações', 'tutor-lms-redirect'); ?>">
        </p>
        <?php wp_nonce_field('tutor_lms_redirect_nonce', 'tutor_lms_redirect_nonce_field'); ?>
    </form>    
</div>

<!-- JavaScript para alternar entre os campos de página e URL -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Mostra ou esconde os campos apropriados com base no tipo selecionado
    $('.redirect-type-select').on('change', function() {
        var $row = $(this).closest('tr');
        var selectedType = $(this).val();
        
        if (selectedType === 'page') {
            $row.find('.page-select-container').show();
            $row.find('.url-input-container').hide();
        } else { // url
            $row.find('.page-select-container').hide();
            $row.find('.url-input-container').show();
        }
    });
});
</script>