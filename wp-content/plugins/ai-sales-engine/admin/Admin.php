<?php
/**
 * Registers the admin menu and enqueues admin assets.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin {

    public static function init(): void {
        add_action( 'admin_menu',            [ self::class, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
    }

    /**
     * Register the top-level menu and all sub-menus.
     */
    public static function register_menu(): void {
        add_menu_page(
            __( 'IA Vendas', 'ai-sales-engine' ),
            __( 'IA Vendas', 'ai-sales-engine' ),
            'manage_options',
            'ai-sales-engine',
            [ Dashboard::class, 'render' ],
            'dashicons-chart-line',
            30
        );

        $pages = [
            'ai-sales-engine'            => [ __( 'Painel',        'ai-sales-engine' ), [ Dashboard::class,   'render' ] ],
            'ai-sales-leads'             => [ __( 'Leads',         'ai-sales-engine' ), [ Leads::class,        'render' ] ],
            'ai-sales-lists'             => [ __( 'Listas',        'ai-sales-engine' ), [ Lists::class,        'render' ] ],
            'ai-sales-automations'       => [ __( 'Automações',    'ai-sales-engine' ), [ Automations::class,  'render' ] ],
            'ai-sales-agents'            => [ __( 'Agentes',       'ai-sales-engine' ), [ Agents::class,       'render' ] ],
            'ai-sales-pipelines'         => [ __( 'Pipelines',     'ai-sales-engine' ), [ Pipelines::class,    'render' ] ],
            'ai-sales-analytics'         => [ __( 'Relatórios',    'ai-sales-engine' ), [ Analytics::class,    'render' ] ],
            'ai-sales-settings'          => [ __( 'Configurações', 'ai-sales-engine' ), [ Settings::class,     'render' ] ],
        ];

        foreach ( $pages as $slug => [ $label, $callback ] ) {
            add_submenu_page(
                'ai-sales-engine',
                $label,
                $label,
                'manage_options',
                $slug,
                $callback
            );
        }
    }

    /**
     * Enqueue CSS/JS on AI Sales Engine admin pages.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'ai-sales' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'ai-sales-admin',
            AI_SALES_ENGINE_URL . 'assets/css/admin.css',
            [],
            AI_SALES_ENGINE_VERSION
        );

        // Chart.js from CDN for analytics/dashboard charts.
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
            [],
            '4.4.3',
            true
        );

        wp_enqueue_script(
            'ai-sales-admin',
            AI_SALES_ENGINE_URL . 'assets/js/admin.js',
            [ 'chartjs' ],
            AI_SALES_ENGINE_VERSION,
            true
        );

        wp_localize_script(
            'ai-sales-admin',
            'AISalesAdmin',
            [
                'rest_url' => esc_url( rest_url( 'ai-sales/v1/' ) ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'i18n'     => [
                    'semDados'          => __( 'Sem dados.', 'ai-sales-engine' ),
                    'nenhumLead'        => __( 'Nenhum lead encontrado.', 'ai-sales-engine' ),
                    'nenhumaLista'      => __( 'Nenhuma lista encontrada.', 'ai-sales-engine' ),
                    'nenhumaAutomacao'  => __( 'Nenhuma automação encontrada.', 'ai-sales-engine' ),
                    'nenhumAgente'      => __( 'Nenhum agente cadastrado.', 'ai-sales-engine' ),
                    'semFuncao'         => __( 'Sem função definida', 'ai-sales-engine' ),
                    'confirmarExcluir'  => __( 'Excluir este lead?', 'ai-sales-engine' ),
                    'excluir'           => __( 'Excluir', 'ai-sales-engine' ),
                    'editar'            => __( 'Editar', 'ai-sales-engine' ),
                    'eventos'           => __( 'Eventos', 'ai-sales-engine' ),
                    'leads'             => __( 'Leads', 'ai-sales-engine' ),
                    'selecionePipeline' => __( 'Selecione um pipeline para ver o quadro.', 'ai-sales-engine' ),
                    'nenhumPipeline'    => __( 'Nenhum pipeline cadastrado.', 'ai-sales-engine' ),
                    'nenhumEstagio'     => __( 'Nenhuma etapa nesta fase.', 'ai-sales-engine' ),
                    'carregando'        => __( 'Carregando…', 'ai-sales-engine' ),
                    'salvando'          => __( 'Salvando…', 'ai-sales-engine' ),
                    'salvoSucesso'      => __( 'Salvo com sucesso!', 'ai-sales-engine' ),
                    'erroSalvar'        => __( 'Erro ao salvar. Tente novamente.', 'ai-sales-engine' ),
                    'confirmarExcluirAutomacao' => __( 'Excluir esta automação?', 'ai-sales-engine' ),
                    'confirmarExcluirAgente'    => __( 'Excluir este agente?', 'ai-sales-engine' ),
                    'confirmarExcluirLista'     => __( 'Excluir esta lista?', 'ai-sales-engine' ),
                    // Status labels
                    'statusAtivo'       => __( 'Ativo', 'ai-sales-engine' ),
                    'statusInativo'     => __( 'Inativo', 'ai-sales-engine' ),
                    'statusPendente'    => __( 'Pendente', 'ai-sales-engine' ),
                    'statusFalhou'      => __( 'Falhou', 'ai-sales-engine' ),
                    // Trigger labels
                    'trigPageVisit'         => __( 'Visita à Página', 'ai-sales-engine' ),
                    'trigMessageReply'      => __( 'Resposta de Mensagem', 'ai-sales-engine' ),
                    'trigWebinarCompleted'  => __( 'Webinar Concluído', 'ai-sales-engine' ),
                    'trigPurchaseCompleted' => __( 'Compra Concluída', 'ai-sales-engine' ),
                ],
            ]
        );
    }
}
