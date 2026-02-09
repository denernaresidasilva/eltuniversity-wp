<?php
/**
 * Plugin Name: Restrição de Acesso Admin
 * Description: Restringe o acesso de usuários a páginas específicas do painel administrativo (wp-admin), permitindo que apenas um usuário designado tenha acesso completo.
 * Version: 1.3
 * Author: Seu Nome
 */

// Impede acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

class Restricao_Acesso_Admin {
    
    // ID do usuário com acesso completo
    private $usuario_acesso_total;
    
    // Páginas admin restritas
    private $paginas_restritas = array(
        'plugins.php',
        'plugin-install.php',
        'plugin-editor.php',
        'themes.php',
        'theme-editor.php',
        'users.php',
        'user-new.php',
        'options-general.php',
      	'wp-login.php',
        'wp-adminify-settings'  // Adicionado a nova página de configurações do WP Adminify
    );
    
    public function __construct() {
        // Carrega as configurações
        $this->carregar_configuracoes();
        
        // Hook para verificar acesso - deve ser o mais cedo possível
        add_action('admin_init', array($this, 'verificar_acesso_admin'), 1);
        
        // Remove itens do menu para usuários sem acesso
        add_action('admin_menu', array($this, 'remover_itens_menu'), 999);
        
        // Adiciona página de opções no admin
        add_action('admin_menu', array($this, 'adicionar_menu_admin'));
        
        // Registra as configurações
        add_action('admin_init', array($this, 'registrar_configuracoes'));
    }
    
    private function carregar_configuracoes() {
        // Obtém as opções salvas
        $opcoes = get_option('raa_opcoes');
        
        // Carrega as configurações existentes ou deixa vazio para forçar seleção
        if ($opcoes) {
            $this->usuario_acesso_total = isset($opcoes['usuario_acesso_total']) ? $opcoes['usuario_acesso_total'] : 0;
            $this->paginas_restritas = isset($opcoes['paginas_restritas']) ? $opcoes['paginas_restritas'] : $this->paginas_restritas;
        } else {
            // Inicializa com ID 0 (nenhum usuário) para forçar seleção manual
            $this->usuario_acesso_total = 0;
            
            // Salva as configurações iniciais
            update_option('raa_opcoes', array(
                'usuario_acesso_total' => $this->usuario_acesso_total,
                'paginas_restritas' => $this->paginas_restritas
            ));
        }
    }
    
    public function verificar_acesso_admin() {
        // Se nenhum usuário foi selecionado, permite acesso para evitar bloqueio completo
        if ($this->usuario_acesso_total == 0) {
            // Verifica se estamos na página de configuração deste plugin
            $current_page = isset($_GET['page']) ? $_GET['page'] : '';
            if ($current_page != 'restricao-acesso-admin') {
                // Exibe notificação para configurar o plugin
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-warning is-dismissible"><p><strong>Restrição de Acesso Admin:</strong> Você precisa <a href="' . admin_url('options-general.php?page=restricao-acesso-admin') . '">selecionar um usuário</a> que terá acesso às páginas restritas.</p></div>';
                });
            }
            return;
        }
        
        // Verifica se estamos em uma página de admin
        if (!is_admin()) {
            return;
        }
        
        // Obtém a página atual
        $current_page = isset($_SERVER['PHP_SELF']) ? basename($_SERVER['PHP_SELF']) : '';
        
        // Verifica também se é uma página com parâmetro 'page' na URL (para páginas de plugins)
        $page_param = isset($_GET['page']) ? $_GET['page'] : '';
        
        // Se nem a página atual nem o parâmetro 'page' estiverem na lista de restritas, permite acesso
        if (!in_array($current_page, $this->paginas_restritas) && !in_array($page_param, $this->paginas_restritas)) {
            return;
        }
        
        // Verifica se o usuário está logado
        if (!is_user_logged_in()) {
            wp_die('Acesso negado.', 'Acesso Restrito', array('response' => 403));
        }
        
        // Obtém o usuário atual
        $usuario_atual = wp_get_current_user();
        
        // Se não for o usuário com acesso total, redireciona para o dashboard com mensagem
        if ($usuario_atual->ID != $this->usuario_acesso_total) {
            wp_redirect(admin_url('index.php?acesso_restrito=1'));
            exit;
        }
    }
    
    public function remover_itens_menu() {
        // Se nenhum usuário foi selecionado, não remove nada
        if ($this->usuario_acesso_total == 0) {
            return;
        }
        
        // Obtém o usuário atual
        $usuario_atual = wp_get_current_user();
        
        // Se for o usuário com acesso total, não remove nada
        if ($usuario_atual->ID == $this->usuario_acesso_total) {
            return;
        }
        
        // Remove os itens de menu para as páginas restritas
        foreach ($this->paginas_restritas as $pagina) {
            switch ($pagina) {
                case 'plugins.php':
                    remove_menu_page('plugins.php');
                    break;
                case 'themes.php':
                    remove_menu_page('themes.php');
                    break;
                case 'users.php':
                    remove_menu_page('users.php');
                    break;
                case 'plugin-install.php':
                    remove_submenu_page('plugins.php', 'plugin-install.php');
                    break;
                case 'plugin-editor.php':
                    remove_submenu_page('plugins.php', 'plugin-editor.php');
                    break;
                case 'theme-editor.php':
                    remove_submenu_page('themes.php', 'theme-editor.php');
                    break;
                case 'user-new.php':
                    remove_submenu_page('users.php', 'user-new.php');
                    break;
                case 'wp-adminify-settings':
                    // Remove o menu do WP Adminify
                    global $submenu;
                    if (isset($submenu['wp-adminify'])) {
                        foreach ($submenu['wp-adminify'] as $key => $item) {
                            if ($item[2] == 'wp-adminify-settings') {
                                unset($submenu['wp-adminify'][$key]);
                            }
                        }
                    }
                    
                    // Se não houver mais itens, remove o menu principal
                    if (isset($submenu['wp-adminify']) && empty($submenu['wp-adminify'])) {
                        remove_menu_page('wp-adminify');
                    }
                    break;
            }
        }
    }
    
    public function adicionar_menu_admin() {
        add_options_page(
            'Restrição de Acesso Admin',
            'Restrição Admin',
            'manage_options',
            'restricao-acesso-admin',
            array($this, 'renderizar_pagina_admin')
        );
    }
    
    public function registrar_configuracoes() {
        register_setting('raa_grupo', 'raa_opcoes', array($this, 'validar_opcoes'));
        
        // Adiciona notificação de acesso restrito
        if (isset($_GET['acesso_restrito']) && $_GET['acesso_restrito'] == 1) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Acesso restrito:</strong> Você não tem permissão para acessar a página solicitada. Apenas o usuário designado pode acessar esta área.</p></div>';
            });
        }
    }
    
    public function validar_opcoes($opcoes) {
        // Garante que pelo menos uma página está selecionada
        if (!isset($opcoes['paginas_restritas']) || empty($opcoes['paginas_restritas'])) {
            $opcoes['paginas_restritas'] = $this->paginas_restritas;
            add_settings_error('raa_opcoes', 'paginas_vazias', 'É necessário selecionar pelo menos uma página para restringir.', 'error');
        }
        
        return $opcoes;
    }
    
    public function renderizar_pagina_admin() {
        // Obtém as opções salvas
        $opcoes = get_option('raa_opcoes', array(
            'usuario_acesso_total' => $this->usuario_acesso_total,
            'paginas_restritas' => $this->paginas_restritas
        ));
        
        // Lista das páginas a serem restritas
        $paginas_admin = array(
            'plugins.php' => 'Plugins',
            'plugin-install.php' => 'Adicionar Plugins',
            'plugin-editor.php' => 'Editor de Plugins',
            'themes.php' => 'Temas',
            'theme-editor.php' => 'Editor de Temas',
            'users.php' => 'Usuários',
            'user-new.php' => 'Adicionar Usuário',
            'wp-adminify-settings' => 'WP Adminify Settings'
        );
        
        ?>
        <div class="wrap">
            <h1>Restrição de Acesso Admin</h1>
            <p>Este plugin restringe o acesso a páginas específicas do painel administrativo, permitindo que apenas um usuário designado tenha acesso completo.</p>
            
            <div class="notice notice-warning">
                <p><strong>Atenção:</strong> Se você não selecionar um usuário de acesso total, todas as páginas restritas ficarão inacessíveis para todos os usuários. Certifique-se de selecionar um usuário de confiança.</p>
            </div>
            
            <form method="post" action="options.php">
                <?php settings_fields('raa_grupo'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Usuário com Acesso Total</th>
                        <td>
                            <select name="raa_opcoes[usuario_acesso_total]">
                                <option value="0" <?php selected($opcoes['usuario_acesso_total'], 0); ?>>-- Selecione um usuário --</option>
                                <?php
                                // Obtém TODOS os usuários, não apenas administradores
                                $todos_usuarios = get_users();
                                foreach ($todos_usuarios as $usuario) {
                                    $selected = ($usuario->ID == $opcoes['usuario_acesso_total']) ? 'selected="selected"' : '';
                                    $role = !empty($usuario->roles) ? ' - ' . ucfirst(implode(', ', $usuario->roles)) : '';
                                    echo '<option value="' . $usuario->ID . '" ' . $selected . '>' . $usuario->display_name . ' (' . $usuario->user_login . $role . ')</option>';
                                }
                                ?>
                            </select>
                            <p class="description">Este usuário será o único com acesso às páginas restritas do painel administrativo.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Páginas Admin Restritas</th>
                        <td>
                            <?php foreach ($paginas_admin as $slug => $nome) : ?>
                                <?php
                                $checked = in_array($slug, $opcoes['paginas_restritas']) ? 'checked="checked"' : '';
                                ?>
                                <label>
                                    <input type="checkbox" name="raa_opcoes[paginas_restritas][]" value="<?php echo $slug; ?>" <?php echo $checked; ?>>
                                    <?php echo $nome; ?> (<?php echo $slug; ?>)
                                </label><br>
                            <?php endforeach; ?>
                            <p class="description">Selecione as páginas do painel administrativo que deseja restringir.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar Configurações'); ?>
            </form>
            
            <hr>
            
            <h3>Status atual</h3>
            
            <?php if ($opcoes['usuario_acesso_total'] == 0) : ?>
                <div class="notice notice-error">
                    <p><strong>Nenhum usuário selecionado!</strong> Você precisa selecionar um usuário específico que terá acesso às páginas restritas.</p>
                </div>
            <?php else : ?>
                <p><strong>Usuário com acesso total:</strong> 
                <?php 
                    $user_info = get_userdata($opcoes['usuario_acesso_total']);
                    echo $user_info ? $user_info->display_name . ' (ID: ' . $user_info->ID . ')' : 'Usuário não encontrado';
                ?>
                </p>
            <?php endif; ?>
            
            <p><strong>Páginas restritas:</strong></p>
            <ul>
                <?php 
                foreach ($opcoes['paginas_restritas'] as $pagina) {
                    echo '<li>' . $pagina . ' - ' . (isset($paginas_admin[$pagina]) ? $paginas_admin[$pagina] : 'Página personalizada') . '</li>';
                }
                ?>
            </ul>
            
            <hr>
            
            <h3>Adicionar página personalizada</h3>
            <p>Se precisar adicionar outra página personalizada à lista de restrições, insira o nome do arquivo ou o slug da página abaixo:</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('raa_adicionar_pagina', 'raa_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Nome da Página</th>
                        <td>
                            <input type="text" name="pagina_personalizada" placeholder="exemplo.php ou slug-da-pagina">
                            <p class="description">Digite o nome do arquivo PHP (sem wp-admin/) ou o slug da página (parâmetro 'page=' na URL)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Adicionar Página Personalizada'); ?>
            </form>
            
            <?php
            // Processa o formulário de adicionar página personalizada
            if (isset($_POST['pagina_personalizada']) && isset($_POST['raa_nonce']) && wp_verify_nonce($_POST['raa_nonce'], 'raa_adicionar_pagina')) {
                $pagina = sanitize_text_field($_POST['pagina_personalizada']);
                if (!empty($pagina)) {
                    if (!in_array($pagina, $opcoes['paginas_restritas'])) {
                        $opcoes['paginas_restritas'][] = $pagina;
                        update_option('raa_opcoes', $opcoes);
                        echo '<div class="notice notice-success is-dismissible"><p>Página <strong>' . $pagina . '</strong> adicionada com sucesso à lista de restrições!</p></div>';
                    } else {
                        echo '<div class="notice notice-warning is-dismissible"><p>A página <strong>' . $pagina . '</strong> já está na lista de restrições.</p></div>';
                    }
                }
            }
            ?>
        </div>
        <?php
    }
}

// Inicializa o plugin
$restricao_acesso_admin = new Restricao_Acesso_Admin();