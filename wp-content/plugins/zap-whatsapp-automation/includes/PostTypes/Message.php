<?php
namespace ZapWA\PostTypes;

if (!defined('ABSPATH')) {
    exit;
}

class Message {

    /**
     * Registra o Post Type zapwa_message
     */
    public static function register() {

        register_post_type('zapwa_message', [
            'labels' => [
                'name'          => 'Mensagens WhatsApp',
                'singular_name' => 'Mensagem WhatsApp',
                'add_new'       => 'Nova Mensagem',
                'add_new_item'  => 'Adicionar Nova Mensagem',
                'edit_item'     => 'Editar Mensagem',
            ],
            'public'        => false,
            'show_ui'       => true,   // USAR UI DO WP
            'show_in_menu'  => false,  // controlado pelo menu Zap WhatsApp
            'supports'      => ['title', 'editor'],
            'capability_type' => 'post',
            'rewrite'       => false,
            'query_var'     => false,
        ]);
    }
}
