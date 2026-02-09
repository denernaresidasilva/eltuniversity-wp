<?php
/**
 * Plugin Name: Zap WhatsApp Automation
 */

if (!defined('ABSPATH')) exit;

define('ZAP_WA_PATH', plugin_dir_path(__FILE__));

// Installer
require_once ZAP_WA_PATH . 'includes/Installer.php';
register_activation_hook(__FILE__, ['ZapWA\Installer', 'activate']);
register_deactivation_hook(__FILE__, ['ZapWA\Installer', 'deactivate']);

// Loader
require_once ZAP_WA_PATH . 'includes/Loader.php';
add_action('plugins_loaded', ['ZapWA\Loader', 'init']);
