<?php
/*
Plugin Name: Supermembros - Bloqueio de Cursos
Description: Plugin para Bloquear os Cursos para Usuários não Matriculados
Version: 2.0
Author: Raul Julio da Cruz
*/

// Bloqueia acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Inclui os arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/class-redirect-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-meta-box-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-save-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-logger.php';

class TutorLMSRedirect {
    public function __construct() {
        $this->logger = new Logger(); // Inicializa o logger 
        $this->init_hooks(); // Inicializa os hooks necessários 
    }

    private function init_hooks() {
        // Adiciona menu de administração 
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // Inicializa manipuladores 
        new MetaBoxHandler();
        new RedirectHandler($this->logger); // Passa o logger para o manipulador de redirecionamento 
        new SaveHandler($this->logger); // Passa o logger para o manipulador de salvamento 

        // Carrega scripts e estilos 
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    // Adiciona o menu ao painel de administração 
    public function add_admin_menu() {
        add_menu_page(
            __('Bloqueio de Cursos', 'tutor-lms-redirect'),
            __('Bloqueio de Cursos', 'tutor-lms-redirect'),
            'manage_options',
            'tutor-lms-redirect',
            [$this, 'admin_page'],
            'dashicons-lock', // Ícone de bloqueio 
            6
        );
    }

    // Renderiza a página de administração 
    public function admin_page() {
        include plugin_dir_path(__FILE__) . 'admin/admin-page.php';
    }

    // Carrega scripts e estilos para a página de administração 
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_tutor-lms-redirect') {
            return;
        }
        wp_enqueue_style('tutor-lms-redirect-admin', plugins_url('admin/css/admin-style.css', __FILE__));
        wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css'); // Adiciona Bootstrap CSS 
        wp_enqueue_script('bootstrap-js', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js', ['jquery'], null, true); // Adiciona Bootstrap JS 
        wp_enqueue_script('tutor-lms-redirect-admin', plugins_url('admin/js/admin-script.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('tutor-lms-redirect-admin', 'ajaxurl', admin_url('admin-ajax.php'));
    }
}

// Inicializa o plugin 
new TutorLMSRedirect();
