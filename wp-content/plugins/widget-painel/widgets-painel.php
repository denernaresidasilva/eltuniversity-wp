<?php
/**
 * Plugin Name: Painel de Widgets Personalizados
 * Description: Adiciona um painel administrativo para gerenciar widgets personalizados no dashboard do WordPress
 * Version: 1.0
 * Author: Seu Nome
 * Text Domain: painel-widgets
 */

// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

class PainelWidgetsPlugin {
    // Variável para armazenar instância (padrão singleton)
    private static $instance = null;
    
    // Construtor
    private function __construct() {
        // Inicializar hooks
        add_action('admin_menu', array($this, 'adicionar_menu_admin'));
        add_action('admin_enqueue_scripts', array($this, 'carregar_recursos'));
        add_action('wp_ajax_salvar_widget', array($this, 'ajax_salvar_widget'));
        add_action('wp_ajax_excluir_widget', array($this, 'ajax_excluir_widget'));
        add_action('wp_dashboard_setup', array($this, 'registrar_widgets_dashboard'));
    }
    
    // Método para obter instância (padrão singleton)
    public static function obter_instancia() {
        if (self::$instance == null) {
            self::$instance = new PainelWidgetsPlugin();
        }
        return self::$instance;
    }
    
    // Adicionar menu ao painel administrativo
    public function adicionar_menu_admin() {
        add_menu_page(
            __('Gerenciador de Widgets', 'painel-widgets'),
            __('Widgets Painel', 'painel-widgets'),
            'manage_options',
            'gerenciador-widgets',
            array($this, 'pagina_admin'),
            'dashicons-layout',
            65
        );
    }
    
    // Carregar CSS e JavaScript
    public function carregar_recursos($hook) {
        // Carregar apenas na página do plugin
        if ('toplevel_page_gerenciador-widgets' === $hook) {
            wp_enqueue_style('painel-widgets-admin', plugin_dir_url(__FILE__) . 'css/admin.css', array(), '1.0.0');
            wp_enqueue_script('painel-widgets-admin', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), '1.0.0', true);
            
            // Adicionar editor visual do WordPress
            wp_enqueue_editor();
            
            // Adicionar media uploader do WordPress
            wp_enqueue_media();
            
            // Localize script para usar AJAX
            wp_localize_script('painel-widgets-admin', 'painelWidgetsAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('painel_widgets_nonce')
            ));
        }
    }
    
    // Conteúdo da página de administração
    public function pagina_admin() {
        ?>
        <div class="wrap painel-widgets-admin">
            <h1><?php _e('Gerenciador de Widgets do Painel', 'painel-widgets'); ?></h1>
            
            <div class="nav-tab-wrapper">
                <a href="#lista-widgets" class="nav-tab nav-tab-active"><?php _e('Widgets', 'painel-widgets'); ?></a>
                <a href="#adicionar-widget" class="nav-tab"><?php _e('Adicionar Novo', 'painel-widgets'); ?></a>
            </div>
            
            <div id="lista-widgets" class="tab-content active">
                <h2><?php _e('Widgets Existentes', 'painel-widgets'); ?></h2>
                <?php $this->listar_widgets(); ?>
            </div>
            
            <div id="adicionar-widget" class="tab-content">
                <h2><?php _e('Adicionar Novo Widget', 'painel-widgets'); ?></h2>
                <?php $this->form_adicionar_widget(); ?>
            </div>
        </div>
        <?php
    }
    
    // Lista os widgets existentes
    private function listar_widgets() {
        $widgets = $this->obter_widgets_salvos();
        
        if (empty($widgets)) {
            echo '<p>' . __('Nenhum widget personalizado adicionado ainda.', 'painel-widgets') . '</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . __('Título', 'painel-widgets') . '</th>';
        echo '<th>' . __('Tipo', 'painel-widgets') . '</th>';
        echo '<th>' . __('Posição', 'painel-widgets') . '</th>';
        echo '<th>' . __('Ações', 'painel-widgets') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($widgets as $id => $widget) {
            echo '<tr>';
            echo '<td>' . esc_html($widget['titulo']) . '</td>';
            echo '<td>' . $this->obter_nome_tipo($widget['tipo']) . '</td>';
            echo '<td>' . $this->obter_nome_posicao($widget['posicao']) . '</td>';
            echo '<td>';
            echo '<a href="#" class="editar-widget" data-id="' . esc_attr($id) . '">' . __('Editar', 'painel-widgets') . '</a> | ';
            echo '<a href="#" class="excluir-widget" data-id="' . esc_attr($id) . '">' . __('Excluir', 'painel-widgets') . '</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    // Formulário para adicionar/editar widget
    private function form_adicionar_widget($widget_id = '', $widget = array()) {
        $is_edit = !empty($widget_id);
        $titulo = $is_edit ? $widget['titulo'] : '';
        $tipo = $is_edit ? $widget['tipo'] : 'texto';
        $conteudo = $is_edit ? $widget['conteudo'] : '';
        $posicao = $is_edit ? $widget['posicao'] : 'normal';
        $prioridade = $is_edit ? $widget['prioridade'] : 'default';
        
        ?>
        <form id="form-widget" class="form-widget">
            <?php if ($is_edit): ?>
                <input type="hidden" name="widget_id" value="<?php echo esc_attr($widget_id); ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="titulo"><?php _e('Título do Widget:', 'painel-widgets'); ?></label>
                <input type="text" id="titulo" name="titulo" value="<?php echo esc_attr($titulo); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="tipo"><?php _e('Tipo de Conteúdo:', 'painel-widgets'); ?></label>
                <select id="tipo" name="tipo">
                    <option value="texto" <?php selected($tipo, 'texto'); ?>><?php _e('Texto/HTML', 'painel-widgets'); ?></option>
                    <option value="editor" <?php selected($tipo, 'editor'); ?>><?php _e('Editor Visual', 'painel-widgets'); ?></option>
                    <option value="youtube" <?php selected($tipo, 'youtube'); ?>><?php _e('Vídeo do YouTube', 'painel-widgets'); ?></option>
                    <option value="imagem" <?php selected($tipo, 'imagem'); ?>><?php _e('Imagem', 'painel-widgets'); ?></option>
                    <option value="links" <?php selected($tipo, 'links'); ?>><?php _e('Lista de Links', 'painel-widgets'); ?></option>
                    <option value="rss" <?php selected($tipo, 'rss'); ?>><?php _e('Feed RSS', 'painel-widgets'); ?></option>
                </select>
            </div>
            
            <div class="conteudo-container">
                <!-- Área de conteúdo dinâmica baseada no tipo selecionado -->
                <div class="tipo-conteudo tipo-texto" <?php echo $tipo == 'texto' ? '' : 'style="display:none;"'; ?>>
                    <label for="conteudo-texto"><?php _e('Conteúdo HTML:', 'painel-widgets'); ?></label>
                    <textarea id="conteudo-texto" name="conteudo[texto]" rows="8"><?php echo esc_textarea($tipo == 'texto' ? $conteudo : ''); ?></textarea>
                </div>
                
                <div class="tipo-conteudo tipo-editor" <?php echo $tipo == 'editor' ? '' : 'style="display:none;"'; ?>>
                    <label for="conteudo-editor"><?php _e('Conteúdo (Editor Visual):', 'painel-widgets'); ?></label>
                    <?php 
                    $editor_content = $tipo == 'editor' ? $conteudo : '';
                    wp_editor($editor_content, 'conteudo-editor', array(
                        'textarea_name' => 'conteudo[editor]',
                        'media_buttons' => true,
                        'textarea_rows' => 10,
                        'teeny' => false
                    )); 
                    ?>
                </div>
                
                <div class="tipo-conteudo tipo-youtube" <?php echo $tipo == 'youtube' ? '' : 'style="display:none;"'; ?>>
                    <label for="conteudo-youtube"><?php _e('URL ou ID do Vídeo do YouTube:', 'painel-widgets'); ?></label>
                    <input type="text" id="conteudo-youtube" name="conteudo[youtube]" value="<?php echo esc_attr($tipo == 'youtube' ? $conteudo : ''); ?>">
                    <p class="description"><?php _e('Insira a URL do vídeo ou apenas o ID (Ex: https://www.youtube.com/watch?v=XXXXXXXXXXX ou XXXXXXXXXXX)', 'painel-widgets'); ?></p>
                </div>
                
                <div class="tipo-conteudo tipo-imagem" <?php echo $tipo == 'imagem' ? '' : 'style="display:none;"'; ?>>
                    <label><?php _e('Imagem:', 'painel-widgets'); ?></label>
                    <div class="imagem-preview-container">
                        <?php if ($tipo == 'imagem' && !empty($conteudo)): ?>
                            <img src="<?php echo esc_url($conteudo); ?>" alt="" class="imagem-preview">
                        <?php else: ?>
                            <div class="sem-imagem"><?php _e('Nenhuma imagem selecionada', 'painel-widgets'); ?></div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="conteudo-imagem" name="conteudo[imagem]" value="<?php echo esc_attr($tipo == 'imagem' ? $conteudo : ''); ?>">
                    <button type="button" class="button upload-imagem"><?php _e('Selecionar Imagem', 'painel-widgets'); ?></button>
                    <button type="button" class="button remover-imagem" <?php echo ($tipo == 'imagem' && !empty($conteudo)) ? '' : 'style="display:none;"'; ?>><?php _e('Remover Imagem', 'painel-widgets'); ?></button>
                </div>
                
                <div class="tipo-conteudo tipo-links" <?php echo $tipo == 'links' ? '' : 'style="display:none;"'; ?>>
                    <label><?php _e('Links:', 'painel-widgets'); ?></label>
                    <div id="links-container">
                        <?php if ($tipo == 'links' && is_array($conteudo)): ?>
                            <?php foreach ($conteudo as $i => $link): ?>
                                <div class="link-item">
                                    <input type="text" name="conteudo[links][<?php echo $i; ?>][url]" 
                                           placeholder="<?php esc_attr_e('URL', 'painel-widgets'); ?>" 
                                           value="<?php echo esc_attr($link['url']); ?>">
                                    <input type="text" name="conteudo[links][<?php echo $i; ?>][texto]" 
                                           placeholder="<?php esc_attr_e('Texto do link', 'painel-widgets'); ?>" 
                                           value="<?php echo esc_attr($link['texto']); ?>">
                                    <button type="button" class="button remover-link"><?php _e('Remover', 'painel-widgets'); ?></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="link-item">
                                <input type="text" name="conteudo[links][0][url]" 
                                       placeholder="<?php esc_attr_e('URL', 'painel-widgets'); ?>">
                                <input type="text" name="conteudo[links][0][texto]" 
                                       placeholder="<?php esc_attr_e('Texto do link', 'painel-widgets'); ?>">
                                <button type="button" class="button remover-link"><?php _e('Remover', 'painel-widgets'); ?></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button adicionar-link"><?php _e('Adicionar Link', 'painel-widgets'); ?></button>
                </div>
                
                <div class="tipo-conteudo tipo-rss" <?php echo $tipo == 'rss' ? '' : 'style="display:none;"'; ?>>
                    <label for="conteudo-rss-url"><?php _e('URL do Feed RSS:', 'painel-widgets'); ?></label>
                    <input type="text" id="conteudo-rss-url" name="conteudo[rss][url]" 
                           value="<?php echo esc_attr($tipo == 'rss' && isset($conteudo['url']) ? $conteudo['url'] : ''); ?>">
                    
                    <label for="conteudo-rss-itens"><?php _e('Número de itens a exibir:', 'painel-widgets'); ?></label>
                    <input type="number" id="conteudo-rss-itens" name="conteudo[rss][itens]" min="1" max="10" 
                           value="<?php echo esc_attr($tipo == 'rss' && isset($conteudo['itens']) ? $conteudo['itens'] : 5); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="posicao"><?php _e('Posição no Painel:', 'painel-widgets'); ?></label>
                <select id="posicao" name="posicao">
                    <option value="normal" <?php selected($posicao, 'normal'); ?>><?php _e('Normal (Coluna Central)', 'painel-widgets'); ?></option>
                    <option value="side" <?php selected($posicao, 'side'); ?>><?php _e('Lateral (Coluna Direita)', 'painel-widgets'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="prioridade"><?php _e('Prioridade:', 'painel-widgets'); ?></label>
                <select id="prioridade" name="prioridade">
                    <option value="high" <?php selected($prioridade, 'high'); ?>><?php _e('Alta', 'painel-widgets'); ?></option>
                    <option value="default" <?php selected($prioridade, 'default'); ?>><?php _e('Normal', 'painel-widgets'); ?></option>
                    <option value="low" <?php selected($prioridade, 'low'); ?>><?php _e('Baixa', 'painel-widgets'); ?></option>
                </select>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="button button-primary"><?php echo $is_edit ? __('Atualizar Widget', 'painel-widgets') : __('Adicionar Widget', 'painel-widgets'); ?></button>
                <?php if ($is_edit): ?>
                    <a href="#" class="button cancelar-edicao"><?php _e('Cancelar', 'painel-widgets'); ?></a>
                <?php endif; ?>
            </div>
        </form>
        <?php
    }
    
    // Callback AJAX para salvar widget
    public function ajax_salvar_widget() {
        check_ajax_referer('painel_widgets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'painel-widgets'));
        }
        
        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        $is_edit = !empty($widget_id);
        
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : '';
        $posicao = isset($_POST['posicao']) ? sanitize_key($_POST['posicao']) : 'normal';
        $prioridade = isset($_POST['prioridade']) ? sanitize_key($_POST['prioridade']) : 'default';
        
        // Validar campos obrigatórios
        if (empty($titulo) || empty($tipo)) {
            wp_send_json_error(__('Preencha todos os campos obrigatórios.', 'painel-widgets'));
        }
        
        // Processar conteúdo baseado no tipo
        $conteudo = $this->processar_conteudo_por_tipo($tipo, $_POST['conteudo']);
        
        // Obter widgets existentes
        $widgets = $this->obter_widgets_salvos();
        
        // Gerar ID único se for novo widget
        if (!$is_edit) {
            $widget_id = 'widget_' . uniqid();
        }
        
        // Adicionar/atualizar widget
        $widgets[$widget_id] = array(
            'titulo' => $titulo,
            'tipo' => $tipo,
            'conteudo' => $conteudo,
            'posicao' => $posicao,
            'prioridade' => $prioridade
        );
        
        // Salvar widgets atualizados
        update_option('painel_widgets_personalizados', $widgets);
        
        wp_send_json_success(array(
            'message' => $is_edit ? __('Widget atualizado com sucesso.', 'painel-widgets') : __('Widget adicionado com sucesso.', 'painel-widgets'),
            'redirect' => admin_url('admin.php?page=gerenciador-widgets')
        ));
    }
    
    // Callback AJAX para excluir widget
    public function ajax_excluir_widget() {
        check_ajax_referer('painel_widgets_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Permissão negada.', 'painel-widgets'));
        }
        
        $widget_id = isset($_POST['widget_id']) ? sanitize_key($_POST['widget_id']) : '';
        
        if (empty($widget_id)) {
            wp_send_json_error(__('ID do widget não especificado.', 'painel-widgets'));
        }
        
        // Obter widgets existentes
        $widgets = $this->obter_widgets_salvos();
        
        // Verificar se o widget existe
        if (!isset($widgets[$widget_id])) {
            wp_send_json_error(__('Widget não encontrado.', 'painel-widgets'));
        }
        
        // Remover widget
        unset($widgets[$widget_id]);
        
        // Salvar widgets atualizados
        update_option('painel_widgets_personalizados', $widgets);
        
        wp_send_json_success(array(
            'message' => __('Widget excluído com sucesso.', 'painel-widgets')
        ));
    }
    
    // Processa conteúdo baseado no tipo selecionado
    private function processar_conteudo_por_tipo($tipo, $dados_conteudo) {
        switch ($tipo) {
            case 'texto':
                return isset($dados_conteudo['texto']) ? wp_kses_post($dados_conteudo['texto']) : '';
                
            case 'editor':
                return isset($dados_conteudo['editor']) ? wp_kses_post($dados_conteudo['editor']) : '';
                
            case 'youtube':
                return isset($dados_conteudo['youtube']) ? sanitize_text_field($dados_conteudo['youtube']) : '';
                
            case 'imagem':
                return isset($dados_conteudo['imagem']) ? esc_url_raw($dados_conteudo['imagem']) : '';
                
            case 'links':
                if (!isset($dados_conteudo['links']) || !is_array($dados_conteudo['links'])) {
                    return array();
                }
                
                $links = array();
                foreach ($dados_conteudo['links'] as $link) {
                    if (!empty($link['url'])) {
                        $links[] = array(
                            'url' => esc_url_raw($link['url']),
                            'texto' => sanitize_text_field($link['texto'])
                        );
                    }
                }
                return $links;
                
            case 'rss':
                return array(
                    'url' => isset($dados_conteudo['rss']['url']) ? esc_url_raw($dados_conteudo['rss']['url']) : '',
                    'itens' => isset($dados_conteudo['rss']['itens']) ? intval($dados_conteudo['rss']['itens']) : 5
                );
                
            default:
                return '';
        }
    }
    
    // Registra os widgets no dashboard do WordPress
    public function registrar_widgets_dashboard() {
        $widgets = $this->obter_widgets_salvos();
        
        if (empty($widgets)) {
            return;
        }
        
        foreach ($widgets as $id => $widget) {
            wp_add_dashboard_widget(
                $id,
                $widget['titulo'],
                array($this, 'renderizar_widget'),
                null,
                array('widget_id' => $id)
            );
            
            // Definir posição do widget
            global $wp_meta_boxes;
            $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
            $side_dashboard = $wp_meta_boxes['dashboard']['side']['core'];
            
            if ($widget['posicao'] === 'side' && isset($normal_dashboard[$id])) {
                // Mover para a coluna lateral
                $side_dashboard[$id] = $normal_dashboard[$id];
                unset($normal_dashboard[$id]);
                $wp_meta_boxes['dashboard']['side']['core'] = $side_dashboard;
                $wp_meta_boxes['dashboard']['normal']['core'] = $normal_dashboard;
            } elseif ($widget['posicao'] === 'normal' && isset($side_dashboard[$id])) {
                // Mover para a coluna normal
                $normal_dashboard[$id] = $side_dashboard[$id];
                unset($side_dashboard[$id]);
                $wp_meta_boxes['dashboard']['normal']['core'] = $normal_dashboard;
                $wp_meta_boxes['dashboard']['side']['core'] = $side_dashboard;
            }
        }
    }
    
    // Renderiza o conteúdo do widget no dashboard
    public function renderizar_widget($null, $args) {
        $widget_id = $args['args']['widget_id'];
        $widgets = $this->obter_widgets_salvos();
        
        if (!isset($widgets[$widget_id])) {
            echo '<p>' . __('Widget não encontrado.', 'painel-widgets') . '</p>';
            return;
        }
        
        $widget = $widgets[$widget_id];
        
        echo '<div class="widget-personalizado tipo-' . esc_attr($widget['tipo']) . '">';
        
        switch ($widget['tipo']) {
            case 'texto':
            case 'editor':
                echo wpautop($widget['conteudo']);
                break;
                
            case 'youtube':
                $video_id = $this->extrair_id_youtube($widget['conteudo']);
                if ($video_id) {
                    echo '<div class="video-container">';
                    echo '<iframe width="100%" height="315" src="https://www.youtube.com/embed/' . esc_attr($video_id) . '" frameborder="0" allowfullscreen></iframe>';
                    echo '</div>';
                } else {
                    echo '<p>' . __('ID do vídeo do YouTube inválido.', 'painel-widgets') . '</p>';
                }
                break;
                
            case 'imagem':
                if (!empty($widget['conteudo'])) {
                    echo '<img src="' . esc_url($widget['conteudo']) . '" alt="" style="max-width:100%;">';
                } else {
                    echo '<p>' . __('Nenhuma imagem selecionada.', 'painel-widgets') . '</p>';
                }
                break;
                
            case 'links':
                if (!empty($widget['conteudo']) && is_array($widget['conteudo'])) {
                    echo '<ul class="links-lista">';
                    foreach ($widget['conteudo'] as $link) {
                        $texto = !empty($link['texto']) ? $link['texto'] : $link['url'];
                        echo '<li><a href="' . esc_url($link['url']) . '" target="_blank">' . esc_html($texto) . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>' . __('Nenhum link adicionado.', 'painel-widgets') . '</p>';
                }
                break;
                
            case 'rss':
                if (!empty($widget['conteudo']['url'])) {
                    $rss = fetch_feed($widget['conteudo']['url']);
                    $itens = isset($widget['conteudo']['itens']) ? intval($widget['conteudo']['itens']) : 5;
                    
                    if (!is_wp_error($rss)) {
                        $maxitems = $rss->get_item_quantity($itens);
                        $rss_items = $rss->get_items(0, $maxitems);
                        
                        if ($maxitems > 0) {
                            echo '<ul class="feed-lista">';
                            foreach ($rss_items as $item) {
                                echo '<li>';
                                echo '<a href="' . esc_url($item->get_permalink()) . '" target="_blank">';
                                echo esc_html($item->get_title());
                                echo '</a>';
                                echo '</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>' . __('Nenhum item encontrado no feed.', 'painel-widgets') . '</p>';
                        }
                    } else {
                        echo '<p>' . __('Erro ao carregar o feed RSS.', 'painel-widgets') . '</p>';
                    }
                } else {
                    echo '<p>' . __('URL do feed RSS não especificada.', 'painel-widgets') . '</p>';
                }
                break;
        }
        
        echo '</div>';
    }
    
    // Obtém os widgets salvos
    private function obter_widgets_salvos() {
        $widgets = get_option('painel_widgets_personalizados', array());
        return is_array($widgets) ? $widgets : array();
    }
    
    // Obtém o nome do tipo de widget
    private function obter_nome_tipo($tipo) {
        $tipos = array(
            'texto' => __('Texto/HTML', 'painel-widgets'),
            'editor' => __('Editor Visual', 'painel-widgets'),
            'youtube' => __('Vídeo do YouTube', 'painel-widgets'),
            'imagem' => __('Imagem', 'painel-widgets'),
            'links' => __('Lista de Links', 'painel-widgets'),
            'rss' => __('Feed RSS', 'painel-widgets')
        );
        
        return isset($tipos[$tipo]) ? $tipos[$tipo] : $tipo;
    }
    
    // Obtém o nome da posição
    private function obter_nome_posicao($posicao) {
        $posicoes = array(
            'normal' => __('Normal (Coluna Central)', 'painel-widgets'),
            'side' => __('Lateral (Coluna Direita)', 'painel-widgets')
        );
        
        return isset($posicoes[$posicao]) ? $posicoes[$posicao] : $posicao;
    }
    
    // Extrai o ID de um vídeo do YouTube a partir da URL
    private function extrair_id_youtube($url) {
        // Verificar se já é um ID
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
            return $url;
        }
        
        // Extrair ID de URLs do YouTube
        $pattern = '/(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        preg_match($pattern, $url, $matches);
        
        return isset($matches[1]) ? $matches[1] : false;
    }
}

// Arquivos de suporte
function painel_widgets_criar_diretorios() {
    // Diretório CSS
    $css_dir = plugin_dir_path(__FILE__) . 'css';
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
        
        $css_content = '/* Estilos para o painel administrativo */
.painel-widgets-admin .tab-content {
    display: none;
    padding: 20px 0;
}

.painel-widgets-admin .tab-content.active {
    display: block;
}

.painel-widgets-admin .form-group {
    margin-bottom: 15px;
}

.painel-widgets-admin .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.painel-widgets-admin .form-actions {
    margin-top: 20px;
}

.painel-widgets-admin .tipo-conteudo {
    margin-bottom: 20px;
}

.painel-widgets-admin .link-item {
    display: flex;
    gap: 10px;
    margin-bottom: 10px;
    align-items: center;
}

.painel-widgets-admin .link-item input {
    flex: 1;
}

.painel-widgets-admin .imagem-preview-container {
    margin: 10px 0;
    border: 1px solid #ddd;
    padding: 10px;
    background: #f9f9f9;
    min-height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.painel-widgets-admin .imagem-preview {
    max-width: 100%;
    max-height: 200px;
}

.painel-widgets-admin .sem-imagem {
    color: #888;
    text-align: center;
}

/* Estilos para os widgets no dashboard */
.widget-personalizado .video-container {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 */
    height: 0;
    overflow: hidden;
}

.widget-personalizado .video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.widget-personalizado .links-lista,
.widget-personalizado .feed-lista {
    margin-left: 1.5em;
}

.widget-personalizado .links-lista li,
.widget-personalizado .feed-lista li {
    margin-bottom: 8px;
}';
        
        file_put_contents($css_dir . '/admin.css', $css_content);
    }
    
    // Diretório JS
    $js_dir = plugin_dir_path(__FILE__) . 'js';
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
        
        $js_content = "jQuery(document).ready(function($) {
    // Navegação por abas
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Ativar aba
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar conteúdo
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Alternar campos com base no tipo de widget
    $('#tipo').on('change', function() {
        var tipo = $(this).val();
        $('.tipo-conteudo').hide();
        $('.tipo-' + tipo).show();
    });
    
    // Upload de imagem
    $('.upload-imagem').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var imagePreviewContainer = button.closest('.tipo-imagem').find('.imagem-preview-container');
        var inputField = button.closest('.tipo-imagem').find('#conteudo-imagem');
        
        var mediaUploader = wp.media({
            title: 'Selecionar Imagem',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            
            // Atualizar preview
            imagePreviewContainer.html('<img src=\"' + attachment.url + '\" alt=\"\" class=\"imagem-preview\">');
            button.siblings('.remover-imagem').show();
        });
        
        mediaUploader.open();
    });
    
    // Remover imagem
    $('.remover-imagem').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var imagePreviewContainer = button.closest('.tipo-imagem').find('.imagem-preview-container');
        var inputField = button.closest('.tipo-imagem').find('#conteudo-imagem');
        
        inputField.val('');
        imagePreviewContainer.html('<div class=\"sem-imagem\">Nenhuma imagem selecionada</div>');
        button.hide();
    });
    
    // Adicionar link
    $('.adicionar-link').on('click', function(e) {
        e.preventDefault();
        
        var linksContainer = $('#links-container');
        var index = linksContainer.children().length;
        
        var newLinkItem = $('<div class=\"link-item\"></div>');
        newLinkItem.append('<input type=\"text\" name=\"conteudo[links][' + index + '][url]\" placeholder=\"URL\">');
        newLinkItem.append('<input type=\"text\" name=\"conteudo[links][' + index + '][texto]\" placeholder=\"Texto do link\">');
        newLinkItem.append('<button type=\"button\" class=\"button remover-link\">Remover</button>');
        
        linksContainer.append(newLinkItem);
    });
    
    // Remover link (delegação de eventos)
    $(document).on('click', '.remover-link', function() {
        $(this).closest('.link-item').remove();
        
        // Reindexar campos
        $('#links-container .link-item').each(function(index) {
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                var newName = name.replace(/\\[\\d+\\]/, '[' + index + ']');
                $(this).attr('name', newName);
            });
        });
    });
    
    // Enviar formulário via AJAX
    $('#form-widget').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var editorContent = tinymce.get('conteudo-editor');
        
        // Atualizar conteúdo do editor para o campo oculto (se estiver visível)
        if (editorContent && $('.tipo-editor').is(':visible')) {
            $('#conteudo-editor').val(editorContent.getContent());
        }
        
        $.ajax({
            url: painelWidgetsAjax.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=salvar_widget&nonce=' + painelWidgetsAjax.nonce,
            beforeSend: function() {
                form.find('button[type=\"submit\"]').prop('disabled', true).text('Salvando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data);
                    form.find('button[type=\"submit\"]').prop('disabled', false).text('Adicionar Widget');
                }
            },
            error: function() {
                alert('Erro ao processar a solicitação.');
                form.find('button[type=\"submit\"]').prop('disabled', false).text('Adicionar Widget');
            }
        });
    });
    
    // Editar widget
    $(document).on('click', '.editar-widget', function(e) {
        e.preventDefault();
        
        var widgetId = $(this).data('id');
        
        // Mostrar a aba de adicionar
        $('.nav-tab-wrapper a[href=\"#adicionar-widget\"]').trigger('click');
        
        // Carregar dados do widget para edição
        // Nota: Aqui você precisaria implementar um AJAX para buscar os detalhes do widget
        // Para simplificar, recarregaremos a página com um parâmetro para edição
        window.location.href = window.location.href + '&edit=' + widgetId;
    });
    
    // Excluir widget
    $(document).on('click', '.excluir-widget', function(e) {
        e.preventDefault();
        
        if (confirm('Tem certeza que deseja excluir este widget?')) {
            var widgetId = $(this).data('id');
            
            $.ajax({
                url: painelWidgetsAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'excluir_widget',
                    widget_id: widgetId,
                    nonce: painelWidgetsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        }
    });
    
    // Cancelar edição
    $(document).on('click', '.cancelar-edicao', function(e) {
        e.preventDefault();
        window.location.href = window.location.href.split('&edit=')[0];
    });
});";
        
        file_put_contents($js_dir . '/admin.js', $js_content);
    }
}

// Inicializar o plugin
function painel_widgets_init() {
    painel_widgets_criar_diretorios();
    $plugin = PainelWidgetsPlugin::obter_instancia();
}

add_action('plugins_loaded', 'painel_widgets_init');