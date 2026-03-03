<?php
namespace ZapWA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {

    public static function init() {

        self::load_pages();
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    public static function enqueue_assets($hook) {

        $post_type   = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
        $page        = isset($_GET['page'])      ? sanitize_key($_GET['page'])      : '';
        $is_zapwa_pt = ($post_type === 'zapwa_message');
        $is_zapwa_pg = ($page !== '' && strpos($page, 'zap-wa') === 0);

        // Também aplica quando se está editando um post do CPT zapwa_message
        if (!$is_zapwa_pt && !$is_zapwa_pg) {
            $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
            if ($post_id > 0 && get_post_type($post_id) === 'zapwa_message') {
                $is_zapwa_pt = true;
            }
        }

        if (!$is_zapwa_pt && !$is_zapwa_pg) {
            return;
        }

        $url     = ZAP_WA_URL . 'assets/admin/';
        $version = '1.0.0';

        wp_enqueue_style(
            'zapwa-admin',
            $url . 'zapwa-admin.css',
            [],
            $version
        );

        wp_enqueue_script(
            'zapwa-admin',
            $url . 'zapwa-admin.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('zapwa-admin', 'zapwaAdmin', [
            'previewPlaceholder' => __('Escreva a mensagem acima para ver o preview...', 'zap-whatsapp-automation'),
        ]);
    }

    private static function load_pages() {

        $base = plugin_dir_path(__FILE__) . 'Pages/';

        $files = [
            'Connection.php',
            'Messages.php',
            'Logs.php',
            'QueuePage.php', // ✅ FILA
            'Metrics.php', // ✅ MÉTRICAS
            'Settings.php', // ✅ CONFIGURAÇÕES
        ];

        foreach ($files as $file) {
            $path = $base . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public static function register_menu() {

        if (!class_exists('ZapWA\\Admin\\Pages\\Connection')) {
            return;
        }

        // MENU PRINCIPAL - Deve abrir MÉTRICAS (dashboard)
        add_menu_page(
            'Zap WhatsApp Automation',
            'Zap WhatsApp',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render'],
            'dashicons-whatsapp',
            57
        );

        // SUBMENU MENSAGENS - Link direto para lista de mensagens
        add_submenu_page(
            'zap-wa-metrics',
            'Mensagens',
            'Mensagens',
            'manage_options',
            'edit.php?post_type=zapwa_message'
        );

        // SUBMENU CONEXÃO
        add_submenu_page(
            'zap-wa-metrics',
            'Conexão',
            'Conexão',
            'manage_options',
            'zap-wa-connection',
            ['ZapWA\\Admin\\Pages\\Connection', 'render']
        );

        if (get_option('zapwa_logging_enabled', true)) {
            add_submenu_page(
                'zap-wa-metrics',
                'Logs',
                'Logs',
                'manage_options',
                'zap-wa-logs',
                ['ZapWA\\Admin\\Pages\\Logs', 'render']
            );
        }

        add_submenu_page(
            'zap-wa-metrics',
            'Fila',
            'Fila',
            'manage_options',
            'zap-wa-queue',
            ['ZapWA\\Admin\\Pages\\QueuePage', 'render']
        );

        add_submenu_page(
            'zap-wa-metrics',
            'Métricas',
            'Métricas',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render']
        );

        add_submenu_page(
            'zap-wa-metrics',
            'Configurações',
            'Configurações',
            'manage_options',
            'zap-wa-settings',
            ['ZapWA\\Admin\\Pages\\Settings', 'render']
        );
    }
}
