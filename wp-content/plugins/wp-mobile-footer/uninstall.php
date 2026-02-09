<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remover as opções do banco de dados
delete_option('footer_enabled');
delete_option('footer_background_color');
delete_option('footer_text_color');
delete_option('footer_hover_color');
delete_option('footer_font_family');
delete_option('footer_global_status');
delete_option('footer_page_visibility');
delete_option('footer_show_for_users');
