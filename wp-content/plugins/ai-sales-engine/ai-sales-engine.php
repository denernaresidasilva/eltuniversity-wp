<?php
/**
 * Plugin Name: AI Sales Engine
 * Plugin URI:  https://eltuniversity.com
 * Description: Plataforma completa de automação de vendas com IA, CRM, pipelines e agentes de IA.
 * Version:     1.0.0
 * Author:      ELT University
 * Author URI:  https://eltuniversity.com
 * Text Domain: ai-sales-engine
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'AI_SALES_ENGINE_VERSION', '1.0.0' );
define( 'AI_SALES_ENGINE_PATH',    plugin_dir_path( __FILE__ ) );
define( 'AI_SALES_ENGINE_URL',     plugin_dir_url( __FILE__ ) );
define( 'AI_SALES_ENGINE_FILE',    __FILE__ );

require_once AI_SALES_ENGINE_PATH . 'includes/Installer.php';
require_once AI_SALES_ENGINE_PATH . 'includes/Loader.php';

register_activation_hook(   __FILE__, [ 'AISalesEngine\\Installer', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'AISalesEngine\\Installer', 'deactivate' ] );

add_action( 'plugins_loaded', [ 'AISalesEngine\\Loader', 'init' ] );
