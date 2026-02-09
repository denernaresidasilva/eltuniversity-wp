<?php
/**
 * Plugin Name: Redirecionamento de Login para Vitrine
 * Plugin URI: https://wordpressclub.com.br
 * Description: Redirecionamento de users logados para a página de Vitrine, redirecionamento de users não logados para a página de Login, e redirecionamento de páginas 404 para Vitrine ou Login.
 * Version: 2.1
 * Author: Raul da Cruz
 * Author URI: https://wordpressclub.com.br
 */

// Redirecionar usuário logado para /vitrine após o login, exceto administradores
function custom_login_redirect($redirect_to, $request, $user) {
    // Verifica se o usuário tem o papel de administrador
    if (isset($user->roles) && is_array($user->roles)) {
        if (in_array('administrator', $user->roles)) {
            // Redireciona o administrador para o painel de administração (backoffice)
            return admin_url();
        }
    }
    // Para outros usuários, redireciona para a página de cursos (/vitrine)
    return home_url('/cursos');
}
add_filter('login_redirect', 'custom_login_redirect', 10, 3);

// Redirecionar usuários não logados ao tentarem acessar a página /vitrine
function custom_redirect_non_logged_users_from_vitrine() {
    // Não redireciona se estivermos no admin ou na página de login para evitar loops
    if (is_admin() || is_page('login')) {
        return;
    }

    // Verifica se o usuário não está logado e está tentando acessar a página /vitrine
    if (!is_user_logged_in() && is_page('vitrine')) {
        // Redireciona o usuário não logado para a página de login (/login)
        wp_redirect(home_url('/login'));
        exit;
    }
}
add_action('template_redirect', 'custom_redirect_non_logged_users_from_vitrine');

// Redirecionar para /vitrine ou /login dependendo do status do login ao acessar uma página 404 (não existente)
function custom_redirect_on_404() {
    // Não redireciona se estivermos no admin
    if (is_admin()) {
        return;
    }

    // Verifica se a página solicitada não existe (404)
    if (is_404()) {
        // Se o usuário estiver logado
        if (is_user_logged_in()) {
            // Redireciona para a página de cursos (/vitrine)
            wp_redirect(home_url('/vitrine'));
            exit;
        } else {
            // Se o usuário não estiver logado, redireciona para a página de login (/login)
            wp_redirect(home_url('/login'));
            exit;
        }
    }
}
add_action('template_redirect', 'custom_redirect_on_404');

// **NOVA FUNCIONALIDADE** - Redirecionar usuários não logados de qualquer página para /login
function custom_redirect_non_logged_users_to_login() {
    // Não redireciona se estivermos no admin, na página de login, reset, login-sem-senha, ou em requisições AJAX
    if (is_admin() || is_page('login') || is_page('reset') || is_page('login-sem-senha') || wp_doing_ajax()) {
        return;
    }

    // Não redireciona se for uma requisição de login (wp-login.php)
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
        return;
    }

    // Verifica se o usuário não está logado
    if (!is_user_logged_in()) {
        // Redireciona para a página de login (/login)
        wp_redirect(home_url('/login'));
        exit;
    }
}
add_action('template_redirect', 'custom_redirect_non_logged_users_to_login', 1); // Prioridade alta para executar primeiro

// Redirecionar usuário após logout para a página /login e evitar página de confirmação
function custom_logout_redirect() {
    // Apenas redireciona se o usuário não estiver na página de login
    if (!is_page('login')) {
        wp_redirect(home_url('/login'));
        exit;
    }
}
add_action('wp_logout', 'custom_logout_redirect');

// Verifica se a ação de logout está sendo chamada e intercepta para evitar a página de confirmação
function intercept_logout_confirmation() {
    if (isset($_GET['action']) && $_GET['action'] === 'logout') {
        // Deslogar o usuário e redirecionar diretamente para /login
        wp_logout(); // Efetua o logout
        wp_redirect(home_url('/login')); // Redireciona para /login
        exit;
    }
}
add_action('init', 'intercept_logout_confirmation');

// Modificar o link "Visitar site" para redirecionar administradores para /vitrine
function modify_admin_visit_site_link($wp_admin_bar) {
    if (current_user_can('administrator')) {
        // Modifica o link "Visitar site" para redirecionar para /vitrine
        $wp_admin_bar->add_node(array(
            'id'    => 'view-site',
            'title' => __('Visitar site'),
            'href'  => home_url('/vitrine'),
        ));
    }
}
add_action('admin_bar_menu', 'modify_admin_visit_site_link', 999);

// Redireciona para /login em caso de erro de login
function custom_login_failed_redirect($username) {
    // Redireciona para a página de login personalizada em caso de falha
    wp_redirect(home_url('/login?login=failed'));
    exit;
}
add_action('wp_login_failed', 'custom_login_failed_redirect');

// Verifica credenciais incorretas e impede redirecionamento para wp-login.php
function custom_login_empty_redirect($redirect_to, $requested_redirect_to, $user) {
    if (isset($_GET['login']) && $_GET['login'] == 'failed') {
        return home_url('/login?login=failed');
    }
    return $redirect_to;
}
add_filter('login_redirect', 'custom_login_empty_redirect', 10, 3);

// Redireciona em caso de campos vazios
function custom_check_empty_fields($user, $username, $password) {
    if (empty($username) || empty($password)) {
        // Redireciona para a página de login se os campos estiverem vazios
        wp_redirect(home_url('/login?login=empty'));
        exit;
    }
    return $user;
}
add_filter('authenticate', 'custom_check_empty_fields', 30, 3);

// Redirecionar usuários logados para /vitrine apenas quando acessarem a página inicial
function custom_redirect_logged_in_users_home() {
    if (is_user_logged_in() && is_front_page() && !is_admin()) {
        // Se o usuário estiver logado e acessar a página inicial, redireciona para /vitrine
        wp_redirect(home_url('/vitrine'));
        exit;
    }
}
add_action('template_redirect', 'custom_redirect_logged_in_users_home');