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
            'Settings.php',
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

        if (!class_exists('ZapWA\\Admin\\Pages\\Settings')) {
            return;
        }

        add_menu_page(
            'Zap WhatsApp Automation',
            'Zap WhatsApp',
            'manage_options',
            'zap-wa',
            ['ZapWA\\Admin\\Pages\\Settings', 'render'],
            'dashicons-whatsapp',
            57
        );

        add_submenu_page(
            'zap-wa',
            'Conexão',
            'Conexão WhatsApp',
            'manage_options',
            'zap-wa-connection',
            ['ZapWA\\Admin\\Pages\\Connection', 'render']
        );

        add_submenu_page(
            'zap-wa',
            'Configurações',
            'Configurações',
            'manage_options',
            'zap-wa',
            ['ZapWA\\Admin\\Pages\\Settings', 'render']
        );

        add_submenu_page(
            'zap-wa',
            'Mensagens',
            'Mensagens',
            'manage_options',
            'zap-wa-messages',
            ['ZapWA\\Admin\\Pages\\Messages', 'render']
        );

        add_submenu_page(
            'zap-wa',
            'Logs',
            'Logs',
            'manage_options',
            'zap-wa-logs',
            ['ZapWA\\Admin\\Pages\\Logs', 'render']
        );

        add_submenu_page(
            'zap-wa',
            'Histórico',
            'Histórico',
            'manage_options',
            'zap-wa-history',
            ['ZapWA\\Admin\\Pages\\History', 'render']
        );

        add_submenu_page(
            'zap-wa',
            'Métricas',
            'Métricas',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render']
        );
    }
}
