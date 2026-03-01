<?php
namespace ZapWA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {

    public static function init() {

        self::load_pages();
        add_action('admin_menu', [self::class, 'register_menu']);
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
