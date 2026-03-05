<?php
namespace ZapWA\Flows;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registers the automation_flow Custom Post Type.
 */
class Flow_CPT {

    public static function register() {

        register_post_type('automation_flow', [
            'labels' => [
                'name'               => __('Fluxos de Automação', 'zap-whatsapp-automation'),
                'singular_name'      => __('Fluxo', 'zap-whatsapp-automation'),
                'add_new'            => __('Novo Fluxo', 'zap-whatsapp-automation'),
                'add_new_item'       => __('Adicionar Novo Fluxo', 'zap-whatsapp-automation'),
                'edit_item'          => __('Editar Fluxo', 'zap-whatsapp-automation'),
                'new_item'           => __('Novo Fluxo', 'zap-whatsapp-automation'),
                'view_item'          => __('Ver Fluxo', 'zap-whatsapp-automation'),
                'search_items'       => __('Buscar Fluxos', 'zap-whatsapp-automation'),
                'not_found'          => __('Nenhum fluxo encontrado', 'zap-whatsapp-automation'),
                'not_found_in_trash' => __('Nenhum fluxo na lixeira', 'zap-whatsapp-automation'),
            ],
            'public'       => false,
            'show_ui'      => false,
            'show_in_menu' => false,
            'show_in_rest' => false,
            'supports'     => ['title'],
            'rewrite'      => false,
            'query_var'    => false,
        ]);
    }
}
