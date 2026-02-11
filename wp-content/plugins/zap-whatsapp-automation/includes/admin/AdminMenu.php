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
            'History.php',
            'Metrics.php', // ✅ MÉTRICAS
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

        add_submenu_page(
            'zap-wa-metrics',
            'Logs',
            'Logs',
            'manage_options',
            'zap-wa-logs',
            ['ZapWA\\Admin\\Pages\\Logs', 'render']
        );

        add_submenu_page(
            'zap-wa-metrics',
            'Histórico',
            'Histórico',
            'manage_options',
            'zap-wa-history',
            ['ZapWA\\Admin\\Pages\\History', 'render']
        );

        // Remover o primeiro submenu duplicado (Métricas aparece duas vezes)
        add_submenu_page(
            'zap-wa-metrics',
            'Métricas',
            'Métricas',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render']
        );
    }
}
