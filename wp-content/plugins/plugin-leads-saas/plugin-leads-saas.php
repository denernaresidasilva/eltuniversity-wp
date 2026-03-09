<?php
/**
 * Plugin Name: Gerenciador de Leads SaaS
 * Plugin URI:  https://github.com/denernaresidasilva/eltuniversity-wp
 * Description: Sistema completo de gerenciamento de leads, automação e formulários avançados estilo SaaS.
 * Version:     1.0.0
 * Author:      ELT University
 * Text Domain: plugin-leads-saas
 * Domain Path: /languages
 * Requires at least: 5.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LEADS_SAAS_VERSION',     '1.0.0' );
define( 'LEADS_SAAS_FILE',        __FILE__ );
define( 'LEADS_SAAS_DIR',         plugin_dir_path( __FILE__ ) );
define( 'LEADS_SAAS_URL',         plugin_dir_url( __FILE__ ) );
define( 'LEADS_SAAS_BASENAME',    plugin_basename( __FILE__ ) );
define( 'LEADS_SAAS_DB_VERSION',  '1.0.0' );

require_once LEADS_SAAS_DIR . 'includes/Installer.php';
require_once LEADS_SAAS_DIR . 'includes/Loader.php';

register_activation_hook( __FILE__, [ 'LeadsSaaS\\Installer', 'install' ] );

LeadsSaaS\Loader::init();
