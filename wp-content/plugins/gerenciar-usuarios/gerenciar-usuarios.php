<?php
/**
 * Plugin Name: Gerenciador de Usuários Assinantes
 * Description: Plugin para criar e gerenciar usuários apenas como assinantes com painel administrativo próprio
 * Version: 1.0.1
 * Author: Seu Nome
 * Text Domain: subscriber-manager
 */

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('SUBSCRIBER_MANAGER_VERSION', '1.0.1');
define('SUBSCRIBER_MANAGER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SUBSCRIBER_MANAGER_PLUGIN_PATH', plugin_dir_path(__FILE__));

class SubscriberManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_toggle_user_status', array($this, 'toggle_user_status'));
        add_action('wp_ajax_delete_subscriber', array($this, 'delete_subscriber'));
        add_action('wp_ajax_get_user_data', array($this, 'get_user_data'));
        
        // Criar tabela personalizada na ativação
        register_activation_hook(__FILE__, array($this, 'create_user_status_table'));
    }
    
    public function init() {
        load_plugin_textdomain('subscriber-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function create_user_status_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'subscriber_status';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            status tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Gerenciador de Assinantes',
            'Assinantes',
            'manage_options',
            'subscriber-manager',
            array($this, 'admin_page'),
            'dashicons-groups',
            30
        );
        
        add_submenu_page(
            'subscriber-manager',
            'Todos os Assinantes',
            'Todos os Assinantes',
            'manage_options',
            'subscriber-manager',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'subscriber-manager',
            'Adicionar Assinante',
            'Adicionar Assinante',
            'manage_options',
            'subscriber-manager-add',
            array($this, 'add_subscriber_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'subscriber-manager') === false) {
            return;
        }
        
        wp_enqueue_script('subscriber-manager-js', SUBSCRIBER_MANAGER_PLUGIN_URL . 'assets/admin.js', array('jquery'), SUBSCRIBER_MANAGER_VERSION, true);
        wp_enqueue_style('subscriber-manager-css', SUBSCRIBER_MANAGER_PLUGIN_URL . 'assets/admin.css', array(), SUBSCRIBER_MANAGER_VERSION);
        
        wp_localize_script('subscriber-manager-js', 'subscriberManager', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('subscriber_manager_nonce'),
            'current_user_id' => get_current_user_id()
        ));
    }
    
    public function admin_page() {
        // Processar ações
        if (isset($_POST['action']) && $_POST['action'] === 'edit_subscriber') {
            $this->handle_edit_subscriber();
        }
        
        // Buscar usuários assinantes
        $subscribers = get_users(array('role' => 'subscriber'));
        
        // Buscar o usuário adminsite especificamente
        $adminsite_user = get_user_by('login', 'adminsite');
        
        // Combinar usuários
        $users = $subscribers;
        if ($adminsite_user && in_array('administrator', $adminsite_user->roles)) {
            $users[] = $adminsite_user;
        }
        
        $current_user_id = get_current_user_id();
        
        ?>
        <div class="wrap">
            <h1>Gerenciador de Assinantes</h1>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($_GET['message']); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($_GET['error']); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <a href="<?php echo admin_url('admin.php?page=subscriber-manager-add'); ?>" class="button button-primary">
                        Adicionar Novo Assinante
                    </a>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome de Usuário</th>
                        <th>Email</th>
                        <th>Nome Completo</th>
                        <th>Papel</th>
                        <th>Data de Registro</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php 
                        $user_status = $this->get_user_status($user->ID); 
                        $is_current_user = ($user->ID == $current_user_id);
                        $user_roles = $user->roles;
                        $role_name = in_array('administrator', $user_roles) ? 'Administrador' : 'Assinante';
                        $is_admin = in_array('administrator', $user_roles);
                        ?>
                        <tr <?php echo $is_current_user ? 'class="current-user-row"' : ''; ?>>
                            <td><?php echo $user->ID; ?></td>
                            <td>
                                <?php echo esc_html($user->user_login); ?>
                                <?php if ($is_current_user): ?>
                                    <span class="current-user-badge">Você</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><span class="user-role <?php echo $is_admin ? 'role-admin' : 'role-subscriber'; ?>"><?php echo $role_name; ?></span></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($user->user_registered)); ?></td>
                            <td>
                                <?php if ($is_current_user): ?>
                                    <span class="status-text-fixed">Ativo (Usuário Logado)</span>
                                <?php else: ?>
                                    <label class="switch">
                                        <input type="checkbox" 
                                               class="status-toggle" 
                                               data-user-id="<?php echo $user->ID; ?>"
                                               <?php checked($user_status, 1); ?>>
                                        <span class="slider round"></span>
                                    </label>
                                    <span class="status-text"><?php echo $user_status ? 'Ativo' : 'Inativo'; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button button-small edit-user" data-user-id="<?php echo $user->ID; ?>">
                                    Editar
                                </button>
                                <?php if (!$is_current_user && !$is_admin): ?>
                                    <button class="button button-small button-link-delete delete-user" data-user-id="<?php echo $user->ID; ?>">
                                        Excluir
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Modal para edição -->
        <div id="edit-user-modal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Editar Usuário</h2>
                <form id="edit-user-form" method="post">
                    <input type="hidden" name="action" value="edit_subscriber">
                    <input type="hidden" name="user_id" id="edit-user-id">
                    <?php wp_nonce_field('edit_subscriber_nonce', 'edit_subscriber_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="edit-username">Nome de Usuário</label></th>
                            <td><input type="text" id="edit-username" name="username" readonly></td>
                        </tr>
                        <tr>
                            <th><label for="edit-email">Email</label></th>
                            <td><input type="email" id="edit-email" name="email" required></td>
                        </tr>
                        <tr>
                            <th><label for="edit-first-name">Nome</label></th>
                            <td><input type="text" id="edit-first-name" name="first_name"></td>
                        </tr>
                        <tr>
                            <th><label for="edit-last-name">Sobrenome</label></th>
                            <td><input type="text" id="edit-last-name" name="last_name"></td>
                        </tr>
                        <tr>
                            <th><label for="edit-password">Nova Senha</label></th>
                            <td>
                                <input type="password" id="edit-password" name="password">
                                <p class="description">Deixe em branco para manter a senha atual</p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" class="button button-primary" value="Salvar Alterações">
                        <button type="button" class="button cancel-edit">Cancelar</button>
                    </p>
                </form>
            </div>
        </div>
        
        <?php
    }
    
    public function add_subscriber_page() {
        if (isset($_POST['action']) && $_POST['action'] === 'add_subscriber') {
            $this->handle_add_subscriber();
        }
        
        ?>
        <div class="wrap">
            <h1>Adicionar Novo Assinante</h1>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="notice notice-error is-dismissible">
                    <p><?php echo esc_html($_GET['error']); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="post" class="subscriber-form">
                <input type="hidden" name="action" value="add_subscriber">
                <?php wp_nonce_field('add_subscriber_nonce', 'add_subscriber_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="username">Nome de Usuário *</label></th>
                        <td>
                            <input type="text" name="username" id="username" required>
                            <p class="description">O nome de usuário não pode ser alterado após a criação.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="email">Email *</label></th>
                        <td><input type="email" name="email" id="email" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="first_name">Nome</label></th>
                        <td><input type="text" name="first_name" id="first_name"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="last_name">Sobrenome</label></th>
                        <td><input type="text" name="last_name" id="last_name"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="password">Senha *</label></th>
                        <td>
                            <input type="password" name="password" id="password" required>
                            <button type="button" id="generate-password" class="button">Gerar Senha</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="send_notification">Notificar usuário</label></th>
                        <td>
                            <label>
                                <input type="checkbox" name="send_notification" id="send_notification" value="1">
                                Enviar email com detalhes da conta para o usuário
                            </label>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Adicionar Assinante'); ?>
            </form>
        </div>
        <?php
    }
    
    private function handle_add_subscriber() {
        if (!wp_verify_nonce($_POST['add_subscriber_nonce'], 'add_subscriber_nonce')) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager-add&error=Erro de segurança'));
            exit;
        }
        
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        $send_notification = isset($_POST['send_notification']);
        
        // Validações
        if (username_exists($username)) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager-add&error=Nome de usuário já existe'));
            exit;
        }
        
        if (email_exists($email)) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager-add&error=Email já está em uso'));
            exit;
        }
        
        // Criar usuário
        $user_id = wp_create_user($username, $password, $email);
        
        if (is_wp_error($user_id)) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager-add&error=' . urlencode($user_id->get_error_message())));
            exit;
        }
        
        // Atualizar dados do usuário
        wp_update_user(array(
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name),
            'role' => 'subscriber'
        ));
        
        // Adicionar status na tabela personalizada
        $this->set_user_status($user_id, 1);
        
        // Enviar notificação se solicitado
        if ($send_notification) {
            wp_new_user_notification($user_id, null, 'both');
        }
        
        wp_redirect(admin_url('admin.php?page=subscriber-manager&message=Assinante criado com sucesso'));
        exit;
    }
    
    private function handle_edit_subscriber() {
        if (!wp_verify_nonce($_POST['edit_subscriber_nonce'], 'edit_subscriber_nonce')) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager&error=Erro de segurança'));
            exit;
        }
        
        $user_id = intval($_POST['user_id']);
        $email = sanitize_email($_POST['email']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $password = $_POST['password'];
        
        // Verificar se o email já existe para outro usuário
        $existing_user = get_user_by('email', $email);
        if ($existing_user && $existing_user->ID != $user_id) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager&error=Email já está em uso por outro usuário'));
            exit;
        }
        
        $user_data = array(
            'ID' => $user_id,
            'user_email' => $email,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => trim($first_name . ' ' . $last_name)
        );
        
        if (!empty($password)) {
            $user_data['user_pass'] = $password;
        }
        
        $result = wp_update_user($user_data);
        
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=subscriber-manager&error=' . urlencode($result->get_error_message())));
            exit;
        }
        
        wp_redirect(admin_url('admin.php?page=subscriber-manager&message=Usuário atualizado com sucesso'));
        exit;
    }
    
    public function get_user_data() {
        check_ajax_referer('subscriber_manager_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            wp_send_json_error('Usuário não encontrado');
        }
        
        wp_send_json_success(array(
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->user_email
        ));
    }
    
    public function toggle_user_status() {
        check_ajax_referer('subscriber_manager_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id']);
        $current_user_id = get_current_user_id();
        
        // Impedir que o usuário logado desative a si mesmo
        if ($user_id == $current_user_id) {
            wp_send_json_error('Você não pode desativar sua própria conta');
            return;
        }
        
        $current_status = $this->get_user_status($user_id);
        $new_status = $current_status ? 0 : 1;
        
        $this->set_user_status($user_id, $new_status);
        
        wp_send_json_success(array(
            'status' => $new_status,
            'status_text' => $new_status ? 'Ativo' : 'Inativo'
        ));
    }
    
    public function delete_subscriber() {
        check_ajax_referer('subscriber_manager_nonce', 'nonce');
        
        $user_id = intval($_POST['user_id']);
        $current_user_id = get_current_user_id();
        
        // Impedir que o usuário logado se delete
        if ($user_id == $current_user_id) {
            wp_send_json_error('Você não pode excluir sua própria conta');
            return;
        }
        
        // Verificar se é assinante
        $user = get_user_by('id', $user_id);
        if (!$user) {
            wp_send_json_error('Usuário não encontrado');
            return;
        }
        
        // Impedir exclusão de administradores
        if (in_array('administrator', $user->roles)) {
            wp_send_json_error('Administradores não podem ser excluídos');
            return;
        }
        
        if (!in_array('subscriber', $user->roles)) {
            wp_send_json_error('Usuário não é um assinante');
            return;
        }
        
        // Remover da tabela de status
        global $wpdb;
        $table_name = $wpdb->prefix . 'subscriber_status';
        $wpdb->delete($table_name, array('user_id' => $user_id));
        
        // Deletar usuário
        wp_delete_user($user_id);
        
        wp_send_json_success('Assinante excluído com sucesso');
    }
    
    private function get_user_status($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'subscriber_status';
        
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table_name WHERE user_id = %d",
            $user_id
        ));
        
        return $status !== null ? intval($status) : 1;
    }
    
    private function set_user_status($user_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'subscriber_status';
        
        $wpdb->replace($table_name, array(
            'user_id' => $user_id,
            'status' => $status
        ));
    }
}

// Inicializar o plugin
new SubscriberManager();
?>