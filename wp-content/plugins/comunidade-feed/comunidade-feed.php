<?php
/**
 * Plugin Name: Comunidade Q&A
 * Description: Sistema de perguntas e respostas para criar uma comunidade no WordPress
 * Version: 1.0.0
 * Author: Seu Nome
 */

// Evitar acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class ComunidadeQA {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_submit_question', array($this, 'handle_submit_question'));
        add_action('wp_ajax_submit_answer', array($this, 'handle_submit_answer'));
        add_action('wp_ajax_delete_question', array($this, 'handle_delete_question'));
        add_action('wp_ajax_delete_answer', array($this, 'handle_delete_answer'));
        add_action('wp_ajax_upload_question_image', array($this, 'handle_upload_image'));
        
        // Adicionar widget ao dashboard
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
    }
    
    public function init() {
        add_shortcode('comunidade_fazer_pergunta', array($this, 'shortcode_form_pergunta'));
        add_shortcode('comunidade_feed', array($this, 'shortcode_feed'));
        add_shortcode('comunidade_completa', array($this, 'shortcode_comunidade_completa'));
    }
    
    // Adicionar widget ao dashboard
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'comunidade_qa_stats_widget',
            '📊 Estatísticas da Comunidade Q&A',
            array($this, 'dashboard_widget_content')
        );
    }
    
    // Conteúdo do widget do dashboard
    public function dashboard_widget_content() {
        global $wpdb;
        
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        // Coletar estatísticas principais
        $stats = array(
            'total_questions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE status = 'published'"),
            'total_answers' => $wpdb->get_var("SELECT COUNT(*) FROM $table_answers"),
            'questions_today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE DATE(created_at) = CURDATE()"),
            'questions_week' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'answers_today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_answers WHERE DATE(created_at) = CURDATE()"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_questions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")
        );
        
        // Atividade recente
        $recent_questions = $wpdb->get_results("
            SELECT q.title, q.created_at, u.display_name 
            FROM $table_questions q 
            LEFT JOIN {$wpdb->users} u ON q.user_id = u.ID 
            WHERE q.status = 'published' 
            ORDER BY q.created_at DESC 
            LIMIT 3
        ");
        
        ?>
        <div class="comunidade-dashboard-widget">
            <!-- Estatísticas principais em grid -->
            <div class="stats-grid-widget">
                <div class="stat-item-widget">
                    <div class="stat-number-widget"><?php echo number_format($stats['total_questions']); ?></div>
                    <div class="stat-label-widget">Total de Perguntas</div>
                </div>
                <div class="stat-item-widget">
                    <div class="stat-number-widget"><?php echo number_format($stats['total_answers']); ?></div>
                    <div class="stat-label-widget">Total de Respostas</div>
                </div>
                <div class="stat-item-widget">
                    <div class="stat-number-widget"><?php echo number_format($stats['questions_today']); ?></div>
                    <div class="stat-label-widget">Perguntas Hoje</div>
                </div>
                <div class="stat-item-widget">
                    <div class="stat-number-widget"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="stat-label-widget">Usuários Ativos</div>
                </div>
            </div>
            
            <!-- Estatísticas semanais -->
            <div class="weekly-stats-widget">
                <h4><span class="emoji">📅</span> Últimos 7 dias</h4>
                <div class="weekly-info">
                    <span><strong><?php echo $stats['questions_week']; ?></strong> perguntas</span>
                    <span><strong><?php echo $stats['answers_today']; ?></strong> respostas hoje</span>
                </div>
            </div>
            
            <!-- Atividade recente -->
            <?php if (!empty($recent_questions)): ?>
            <div class="recent-activity-widget">
                <h4><span class="emoji">🚀</span> Atividade Recente</h4>
                <ul class="recent-list-widget">
                    <?php foreach ($recent_questions as $question): ?>
                    <li class="recent-item-widget">
                        <div class="recent-title-widget"><?php echo esc_html(wp_trim_words($question->title, 8)); ?></div>
                        <div class="recent-meta-widget">
                            por <?php echo esc_html($question->display_name ?: 'Usuário'); ?> • 
                            <?php echo human_time_diff(strtotime($question->created_at)); ?> atrás
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <!-- Links de ação -->
            <div class="widget-actions">
                <a href="<?php echo admin_url('admin.php?page=comunidade-stats'); ?>" class="button button-primary">
                    <span class="emoji">📈</span> Ver Todas as Estatísticas
                </a>
                <a href="<?php echo admin_url('admin.php?page=comunidade-gerenciar'); ?>" class="button">
                    <span class="emoji">⚙️</span> Gerenciar Conteúdo
                </a>
            </div>
        </div>
        
        <style>
        .comunidade-dashboard-widget {
            padding: 5px;
        }
        
        .stats-grid-widget {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stat-item-widget {
            text-align: center;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #0073aa;
        }
        
        .stat-number-widget {
            font-size: 20px;
            font-weight: bold;
            color: #0073aa;
            line-height: 1;
            margin-bottom: 2px;
        }
        
        .stat-label-widget {
            font-size: 11px;
            color: #666;
            font-weight: 500;
        }
        
        .weekly-stats-widget {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }
        
        .weekly-stats-widget h4 {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #856404;
        }
        
        .weekly-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #856404;
        }
        
        .recent-activity-widget {
            margin-bottom: 15px;
        }
        
        .recent-activity-widget h4 {
            margin: 0 0 10px 0;
            font-size: 13px;
            color: #23282d;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        .recent-list-widget {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .recent-item-widget {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        
        .recent-item-widget:last-child {
            border-bottom: none;
        }
        
        .recent-title-widget {
            font-size: 12px;
            font-weight: 500;
            color: #23282d;
            margin-bottom: 2px;
        }
        
        .recent-meta-widget {
            font-size: 11px;
            color: #666;
        }
        
        .widget-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .widget-actions .button {
            font-size: 11px;
            padding: 4px 8px;
            height: auto;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .emoji {
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;
            font-size: 12px;
        }
        
        /* Responsividade para telas menores */
        @media (max-width: 782px) {
            .stats-grid-widget {
                grid-template-columns: 1fr;
                gap: 8px;
            }
            
            .widget-actions {
                flex-direction: column;
            }
            
            .widget-actions .button {
                text-align: center;
                justify-content: center;
            }
        }
        </style>
        <?php
    }
    
    // Adicionar menu administrativo
    public function add_admin_menu() {
        add_menu_page(
            'Comunidade Q&A',
            'Comunidade Q&A',
            'manage_options',
            'comunidade-qa',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'comunidade-qa',
            'Personalizar Cores',
            'Personalizar Cores',
            'manage_options',
            'comunidade-cores',
            array($this, 'colors_admin_page')
        );
        
        add_submenu_page(
            'comunidade-qa',
            'Gerenciar Conteúdo',
            'Gerenciar Conteúdo',
            'manage_options',
            'comunidade-gerenciar',
            array($this, 'manage_admin_page')
        );
        
        add_submenu_page(
            'comunidade-qa',
            'Estatísticas',
            'Estatísticas',
            'manage_options',
            'comunidade-stats',
            array($this, 'stats_admin_page')
        );
    }
    
    // Inicializar configurações do admin
    public function admin_init() {
        register_setting('comunidade_colors', 'comunidade_colors');
        
        add_settings_section(
            'comunidade_colors_section',
            'Personalização de Cores',
            array($this, 'colors_section_callback'),
            'comunidade_colors'
        );
        
        // Cores principais
        $color_fields = array(
            'primary_color' => 'Cor Principal',
            'secondary_color' => 'Cor Secundária',
            'accent_color' => 'Cor de Destaque',
            'background_color' => 'Cor de Fundo',
            'card_background' => 'Fundo dos Cards',
            'text_color' => 'Cor do Texto',
            'link_color' => 'Cor dos Links',
            'border_color' => 'Cor das Bordas',
            'button_color' => 'Cor dos Botões',
            'button_hover' => 'Cor dos Botões (Hover)',
            'answer_bg' => 'Fundo das Respostas',
            'form_bg' => 'Fundo dos Formulários',
            'success_color' => 'Cor de Sucesso',
            'error_color' => 'Cor de Erro',
            'meta_text' => 'Cor do Texto Secundário'
        );
        
        foreach ($color_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'color_field_callback'),
                'comunidade_colors',
                'comunidade_colors_section',
                array('field' => $field, 'label' => $label)
            );
        }
    }
    
    // Callback da seção de cores
    public function colors_section_callback() {
        echo '<p>Personalize todas as cores da sua comunidade Q&A:</p>';
    }
    
    // Callback dos campos de cor
    public function color_field_callback($args) {
        $options = get_option('comunidade_colors');
        $defaults = $this->get_default_colors();
        $value = isset($options[$args['field']]) ? $options[$args['field']] : $defaults[$args['field']];
        
        echo '<input type="color" name="comunidade_colors[' . $args['field'] . ']" value="' . esc_attr($value) . '" />';
        echo '<input type="text" name="comunidade_colors[' . $args['field'] . ']" value="' . esc_attr($value) . '" style="margin-left: 10px; width: 80px;" />';
        echo '<button type="button" class="button reset-color" data-default="' . $defaults[$args['field']] . '" data-field="' . $args['field'] . '">Resetar</button>';
    }
    
    // Cores padrão
    private function get_default_colors() {
        return array(
            'primary_color' => '#3498db',
            'secondary_color' => '#2c3e50',
            'accent_color' => '#e74c3c',
            'background_color' => '#ffffff',
            'card_background' => '#f9f9f9',
            'text_color' => '#333333',
            'link_color' => '#3498db',
            'border_color' => '#dddddd',
            'button_color' => '#27ae60',
            'button_hover' => '#219a52',
            'answer_bg' => '#ffffff',
            'form_bg' => '#f0f8ff',
            'success_color' => '#27ae60',
            'error_color' => '#e74c3c',
            'meta_text' => '#666666'
        );
    }
    
    // Página principal do admin
    public function admin_page() {
        global $wpdb;
        
        // Estatísticas básicas
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE status = 'published'");
        $total_answers = $wpdb->get_var("SELECT COUNT(*) FROM $table_answers");
        $questions_today = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE DATE(created_at) = CURDATE()");
        
        ?>
        <div class="wrap">
            <h1>Comunidade Q&A - Painel Principal</h1>
            
            <div class="comunidade-admin-dashboard">
                <div class="comunidade-stats-cards">
                    <div class="stat-card">
                        <h3>Total de Perguntas</h3>
                        <span class="stat-number"><?php echo $total_questions; ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>Total de Respostas</h3>
                        <span class="stat-number"><?php echo $total_answers; ?></span>
                    </div>
                    <div class="stat-card">
                        <h3>Perguntas Hoje</h3>
                        <span class="stat-number"><?php echo $questions_today; ?></span>
                    </div>
                </div>
                
                <div class="comunidade-shortcodes">
                    <h2>Como Usar</h2>
                    <div class="shortcode-box">
                        <h3>Comunidade Completa (Recomendado)</h3>
                        <code>[comunidade_completa]</code>
                        <p>Use este shortcode para ter o formulário e feed integrados em uma única interface.</p>
                        <p><strong>Parâmetros:</strong> <code>per_page="10"</code> (número de perguntas por página)</p>
                    </div>
                    <div class="shortcode-box">
                        <h3>Formulário de Perguntas</h3>
                        <code>[comunidade_fazer_pergunta]</code>
                        <p>Use este shortcode na página onde os usuários poderão fazer perguntas.</p>
                    </div>
                    <div class="shortcode-box">
                        <h3>Feed de Perguntas</h3>
                        <code>[comunidade_feed]</code>
                        <p>Use este shortcode para exibir o feed de perguntas e respostas.</p>
                        <p><strong>Parâmetros:</strong> <code>per_page="10"</code> (número de perguntas por página)</p>
                    </div>
                </div>
                
                <div class="comunidade-admin-info">
                    <h2>Gerenciar Conteúdo</h2>
                    <p>Acesse o menu <strong>"Gerenciar Conteúdo"</strong> para visualizar, moderar e excluir perguntas e respostas da sua comunidade.</p>
                    <p>Você pode excluir conteúdo inadequado e moderar a participação dos usuários.</p>
                    
                    <h3>📊 Widget no Dashboard</h3>
                    <p>As estatísticas da comunidade agora aparecem automaticamente no painel principal do WordPress! Você pode ver um resumo das atividades diretamente na página inicial do admin.</p>
                </div>
            </div>
        </div>
        
        <style>
        .comunidade-admin-dashboard {
            margin-top: 20px;
        }
        .comunidade-stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #23282d;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #0073aa;
        }
        .shortcode-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .shortcode-box h3 {
            margin-top: 0;
        }
        .shortcode-box code {
            background: #f1f1f1;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 14px;
        }
        .comunidade-admin-info {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        .comunidade-admin-info h2 {
            margin-top: 0;
            color: #d63384;
        }
        .emoji {
            font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;
        }	
        </style>
        <?php
    }
    
    // Página de personalização de cores
    public function colors_admin_page() {
        ?>
        <div class="wrap">
            <h1>Personalizar Cores da Comunidade</h1>
            
            <div class="comunidade-colors-admin">
                <div class="colors-preview">
                    <h2>Pré-visualização</h2>
                    <div id="comunidade-preview">
                        <div class="preview-question">
                            <div class="preview-header">
                                <div class="preview-avatar"></div>
                                <div class="preview-user">
                                    <strong>João Silva</strong>
                                    <span class="preview-date">2 horas atrás</span>
                                </div>
                            </div>
                            <h4>Como criar um plugin WordPress?</h4>
                            <p>Gostaria de aprender a desenvolver plugins para WordPress...</p>
                            <div class="preview-stats">
                                <span>3 resposta(s)</span>
                                <button class="preview-button">Ver Respostas</button>
                            </div>
                        </div>
                        
                        <div class="preview-form">
                            <div class="main-input-row-preview">
                                <input type="text" placeholder="No que está pensando hoje?" readonly />
                                <button class="preview-submit">Publicar</button>
                            </div>
                            <div class="media-options-preview">
                                <span class="media-option-preview">📷 Imagem</span>
                                <span class="media-option-preview">🎥 Vídeo</span>
                            </div>
                        </div>
                        
                        <div class="preview-answer">
                            <div class="preview-header">
                                <div class="preview-avatar-small"></div>
                                <div class="preview-user">
                                    <strong>Maria Silva</strong>
                                    <span class="preview-date">1 hora atrás</span>
                                </div>
                            </div>
                            <p>Ótima pergunta! Você pode começar criando um arquivo PHP...</p>
                        </div>
                        
                        <div class="preview-answer-form">
                            <div class="form-group">
                                <input type="text" placeholder="Deixe sua resposta..." readonly />
                            </div>
                            <span class="emoji-btn-preview">😊</span>
                            <button class="preview-button">Responder</button>
                        </div>
                    </div>
                </div>
                
                <div class="colors-form">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('comunidade_colors');
                        do_settings_sections('comunidade_colors');
                        submit_button('Salvar Cores');
                        ?>
                        <button type="button" id="reset-all-colors" class="button">Resetar Todas as Cores</button>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
        .comunidade-colors-admin {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            margin-top: 20px;
        }
        
        .colors-preview {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .colors-form {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .colors-form table {
            width: 100%;
        }
        
        .colors-form th {
            text-align: left;
            padding: 10px 0;
            width: 50%;
        }
        
        .colors-form td {
            padding: 10px 0;
        }
        
        .reset-color {
            margin-left: 10px;
        }
        
        /* Preview Styles */
        #comunidade-preview {
            border: 2px dashed #ddd;
            padding: 20px;
            border-radius: 8px;
        }
        
        .preview-question {
            background: var(--card-bg, #f9f9f9);
            border: 1px solid var(--border-color, #ddd);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .preview-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .preview-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-color, #3498db);
            margin-right: 10px;
        }
        
        .preview-user strong {
            display: block;
            color: var(--text-color, #333);
        }
        
        .preview-date {
            font-size: 12px;
            color: var(--meta-text, #666);
        }
        
        .preview-question h4 {
            color: var(--secondary-color, #2c3e50);
            margin: 0 0 10px 0;
        }
        
        .preview-question p {
            color: var(--text-color, #333);
        }
        
        .preview-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color, #eee);
        }
        
        .preview-button {
            background: var(--primary-color, #3498db);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .preview-form {
            background: var(--form-bg, #f0f8ff);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .main-input-row-preview {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color, #e0e0e0);
        }
        
        .main-input-row-preview input {
            flex: 1;
            padding: 12px;
            border: 1px solid var(--border-color, #ddd);
            border-radius: 6px;
            background: var(--background-color, #fff);
            color: var(--text-color, #333);
            font-size: 14px;
        }
        
        .preview-submit {
            background: var(--button-color, #27ae60);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .preview-answer {
            background: var(--card-bg, #f9f9f9);
            border: 1px solid var(--border-color, #ddd);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .preview-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--accent-color, #e74c3c);
            margin-right: 10px;
        }
        
        .preview-answer-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 15px;
        }
        
        .preview-answer-form .form-group {
            flex: 1;
        }
        
        .preview-answer-form input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color, #ddd);
            border-radius: 4px;
            background: var(--background-color, #fff);
            color: var(--text-color, #333);
        }
        
        .media-options-preview {
            display: flex;
            gap: 12px;
        }
        
        .media-option-preview {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            border: 1px solid var(--border-color, #ddd);
            border-radius: 20px;
            background: var(--background-color, #fff);
            font-size: 13px;
            color: var(--text-color, #333);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .media-option-preview:hover {
            background: var(--primary-color, #3498db);
            color: white;
            border-color: var(--primary-color, #3498db);
        }
        
        .emoji-btn-preview {
            background: var(--primary-color, #3498db);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 50%;
            font-size: 14px;
            cursor: pointer;
        }
        
        @media (max-width: 1200px) {
            .comunidade-colors-admin {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const colorInputs = document.querySelectorAll('input[type="color"], input[type="text"][name*="comunidade_colors"]');
            const preview = document.getElementById('comunidade-preview');
            
            function updatePreview() {
                const colors = {};
                colorInputs.forEach(input => {
                    if (input.name.includes('comunidade_colors')) {
                        const fieldName = input.name.match(/\[(.*?)\]/)[1];
                        colors[fieldName] = input.value;
                    }
                });
                
                const cssVars = {
                    '--primary-color': colors.primary_color,
                    '--secondary-color': colors.secondary_color,
                    '--accent-color': colors.accent_color,
                    '--background-color': colors.background_color,
                    '--card-bg': colors.card_background,
                    '--text-color': colors.text_color,
                    '--link-color': colors.link_color,
                    '--border-color': colors.border_color,
                    '--button-color': colors.button_color,
                    '--button-hover': colors.button_hover,
                    '--answer-bg': colors.answer_bg,
                    '--form-bg': colors.form_bg,
                    '--success-color': colors.success_color,
                    '--error-color': colors.error_color,
                    '--meta-text': colors.meta_text
                };
                
                let cssString = '';
                for (const [prop, value] of Object.entries(cssVars)) {
                    if (value) {
                        cssString += prop + ': ' + value + '; ';
                    }
                }
                
                preview.style.cssText = cssString;
            }
            
            colorInputs.forEach(input => {
                input.addEventListener('input', updatePreview);
                input.addEventListener('change', updatePreview);
            });
            
            // Reset individual colors
            document.querySelectorAll('.reset-color').forEach(button => {
                button.addEventListener('click', function() {
                    const field = this.dataset.field;
                    const defaultColor = this.dataset.default;
                    const colorInput = document.querySelector('input[name="comunidade_colors[' + field + ']"][type="color"]');
                    const textInput = document.querySelector('input[name="comunidade_colors[' + field + ']"][type="text"]');
                    
                    if (colorInput) colorInput.value = defaultColor;
                    if (textInput) textInput.value = defaultColor;
                    updatePreview();
                });
            });
            
            // Reset all colors
            document.getElementById('reset-all-colors').addEventListener('click', function() {
                document.querySelectorAll('.reset-color').forEach(button => {
                    button.click();
                });
            });
            
            // Initial preview update
            updatePreview();
        });
        </script>
        <?php
    }
    
    // Página de gerenciamento de conteúdo
    public function manage_admin_page() {
        global $wpdb;
        
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        // Paginação
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Buscar perguntas com dados do usuário
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT q.*, u.display_name as user_name 
             FROM $table_questions q 
             LEFT JOIN {$wpdb->users} u ON q.user_id = u.ID 
             WHERE q.status = 'published' 
             ORDER BY q.created_at DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        // Total de perguntas para paginação
        $total_questions = $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE status = 'published'");
        $total_pages = ceil($total_questions / $per_page);
        
        ?>
        <div class="wrap">
            <h1>Gerenciar Conteúdo da Comunidade</h1>
            
            <div class="comunidade-manage-container">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <span class="displaying-num"><?php echo $total_questions; ?> itens</span>
                    </div>
                    <?php if ($total_pages > 1): ?>
                    <div class="tablenav-pages">
                        <span class="pagination-links">
                            <?php
                            $base_url = admin_url('admin.php?page=comunidade-gerenciar');
                            if ($current_page > 1) {
                                echo '<a class="prev-page button" href="' . $base_url . '&paged=' . ($current_page - 1) . '">‹</a>';
                            }
                            echo '<span class="paging-input">' . $current_page . ' de ' . $total_pages . '</span>';
                            if ($current_page < $total_pages) {
                                echo '<a class="next-page button" href="' . $base_url . '&paged=' . ($current_page + 1) . '">›</a>';
                            }
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 50px;">ID</th>
                            <th scope="col">Pergunta</th>
                            <th scope="col" style="width: 120px;">Autor</th>
                            <th scope="col" style="width: 80px;">Respostas</th>
                            <th scope="col" style="width: 120px;">Data</th>
                            <th scope="col" style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $question): 
                            // Contar respostas
                            $answer_count = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $table_answers WHERE question_id = %d",
                                $question->id
                            ));
                        ?>
                        <tr data-question-id="<?php echo $question->id; ?>">
                            <td><?php echo $question->id; ?></td>
                            <td>
                                <strong><?php echo esc_html($question->title); ?></strong>
                                <?php if (!empty($question->content)): ?>
                                <br><small><?php echo esc_html(wp_trim_words($question->content, 15)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($question->user_name ?: 'Usuário Removido'); ?></td>
                            <td>
                                <?php if ($answer_count > 0): ?>
                                <a href="#" class="toggle-answers-admin" data-question-id="<?php echo $question->id; ?>">
                                    <?php echo $answer_count; ?> resposta(s)
                                </a>
                                <?php else: ?>
                                0 respostas
                                <?php endif; ?>
                            </td>
                            <td><?php echo date_i18n('d/m/Y H:i', strtotime($question->created_at)); ?></td>
                            <td>
                                <button type="button" class="button button-small delete-question" 
                                        data-question-id="<?php echo $question->id; ?>">
                                    Excluir
                                </button>
                            </td>
                        </tr>
                        <?php if ($answer_count > 0): ?>
                        <tr class="answers-row" id="answers-row-<?php echo $question->id; ?>" style="display: none;">
                            <td colspan="6">
                                <div class="answers-container-admin">
                                    <?php echo $this->get_answers_for_admin($question->id); ?>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <style>
        .comunidade-manage-container {
            background: #fff;
            margin-top: 20px;
        }
        .answers-container-admin {
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #0073aa;
        }
        .answer-item-admin {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 10px;
        }
        .answer-item-admin .answer-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 12px;
            color: #666;
        }
        .answer-item-admin .answer-content {
            margin-bottom: 8px;
        }
        .delete-answer {
            font-size: 11px;
            padding: 2px 8px;
        }
        .toggle-answers-admin {
            text-decoration: none;
        }
        .toggle-answers-admin:hover {
            text-decoration: underline;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Toggle respostas no admin
            $('.toggle-answers-admin').on('click', function(e) {
                e.preventDefault();
                var questionId = $(this).data('question-id');
                $('#answers-row-' + questionId).toggle();
            });
            
            // Excluir pergunta
            $('.delete-question').on('click', function() {
                var questionId = $(this).data('question-id');
                var $row = $(this).closest('tr');
                
                if (confirm('Tem certeza que deseja excluir esta pergunta? Esta ação não pode ser desfeita.')) {
                    $.post(ajaxurl, {
                        action: 'delete_question',
                        question_id: questionId,
                        nonce: '<?php echo wp_create_nonce('comunidade_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $row.fadeOut(function() {
                                $(this).remove();
                            });
                            // Também remover linha de respostas se existir
                            $('#answers-row-' + questionId).remove();
                        } else {
                            alert('Erro: ' + response.data);
                        }
                    });
                }
            });
            
            // Excluir resposta
            $(document).on('click', '.delete-answer', function() {
                var answerId = $(this).data('answer-id');
                var $answerItem = $(this).closest('.answer-item-admin');
                
                if (confirm('Tem certeza que deseja excluir esta resposta?')) {
                    $.post(ajaxurl, {
                        action: 'delete_answer',
                        answer_id: answerId,
                        nonce: '<?php echo wp_create_nonce('comunidade_admin_nonce'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $answerItem.fadeOut(function() {
                                $(this).remove();
                            });
                        } else {
                            alert('Erro: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    // Página de estatísticas
    public function stats_admin_page() {
        global $wpdb;
        
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        // Estatísticas detalhadas
        $stats = array(
            'total_questions' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE status = 'published'"),
            'total_answers' => $wpdb->get_var("SELECT COUNT(*) FROM $table_answers"),
            'questions_today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE DATE(created_at) = CURDATE()"),
            'questions_week' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'questions_month' => $wpdb->get_var("SELECT COUNT(*) FROM $table_questions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
            'answers_today' => $wpdb->get_var("SELECT COUNT(*) FROM $table_answers WHERE DATE(created_at) = CURDATE()"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table_questions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")
        );
        
        // Top usuários
        $top_users = $wpdb->get_results("
            SELECT user_id, COUNT(*) as question_count 
            FROM $table_questions 
            WHERE status = 'published' 
            GROUP BY user_id 
            ORDER BY question_count DESC 
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1>Estatísticas da Comunidade</h1>
            
            <div class="comunidade-stats-page">
                <div class="stats-grid">
                    <div class="stat-box">
                        <h3>Total de Perguntas</h3>
                        <span class="big-number"><?php echo $stats['total_questions']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Total de Respostas</h3>
                        <span class="big-number"><?php echo $stats['total_answers']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Perguntas Hoje</h3>
                        <span class="big-number"><?php echo $stats['questions_today']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Perguntas (7 dias)</h3>
                        <span class="big-number"><?php echo $stats['questions_week']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Perguntas (30 dias)</h3>
                        <span class="big-number"><?php echo $stats['questions_month']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Respostas Hoje</h3>
                        <span class="big-number"><?php echo $stats['answers_today']; ?></span>
                    </div>
                    <div class="stat-box">
                        <h3>Usuários Ativos (30 dias)</h3>
                        <span class="big-number"><?php echo $stats['active_users']; ?></span>
                    </div>
                </div>
                
                <div class="top-users-section">
                    <h2>Top Usuários (Por Perguntas)</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Usuário</th>
                                <th>Perguntas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_users as $user_stat): 
                                $user = get_user_by('id', $user_stat->user_id);
                                $user_name = $user ? $user->display_name : 'Usuário Removido';
                            ?>
                            <tr>
                                <td><?php echo esc_html($user_name); ?></td>
                                <td><?php echo $user_stat->question_count; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <style>
        .comunidade-stats-page {
            margin-top: 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
        }
        .stat-box h3 {
            margin: 0 0 10px 0;
            color: #23282d;
            font-size: 14px;
        }
        .big-number {
            font-size: 36px;
            font-weight: bold;
            color: #0073aa;
        }
        .top-users-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        </style>
        <?php
    }
    
    // Criar tabelas no banco de dados
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela de perguntas
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $sql_questions = "CREATE TABLE $table_questions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            image_url varchar(500) DEFAULT NULL,
            youtube_url varchar(500) DEFAULT NULL,
            youtube_id varchar(50) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'published',
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabela de respostas
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        $sql_answers = "CREATE TABLE $table_answers (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            question_id mediumint(9) NOT NULL,
            user_id bigint(20) NOT NULL,
            content text NOT NULL,
            is_emoji_only tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_questions);
        dbDelta($sql_answers);
        
        // Configurar cores padrão na primeira instalação
        if (!get_option('comunidade_colors')) {
            add_option('comunidade_colors', $this->get_default_colors());
        }
    }
    
    // Carregar scripts e estilos
    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        
        wp_localize_script('jquery', 'comunidade_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comunidade_nonce'),
            'admin_nonce' => wp_create_nonce('comunidade_admin_nonce')
        ));
        
        // CSS dinâmico com cores personalizadas
        add_action('wp_head', array($this, 'dynamic_css'));
    }
    
    // CSS dinâmico com cores personalizadas
    public function dynamic_css() {
        $colors = get_option('comunidade_colors', $this->get_default_colors());
        ?>
        <style id="comunidade-dynamic-css">
        .comunidade-form-container, .comunidade-feed-container {
            max-width: 800px;
            margin: 20px 0;
        }
        
        .comunidade-completa-container {
            max-width: 800px;
            margin: 20px 0;
        }
        
        .comunidade-question, .comunidade-question-complete {
            background: <?php echo esc_attr($colors['card_background']); ?>;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .question-header, .answer-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-details strong {
            display: block;
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        .question-date, .answer-date {
            font-size: 12px;
            color: <?php echo esc_attr($colors['meta_text']); ?>;
        }
        
        .question-content h4 {
            margin: 0 0 10px 0;
            color: <?php echo esc_attr($colors['secondary_color']); ?>;
        }
        
        .question-content p {
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        .question-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
        }
        
        .question-stats span {
            color: <?php echo esc_attr($colors['meta_text']); ?>;
        }
        
        .toggle-answers {
            background: <?php echo esc_attr($colors['primary_color']); ?>;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .toggle-answers:hover {
            background: <?php echo esc_attr($colors['button_hover']); ?>;
        }
        
        .answers-container {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
        }
        
        .answer {
            background: <?php echo esc_attr($colors['answer_bg']); ?>;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 3px solid <?php echo esc_attr($colors['primary_color']); ?>;
        }
        
        .answer-content p {
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        .answer-form {
            margin-top: 20px;
            padding: 15px;
            background: <?php echo esc_attr($colors['form_bg']); ?>;
            border-radius: 6px;
        }
        
        .answer-form h5 {
            color: <?php echo esc_attr($colors['secondary_color']); ?>;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        .form-group input, .form-group textarea, .answer-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 4px;
            font-size: 14px;
            background: <?php echo esc_attr($colors['background_color']); ?>;
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        button[type="submit"] {
            background: <?php echo esc_attr($colors['button_color']); ?>;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        button[type="submit"]:hover {
            background: <?php echo esc_attr($colors['button_hover']); ?>;
        }
        
        .no-answers {
            color: <?php echo esc_attr($colors['meta_text']); ?>;
            font-style: italic;
        }
        
        #question-response {
            margin-top: 15px;
        }
        
        .success {
            color: <?php echo esc_attr($colors['success_color']); ?>;
            padding: 10px;
            background: rgba(<?php echo implode(',', sscanf($colors['success_color'], "#%02x%02x%02x")); ?>, 0.1);
            border: 1px solid <?php echo esc_attr($colors['success_color']); ?>;
            border-radius: 4px;
        }
        
        .error {
            color: <?php echo esc_attr($colors['error_color']); ?>;
            padding: 10px;
            background: rgba(<?php echo implode(',', sscanf($colors['error_color'], "#%02x%02x%02x")); ?>, 0.1);
            border: 1px solid <?php echo esc_attr($colors['error_color']); ?>;
            border-radius: 4px;
        }
        
        a {
            color: <?php echo esc_attr($colors['link_color']); ?>;
        }
        
        a:hover {
            color: <?php echo esc_attr($colors['accent_color']); ?>;
        }
        
        /* Estilos para comunidade completa */
        .answer-complete {
            background: <?php echo esc_attr($colors['card_background']); ?>;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .answer-form-complete {
            margin-top: 15px;
        }
        
        .answer-form-complete button {
            background: <?php echo esc_attr($colors['button_color']); ?>;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            white-space: nowrap;
        }
        
        .answer-form-complete button:hover {
            background: <?php echo esc_attr($colors['button_hover']); ?>;
        }
        
        .answers-section {
            margin-top: 15px;
        }
        
        .question-stats {
            margin-top: 10px;
            margin-bottom: 10px;
            color: <?php echo esc_attr($colors['meta_text']); ?>;
            font-size: 14px;
        }
        
        /* ===== LAYOUT AJUSTADO PARA FORMULÁRIO ===== */
        
        /* Container principal do formulário */
        .comunidade-form-container form,
        .comunidade-form-container #comunidade-question-form {
            background: <?php echo esc_attr($colors['form_bg']); ?>;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
        }
        
        /* Primeira seção - Input e Botão Publicar */
        .main-input-row {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid <?php echo esc_attr($colors['border_color']); ?>;
        }
        
        .main-input-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .main-input-row input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 8px;
            font-size: 16px;
            background: <?php echo esc_attr($colors['background_color']); ?>;
            color: <?php echo esc_attr($colors['text_color']); ?>;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .main-input-row input[type="text"]:focus {
            outline: none;
            border-color: <?php echo esc_attr($colors['primary_color']); ?>;
            box-shadow: 0 0 0 3px rgba(<?php echo implode(',', sscanf($colors['primary_color'], "#%02x%02x%02x")); ?>, 0.1);
        }
        
        .main-input-row button[type="submit"] {
            background: <?php echo esc_attr($colors['button_color']); ?>;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
            min-width: 100px;
        }
        
        .main-input-row button[type="submit"]:hover {
            background: <?php echo esc_attr($colors['button_hover']); ?>;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Segunda seção - Opções de Mídia */
        .media-options-row {
            display: flex;
            gap: 16px;
            justify-content: flex-start;
        }
        
        .media-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 10px 18px;
            border: 2px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 25px;
            background: <?php echo esc_attr($colors['background_color']); ?>;
            color: <?php echo esc_attr($colors['text_color']); ?>;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            user-select: none;
        }
        
        .media-option:hover {
            background: <?php echo esc_attr($colors['primary_color']); ?>;
            color: white;
            border-color: <?php echo esc_attr($colors['primary_color']); ?>;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(<?php echo implode(',', sscanf($colors['primary_color'], "#%02x%02x%02x")); ?>, 0.3);
        }
        
        .media-option.active {
            background: <?php echo esc_attr($colors['primary_color']); ?>;
            color: white;
            border-color: <?php echo esc_attr($colors['primary_color']); ?>;
            box-shadow: 0 2px 8px rgba(<?php echo implode(',', sscanf($colors['primary_color'], "#%02x%02x%02x")); ?>, 0.4);
        }
        
        .media-icon {
            font-size: 16px;
        }
        
        /* Estilos para emoji button */
        .emoji-btn {
            background: <?php echo esc_attr($colors['primary_color']); ?>;
            color: white;
            border: none;
            padding: 10px 12px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-left: 10px;
        }
        
        .emoji-btn:hover {
            background: <?php echo esc_attr($colors['button_hover']); ?>;
            transform: scale(1.1);
        }
        
        .emoji-response {
            background: <?php echo esc_attr($colors['card_background']); ?>;
            border-radius: 20px;
            display: inline-block;
            padding: 8px 15px;
            margin: 5px 0;
        }
        
        /* Layout para resposta */
        .answer-form-complete form {
            display: flex;
            gap: 10px;
            align-items: center;
            background: transparent;
            padding: 0;
            border: none;
        }
        
        .answer-form-complete .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .answer-form-complete input[type="text"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid <?php echo esc_attr($colors['border_color']); ?>;
            border-radius: 6px;
            font-size: 14px;
            background: <?php echo esc_attr($colors['background_color']); ?>;
            color: <?php echo esc_attr($colors['text_color']); ?>;
        }
        
        /* Responsividade */
        @media (max-width: 600px) {
            .main-input-row {
                flex-direction: column;
                gap: 15px;
                align-items: stretch;
            }
            
            .main-input-row button[type="submit"] {
                min-width: auto;
                width: 100%;
            }
            
            .media-options-row {
                flex-wrap: wrap;
                gap: 12px;
            }
            
            .media-option {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }
        }
        </style>
        <?php
    }
    
    // Shortcode para formulário de pergunta
    public function shortcode_form_pergunta($atts) {
        if (!is_user_logged_in()) {
            return '<p>Você precisa estar logado para fazer uma pergunta. <a href="' . wp_login_url() . '">Fazer login</a></p>';
        }
        
        ob_start();
        ?>
        <div class="comunidade-form-container">
            <form id="comunidade-question-form" enctype="multipart/form-data">
                <div class="main-input-row">
                    <div class="form-group">
                        <input type="text" id="question-title" name="title" placeholder="No que está pensando hoje?" required maxlength="255">
                    </div>
                    <button type="submit">Publicar</button>
                </div>
                <div class="media-options-row">
                    <label class="media-option">
                        <input type="file" name="question_image" accept="image/*" style="display: none;">
                        <span class="media-icon">📷</span>
                        Imagem
                    </label>
                    <label class="media-option" id="youtube-option">
                        <span class="media-icon">🎥</span>
                        Vídeo YouTube
                    </label>
                </div>
            </form>
            <div id="question-response"></div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Shortcode para feed de perguntas
    public function shortcode_feed($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
        ), $atts);
        
        ob_start();
        ?>
        <div class="comunidade-feed-container">
            <div id="comunidade-feed">
                <?php echo $this->get_questions_html(0, $atts['per_page']); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Shortcode para comunidade completa (formulário + feed)
    public function shortcode_comunidade_completa($atts) {
        $atts = shortcode_atts(array(
            'per_page' => 10,
        ), $atts);
        
        if (!is_user_logged_in()) {
            $login_message = '<p>Você precisa estar logado para participar da comunidade. <a href="' . wp_login_url() . '">Fazer login</a></p>';
        } else {
            $login_message = '';
        }
        
        ob_start();
        ?>
        <div class="comunidade-completa-container">
            <?php if (is_user_logged_in()): ?>
            <div class="comunidade-form-container">
                <form id="comunidade-question-form" enctype="multipart/form-data">
                    <div class="main-input-row">
                        <div class="form-group">
                            <input type="text" id="question-title" name="title" placeholder="No que está pensando hoje?" required maxlength="255">
                        </div>
                        <button type="submit">Publicar</button>
                    </div>
                    <div class="media-options-row">
                        <label class="media-option">
                            <input type="file" name="question_image" accept="image/*" style="display: none;">
                            <span class="media-icon">📷</span>
                            Imagem
                        </label>
                        <label class="media-option" id="youtube-option">
                            <span class="media-icon">🎥</span>
                            Vídeo YouTube
                        </label>
                    </div>
                </form>
                <div id="question-response"></div>
            </div>
            <?php else: ?>
                <?php echo $login_message; ?>
            <?php endif; ?>
            
            <div class="comunidade-feed-container">
                <div id="comunidade-feed">
                    <?php echo $this->get_questions_html_complete(0, $atts['per_page']); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Gerar HTML das perguntas
    private function get_questions_html($offset = 0, $per_page = 10) {
        global $wpdb;
        
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_questions 
             WHERE status = 'published' 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        if (empty($questions)) {
            return '<p>Nenhuma pergunta encontrada.</p>';
        }
        
        $html = '';
        foreach ($questions as $question) {
            $user = get_user_by('id', $question->user_id);
            $avatar = get_avatar($question->user_id, 50);
            $user_name = $user ? $user->display_name : 'Usuário Desconhecido';
            
            // Contar respostas
            $answer_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_answers WHERE question_id = %d",
                $question->id
            ));
            
            $html .= '<div class="comunidade-question" data-question-id="' . $question->id . '">';
            $html .= '<div class="question-header">';
            $html .= '<div class="user-info">';
            $html .= $avatar;
            $html .= '<div class="user-details">';
            $html .= '<strong>' . esc_html($user_name) . '</strong>';
            $html .= '<span class="question-date">' . human_time_diff(strtotime($question->created_at)) . ' atrás</span>';
            $html .= '</div></div></div>';
            
            $html .= '<div class="question-content">';
            $html .= '<h4>' . esc_html($question->title) . '</h4>';
            if (!empty($question->content)) {
                $html .= '<p>' . wp_kses_post(nl2br($question->content)) . '</p>';
            }
            
            // Exibir imagem se existir
            if (!empty($question->image_url)) {
                $html .= '<div class="question-image">';
                $html .= '<img src="' . esc_url($question->image_url) . '" alt="Imagem da pergunta" style="max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0;">';
                $html .= '</div>';
            }
            
            // Exibir vídeo do YouTube se existir
            if (!empty($question->youtube_id)) {
                $html .= '<div class="question-youtube">';
                $html .= '<div class="youtube-embed" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 10px 0;">';
                $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($question->youtube_id) . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            $html .= '<div class="question-stats">';
            $html .= '<span>' . $answer_count . ' resposta(s)</span>';
            $html .= '<button class="toggle-answers" data-question-id="' . $question->id . '">Ver Respostas</button>';
            $html .= '</div>';
            
            // Container para respostas
            $html .= '<div class="answers-container" id="answers-' . $question->id . '" style="display:none;">';
            $html .= $this->get_answers_html($question->id);
            
            // Formulário para nova resposta (apenas para usuários logados)
            if (is_user_logged_in()) {
                $html .= '<div class="answer-form">';
                $html .= '<h5>Sua Resposta:</h5>';
                $html .= '<form class="submit-answer-form" data-question-id="' . $question->id . '">';
                $html .= '<textarea name="answer_content" rows="3" placeholder="Digite sua resposta..." required></textarea>';
                $html .= '<button type="submit">Responder</button>';
                $html .= '</form>';
                $html .= '</div>';
            } else {
                $html .= '<p><a href="' . wp_login_url() . '">Faça login</a> para responder.</p>';
            }
            
            $html .= '</div></div>';
        }
        
        return $html;
    }
    
    // Gerar HTML das perguntas para comunidade completa (sempre com respostas visíveis)
    private function get_questions_html_complete($offset = 0, $per_page = 10) {
        global $wpdb;
        
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $questions = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_questions 
             WHERE status = 'published' 
             ORDER BY created_at DESC 
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        
        if (empty($questions)) {
            return '<p>Nenhuma pergunta encontrada.</p>';
        }
        
        $html = '';
        foreach ($questions as $question) {
            $user = get_user_by('id', $question->user_id);
            $avatar = get_avatar($question->user_id, 50);
            $user_name = $user ? $user->display_name : 'Usuário Desconhecido';
            
            // Contar respostas
            $answer_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_answers WHERE question_id = %d",
                $question->id
            ));
            
            $html .= '<div class="comunidade-question-complete" data-question-id="' . $question->id . '">';
            $html .= '<div class="question-header">';
            $html .= '<div class="user-info">';
            $html .= $avatar;
            $html .= '<div class="user-details">';
            $html .= '<strong>' . esc_html($user_name) . '</strong>';
            $html .= '<span class="question-date">' . human_time_diff(strtotime($question->created_at)) . ' atrás</span>';
            $html .= '</div></div></div>';
            
            $html .= '<div class="question-content">';
            $html .= '<h4>' . esc_html($question->title) . '</h4>';
            if (!empty($question->content)) {
                $html .= '<p>' . wp_kses_post(nl2br($question->content)) . '</p>';
            }
            
            // Exibir imagem se existir
            if (!empty($question->image_url)) {
                $html .= '<div class="question-image">';
                $html .= '<img src="' . esc_url($question->image_url) . '" alt="Imagem da pergunta" style="max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0;">';
                $html .= '</div>';
            }
            
            // Exibir vídeo do YouTube se existir
            if (!empty($question->youtube_id)) {
                $html .= '<div class="question-youtube">';
                $html .= '<div class="youtube-embed" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 10px 0;">';
                $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($question->youtube_id) . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>';
                $html .= '</div>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            
            if ($answer_count > 0) {
                $html .= '<div class="question-stats">';
                $html .= '<span>' . $answer_count . ' resposta(s)</span>';
                $html .= '</div>';
            }
            
            // Respostas sempre visíveis
            $html .= '<div class="answers-section">';
            $html .= $this->get_answers_html_complete($question->id);
            $html .= '</div>';
            
            // Formulário para nova resposta sempre visível (apenas para usuários logados)
            if (is_user_logged_in()) {
                $html .= '<div class="answer-form-complete">';
                $html .= '<form class="submit-answer-form" data-question-id="' . $question->id . '">';
                $html .= '<div class="form-group">';
                $html .= '<input type="text" name="answer_content" placeholder="Deixe sua resposta..." required>';
                $html .= '</div>';
                $html .= '<button type="button" class="emoji-btn" title="Adicionar emoji">😊</button>';
                $html .= '<button type="submit">Responder</button>';
                $html .= '</form>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
        }
        
        return $html;
    }
    
    // Gerar HTML das respostas
    private function get_answers_html($question_id) {
        global $wpdb;
        
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_answers 
             WHERE question_id = %d 
             ORDER BY created_at ASC",
            $question_id
        ));
        
        if (empty($answers)) {
            return '<p class="no-answers">Nenhuma resposta ainda.</p>';
        }
        
        $html = '<div class="answers-list">';
        foreach ($answers as $answer) {
            $user = get_user_by('id', $answer->user_id);
            $avatar = get_avatar($answer->user_id, 40);
            $user_name = $user ? $user->display_name : 'Usuário Desconhecido';
            
            $html .= '<div class="answer">';
            $html .= '<div class="answer-header">';
            $html .= '<div class="user-info">';
            $html .= $avatar;
            $html .= '<div class="user-details">';
            $html .= '<strong>' . esc_html($user_name) . '</strong>';
            $html .= '<span class="answer-date">' . human_time_diff(strtotime($answer->created_at)) . ' atrás</span>';
            $html .= '</div></div></div>';
            $html .= '<div class="answer-content">';
            if ($answer->is_emoji_only) {
                $html .= '<div class="emoji-response" style="font-size: 24px; text-align: center; padding: 5px;">' . wp_kses_post($answer->content) . '</div>';
            } else {
                $html .= '<p>' . wp_kses_post(nl2br($answer->content)) . '</p>';
            }
            $html .= '</div></div>';
        }
        $html .= '</div>';
        
        return $html;
    }
    
    // Gerar HTML das respostas para comunidade completa
    private function get_answers_html_complete($question_id) {
        global $wpdb;
        
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_answers 
             WHERE question_id = %d 
             ORDER BY created_at ASC",
            $question_id
        ));
        
        if (empty($answers)) {
            return '';
        }
        
        $html = '';
        foreach ($answers as $answer) {
            $user = get_user_by('id', $answer->user_id);
            $avatar = get_avatar($answer->user_id, 40);
            $user_name = $user ? $user->display_name : 'Usuário Desconhecido';
            
            $html .= '<div class="answer-complete">';
            $html .= '<div class="answer-header">';
            $html .= '<div class="user-info">';
            $html .= $avatar;
            $html .= '<div class="user-details">';
            $html .= '<strong>' . esc_html($user_name) . '</strong>';
            $html .= '<span class="answer-date">' . human_time_diff(strtotime($answer->created_at)) . ' atrás</span>';
            $html .= '</div></div></div>';
            $html .= '<div class="answer-content">';
            if ($answer->is_emoji_only) {
                $html .= '<div class="emoji-response" style="font-size: 24px; text-align: center; padding: 5px;">' . wp_kses_post($answer->content) . '</div>';
            } else {
                $html .= '<p>' . wp_kses_post(nl2br($answer->content)) . '</p>';
            }
            $html .= '</div></div>';
        }
        
        return $html;
    }
    
    // Gerar HTML das respostas para o painel administrativo
    private function get_answers_for_admin($question_id) {
        global $wpdb;
        
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $answers = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as user_name 
             FROM $table_answers a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             WHERE a.question_id = %d 
             ORDER BY a.created_at ASC",
            $question_id
        ));
        
        if (empty($answers)) {
            return '<p>Nenhuma resposta encontrada.</p>';
        }
        
        $html = '';
        foreach ($answers as $answer) {
            $user_name = $answer->user_name ?: 'Usuário Removido';
            
            $html .= '<div class="answer-item-admin">';
            $html .= '<div class="answer-meta">';
            $html .= '<span><strong>' . esc_html($user_name) . '</strong> - ' . date_i18n('d/m/Y H:i', strtotime($answer->created_at)) . '</span>';
            $html .= '<button type="button" class="button button-small delete-answer" data-answer-id="' . $answer->id . '">Excluir</button>';
            $html .= '</div>';
            $html .= '<div class="answer-content">' . wp_kses_post(nl2br($answer->content)) . '</div>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    // Gerar HTML de uma única pergunta para comunidade completa
    private function generate_single_question_html_complete($question) {
        global $wpdb;
        
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $user = get_user_by('id', $question->user_id);
        $avatar = get_avatar($question->user_id, 50);
        $user_name = $user ? $user->display_name : 'Usuário Desconhecido';
        
        // Contar respostas
        $answer_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_answers WHERE question_id = %d",
            $question->id
        ));
        
        $html = '<div class="comunidade-question-complete" data-question-id="' . $question->id . '">';
        $html .= '<div class="question-header">';
        $html .= '<div class="user-info">';
        $html .= $avatar;
        $html .= '<div class="user-details">';
        $html .= '<strong>' . esc_html($user_name) . '</strong>';
        $html .= '<span class="question-date">' . human_time_diff(strtotime($question->created_at)) . ' atrás</span>';
        $html .= '</div></div></div>';
        
        $html .= '<div class="question-content">';
        $html .= '<h4>' . esc_html($question->title) . '</h4>';
        if (!empty($question->content)) {
            $html .= '<p>' . wp_kses_post(nl2br($question->content)) . '</p>';
        }
        
        // Exibir imagem se existir
        if (!empty($question->image_url)) {
            $html .= '<div class="question-image">';
            $html .= '<img src="' . esc_url($question->image_url) . '" alt="Imagem da pergunta" style="max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0;">';
            $html .= '</div>';
        }
        
        // Exibir vídeo do YouTube se existir
        if (!empty($question->youtube_id)) {
            $html .= '<div class="question-youtube">';
            $html .= '<div class="youtube-embed" style="position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; margin: 10px 0;">';
            $html .= '<iframe src="https://www.youtube.com/embed/' . esc_attr($question->youtube_id) . '" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;" frameborder="0" allowfullscreen></iframe>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        if ($answer_count > 0) {
            $html .= '<div class="question-stats">';
            $html .= '<span>' . $answer_count . ' resposta(s)</span>';
            $html .= '</div>';
        }
        
        // Respostas sempre visíveis
        $html .= '<div class="answers-section">';
        $html .= $this->get_answers_html_complete($question->id);
        $html .= '</div>';
        
        // Formulário para nova resposta sempre visível (apenas para usuários logados)
        if (is_user_logged_in()) {
            $html .= '<div class="answer-form-complete">';
            $html .= '<form class="submit-answer-form" data-question-id="' . $question->id . '">';
            $html .= '<div class="form-group">';
            $html .= '<input type="text" name="answer_content" placeholder="Deixe sua resposta..." required>';
            $html .= '</div>';
            $html .= '<button type="button" class="emoji-btn" title="Adicionar emoji">😊</button>';
            $html .= '<button type="submit">Responder</button>';
            $html .= '</form>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    // Processar submissão de pergunta
    public function handle_submit_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'comunidade_nonce')) {
            wp_die('Nonce inválido');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado');
        }
        
        $title = sanitize_text_field($_POST['title']);
        
        if (empty($title)) {
            wp_send_json_error('Título é obrigatório');
        }
        
        // Processar imagem se enviada
        $image_url = '';
        if (isset($_FILES['question_image']) && $_FILES['question_image']['error'] === UPLOAD_ERR_OK) {
            $image_result = $this->process_image_upload($_FILES['question_image']);
            if ($image_result['success']) {
                $image_url = $image_result['url'];
            } else {
                wp_send_json_error($image_result['error']);
            }
        }
        
        // Processar YouTube se enviado
        $youtube_url = '';
        $youtube_id = '';
        if (!empty($_POST['youtube_url'])) {
            $youtube_url = sanitize_url($_POST['youtube_url']);
            $youtube_id = $this->extract_youtube_id($youtube_url);
            if (!$youtube_id) {
                wp_send_json_error('Link do YouTube inválido');
            }
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        
        $result = $wpdb->insert(
            $table_questions,
            array(
                'user_id' => get_current_user_id(),
                'title' => $title,
                'content' => '',
                'image_url' => $image_url,
                'youtube_url' => $youtube_url,
                'youtube_id' => $youtube_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        if ($result) {
            $question_id = $wpdb->insert_id;
            
            // Buscar a pergunta recém-criada para retornar o HTML
            $new_question = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_questions WHERE id = %d",
                $question_id
            ));
            
            // Gerar HTML da nova pergunta
            $new_question_html = $this->generate_single_question_html_complete($new_question);
            
            wp_send_json_success(array(
                'message' => 'Pergunta publicada com sucesso!',
                'html' => $new_question_html
            ));
        } else {
            wp_send_json_error('Erro ao publicar pergunta');
        }
    }
    
    // Processar submissão de resposta
    public function handle_submit_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'comunidade_nonce')) {
            wp_die('Nonce inválido');
        }
        
        if (!is_user_logged_in()) {
            wp_send_json_error('Você precisa estar logado');
        }
        
        $question_id = intval($_POST['question_id']);
        $content = sanitize_textarea_field($_POST['content']);
        
        if (empty($content)) {
            wp_send_json_error('Conteúdo da resposta é obrigatório');
        }
        
        // Verificar se é apenas emoji
        $is_emoji_only = $this->is_emoji_only($content);
        
        global $wpdb;
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $result = $wpdb->insert(
            $table_answers,
            array(
                'question_id' => $question_id,
                'user_id' => get_current_user_id(),
                'content' => $content,
                'is_emoji_only' => $is_emoji_only ? 1 : 0,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%d', '%s')
        );
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Resposta enviada com sucesso!',
                'html' => $this->get_answers_html_complete($question_id)
            ));
        } else {
            wp_send_json_error('Erro ao enviar resposta');
        }
    }
    
    // Processar exclusão de pergunta
    public function handle_delete_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'comunidade_admin_nonce')) {
            wp_die('Nonce inválido');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para fazer isso');
        }
        
        $question_id = intval($_POST['question_id']);
        
        if (empty($question_id)) {
            wp_send_json_error('ID da pergunta é obrigatório');
        }
        
        global $wpdb;
        $table_questions = $wpdb->prefix . 'comunidade_questions';
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        // Primeiro, excluir todas as respostas relacionadas
        $wpdb->delete(
            $table_answers,
            array('question_id' => $question_id),
            array('%d')
        );
        
        // Depois, excluir a pergunta
        $result = $wpdb->delete(
            $table_questions,
            array('id' => $question_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success('Pergunta excluída com sucesso');
        } else {
            wp_send_json_error('Erro ao excluir pergunta');
        }
    }
    
    // Processar exclusão de resposta
    public function handle_delete_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'comunidade_admin_nonce')) {
            wp_die('Nonce inválido');
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Você não tem permissão para fazer isso');
        }
        
        $answer_id = intval($_POST['answer_id']);
        
        if (empty($answer_id)) {
            wp_send_json_error('ID da resposta é obrigatório');
        }
        
        global $wpdb;
        $table_answers = $wpdb->prefix . 'comunidade_answers';
        
        $result = $wpdb->delete(
            $table_answers,
            array('id' => $answer_id),
            array('%d')
        );
        
        if ($result) {
            wp_send_json_success('Resposta excluída com sucesso');
        } else {
            wp_send_json_error('Erro ao excluir resposta');
        }
    }
    
    // Processar upload de imagem
    private function process_image_upload($file) {
        // Verificar tamanho (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            return array('success' => false, 'error' => 'Imagem muito grande. Máximo 2MB.');
        }
        
        // Verificar tipo de arquivo
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        if (!in_array($file['type'], $allowed_types)) {
            return array('success' => false, 'error' => 'Tipo de arquivo não permitido. Use JPG, PNG, GIF ou WebP.');
        }
        
        // Upload usando WordPress
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return array('success' => true, 'url' => $movefile['url']);
        } else {
            return array('success' => false, 'error' => 'Erro no upload: ' . $movefile['error']);
        }
    }
    
    // Extrair ID do vídeo do YouTube
    private function extract_youtube_id($url) {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        preg_match($pattern, $url, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }
    
    // Verificar se o texto contém apenas emojis
    private function is_emoji_only($text) {
        // Remove espaços em branco
        $text = trim($text);
        
        // Verifica se o texto tem menos de 20 caracteres e contém emojis
        if (strlen($text) > 50) {
            return false;
        }
        
        // Regex para detectar emojis
        $emoji_regex = '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u';
        
        // Remove todos os emojis e verifica se sobra algum texto significativo
        $text_without_emojis = preg_replace($emoji_regex, '', $text);
        $text_without_emojis = trim($text_without_emojis);
        
        // Se depois de remover emojis restam apenas espaços ou poucos caracteres, é emoji-only
        return strlen($text_without_emojis) <= 3;
    }
    
    // Função para upload de imagem (compatibilidade)
    public function handle_upload_image() {
        // Esta função é mantida para compatibilidade com chamadas AJAX antigas
        wp_send_json_error('Método de upload obsoleto');
    }
}

// Inicializar o plugin
new ComunidadeQA();

// JavaScript inline
function comunidade_add_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Variáveis globais para arquivos
        var selectedImage = null;
        var selectedYouTube = '';
        
        // Criar modal de emoji uma única vez
        var emojiModal = $('<div id="emoji-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000;"><div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1a1a1a; padding: 20px; border-radius: 10px; max-width: 400px; max-height: 60vh; overflow-y: auto;"><div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; font-family: "Apple Color Emoji", "Segoe UI Emoji", "Noto Color Emoji", sans-serif;"><h3 style="margin: 0;">Escolha um emoji</h3><button id="close-emoji-modal" style="background: none; border: none; font-size: 20px; cursor: pointer;">✖️</button></div><div id="emoji-grid-modal" style="display: grid; grid-template-columns: repeat(8, 1fr); gap: 5px;"></div></div></div>');
        $('body').append(emojiModal);
        
        // Emojis populares
        var emojis = '😂 🙃 😉 😊 🥰 😍 🤩 😘 😋 😜 🤭 🤔 🤨 🙄 🙂‍↔️ 🙂‍↕️ 🤤 🥶 😎 🤓 🙁 😮 🥺 😨 😭 😱 🤬 💀 ☠️ 💩 🤡 💔 ❤️‍🔥 ❤️‍🩹 ❤️ 🧡 💛 💚 💙 💜 🤍 💬 💭 👉 👇 ☝️ 👍 🙌 🤝 🙏 👀 🏋️‍♀️ 🦋 🚨 🚀 ☀️ ⭐ 🔥 ✨ 🎀 🧸 🎭 👑 💍 🎶 💸 📈 📍 ⚖️ 🧿 🗿 ⚠️ 🔞 ⬆️ ➡️ ⬇️ ☯️ ➗ ♾️ ❓ ❗ ⚕️ ✅ ✔️ ❌ ⚫ ⬜ 🇧🇷 🇵🇹';
        
        // Popular o grid de emojis
        function populateEmojiGrid() {
            var $grid = $('#emoji-grid-modal');
            $grid.empty();
            
            for (var i = 0; i < emojis.length; i++) {
                var emoji = emojis.charAt(i);
                $grid.append('<span style="font-size: 24px; cursor: pointer; padding: 8px; border-radius: 4px; text-align: center; transition: background-color 0.2s;" data-emoji="' + emoji + '">' + emoji + '</span>');
            }
        }
        
        populateEmojiGrid();
        
        // Variável para armazenar o input ativo
        var activeInput = null;
        
        // Click handler para opções de mídia
        $('.media-option').on('click', function() {
            var $this = $(this);
            var $input = $this.find('input[type="file"]');
            
            if ($input.length) {
                // Trigger file input para imagem
                $input.click();
            } else if ($this.attr('id') === 'youtube-option') {
                // Show YouTube input
                var url = prompt('Cole o link do YouTube:');
                if (url && url.trim()) {
                    processYouTubeURL(url.trim());
                }
            }
        });
        
        // Preview de imagem
        $('input[name="question_image"]').on('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                // Verificar tamanho
                if (file.size > 2 * 1024 * 1024) {
                    alert('Arquivo muito grande. Máximo 2MB.');
                    $(this).val('');
                    return;
                }
                
                selectedImage = file;
                $('.media-option:has(input[type="file"])').addClass('active').text('📷 Imagem selecionada');
            }
        });
        
        // Processar URL do YouTube
        function processYouTubeURL(url) {
            var videoId = extractYouTubeID(url);
            if (videoId) {
                selectedYouTube = url;
                $('#youtube-option').addClass('active').text('🎥 Vídeo selecionado');
            } else {
                alert('Link do YouTube inválido');
            }
        }
        
        // Extrair ID do YouTube
        function extractYouTubeID(url) {
            var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
            var match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : false;
        }
        
        // Abrir modal de emoji
        $(document).on('click', '.emoji-btn', function(e) {
            e.preventDefault();
            activeInput = $(this).siblings('.form-group').find('input[name="answer_content"]');
            $('#emoji-modal').show();
        });
        
        // Fechar modal de emoji
        $('#close-emoji-modal, #emoji-modal').on('click', function(e) {
            if (e.target === this) {
                $('#emoji-modal').hide();
                activeInput = null;
            }
        });
        
        // Selecionar emoji
        $(document).on('click', '#emoji-grid-modal span', function() {
            if (activeInput) {
                var emoji = $(this).data('emoji');
                var currentValue = activeInput.val();
                activeInput.val(currentValue + emoji);
                $('#emoji-modal').hide();
                activeInput = null;
            }
        });
        
        // Hover effect nos emojis
        $(document).on('mouseenter', '#emoji-grid-modal span', function() {
            $(this).css('background-color', '#f0f0f0');
        });
        
        $(document).on('mouseleave', '#emoji-grid-modal span', function() {
            $(this).css('background-color', 'transparent');
        });
        
        // Submeter pergunta
        $('#comunidade-question-form').on('submit', function(e) {
            e.preventDefault();
            
            var formData = new FormData();
            formData.append('action', 'submit_question');
            formData.append('title', $('#question-title').val());
            formData.append('nonce', comunidade_ajax.nonce);
            
            if (selectedImage) {
                formData.append('question_image', selectedImage);
            }
            
            if (selectedYouTube) {
                formData.append('youtube_url', selectedYouTube);
            }
            
            $.ajax({
                url: comunidade_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#question-response').html('<div class="success">' + response.data.message + '</div>');
                        $('#comunidade-question-form')[0].reset();
                        
                        // Reset media
                        selectedImage = null;
                        selectedYouTube = '';
                        $('.media-option').removeClass('active');
                        $('.media-option:first').html('<span class="media-icon">📷</span> Imagem');
                        $('#youtube-option').html('<span class="media-icon">🎥</span> Vídeo YouTube');
                        
                        // Adicionar nova pergunta no topo do feed
                        if ($('#comunidade-feed').length) {
                            $('#comunidade-feed').prepend(response.data.html);
                        }
                        
                        // Remover mensagem de sucesso após 3 segundos
                        setTimeout(function() {
                            $('#question-response').html('');
                        }, 3000);
                    } else {
                        $('#question-response').html('<div class="error">' + response.data + '</div>');
                    }
                }
            });
        });
        
        // Toggle respostas (para o formato antigo)
        $(document).on('click', '.toggle-answers', function() {
            var questionId = $(this).data('question-id');
            $('#answers-' + questionId).slideToggle();
        });
        
        // Submeter resposta
        $(document).on('submit', '.submit-answer-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var questionId = $form.data('question-id');
            var contentField = $form.find('input[name="answer_content"]');
            
            var formData = {
                action: 'submit_answer',
                question_id: questionId,
                content: contentField.val(),
                nonce: comunidade_ajax.nonce
            };
            
            $.post(comunidade_ajax.ajax_url, formData, function(response) {
                if (response.success) {
                    // Para comunidade completa, atualizar a seção de respostas
                    var $answersSection = $form.closest('.comunidade-question-complete').find('.answers-section');
                    if ($answersSection.length) {
                        $answersSection.html(response.data.html);
                    } else {
                        // Para formato antigo
                        $('#answers-' + questionId + ' .answers-list').replaceWith(response.data.html);
                    }
                    $form[0].reset();
                } else {
                    alert('Erro: ' + response.data);
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'comunidade_add_inline_js');
?>