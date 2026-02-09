<?php
if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

// Adicionar a página de configurações para cores e ícones do rodapé
function sticky_footer_add_color_submenu() {
    add_submenu_page(
        'themes.php',  // Localização do submenu (Aparência > Cores do Footer)
        'Configurações de Cores e Ícones do Footer',
        'Cores e Ícones do Footer',
        'manage_options',
        'sticky_footer_color_icon_settings',
        'sticky_footer_color_icon_settings_page'
    );
}
add_action('admin_menu', 'sticky_footer_add_color_submenu');

// Renderizar a página de configurações de cores e ícones do rodapé
function sticky_footer_color_icon_settings_page() {
    ?>
    <div class="wrap">
        <h1>Configurações de Cores e Ícones do Footer</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('sticky_footer_color_icon_settings_group');
            do_settings_sections('sticky_footer_color_icon_settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Cor de Fundo do Footer</th>
                    <td>
                        <input type="text" name="footer_background_color" value="<?php echo esc_attr(get_option('footer_background_color', '#2E3748')); ?>" class="footer-color-picker" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cor dos Textos</th>
                    <td>
                        <input type="text" name="footer_text_color" value="<?php echo esc_attr(get_option('footer_text_color', '#ffffff')); ?>" class="footer-color-picker" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Cor dos Ícones ao Passar o Mouse (Hover)</th>
                    <td>
                        <input type="text" name="footer_hover_color" value="<?php echo esc_attr(get_option('footer_hover_color', '#FFCC02')); ?>" class="footer-color-picker" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Biblioteca de Ícones</th>
                    <td>
                        <select name="footer_icon_library">
                            <option value="ionicons" <?php selected(get_option('footer_icon_library'), 'ionicons'); ?>>Ionicons</option>
                            <option value="fontawesome" <?php selected(get_option('footer_icon_library'), 'fontawesome'); ?>>Font Awesome</option>
                            <option value="dashicons" <?php selected(get_option('footer_icon_library'), 'dashicons'); ?>>Dashicons</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Registrar as configurações de cores e ícones
function sticky_footer_register_color_icon_settings() {
    add_option('footer_background_color', '#2E3748');
    add_option('footer_text_color', '#ffffff');
    add_option('footer_hover_color', '#FFCC02');
    add_option('footer_icon_library', 'ionicons'); // Valor padrão

    register_setting('sticky_footer_color_icon_settings_group', 'footer_background_color');
    register_setting('sticky_footer_color_icon_settings_group', 'footer_text_color');
    register_setting('sticky_footer_color_icon_settings_group', 'footer_hover_color');
    register_setting('sticky_footer_color_icon_settings_group', 'footer_icon_library');
}
add_action('admin_init', 'sticky_footer_register_color_icon_settings');
