<?php
/**
 * Plugin Name: Custom Scrollbar Styler
 * Plugin URI: https://example.com/custom-scrollbar-styler
 * Description: Um plugin para controlar as cores e estilo da barra de rolagem através do painel administrativo.
 * Version: 1.0
 * Author: Custom Scrollbar Team
 * Author URI: https://example.com
 * Text Domain: custom-scrollbar-styler
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class Custom_Scrollbar_Styler {
    
    // Variáveis para armazenar as configurações padrão
    private $defaults = [
        'scrollbar_width' => '8',
        'track_color' => '#171616',
        'thumb_gradient_start' => '#634AAC',
        'thumb_gradient_end' => '#A9D818',
        'thumb_border_radius' => '3',
        'thumb_hover_direction' => 'reverse'
    ];
    
    /**
     * Construtor da classe
     */
    public function __construct() {
        // Hooks para iniciar o plugin
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'output_custom_css']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }
    
    /**
     * Adiciona a página de menu no admin
     */
    public function add_menu_page() {
        add_menu_page(
            __('Custom Scrollbar', 'custom-scrollbar-styler'),
            __('Custom Scrollbar', 'custom-scrollbar-styler'),
            'manage_options',
            'custom-scrollbar-styler',
            [$this, 'render_settings_page'],
            'dashicons-admin-appearance',
            81
        );
    }
    
    /**
     * Registra as configurações do plugin
     */
    public function register_settings() {
        register_setting(
            'custom_scrollbar_settings',
            'custom_scrollbar_options',
            [$this, 'sanitize_settings']
        );
        
        add_settings_section(
            'custom_scrollbar_section',
            __('Configurações da Barra de Rolagem', 'custom-scrollbar-styler'),
            [$this, 'settings_section_callback'],
            'custom-scrollbar-styler'
        );
        
        // Campo para largura da barra
        add_settings_field(
            'scrollbar_width',
            __('Largura da Barra (px)', 'custom-scrollbar-styler'),
            [$this, 'render_input_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'scrollbar_width',
                'type' => 'number',
                'min' => '1',
                'max' => '20',
                'step' => '1'
            ]
        );
        
        // Campo para cor de fundo da barra
        add_settings_field(
            'track_color',
            __('Cor de Fundo', 'custom-scrollbar-styler'),
            [$this, 'render_input_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'track_color',
                'type' => 'color'
            ]
        );
        
        // Campo para cor inicial do gradiente
        add_settings_field(
            'thumb_gradient_start',
            __('Gradiente - Cor Inicial', 'custom-scrollbar-styler'),
            [$this, 'render_input_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'thumb_gradient_start',
                'type' => 'color'
            ]
        );
        
        // Campo para cor final do gradiente
        add_settings_field(
            'thumb_gradient_end',
            __('Gradiente - Cor Final', 'custom-scrollbar-styler'),
            [$this, 'render_input_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'thumb_gradient_end',
                'type' => 'color'
            ]
        );
        
        // Campo para border radius
        add_settings_field(
            'thumb_border_radius',
            __('Raio da Borda (px)', 'custom-scrollbar-styler'),
            [$this, 'render_input_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'thumb_border_radius',
                'type' => 'number',
                'min' => '0',
                'max' => '20',
                'step' => '1'
            ]
        );
        
        // Campo para direção do gradiente no hover
        add_settings_field(
            'thumb_hover_direction',
            __('Direção do Gradiente no Hover', 'custom-scrollbar-styler'),
            [$this, 'render_select_field'],
            'custom-scrollbar-styler',
            'custom_scrollbar_section',
            [
                'id' => 'thumb_hover_direction',
                'options' => [
                    'same' => __('Mesma direção', 'custom-scrollbar-styler'),
                    'reverse' => __('Direção invertida', 'custom-scrollbar-styler')
                ]
            ]
        );
    }
    
    /**
     * Callback para a seção de configurações
     */
    public function settings_section_callback() {
        echo '<p>' . __('Personalize o estilo da barra de rolagem do seu site.', 'custom-scrollbar-styler') . '</p>';
    }
    
    /**
     * Renderiza campos de input
     */
    public function render_input_field($args) {
        $options = get_option('custom_scrollbar_options', $this->defaults);
        $id = $args['id'];
        $value = isset($options[$id]) ? $options[$id] : $this->defaults[$id];
        $type = isset($args['type']) ? $args['type'] : 'text';
        
        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($id) . '" name="custom_scrollbar_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '"';
        
        if (isset($args['min'])) {
            echo ' min="' . esc_attr($args['min']) . '"';
        }
        
        if (isset($args['max'])) {
            echo ' max="' . esc_attr($args['max']) . '"';
        }
        
        if (isset($args['step'])) {
            echo ' step="' . esc_attr($args['step']) . '"';
        }
        
        echo ' class="' . ($type === 'color' ? 'color-picker' : 'regular-text') . '" />';
        
        if ($type === 'color') {
            echo '<div class="color-preview" style="background-color: ' . esc_attr($value) . ';"></div>';
        }
    }
    
    /**
     * Renderiza campos select
     */
    public function render_select_field($args) {
        $options = get_option('custom_scrollbar_options', $this->defaults);
        $id = $args['id'];
        $value = isset($options[$id]) ? $options[$id] : $this->defaults[$id];
        $select_options = isset($args['options']) ? $args['options'] : [];
        
        echo '<select id="' . esc_attr($id) . '" name="custom_scrollbar_options[' . esc_attr($id) . ']">';
        
        foreach ($select_options as $option_value => $option_label) {
            echo '<option value="' . esc_attr($option_value) . '" ' . selected($value, $option_value, false) . '>';
            echo esc_html($option_label);
            echo '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * Sanitiza as configurações
     */
    public function sanitize_settings($input) {
        $sanitized = [];
        
        $sanitized['scrollbar_width'] = isset($input['scrollbar_width']) ? absint($input['scrollbar_width']) : $this->defaults['scrollbar_width'];
        $sanitized['track_color'] = isset($input['track_color']) ? sanitize_hex_color($input['track_color']) : $this->defaults['track_color'];
        $sanitized['thumb_gradient_start'] = isset($input['thumb_gradient_start']) ? sanitize_hex_color($input['thumb_gradient_start']) : $this->defaults['thumb_gradient_start'];
        $sanitized['thumb_gradient_end'] = isset($input['thumb_gradient_end']) ? sanitize_hex_color($input['thumb_gradient_end']) : $this->defaults['thumb_gradient_end'];
        $sanitized['thumb_border_radius'] = isset($input['thumb_border_radius']) ? absint($input['thumb_border_radius']) : $this->defaults['thumb_border_radius'];
        $sanitized['thumb_hover_direction'] = isset($input['thumb_hover_direction']) && in_array($input['thumb_hover_direction'], ['same', 'reverse']) ? $input['thumb_hover_direction'] : $this->defaults['thumb_hover_direction'];
        
        return $sanitized;
    }
    
    /**
     * Renderiza a página de configurações
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">

            
            <form method="post" action="options.php">
                <?php
                settings_fields('custom_scrollbar_settings');
                do_settings_sections('custom-scrollbar-styler');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Carrega os assets do admin
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_custom-scrollbar-styler' !== $hook) {
            return;
        }
        
        // Estilos do painel admin
        wp_enqueue_style(
            'custom-scrollbar-admin-styles',
            plugin_dir_url(__FILE__) . 'admin/css/admin-styles.css',
            [],
            '1.0.0'
        );
        
        // Scripts do painel admin
        wp_enqueue_script(
            'custom-scrollbar-admin-scripts',
            plugin_dir_url(__FILE__) . 'admin/js/admin-scripts.js',
            ['jquery', 'wp-color-picker'],
            '1.0.0',
            true
        );
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
    }
    
    /**
     * Gera e adiciona o CSS personalizado
     */
    public function output_custom_css() {
        $options = get_option('custom_scrollbar_options', $this->defaults);
        
        $scrollbar_width = absint($options['scrollbar_width']);
        $track_color = sanitize_hex_color($options['track_color']);
        $thumb_gradient_start = sanitize_hex_color($options['thumb_gradient_start']);
        $thumb_gradient_end = sanitize_hex_color($options['thumb_gradient_end']);
        $thumb_border_radius = absint($options['thumb_border_radius']);
        $thumb_hover_direction = $options['thumb_hover_direction'] === 'reverse' ? '0deg' : '180deg';
        
        $css = "
        ::-webkit-scrollbar {
            width: {$scrollbar_width}px;
        }
        ::-webkit-scrollbar-track {
            background: {$track_color};
        }
        ::-webkit-scrollbar-thumb {
            -webkit-border-radius: {$thumb_border_radius}px;
            border-radius: {$thumb_border_radius}px;
            background: linear-gradient(180deg, {$thumb_gradient_start}, {$thumb_gradient_end});
        }
        ::-webkit-scrollbar-thumb:hover {
            -webkit-border-radius: {$thumb_border_radius}px;
            border-radius: {$thumb_border_radius}px;
            background: linear-gradient({$thumb_hover_direction}, {$thumb_gradient_end}, {$thumb_gradient_start});
        }
        ";
        
        echo '<style type="text/css" id="custom-scrollbar-css">' . $css . '</style>';
    }
}

// Inicializa o plugin
$custom_scrollbar_styler = new Custom_Scrollbar_Styler();

// Ativar o plugin
register_activation_hook(__FILE__, 'custom_scrollbar_activate');

function custom_scrollbar_activate() {
    // Criar diretórios necessários
    $upload_dir = wp_upload_dir();
    $plugin_dir = $upload_dir['basedir'] . '/custom-scrollbar-styler';
    
    if (!file_exists($plugin_dir)) {
        wp_mkdir_p($plugin_dir);
    }
}

// Função para sanitizar cores hex (se o WordPress não tiver)
if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color($color) {
        if ('' === $color) {
            return '';
        }
        
        // 3 or 6 hex digits, optionally with a leading #
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        
        return '';
    }
}
