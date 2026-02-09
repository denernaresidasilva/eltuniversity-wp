<?php
/*
Plugin Name: Tutor Design Personalizado 
Plugin URI: https://pluginstech.com
Description: Este plugin aprimora o design das aulas do Tutor LMS, oferecendo uma aparência mais moderna e atraente, com presets de cores — incluindo um modo escuro.
Version: 1.0
Author: Plugins Tech
Author URI: https://pluginstech.com
License: 
License URI: 
*/
// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class PersonalizadorTutorLMS {
    // Cores padrão
    private $default_colors = [
        'primary_color' => '#7F00FF',
        'hover_color' => '#FFA804',
        'background_color' => '#121212',
        'card_background' => '#424242',
        'card_active_background' => '#6A6A6A',
        'text_color' => '#FFFFFF',
        'complete_button_text_color' => '#FFFFFF',
        'complete_button_hover_text_color' => '#000000',
    ];
    
    // Presets de cores disponíveis
    private $color_presets = [
        'dark_purple' => [
            'name' => 'Roxo Escuro',
            'primary_color' => '#7F00FF',
            'hover_color' => '#FFA804',
            'background_color' => '#121212',
            'card_background' => '#424242',
            'card_active_background' => '#6A6A6A',
            'text_color' => '#FFFFFF',
            'complete_button_text_color' => '#FFFFFF',
            'complete_button_hover_text_color' => '#000000',
        ],
        'blue_night' => [
            'name' => 'Azul Noturno',
            'primary_color' => '#0066CC',
            'hover_color' => '#FF5500',
            'background_color' => '#0A1929',
            'card_background' => '#1E2A38',
            'card_active_background' => '#2C3E50',
            'text_color' => '#FFFFFF',
            'complete_button_text_color' => '#FFFFFF',
            'complete_button_hover_text_color' => '#000000',
        ],
        'forest_green' => [
            'name' => 'Verde Floresta',
            'primary_color' => '#228B22',
            'hover_color' => '#FFD700',
            'background_color' => '#0F2012',
            'card_background' => '#29472D',
            'card_active_background' => '#3A6A41',
            'text_color' => '#FFFFFF',
            'complete_button_text_color' => '#FFFFFF',
            'complete_button_hover_text_color' => '#000000',
        ],
        'light_mode' => [
            'name' => 'Modo Claro',
            'primary_color' => '#7F00FF',
            'hover_color' => '#FFA804',
            'background_color' => '#F5F5F5',
            'card_background' => '#FFFFFF',
            'card_active_background' => '#EEEEEE',
            'text_color' => '#333333',
            'complete_button_text_color' => '#FFFFFF',
            'complete_button_hover_text_color' => '#000000',
        ]
    ];
    
    public function __construct() {
        // Hooks de inicialização
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_head', [$this, 'output_custom_css']);
        
        // Adicionando scripts e estilos para o admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Adiciona link de configurações na lista de plugins
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Personalizador Tutor LMS',
            'Personalizar Tutor',
            'manage_options',
            'personalizador-tutor-lms',
            [$this, 'render_admin_page'],
            'dashicons-admin-customizer',
            100
        );
    }
    
    public function register_settings() {
        register_setting('personalizador_tutor_lms_options', 'ptl_color_preset');
        register_setting('personalizador_tutor_lms_options', 'ptl_use_custom_colors');
        
        // Registrar configurações individuais para cada cor
        foreach ($this->default_colors as $color_id => $color_value) {
            register_setting('personalizador_tutor_lms_options', 'ptl_' . $color_id);
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_personalizador-tutor-lms' !== $hook) {
            return;
        }
        
        // Adicionar o color picker do WordPress
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Adicionar script personalizado
        wp_enqueue_script(
            'personalizador-tutor-admin',
            plugins_url('admin.js', __FILE__),
            ['jquery', 'wp-color-picker'],
            '1.0.0',
            true
        );
        
        // Adicionar estilos admin
        wp_enqueue_style(
            'personalizador-tutor-admin-css',
            plugins_url('admin.css', __FILE__),
            [],
            '1.0.0'
        );
    }
    
    public function add_settings_link($links) {
        $settings_link = '<a href="admin.php?page=personalizador-tutor-lms">' . __('Configurações', 'personalizador-tutor-lms') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }
    
    public function render_admin_page() {
        $color_preset = get_option('ptl_color_preset', 'dark_purple');
        $use_custom_colors = get_option('ptl_use_custom_colors', false);
        ?>
        <div class="wrap">
            <h1>Personalizador de Cores Tutor LMS</h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('personalizador_tutor_lms_options'); ?>
                <?php do_settings_sections('personalizador_tutor_lms_options'); ?>
                
                <div class="ptl-container">
                    <div class="ptl-section">
                        <h2>Escolha um Preset de Cores</h2>
                        <div class="ptl-presets">
                            <?php foreach ($this->color_presets as $preset_id => $preset): ?>
                                <div class="ptl-preset-card <?php echo $color_preset === $preset_id ? 'active' : ''; ?>" 
                                     data-preset="<?php echo esc_attr($preset_id); ?>">
                                    <h3><?php echo esc_html($preset['name']); ?></h3>
                                    <div class="ptl-colors-preview">
                                        <span style="background-color: <?php echo esc_attr($preset['primary_color']); ?>"></span>
                                        <span style="background-color: <?php echo esc_attr($preset['hover_color']); ?>"></span>
                                        <span style="background-color: <?php echo esc_attr($preset['background_color']); ?>"></span>
                                        <span style="background-color: <?php echo esc_attr($preset['card_background']); ?>"></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="ptl_color_preset" name="ptl_color_preset" value="<?php echo esc_attr($color_preset); ?>">
                    </div>
                    
                    <div class="ptl-section">
                        <h2>Personalização Avançada</h2>
                        <label for="ptl_use_custom_colors">
                            <input type="checkbox" id="ptl_use_custom_colors" name="ptl_use_custom_colors" value="1" <?php checked($use_custom_colors, 1); ?>>
                            Usar cores personalizadas (sobrescreve o preset selecionado)
                        </label>
                        
                        <div class="ptl-custom-colors" <?php echo !$use_custom_colors ? 'style="opacity: 0.5;"' : ''; ?>>
                            <?php
                            $color_fields = [
                                'primary_color' => 'Cor Primária',
                                'hover_color' => 'Cor de Hover',
                                'background_color' => 'Cor de Fundo',
                                'card_background' => 'Cor dos Cartões',
                                'card_active_background' => 'Cor Ativa dos Cartões',
                                'text_color' => 'Cor do Texto',
                                'complete_button_text_color' => 'Cor do Texto do Botão Concluído',
                                'complete_button_hover_text_color' => 'Cor do Texto do Botão Concluído (Hover)'
                            ];
                            
                            foreach ($color_fields as $field_id => $field_label):
                                $preset_value = $this->color_presets[$color_preset][$field_id];
                                $saved_value = get_option('ptl_' . $field_id, $preset_value);
                            ?>
                                <div class="ptl-color-field">
                                    <label for="ptl_<?php echo esc_attr($field_id); ?>"><?php echo esc_html($field_label); ?></label>
                                    <input type="text" class="ptl-color-picker" id="ptl_<?php echo esc_attr($field_id); ?>" 
                                           name="ptl_<?php echo esc_attr($field_id); ?>" 
                                           value="<?php echo esc_attr($saved_value); ?>"
                                           data-default-color="<?php echo esc_attr($preset_value); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="ptl-preview">
                        <h2>Preview</h2>
                        <div class="ptl-preview-box">
                            <!-- Simular elementos do Tutor LMS para preview -->
                            <div class="preview-background">
                                <div class="preview-card">
                                    <div class="preview-header">Conteúdo do Curso</div>
                                    <div class="preview-content">
                                        <div class="preview-topic active">
                                            <span class="preview-icon">▶</span>
                                            <span class="preview-text">Módulo 1: Introdução</span>
                                        </div>
                                        <div class="preview-topic">
                                            <span class="preview-icon">▶</span>
                                            <span class="preview-text">Módulo 2: Conceitos Básicos</span>
                                        </div>
                                    </div>
                                    <div class="preview-button-container">
                                        <button class="preview-complete-button">
                                            <span class="preview-complete-icon">✓</span>
                                            <span>Marcar como concluído</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php submit_button('Salvar Configurações'); ?>
                </div>
            </form>
        </div>
        <?php
    }
    
    public function output_custom_css() {
        if (!is_admin() && function_exists('tutor')) {
            $colors = $this->get_active_colors();
            
            $css = $this->generate_css($colors);
            echo '<style type="text/css">' . $css . '</style>';
        }
    }
    
    public function get_active_colors() {
        $use_custom_colors = get_option('ptl_use_custom_colors', false);
        
        if ($use_custom_colors) {
            $colors = [];
            foreach ($this->default_colors as $color_id => $default_value) {
                $colors[$color_id] = get_option('ptl_' . $color_id, $default_value);
            }
            return $colors;
        } else {
            $preset = get_option('ptl_color_preset', 'dark_purple');
            return $this->color_presets[$preset];
        }
    }
    
    public function generate_css($colors) {
        // Substituir as cores no CSS original
        $css = "
        /*Personalização de cores do Tutor LMS*/
        
        /*Ocultar configurações painel cursos*/
        #course-settings {
            display: none;
        }
        
        /*Página Aula*/
        .tutor-course-single-content-wrapper{
            background-color: {$colors['background_color']} !important;
        }
        
        /*Página informação curso*/
        .tutor-page-wrap {
            padding-top: 4%;
        }
        .tutor-single-course-sidebar-more>div:last-child{
            border: 0px !important;
        }
        
        .tutor-course-details-widget{
            border: 0px !important;
            background-color: {$colors['card_background']} !important;
            padding: 30px !important;
        }
        
        /*Página Painel do Cliente*/
        .tutor-wrap{
            padding-top: 4%;
        }
        
        .tutor-dashboard{
            background-color: {$colors['background_color']};
        }
        
        .courses-template-default{
            background-color: {$colors['background_color']} !important;
        }
        
        .tutor-color-black {
            color: {$colors['text_color']};
        }
        
        .tutor-course-content-list-item-title{
            color: {$colors['text_color']};
        }
        
        .tutor-nav:not(.tutor-nav-pills):not(.tutor-nav-tabs) {
            color: {$colors['text_color']};
        }
        
        .tutor-nav:not(.tutor-nav-pills):not(.tutor-nav-tabs) .tutor-nav-link {
            color: {$colors['text_color']};
        }
        
        .tutor-nav-link.is-active {
            color: {$colors['primary_color']} !important;
        }
        
        .tutor-btn-ghost{
            color: {$colors['text_color']};
        }
        
        .tutor-btn-ghost:hover{
            color: {$colors['hover_color']};
        }
        
        .tutor-meta>*{
            color: {$colors['text_color']};
        }
        
        .tutor-nav .tutor-nav-more-icon {
            color: {$colors['text_color']};
        
        }
        
        .tutor-accordion-item-header.is-active {
            color: {$colors['text_color']};
            background-color: {$colors['card_active_background']};
        }
        
        .tutor-course-content-list-item{
            background-color: {$colors['card_background']};
        }
        
        .tutor-course-content-list-item:hover{
            background-color: {$colors['card_active_background']};
        }
        
        .tutor-course-content-list-item-icon{
            color: {$colors['text_color']};
        }
        
        .tutor-course-thumbnail{
            border-radius: 10px;
        }
        
        .tutor-user-public-profile .tutor-user-profile-content h3{
            color: {$colors['text_color']};
        }
        
        .tutor-user-public-profile .photo-area .pp-area .profile-name h3{
            color: {$colors['text_color']} !important;
        }
        .tutor-user-public-profile .tutor-user-profile-content h3{
            color: {$colors['text_color']} !important;
        }
        
        .tutor-accordion-item{
            border: 1px solid rgba(255, 255, 255, 0.05);
            background-color: {$colors['card_background']};
        }
        
        .tutor-accordion-item-body-content{
            border-top: 0px;
        }
        
        /* Backgorund Video */
        .tutor-course-single-content-wrapper .tutor-video-player .loading-spinner {
            background: {$colors['background_color']} !important;
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-header .tutor-course-topic-summary{
            color: {$colors['text_color']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-header:after{
            color: {$colors['text_color']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item a{
            background-color: {$colors['card_background']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item.is-active a{
            background-color: {$colors['card_active_background']} !important;
            border-radius: 10px;
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item-title{
            color: {$colors['text_color']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item-icon {
            color: {$colors['text_color']};
        }
        
        /* Espaçamento Branco */
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-body {
            background-color: #ffffff00 !important;
        }
        
        
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-body {
            background-color: none;
        }
        
        /* Barra Lateral */
        .tutor-course-single-sidebar-wrapper {
            flex: 0 0 400px;
            width: 400px;
            background-color: {$colors['card_background']};
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin: 30px;
            border-radius: 10px;
            padding: 10px;
        }
        
        /* Barra Lateral - Conteúdo Curso - Titulo Principal */
        .tutor-course-single-sidebar-title {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            height: 60px;
            background-color: {$colors['text_color']};
            border-bottom: 0px !important;
            border-radius: 10px;
        }
        
        #-single-lesson-2-9 .tutor-course-single-content-wrapper .tutor-course-single-sidebar-wrapper {
            border-color: {$colors['card_background']};
            border-style: solid;
            background-color: {$colors['card_background']};
        }
        
        /* Barra Lateral - Conteúdo Curso - Titulo Curso */
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-header {
            font-size: 16px;
            font-weight: 500;
            color: var(--tutor-body-color);
            background-color: {$colors['card_active_background']} !important;
            border-bottom: 0px !important;
            padding: 12px 44px 12px 16px;
            border-radius: 10px !important;
            user-select: none;
            outline: transparent solid 2px;
            outline-offset: 2px;
            cursor: pointer;
            margin-bottom: 15px !important;
        }
        
        .tutor-course-topic-title{
            color: {$colors['text_color']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item.is-active a {
            background-color: {$colors['card_background']};
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-course-topic-item.is-active .tutor-course-topic-item-icon, .tutor-course-single-sidebar-wrapper .tutor-course-topic-item.is-active .tutor-course-topic-item-title {
            color: {$colors['text_color']};
        }
        
        .tutor-nav:not(.tutor-nav-pills):not(.tutor-nav-tabs) {
            border-bottom: 0px;
        }
        
        .tutor-course-single-sidebar-wrapper .tutor-accordion-item-body {
            padding-top: 4px;
            padding-bottom: 4px;
        }
        
        
        .tutor-video-player-wrapper {
            margin-right: 20px;
        }
        
        .plyr--video {
            background: {$colors['background_color']};
            overflow: hidden;
           
        }
        
        .tutor-course-single-sidebar-title {
            background-color: {$colors['card_background']};
        }
        .tutor-iconic-btn:hover, .tutor-iconic-btn:focus, .tutor-iconic-btn:active {
            color: {$colors['text_color']};
            background-color: rgba(var(--tutor-color-primary-rgb), 0.1);
            border-color: rgba(var(--tutor-color-primary-rgb), 0.1);
        }
        
        .tutor-course-single-content-wrapper #tutor-single-entry-content .tutor-course-topic-single-header{
            background-color: {$colors['background_color']};
        }
        
        .tutor-course-single-content-wrapper #tutor-single-entry-content .tutor-course-topic-single-footer {
            border-top: {$colors['background_color']};
            background-color: {$colors['background_color']};
        }
        
        .tutor-btn-secondary {
            border-color: {$colors['primary_color']};
            background-color: {$colors['primary_color']};
            color: {$colors['background_color']};
            border-color: {$colors['primary_color']};
        }
        
        /* Painel Usuário */
        
        .tutor-dashboard .tutor-dashboard-left-menu .tutor-dashboard-menu-item-icon{
            color: {$colors['text_color']};
        }
        
        .tutor-dashboard-menu-item-text{
            color: {$colors['text_color']};
        }
        
        .tutor-round-box{
            color: {$colors['text_color']};
        }
        
        .tutor-fs-3 {
            color: {$colors['text_color']};
        }
        
        .tutor-table tr td {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: {$colors['card_background']};
            color: {$colors['text_color']};
        }
        
        .tutor-table tr th {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            background: {$colors['card_active_background']};
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            color: {$colors['text_color']};
        }
        
        .tutor-dashboard .tutor-dashboard-content .tutor-dashboard-setting-social .tutor-social-field>div:first-child {
            color: {$colors['text_color']} !important;
        }
        
        .tutor-dashboard .tutor-dashboard-content .tutor-dashboard-setting-social .tutor-social-field>div:first-child i {
            color: {$colors['text_color']} !important;
        }
        
        .tutor-form-label {
            color: {$colors['text_color']} !important;
        }
        
        .tutor-dashboard .tutor-dashboard-content>h3, .tutor-dashboard .tutor-dashboard-content>h4 {
            color: {$colors['text_color']} !important;
        }
        
        .tutor-table tr td>a:not(.tutor-btn):not(.tutor-iconic-btn):not(.quiz-manual-review-action), .tutor-table tr td .tutor-table-link {
            color: {$colors['text_color']};
        }
        
        .tutor-table {
            --tutor-table-border-radius: 10px !important;
            border-color: {$colors['card_background']};
        }
        
        .tutor-table tr th:first-child {
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tutor-table tr th:last-child {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tutor-table tr td:first-child {
            border-left: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tutor-table tr td:last-child {
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tutor-col-lg-4{
        padding: 10px;	
        }
        
        .tutor-comment-text .tutor-mt-4 {
            color: #000 !important;
        }
        
        .tutor-course-card .tutor-course-name, .tutor-course-card .tutor-course-name a{
            color: {$colors['text_color']};
        }
        
        .tutor-meta-value, .tutor-meta a{
            color: {$colors['text_color']};
        }
        
        .tutor-thumbnail-uploader .thumbnail-wrapper {
            background: {$colors['card_background']};
        }
        
        .tutor-dashboard .tutor-dashboard-content #tutor_profile_cover_photo_editor #tutor_photo_meta_area>span {
            color: {$colors['text_color']};
        }
        
        .tutor-dashboard .tutor-dashboard-content #tutor_profile_cover_photo_editor #tutor_photo_meta_area>span>span {
            color: {$colors['text_color']};
        }
        
        .tutor-fs-5{
            color: {$colors['text_color']};
        }
        
        .tutor-table-responsive{
            border-radius: 10px !important;
        }
        
        .tutor-fs-5 .tutor-fw-medium .tutor-color-black .tutor-mb-16{
            color: {$colors['text_color']};
        }
        
        .tutor-modal-content {
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .tutor-modal-content-white {
            background-color: {$colors['card_background']};
        }
        
        .tutor-iconic-btn{
            color: {$colors['text_color']};
        }
        
        .tutor-spotlight-mobile-progress-complete {
            padding-right: 80px !important;
        }
        
        
        /*----------------------------*/
        
        /* Cor Botoões Player Vídeo da Aulas */
        button.plyr__control:hover{
            color: #000 !important;
            background-color: {$colors['hover_color']} !important;
        }
        
        .plyr__control--overlaid {
            background: #000 !important;
            border-color: #000;
            color: {$colors['primary_color']};
        }
        .plyr--full-ui input[type=range] {
            color: {$colors['primary_color']} !important;
        }
        
        .plyr--video .plyr__control.plyr__tab-focus, .plyr--video .plyr__control:hover, .plyr--video .plyr__control[aria-expanded=true] {
            background: {$colors['hover_color']} !important;
            border-color: {$colors['hover_color']} !important;
        }
        
        .plyr--full-ui a, .plyr--full-ui button, .plyr--full-ui input, .plyr--full-ui label {
            border: 0 !important;
            color: {$colors['text_color']} !important;
        }
        
        .plyr__menu__container .plyr__control>span {
            color: #000 !important;
        }
        
        .plyr--video .plyr__control.plyr__tab-focus, .plyr--video .plyr__control:hover, .plyr--video .plyr__control[aria-expanded=true] {
            background: {$colors['primary_color']} !important;
            border-color: {$colors['primary_color']} !important;
        }
        
        .plyr--video .plyr__control.plyr__tab-focus, .plyr--video .plyr__control:acitve, .plyr--video .plyr__control[aria-expanded=true] {
            background: {$colors['primary_color']} !important;
            border-color: {$colors['primary_color']} !important;
        }
        
        [type=button]:focus, [type=button]:hover, [type=submit]:focus, [type=submit]:hover, button:focus, button:hover {
            background-color: {$colors['primary_color']} !important;
            border-color: {$colors['primary_color']} !important;
        
        }
        
        /* Botão 'Marcar como concluído' */
        .tutor-topbar-mark-btn.tutor-btn.tutor-btn-primary {
            border-color: {$colors['primary_color']};
            background-color: {$colors['primary_color']};
            color: {$colors['complete_button_text_color']} !important;
        }
        
        .tutor-topbar-mark-btn.tutor-btn.tutor-btn-primary:hover {
            border-color: {$colors['hover_color']};
            background-color: {$colors['hover_color']};
            color: {$colors['complete_button_hover_text_color']} !important;
        }
        
        .tutor-topbar-mark-btn.tutor-btn.tutor-btn-primary .tutor-icon-circle-mark-line {
            color: {$colors['complete_button_text_color']} !important;
        }
        
        .tutor-topbar-mark-btn.tutor-btn.tutor-btn-primary:hover .tutor-icon-circle-mark-line {
            color: {$colors['complete_button_hover_text_color']} !important;
        }
        
        /*----------------------------*/
        
        
        /* Celular */
        
        @media only screen and (max-width: 480px) {
            .tutor-course-single-sidebar-wrapper {
            margin: 0px !important;
            }
        }
        
        .tutor-spotlight-mobile-progress-complete {
            background: {$colors['card_background']};
            box-shadow: 0px 0px 16px 0 rgba(176,182,209,0.180862);
            margin-top: 0px;
            padding: 20px;
            padding-right: 40px;
            padding-left: 40px;
        }
        
        .tutor-color-muted {
            color: {$colors['text_color']};
        }
        
        .tutor-btn-primary {
            border-color: {$colors['primary_color']};
            background-color: {$colors['primary_color']};
            color: {$colors['complete_button_text_color']};
        }
        .tutor-btn-primary:hover {
            border-color: {$colors['hover_color']};
            background-color: {$colors['hover_color']};
            color: {$colors['complete_button_hover_text_color']};
        }
        
        /* Tablet */
        .tutor-tab {
            background-color: {$colors['background_color']} !important;
        }
        
        .tutor-video-player-wrapper{
            background-color: {$colors['background_color']} !important;
        }
        
        @media only screen and (max-width: 921px) {
            .tutor-video-player-wrapper {
            margin-right: 0px !important;
            }
            .plyr--video {
            border-radius: 0px !important;
        }
        }
        
        @media (max-width: 1199.98px){
        .tutor-course-single-content-wrapper.tutor-course-single-sidebar-open .tutor-course-single-sidebar-wrapper {
        background-color: {$colors['card_background']} !important;
        }
            .tutor-course-single-sidebar-wrapper
            {
                margin: 0px !important;
                width: 100% !important;
            }
            
            
        }
        
        .tutor-color-secondary {
            color: {$colors['text_color']};
        }
        
        /* Página Curso Simples */
        .tutor-card{
            background: {$colors['card_background']};
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .tutor-single-course-sidebar .tutor-sidebar-card .tutor-card-body{
            background: {$colors['card_background']};
        }
        
        .tutor-card-footer{
            background: {$colors['card_background']};
        }
        
        .tutor-single-course-sidebar-more>div:first-child {
        border: 1px solid rgba(255, 255, 255, 0.05);
            background-color: {$colors['card_background']};
        }
        
        @media (min-width: 800px){
        .tutor-course-details-page .tutor-course-details-tab .tutor-is-sticky {
            background: {$colors['card_background']};
            border-radius: 10px;
        }
        }
        
        @media (max-width: 991px)
        .tutor-user-public-profile .profile-name span {
            color: {$colors['text_color']} !important;
        }
        ";
        
        return $css;
    }
}

// Inicializar o plugin
new PersonalizadorTutorLMS();

// Criar arquivos necessários na ativação
register_activation_hook(__FILE__, 'ptl_activation');

function ptl_activation() {
    // Criar arquivo admin.js
    $admin_js_path = plugin_dir_path(__FILE__) . 'admin.js';
    
    if (!file_exists($admin_js_path)) {
        $admin_js = '
jQuery(document).ready(function($) {
    // Inicializar color pickers
    $(".ptl-color-picker").wpColorPicker({
        change: function() {
            updatePreview();
        }
    });
    
    // Mudar preset ao clicar
    $(".ptl-preset-card").on("click", function() {
        var preset = $(this).data("preset");
        $("#ptl_color_preset").val(preset);
        
        $(".ptl-preset-card").removeClass("active");
        $(this).addClass("active");
        
        // Atualizar cores personalizadas com base no preset
        updateColorFieldsFromPreset(preset);
        updatePreview();
    });
    
    // Toggle de cores personalizadas
    $("#ptl_use_custom_colors").on("change", function() {
        if ($(this).is(":checked")) {
            $(".ptl-custom-colors").css("opacity", "1");
        } else {
            $(".ptl-custom-colors").css("opacity", "0.5");
        }
        updatePreview();
    });
    
    // Função para atualizar campos de cores com base no preset
    function updateColorFieldsFromPreset(preset) {
        var presets = {
            "dark_purple": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#121212",
                "card_background": "#424242",
                "card_active_background": "#6A6A6A",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "blue_night": {
                "primary_color": "#0066CC",
                "hover_color": "#FF5500",
                "background_color": "#0A1929",
                "card_background": "#1E2A38",
                "card_active_background": "#2C3E50",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "forest_green": {
                "primary_color": "#228B22",
                "hover_color": "#FFD700",
                "background_color": "#0F2012",
                "card_background": "#29472D",
                "card_active_background": "#3A6A41",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "light_mode": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#F5F5F5",
                "card_background": "#FFFFFF",
                "card_active_background": "#EEEEEE",
                "text_color": "#333333",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            }
        };
        
        var colors = presets[preset];
        
        // Atualizar cada campo de cor
        for (var colorId in colors) {
            $("#ptl_" + colorId).val(colors[colorId]).wpColorPicker("color", colors[colorId]);
        }
    }
    
    // Atualizar preview
    function updatePreview() {
        var useCustom = $("#ptl_use_custom_colors").is(":checked");
        var colors;
        
        if (useCustom) {
            colors = {
                primary_color: $("#ptl_primary_color").val(),
                hover_color: $("#ptl_hover_color").val(),
                background_color: $("#ptl_background_color").val(),
                card_background: $("#ptl_card_background").val(),
                card_active_background: $("#ptl_card_active_background").val(),
                text_color: $("#ptl_text_color").val(),
                complete_button_text_color: $("#ptl_complete_button_text_color").val(),
                complete_button_hover_text_color: $("#ptl_complete_button_hover_text_color").val()
            };
        } else {
            var preset = $("#ptl_color_preset").val();
            colors = getPresetsColors(preset);
        }
        
        // Atualizar o preview
        $(".preview-background").css("background-color", colors.background_color);
        $(".preview-card").css("background-color", colors.card_background);
        $(".preview-header").css("background-color", colors.card_active_background);
        $(".preview-topic.active").css("background-color", colors.card_active_background);
        $(".preview-text").css("color", colors.text_color);
        $(".preview-icon").css("color", colors.text_color);
        
        // Atualizar botão de completar
        $(".preview-complete-button").css({
            "background-color": colors.primary_color,
            "color": colors.complete_button_text_color,
            "border-color": colors.primary_color
        });
        
        $(".preview-complete-icon").css("color", colors.complete_button_text_color);
        
        // Efeito hover para o botão de completar
        $(".preview-complete-button").hover(
            function() {
                $(this).css({
                    "background-color": colors.hover_color,
                    "color": colors.complete_button_hover_text_color,
                    "border-color": colors.hover_color
                });
                $(".preview-complete-icon").css("color", colors.complete_button_hover_text_color);
            }, 
            function() {
                $(this).css({
                    "background-color": colors.primary_color,
                    "color": colors.complete_button_text_color,
                    "border-color": colors.primary_color
                });
                $(".preview-complete-icon").css("color", colors.complete_button_text_color);
            }
        );
    }
    
    function getPresetsColors(preset) {
        var presets = {
            "dark_purple": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#121212",
                "card_background": "#424242",
                "card_active_background": "#6A6A6A",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "blue_night": {
                "primary_color": "#0066CC",
                "hover_color": "#FF5500",
                "background_color": "#0A1929",
                "card_background": "#1E2A38",
                "card_active_background": "#2C3E50",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "forest_green": {
                "primary_color": "#228B22",
                "hover_color": "#FFD700",
                "background_color": "#0F2012",
                "card_background": "#29472D",
                "card_active_background": "#3A6A41",
                "text_color": "#FFFFFF",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            },
            "light_mode": {
                "primary_color": "#7F00FF",
                "hover_color": "#FFA804",
                "background_color": "#F5F5F5",
                "card_background": "#FFFFFF",
                "card_active_background": "#EEEEEE",
                "text_color": "#333333",
                "complete_button_text_color": "#FFFFFF",
                "complete_button_hover_text_color": "#000000"
            }
        };
        
        return presets[preset];
    }
    
    // Inicializar preview
    updatePreview();
});
        ';
        
        file_put_contents($admin_js_path, $admin_js);
    }
    
    // Criar arquivo admin.css
    $admin_css_path = plugin_dir_path(__FILE__) . 'admin.css';
    
    if (!file_exists($admin_css_path)) {
        $admin_css = '
.ptl-container {
    max-width: 1200px;
    margin: 20px 0;
}

.ptl-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ptl-presets {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-top: 15px;
}

.ptl-preset-card {
    width: 220px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.ptl-preset-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.ptl-preset-card.active {
    border: 2px solid #7F00FF;
    background-color: rgba(127, 0, 255, 0.05);
}

.ptl-preset-card h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.ptl-colors-preview {
    display: flex;
    gap: 5px;
}

.ptl-colors-preview span {
    width: 25px;
    height: 25px;
    border-radius: 50%;
    display: inline-block;
}

.ptl-custom-colors {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-top: 15px;
    transition: opacity 0.3s ease;
}

.ptl-color-field {
    margin-bottom: 10px;
}

.ptl-color-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
}

.ptl-preview {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.ptl-preview-box {
    height: 300px;
    overflow: hidden;
    border-radius: 5px;
    margin-top: 15px;
}

.preview-background {
    background-color: #121212;
    height: 100%;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.preview-card {
    background-color: #424242;
    width: 350px;
    border-radius: 10px;
    overflow: hidden;
}

.preview-header {
    background-color: #6A6A6A;
    color: white;
    padding: 15px;
    font-weight: bold;
}

.preview-content {
    padding: 10px;
}

.preview-topic {
    padding: 10px;
    margin-bottom: 5px;
    border-radius: 5px;
    display: flex;
    align-items: center;
}

.preview-topic.active {
    background-color: #6A6A6A;
}

.preview-icon {
    margin-right: 10px;
    color: white;
}

.preview-text {
    color: white;
}

.preview-button-container {
    padding: 15px;
}

.preview-complete-button {
    background-color: #7F00FF;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    border: 1px solid #7F00FF;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    transition: all 0.2s ease;
}

.preview-complete-icon {
    margin-right: 8px;
}

.preview-complete-button:hover {
    background-color: #FFA804;
    border-color: #FFA804;
    color: #000;
}
        ';
        
        file_put_contents($admin_css_path, $admin_css);
    }
}
?>