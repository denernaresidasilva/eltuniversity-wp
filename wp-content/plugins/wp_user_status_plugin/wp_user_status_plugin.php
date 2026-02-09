<?php
/**
 * Plugin Name: User Status Toggle
 * Plugin URI: https://seusite.com/
 * Description: Adiciona um switch para ativar/desativar usuários na página de usuários do WordPress
 * Version: 1.0.0
 * Author: Seu Nome
 * License: GPL v2 or later
 * Text Domain: user-status-toggle
 */

// Previne acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class UserStatusToggle {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Hooks para administração
        add_filter('manage_users_columns', array($this, 'add_status_column'));
        add_filter('manage_users_custom_column', array($this, 'show_status_column_content'), 10, 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_toggle_user_status', array($this, 'ajax_toggle_user_status'));
        
        // Hook para verificar login
        add_filter('authenticate', array($this, 'check_user_status_on_login'), 30, 3);
        
        // Adicionar CSS personalizado
        add_action('admin_head', array($this, 'add_admin_styles'));
    }
    
    /**
     * Adiciona coluna de status na tabela de usuários
     */
    public function add_status_column($columns) {
        $columns['user_status'] = __('Status', 'user-status-toggle');
        return $columns;
    }
    
    /**
     * Mostra o conteúdo da coluna de status
     */
    public function show_status_column_content($value, $column_name, $user_id) {
        if ($column_name !== 'user_status') {
            return $value;
        }
        
        $is_active = $this->is_user_active($user_id);
        $current_user_id = get_current_user_id();
        
        // Previne que o usuário atual se desative
        if ($user_id == $current_user_id) {
            $disabled = 'disabled';
            $title = __('Você não pode desativar sua própria conta', 'user-status-toggle');
        } else {
            $disabled = '';
            $title = $is_active ? __('Clique para desativar usuário', 'user-status-toggle') : __('Clique para ativar usuário', 'user-status-toggle');
        }
        
        $checked = $is_active ? 'checked' : '';
        
        return sprintf(
            '<label class="user-status-switch" title="%s">
                <input type="checkbox" class="user-status-toggle" data-user-id="%d" %s %s>
                <span class="slider"></span>
                <span class="status-text">%s</span>
            </label>',
            esc_attr($title),
            $user_id,
            $checked,
            $disabled,
            $is_active ? __('Ativo', 'user-status-toggle') : __('Inativo', 'user-status-toggle')
        );
    }
    
    /**
     * Adiciona scripts e estilos necessários
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'users.php') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        // Script inline para funcionalidade AJAX
        $script = "
        jQuery(document).ready(function($) {
            $('.user-status-toggle').on('change', function() {
                var checkbox = $(this);
                var userId = checkbox.data('user-id');
                var isActive = checkbox.is(':checked') ? 1 : 0;
                var statusText = checkbox.siblings('.status-text');
                
                // Desabilita o checkbox durante a requisição
                checkbox.prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'toggle_user_status',
                        user_id: userId,
                        is_active: isActive,
                        nonce: '" . wp_create_nonce('toggle_user_status_nonce') . "'
                    },
                    success: function(response) {
                        if (response.success) {
                            statusText.text(isActive ? '" . __('Ativo', 'user-status-toggle') . "' : '" . __('Inativo', 'user-status-toggle') . "');
                            
                            // Exibe mensagem de sucesso
                            $('<div class=\"notice notice-success is-dismissible\"><p>' + response.data.message + '</p></div>')
                                .insertAfter('.wp-header-end')
                                .delay(3000)
                                .fadeOut();
                        } else {
                            // Reverte o estado do checkbox em caso de erro
                            checkbox.prop('checked', !isActive);
                            alert('Erro: ' + response.data.message);
                        }
                    },
                    error: function() {
                        // Reverte o estado do checkbox em caso de erro
                        checkbox.prop('checked', !isActive);
                        alert('Erro de comunicação. Tente novamente.');
                    },
                    complete: function() {
                        // Re-habilita o checkbox
                        checkbox.prop('disabled', false);
                    }
                });
            });
        });
        ";
        
        wp_add_inline_script('jquery', $script);
    }
    
    /**
     * Adiciona estilos CSS para o switch
     */
    public function add_admin_styles() {
        if (get_current_screen()->id !== 'users') {
            return;
        }
        
        echo '<style>
        .user-status-switch {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .user-status-switch input[type="checkbox"] {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .user-status-switch input[type="checkbox"]:disabled + .slider {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .slider {
            position: relative;
            width: 50px;
            height: 24px;
            background-color: #ccc;
            border-radius: 24px;
            transition: 0.3s;
            cursor: pointer;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        
        input:checked + .slider {
            background-color: #2196F3;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .status-text {
            font-weight: 500;
            min-width: 40px;
        }
        
        .user-status-switch input:checked + .slider + .status-text {
            color: #2196F3;
        }
        
        .user-status-switch input:not(:checked) + .slider + .status-text {
            color: #999;
        }
        </style>';
    }
    
    /**
     * Manipula a requisição AJAX para alternar status do usuário
     */
    public function ajax_toggle_user_status() {
        // Verificação de nonce
        if (!wp_verify_nonce($_POST['nonce'], 'toggle_user_status_nonce')) {
            wp_die(__('Ação não autorizada.', 'user-status-toggle'));
        }
        
        // Verificação de permissões
        if (!current_user_can('edit_users')) {
            wp_send_json_error(array(
                'message' => __('Você não tem permissão para realizar esta ação.', 'user-status-toggle')
            ));
        }
        
        $user_id = intval($_POST['user_id']);
        $is_active = intval($_POST['is_active']);
        $current_user_id = get_current_user_id();
        
        // Previne que o usuário atual se desative
        if ($user_id == $current_user_id) {
            wp_send_json_error(array(
                'message' => __('Você não pode desativar sua própria conta.', 'user-status-toggle')
            ));
        }
        
        // Verifica se o usuário existe
        if (!get_userdata($user_id)) {
            wp_send_json_error(array(
                'message' => __('Usuário não encontrado.', 'user-status-toggle')
            ));
        }
        
        // Atualiza o status do usuário
        $result = update_user_meta($user_id, 'user_status_active', $is_active);
        
        if ($result !== false) {
            $user_info = get_userdata($user_id);
            $status_text = $is_active ? __('ativado', 'user-status-toggle') : __('desativado', 'user-status-toggle');
            
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Usuário %s foi %s com sucesso.', 'user-status-toggle'),
                    $user_info->display_name,
                    $status_text
                )
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Erro ao atualizar status do usuário.', 'user-status-toggle')
            ));
        }
    }
    
    /**
     * Verifica se o usuário está ativo
     */
    public function is_user_active($user_id) {
        $status = get_user_meta($user_id, 'user_status_active', true);
        
        // Se não há meta definida, considera ativo por padrão
        if ($status === '') {
            return true;
        }
        
        return (bool) $status;
    }
    
    /**
     * Verifica o status do usuário durante o login
     */
    public function check_user_status_on_login($user, $username, $password) {
        // Se já há um erro de autenticação, retorna
        if (is_wp_error($user)) {
            return $user;
        }
        
        // Se o usuário existe e as credenciais estão corretas
        if ($user && !is_wp_error($user)) {
            if (!$this->is_user_active($user->ID)) {
                return new WP_Error(
                    'user_inactive',
                    __('<strong>ERRO</strong>: Sua conta foi desativada. Entre em contato com o administrador.', 'user-status-toggle')
                );
            }
        }
        
        return $user;
    }
}

// Inicializa o plugin
new UserStatusToggle();

/**
 * Função para ativar plugin - cria uma coluna padrão ativa para usuários existentes
 */
register_activation_hook(__FILE__, 'user_status_toggle_activate');
function user_status_toggle_activate() {
    // Define todos os usuários existentes como ativos por padrão
    $users = get_users(array('fields' => 'ID'));
    
    foreach ($users as $user_id) {
        $status = get_user_meta($user_id, 'user_status_active', true);
        if ($status === '') {
            update_user_meta($user_id, 'user_status_active', 1);
        }
    }
}

/**
 * Função para desativar plugin - opcional: remove meta dados
 */
register_deactivation_hook(__FILE__, 'user_status_toggle_deactivate');
function user_status_toggle_deactivate() {
    // Opcional: remover todos os meta dados ao desativar
    // Descomente as linhas abaixo se quiser limpar os dados ao desativar o plugin
    
    /*
    $users = get_users(array('fields' => 'ID'));
    foreach ($users as $user_id) {
        delete_user_meta($user_id, 'user_status_active');
    }
    */
}
?>