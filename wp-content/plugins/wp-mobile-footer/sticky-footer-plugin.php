<?php
/**
 * Plugin Name: Mobile Footer
 * Plugin URI: https://www.agencycoders.com
 * Description: Mobile Footer
 * Version: 1.0
 * Author: AgencyCoders
 * Author URI: https://www.agencycoders.com
 */

if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

// Registrar a localização do menu para o rodapé
function sticky_footer_register_menu() {
    register_nav_menu('footer_menu', 'Menu do Rodapé');
}
add_action('init', 'sticky_footer_register_menu');

// Função para carregar a biblioteca de ícones Ionicons no front-end
function sticky_footer_enqueue_icon_library() {
    echo '<script type="module" src="https://cdn.jsdelivr.net/npm/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>';
    echo '<script nomodule src="https://cdn.jsdelivr.net/npm/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>';
}
add_action('wp_footer', 'sticky_footer_enqueue_icon_library');

// Função para carregar os arquivos CSS no front-end
function sticky_footer_enqueue_styles() {
    wp_enqueue_style('sticky-footer-style', plugin_dir_url(__FILE__) . 'css/sticky-footer.css');
}
add_action('wp_enqueue_scripts', 'sticky_footer_enqueue_styles');

// Função para adicionar CSS inline no rodapé para garantir a aplicação correta
function sticky_footer_inline_styles() {
    $footer_background_color = get_option('footer_background_color', '#2E3748');
    $footer_text_color = get_option('footer_text_color', '#ffffff');
    $footer_hover_color = get_option('footer_hover_color', '#FFCC02');
    $footer_font_family = get_option('footer_font_family', 'Sora');

    $css = "
    .sticky-footer {
        background-color: {$footer_background_color} !important;
        color: {$footer_text_color} !important;
        font-family: '{$footer_font_family}', sans-serif;
    }
    .sticky-footer ul.footer-menu li a {
        color: {$footer_text_color} !important;
        font-family: '{$footer_font_family}', sans-serif;
    }
    .sticky-footer ul.footer-menu li a:hover ion-icon,
    .sticky-footer ul.footer-menu li.active a ion-icon {
        color: {$footer_hover_color} !important;
    }
    ";
    
    wp_add_inline_style('sticky-footer-style', $css);
}
add_action('wp_enqueue_scripts', 'sticky_footer_inline_styles');

// Adicionar campo personalizado de ícones ao menu
function sticky_footer_menu_custom_fields($item_id, $item, $depth, $args) {
    $icon_value = get_post_meta($item->ID, '_menu_item_icon', true);
    ?>
    <p class="description description-wide">
        <label for="edit-menu-item-icon-<?php echo esc_attr($item_id); ?>">
            Escolha o Ícone (somente o nome do ícone Ionicons):
            <input type="text" id="edit-menu-item-icon-<?php echo esc_attr($item_id); ?>" class="widefat code edit-menu-item-icon" name="menu-item-icon[<?php echo esc_attr($item_id); ?>]" value="<?php echo esc_attr($icon_value); ?>" placeholder="Ex: home, person, cart, settings" />
        </label>
    </p>
    <?php
}
add_action('wp_nav_menu_item_custom_fields', 'sticky_footer_menu_custom_fields', 10, 4);

// Guardar o metadado do ícone
function sticky_footer_save_menu_item_icon($menu_id, $menu_item_db_id) {
    if (isset($_POST['menu-item-icon'][$menu_item_db_id])) {
        $icon_value = sanitize_text_field($_POST['menu-item-icon'][$menu_item_db_id]);
        update_post_meta($menu_item_db_id, '_menu_item_icon', $icon_value);
    } else {
        delete_post_meta($menu_item_db_id, '_menu_item_icon');
    }
}
add_action('wp_update_nav_menu_item', 'sticky_footer_save_menu_item_icon', 10, 2);

// Função para verificar se o dispositivo é móvel
function sticky_footer_is_mobile() {
    return wp_is_mobile();
}

// Função para verificar em que páginas o rodapé deve aparecer
function sticky_footer_should_display() {
    // Obter as configurações de visibilidade da página
    $page_visibility = get_option('footer_page_visibility', array());
    $global_status = get_option('footer_global_status', 'enabled');
    $show_for_users = get_option('footer_show_for_users', 'all');
    
    // Verificar se o usuário está qualificado com base na configuração de login
    if ($show_for_users == 'logged_in' && !is_user_logged_in()) {
        return false;
    }
    
    if ($show_for_users == 'logged_out' && is_user_logged_in()) {
        return false;
    }

    // Se estivermos em uma página individual
    if (is_singular()) {
        $post_id = get_the_ID();
        
        // Verificar se esta página específica tem uma configuração
        if (isset($page_visibility[$post_id])) {
            return $page_visibility[$post_id] === 'enabled';
        }
    } 
    
    // Para páginas de arquivo, taxonomias, etc.
    elseif (is_archive() || is_tax()) {
        $post_type = get_post_type();
        
        // Para arquivos de cursos ou do Tutor LMS
        if ($post_type == 'courses' || $post_type == 'lesson') {
            $key = 'archive_' . $post_type;
            if (isset($page_visibility[$key])) {
                return $page_visibility[$key] === 'enabled';
            }
        }
    }
    
    // Verificar para páginas específicas do WordPress
    if (is_front_page() && isset($page_visibility['front_page'])) {
        return $page_visibility['front_page'] === 'enabled';
    }
    
    if (is_home() && isset($page_visibility['blog_page'])) {
        return $page_visibility['blog_page'] === 'enabled';
    }
    
    if (is_search() && isset($page_visibility['search_page'])) {
        return $page_visibility['search_page'] === 'enabled';
    }
    
    if (is_404() && isset($page_visibility['404_page'])) {
        return $page_visibility['404_page'] === 'enabled';
    }
    
    // Se não tiver uma configuração específica, use o status global
    return $global_status === 'enabled';
}

// Exibir o menu do rodapé com os ícones, apenas em dispositivos móveis
function sticky_footer_display_menu_with_icons() {
    if (sticky_footer_should_display() && sticky_footer_is_mobile()) {
        if (has_nav_menu('footer_menu')) {
            wp_nav_menu(array(
                'theme_location' => 'footer_menu',
                'container' => 'div',
                'container_class' => 'sticky-footer',
                'items_wrap' => '<ul class="footer-menu">%3$s</ul>',
                'walker' => new Sticky_Footer_Menu_Walker()
            ));
        }
    }
}
add_action('wp_footer', 'sticky_footer_display_menu_with_icons');

// Personalizar a exibição dos itens do menu para incluir os ícones
class Sticky_Footer_Menu_Walker extends Walker_Nav_Menu {
    function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
        $icon_value = get_post_meta($item->ID, '_menu_item_icon', true);
        $is_active = (in_array('current-menu-item', $item->classes)) ? 'active' : '';

        $output .= '<li class="menu-item-' . esc_attr($item->ID) . ' ' . $is_active . '">';
        $output .= '<a href="' . esc_url($item->url) . '">';

        if (!empty($icon_value)) {
            $output .= '<ion-icon name="' . esc_attr($icon_value) . '"></ion-icon> ';
        }

        $output .= esc_html($item->title) . '</a></li>';
    }
}

// Adicionar submenu de Configurações em Aparência
function sticky_footer_add_menu() {
    add_theme_page(
        'Configurações do Rodapé', 
        'Configurações do Rodapé', 
        'manage_options', 
        'sticky_footer_settings', 
        'sticky_footer_settings_page'
    );
}
add_action('admin_menu', 'sticky_footer_add_menu');

// Página de configurações com abas para Cores, Fontes e Visibilidade
function sticky_footer_settings_page() {
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'visibility';
    ?>
    <div class="wrap">
        <h1>Configurações do Rodapé</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=sticky_footer_settings&tab=visibility" class="nav-tab <?php echo $active_tab == 'visibility' ? 'nav-tab-active' : ''; ?>">Visibilidade</a>
            <a href="?page=sticky_footer_settings&tab=colors" class="nav-tab <?php echo $active_tab == 'colors' ? 'nav-tab-active' : ''; ?>">Cores</a>
            <a href="?page=sticky_footer_settings&tab=fonts" class="nav-tab <?php echo $active_tab == 'fonts' ? 'nav-tab-active' : ''; ?>">Fontes</a>
        </h2>
        <form method="post" action="options.php">
            <?php
            if ($active_tab == 'colors') {
                settings_fields('sticky_footer_color_settings_group');
                do_settings_sections('sticky_footer_color_settings');
            } elseif ($active_tab == 'fonts') {
                settings_fields('sticky_footer_font_settings_group');
                do_settings_sections('sticky_footer_font_settings');
            } else {
                settings_fields('sticky_footer_visibility_settings_group');
                do_settings_sections('sticky_footer_visibility_settings');
            }
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Registrar as configurações de cores
function sticky_footer_register_color_settings() {
    add_option('footer_background_color', '#2E3748');
    add_option('footer_text_color', '#ffffff');
    add_option('footer_hover_color', '#FFCC02');

    register_setting('sticky_footer_color_settings_group', 'footer_background_color');
    register_setting('sticky_footer_color_settings_group', 'footer_text_color');
    register_setting('sticky_footer_color_settings_group', 'footer_hover_color');

    add_settings_section('sticky_footer_color_section', 'Configurações de Cores do Rodapé', null, 'sticky_footer_color_settings');

    add_settings_field('footer_background_color', 'Cor de Fundo', 'sticky_footer_background_color_callback', 'sticky_footer_color_settings', 'sticky_footer_color_section');
    add_settings_field('footer_text_color', 'Cor do Texto', 'sticky_footer_text_color_callback', 'sticky_footer_color_settings', 'sticky_footer_color_section');
    add_settings_field('footer_hover_color', 'Cor do Hover', 'sticky_footer_hover_color_callback', 'sticky_footer_color_settings', 'sticky_footer_color_section');
}
add_action('admin_init', 'sticky_footer_register_color_settings');

// Registrar as configurações de fontes
function sticky_footer_register_font_settings() {
    add_option('footer_font_family', 'Sora');

    register_setting('sticky_footer_font_settings_group', 'footer_font_family');

    add_settings_section('sticky_footer_font_section', 'Configurações de Fontes do Rodapé', null, 'sticky_footer_font_settings');

    add_settings_field('footer_font_family', 'Fonte', 'sticky_footer_font_family_callback', 'sticky_footer_font_settings', 'sticky_footer_font_section');
}
add_action('admin_init', 'sticky_footer_register_font_settings');

// Registrar as configurações de visibilidade
function sticky_footer_register_visibility_settings() {
    // Opções para controlar onde o footer aparece
    add_option('footer_global_status', 'enabled'); // Status global
    add_option('footer_page_visibility', array()); // Configurações por página
    add_option('footer_show_for_users', 'all'); // Quais usuários podem ver

    register_setting('sticky_footer_visibility_settings_group', 'footer_global_status');
    register_setting('sticky_footer_visibility_settings_group', 'footer_page_visibility', array(
        'sanitize_callback' => 'sticky_footer_sanitize_page_visibility',
    ));
    register_setting('sticky_footer_visibility_settings_group', 'footer_show_for_users');

    add_settings_section('sticky_footer_visibility_section', 'Configurações de Visibilidade do Rodapé', 'sticky_footer_visibility_section_callback', 'sticky_footer_visibility_settings');

    add_settings_field('footer_global_status', 'Status Global', 'sticky_footer_global_status_callback', 'sticky_footer_visibility_settings', 'sticky_footer_visibility_section');
    add_settings_field('footer_show_for_users', 'Mostrar para', 'sticky_footer_show_for_users_callback', 'sticky_footer_visibility_settings', 'sticky_footer_visibility_section');
    add_settings_field('footer_page_visibility', 'Visibilidade por Página', 'sticky_footer_page_visibility_callback', 'sticky_footer_visibility_settings', 'sticky_footer_visibility_section');
}
add_action('admin_init', 'sticky_footer_register_visibility_settings');

// Descrição da seção de visibilidade
function sticky_footer_visibility_section_callback() {
    echo '<p>Configure em quais páginas o rodapé móvel deve aparecer. Ative ou desative individualmente para cada página.</p>';
}

// Sanitização para configurações de visibilidade
function sticky_footer_sanitize_page_visibility($input) {
    if (!is_array($input)) {
        return array();
    }
    
    $sanitized = array();
    foreach ($input as $page_id => $status) {
        $sanitized[sanitize_key($page_id)] = ($status === 'enabled') ? 'enabled' : 'disabled';
    }
    
    return $sanitized;
}

// Callback para o status global
function sticky_footer_global_status_callback() {
    $global_status = get_option('footer_global_status', 'enabled');
    ?>
    <label class="switch">
        <input type="checkbox" name="footer_global_status" value="enabled" <?php checked('enabled', $global_status); ?>>
        <span class="slider round"></span>
    </label>
    <span class="status-label"><?php echo $global_status === 'enabled' ? 'Ativado' : 'Desativado'; ?></span>
    <p class="description">Status padrão para todas as páginas que não têm configuração específica.</p>
    
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }
        
        input:checked + .slider {
            background-color: #2196F3;
        }
        
        input:focus + .slider {
            box-shadow: 0 0 1px #2196F3;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .slider.round {
            border-radius: 34px;
        }
        
        .slider.round:before {
            border-radius: 50%;
        }
        
        .status-label {
            margin-left: 10px;
            font-weight: 500;
        }
        
        .page-visibility-table {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
        }
        
        .page-visibility-table th, 
        .page-visibility-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .page-visibility-table th {
            background-color: #f2f2f2;
        }
        
        .page-visibility-search {
            margin-bottom: 15px;
            padding: 8px;
            width: 100%;
            max-width: 300px;
        }
        
        .page-visibility-filter {
            margin-bottom: 15px;
            margin-left: 10px;
        }
    </style>
    
    <script>
    jQuery(document).ready(function($) {
        $('.switch input').on('change', function() {
            var status = $(this).is(':checked') ? 'Ativado' : 'Desativado';
            $(this).closest('td').find('.status-label').text(status);
        });
    });
    </script>
    <?php
}

// Callback para mostrar para quais usuários
function sticky_footer_show_for_users_callback() {
    $option = get_option('footer_show_for_users', 'all');
    ?>
    <select name="footer_show_for_users">
        <option value="all" <?php selected($option, 'all'); ?>>Todos os usuários</option>
        <option value="logged_in" <?php selected($option, 'logged_in'); ?>>Apenas usuários logados</option>
        <option value="logged_out" <?php selected($option, 'logged_out'); ?>>Apenas usuários não logados</option>
    </select>
    <p class="description">Defina quais usuários podem ver o rodapé.</p>
    <?php
}

// Callback para a tabela de visibilidade por página
function sticky_footer_page_visibility_callback() {
    $page_visibility = get_option('footer_page_visibility', array());
    ?>
    <input type="text" id="page-search" class="page-visibility-search" placeholder="Pesquisar páginas...">
    
    <div class="page-visibility-filter">
        <label>
            <input type="radio" name="page-filter" value="all" checked> Todas
        </label>
        <label>
            <input type="radio" name="page-filter" value="enabled"> Ativadas
        </label>
        <label>
            <input type="radio" name="page-filter" value="disabled"> Desativadas
        </label>
    </div>
    
    <table class="page-visibility-table" id="page-visibility-table">
        <thead>
            <tr>
                <th>Título da Página</th>
                <th>Tipo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Adicionar páginas padrão do WordPress
            $special_pages = array(
                'front_page' => 'Página Inicial',
                'blog_page' => 'Página do Blog',
                'search_page' => 'Resultados de Busca',
                '404_page' => 'Página 404',
            );
            
            foreach ($special_pages as $page_key => $page_title) {
                $status = isset($page_visibility[$page_key]) ? $page_visibility[$page_key] : 'enabled';
                ?>
                <tr data-status="<?php echo $status; ?>">
                    <td><?php echo $page_title; ?></td>
                    <td>Especial</td>
                    <td>
                        <label class="switch">
                            <input type="checkbox" name="footer_page_visibility[<?php echo $page_key; ?>]" 
                                   value="enabled" <?php checked('enabled', $status); ?>>
                            <span class="slider round"></span>
                        </label>
                        <span class="status-label"><?php echo $status === 'enabled' ? 'Ativado' : 'Desativado'; ?></span>
                    </td>
                </tr>
                <?php
            }
            
            // Adicionar Tipos de Post (archives)
            $post_types = get_post_types(array('public' => true), 'objects');
            foreach ($post_types as $post_type) {
                $archive_key = 'archive_' . $post_type->name;
                $status = isset($page_visibility[$archive_key]) ? $page_visibility[$archive_key] : 'enabled';
                if ($post_type->has_archive) {
                    ?>
                    <tr data-status="<?php echo $status; ?>">
                        <td>Arquivo: <?php echo $post_type->label; ?></td>
                        <td>Arquivo</td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="footer_page_visibility[<?php echo $archive_key; ?>]" 
                                       value="enabled" <?php checked('enabled', $status); ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="status-label"><?php echo $status === 'enabled' ? 'Ativado' : 'Desativado'; ?></span>
                        </td>
                    </tr>
                    <?php
                }
            }
            
            // Listar todas as páginas publicadas
            $args = array(
                'post_type' => 'any',
                'posts_per_page' => -1,
                'post_status' => 'publish',
            );
            
            $query = new WP_Query($args);
            
            if ($query->have_posts()) {
                while ($query->have_posts()) {
                    $query->the_post();
                    $post_id = get_the_ID();
                    $post_type_obj = get_post_type_object(get_post_type());
                    $status = isset($page_visibility[$post_id]) ? $page_visibility[$post_id] : 'enabled';
                    ?>
                    <tr data-status="<?php echo $status; ?>">
                        <td><?php the_title(); ?></td>
                        <td><?php echo $post_type_obj->labels->singular_name; ?></td>
                        <td>
                            <label class="switch">
                                <input type="checkbox" name="footer_page_visibility[<?php echo $post_id; ?>]" 
                                       value="enabled" <?php checked('enabled', $status); ?>>
                                <span class="slider round"></span>
                            </label>
                            <span class="status-label"><?php echo $status === 'enabled' ? 'Ativado' : 'Desativado'; ?></span>
                        </td>
                    </tr>
                    <?php
                }
            }
            wp_reset_postdata();
            ?>
        </tbody>
    </table>
    
    <script>
    jQuery(document).ready(function($) {
        // Pesquisa de páginas
        $('#page-search').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $("#page-visibility-table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
        
        // Filtro por status
        $('input[name="page-filter"]').on('change', function() {
            var filterValue = $(this).val();
            
            if (filterValue === 'all') {
                $("#page-visibility-table tbody tr").show();
            } else {
                $("#page-visibility-table tbody tr").hide();
                $("#page-visibility-table tbody tr[data-status='" + filterValue + "']").show();
            }
        });
        
        // Atualizar rótulo de status ao alterar o switch
        $('.switch input').on('change', function() {
            var status = $(this).is(':checked') ? 'Ativado' : 'Desativado';
            var statusValue = $(this).is(':checked') ? 'enabled' : 'disabled';
            
            $(this).closest('td').find('.status-label').text(status);
            $(this).closest('tr').attr('data-status', statusValue);
        });
    });
    </script>
    <?php
}

// Funções callback para os campos de cores
function sticky_footer_background_color_callback() {
    $background_color = get_option('footer_background_color');
    echo '<input type="text" name="footer_background_color" value="' . esc_attr($background_color) . '" class="footer-color-picker" data-default-color="#2E3748" />';
}

function sticky_footer_text_color_callback() {
    $text_color = get_option('footer_text_color');
    echo '<input type="text" name="footer_text_color" value="' . esc_attr($text_color) . '" class="footer-color-picker" data-default-color="#ffffff" />';
}

function sticky_footer_hover_color_callback() {
    $hover_color = get_option('footer_hover_color');
    echo '<input type="text" name="footer_hover_color" value="' . esc_attr($hover_color) . '" class="footer-color-picker" data-default-color="#FFCC02" />';
}

// Função callback para a seleção de fontes
function sticky_footer_font_family_callback() {
    $font_family = get_option('footer_font_family', 'Sora');
    $fonts = array('Sora', 'Roboto', 'Lato', 'Montserrat', 'Arial', 'Verdana');
    echo '<select name="footer_font_family">';
    foreach ($fonts as $font) {
        echo '<option value="' . esc_attr($font) . '" ' . selected($font_family, $font, false) . '>' . esc_html($font) . '</option>';
    }
    echo '</select>';
}

// Carregar o Color Picker no painel de administração
function sticky_footer_enqueue_color_picker($hook_suffix) {
    // Atualizar o caminho para a nova página de configurações
    if ($hook_suffix === 'appearance_page_sticky_footer_settings') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_add_inline_script('wp-color-picker', '
            jQuery(document).ready(function($) {
                $(".footer-color-picker").wpColorPicker({
                    clear: function() {
                        $(this).wpColorPicker("color", $(this).data("default-color"));
                    },
                    palettes: true
                });
                $(".wp-picker-clear").text("Padrão");
            });
        ');
    }
}
add_action('admin_enqueue_scripts', 'sticky_footer_enqueue_color_picker');
?>