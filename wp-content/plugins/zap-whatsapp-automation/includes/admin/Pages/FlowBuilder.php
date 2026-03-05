<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page: visual flow builder.
 */
class FlowBuilder {

    public static function render() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Sem permissão', 'zap-whatsapp-automation'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $flow_id = isset($_GET['flow_id']) ? absint($_GET['flow_id']) : 0;

        $flow_title     = '';
        $flow_status    = 'inactive';
        $flow_trigger   = '';
        $flow_structure = wp_json_encode(['nodes' => [], 'edges' => []]);

        if ($flow_id > 0) {
            $post = get_post($flow_id);
            if ($post && $post->post_type === 'automation_flow') {
                $flow_title   = $post->post_title;
                $flow_status  = get_post_meta($flow_id, '_flow_status', true) ?: 'inactive';
                $flow_trigger = get_post_meta($flow_id, '_flow_trigger', true) ?: '';
                $raw          = get_post_meta($flow_id, '_flow_structure', true);
                if ($raw) {
                    $flow_structure = $raw;
                }
            }
        }

        $flows_url = esc_url(admin_url('admin.php?page=zap-wa-flows'));

        $trigger_types = apply_filters('zapwa_flow_trigger_types', [
            'course_enrolled'    => __('Inscrição em Curso', 'zap-whatsapp-automation'),
            'course_completed'   => __('Conclusão de Curso', 'zap-whatsapp-automation'),
            'lesson_completed'   => __('Aula Concluída', 'zap-whatsapp-automation'),
            'user_registered'    => __('Novo Cadastro', 'zap-whatsapp-automation'),
            'tag_added'          => __('Tag Adicionada', 'zap-whatsapp-automation'),
            'tag_removed'        => __('Tag Removida', 'zap-whatsapp-automation'),
            'payment_complete'   => __('Pagamento Confirmado', 'zap-whatsapp-automation'),
            'link_clicked'       => __('Link Clicado', 'zap-whatsapp-automation'),
            'video_completed'    => __('Vídeo Assistido', 'zap-whatsapp-automation'),
            'instagram_comment'  => __('Comentário no Instagram', 'zap-whatsapp-automation'),
            'instagram_dm'       => __('DM no Instagram', 'zap-whatsapp-automation'),
            'webhook_received'   => __('Webhook Recebido', 'zap-whatsapp-automation'),
        ]);
        ?>
        <div class="wrap zapwa-page zapwa-builder-page" id="zapwa-flow-builder-wrap">

            <!-- Builder toolbar -->
            <div class="zapwa-builder-toolbar">
                <div class="zapwa-builder-toolbar__left">
                    <a href="<?php echo $flows_url; ?>" class="zapwa-btn zapwa-btn-ghost zapwa-builder-back">
                        ← <?php esc_html_e('Fluxos', 'zap-whatsapp-automation'); ?>
                    </a>
                    <input type="text"
                           id="zapwa-flow-title"
                           class="zapwa-builder-title"
                           value="<?php echo esc_attr($flow_title ?: __('Novo Fluxo', 'zap-whatsapp-automation')); ?>"
                           placeholder="<?php esc_attr_e('Nome do Fluxo', 'zap-whatsapp-automation'); ?>" />
                </div>
                <div class="zapwa-builder-toolbar__center">
                    <span class="zapwa-builder-toolbar__label"><?php esc_html_e('Gatilho:', 'zap-whatsapp-automation'); ?></span>
                    <select id="zapwa-flow-trigger" class="zapwa-builder-select">
                        <option value=""><?php esc_html_e('— Selecione —', 'zap-whatsapp-automation'); ?></option>
                        <?php foreach ($trigger_types as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($flow_trigger, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label class="zapwa-builder-status-toggle">
                        <input type="checkbox" id="zapwa-flow-status" <?php checked($flow_status, 'active'); ?> />
                        <span class="zapwa-toggle-label"><?php esc_html_e('Ativo', 'zap-whatsapp-automation'); ?></span>
                    </label>
                </div>
                <div class="zapwa-builder-toolbar__right">
                    <button type="button" id="zapwa-zoom-in"  class="zapwa-btn zapwa-btn-icon" title="<?php esc_attr_e('Zoom +', 'zap-whatsapp-automation'); ?>">＋</button>
                    <button type="button" id="zapwa-zoom-out" class="zapwa-btn zapwa-btn-icon" title="<?php esc_attr_e('Zoom -', 'zap-whatsapp-automation'); ?>">－</button>
                    <button type="button" id="zapwa-zoom-reset" class="zapwa-btn zapwa-btn-icon" title="<?php esc_attr_e('Resetar zoom', 'zap-whatsapp-automation'); ?>">⊙</button>
                    <button type="button" id="zapwa-save-flow" class="zapwa-btn zapwa-btn-primary">
                        💾 <?php esc_html_e('Salvar', 'zap-whatsapp-automation'); ?>
                    </button>
                </div>
            </div>

            <!-- Node palette -->
            <div class="zapwa-builder-layout">
                <div class="zapwa-builder-palette" id="zapwa-node-palette">
                    <div class="zapwa-palette-title"><?php esc_html_e('Blocos', 'zap-whatsapp-automation'); ?></div>

                    <div class="zapwa-palette-node" draggable="true" data-node-type="trigger">
                        <span class="zapwa-node-icon">⚡</span>
                        <span><?php esc_html_e('Gatilho', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <div class="zapwa-palette-node" draggable="true" data-node-type="send_whatsapp">
                        <span class="zapwa-node-icon">💬</span>
                        <span><?php esc_html_e('Enviar WhatsApp', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <div class="zapwa-palette-node" draggable="true" data-node-type="send_email">
                        <span class="zapwa-node-icon">✉️</span>
                        <span><?php esc_html_e('Enviar Email', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <div class="zapwa-palette-node" draggable="true" data-node-type="delay">
                        <span class="zapwa-node-icon">⏳</span>
                        <span><?php esc_html_e('Delay', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <div class="zapwa-palette-node" draggable="true" data-node-type="condition">
                        <span class="zapwa-node-icon">🔀</span>
                        <span><?php esc_html_e('Condição', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <div class="zapwa-palette-node" draggable="true" data-node-type="end">
                        <span class="zapwa-node-icon">🏁</span>
                        <span><?php esc_html_e('Fim', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <!-- Add Tag -->
                    <div class="zapwa-palette-node" draggable="true" data-node-type="add_tag">
                        <span class="zapwa-node-icon">🏷️</span>
                        <span><?php esc_html_e('Adicionar Tag', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <!-- Remove Tag -->
                    <div class="zapwa-palette-node" draggable="true" data-node-type="remove_tag">
                        <span class="zapwa-node-icon">🗑️</span>
                        <span><?php esc_html_e('Remover Tag', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <!-- Webhook -->
                    <div class="zapwa-palette-node" draggable="true" data-node-type="webhook">
                        <span class="zapwa-node-icon">🌐</span>
                        <span><?php esc_html_e('Webhook', 'zap-whatsapp-automation'); ?></span>
                    </div>
                    <!-- AI Agent -->
                    <div class="zapwa-palette-node" draggable="true" data-node-type="ai_agent">
                        <span class="zapwa-node-icon">🤖</span>
                        <span><?php esc_html_e('Agente IA', 'zap-whatsapp-automation'); ?></span>
                    </div>

                    <div class="zapwa-palette-hint">
                        <?php esc_html_e('Arraste os blocos para o canvas', 'zap-whatsapp-automation'); ?>
                    </div>
                </div>

                <!-- Canvas -->
                <div class="zapwa-builder-canvas-wrap" id="zapwa-canvas-wrap">
                    <div class="zapwa-builder-canvas" id="zapwa-canvas">
                        <svg class="zapwa-edges-svg" id="zapwa-edges-svg"></svg>
                        <div class="zapwa-nodes-layer" id="zapwa-nodes-layer"></div>
                    </div>
                </div>

                <!-- Node settings panel -->
                <div class="zapwa-builder-settings" id="zapwa-node-settings" style="display:none;">
                    <div class="zapwa-settings-header">
                        <span id="zapwa-settings-title"><?php esc_html_e('Configurações do Bloco', 'zap-whatsapp-automation'); ?></span>
                        <button type="button" id="zapwa-settings-close" class="zapwa-settings-close">✕</button>
                    </div>
                    <div class="zapwa-settings-body" id="zapwa-settings-body"></div>
                </div>
            </div>

            <!-- Save feedback -->
            <div id="zapwa-save-notice" class="zapwa-save-notice" style="display:none;"></div>

        </div>

        <!-- Inline data for JS -->
        <script type="text/javascript">
        window.zapwaFlowBuilderData = <?php echo wp_json_encode([
            'flowId'       => $flow_id,
            'flowTitle'    => $flow_title,
            'flowStatus'   => $flow_status,
            'flowTrigger'  => $flow_trigger,
            'structure'    => json_decode($flow_structure, true),
            'restUrl'      => esc_url_raw(rest_url('zapwa/v1/flows')),
            'restNonce'    => wp_create_nonce('wp_rest'),
            'triggerTypes' => $trigger_types,
            'i18n'         => [
                'saved'          => __('Fluxo salvo com sucesso!', 'zap-whatsapp-automation'),
                'saveError'      => __('Erro ao salvar o fluxo. Tente novamente.', 'zap-whatsapp-automation'),
                'deleteConfirm'  => __('Remover este bloco?', 'zap-whatsapp-automation'),
                'connectHelp'    => __('Clique em um pino de saída para iniciar uma conexão', 'zap-whatsapp-automation'),
            ],
            'nodeConfigs'  => [
                'trigger' => [
                    'label'  => __('Gatilho', 'zap-whatsapp-automation'),
                    'icon'   => '⚡',
                    'color'  => '#10b981',
                    'fields' => [
                        ['name' => 'trigger_type', 'label' => 'Tipo de Gatilho', 'type' => 'select', 'options' => $trigger_types],
                    ],
                ],
                'send_whatsapp' => [
                    'label'  => __('Enviar WhatsApp', 'zap-whatsapp-automation'),
                    'icon'   => '💬',
                    'color'  => '#25d366',
                    'fields' => [
                        ['name' => 'message', 'label' => 'Mensagem', 'type' => 'textarea'],
                    ],
                ],
                'send_email' => [
                    'label'  => __('Enviar Email', 'zap-whatsapp-automation'),
                    'icon'   => '✉️',
                    'color'  => '#3b82f6',
                    'fields' => [
                        ['name' => 'subject', 'label' => 'Assunto', 'type' => 'text'],
                        ['name' => 'body',    'label' => 'Corpo',   'type' => 'textarea'],
                    ],
                ],
                'delay' => [
                    'label'  => __('Delay', 'zap-whatsapp-automation'),
                    'icon'   => '⏳',
                    'color'  => '#f59e0b',
                    'fields' => [
                        ['name' => 'delay_amount', 'label' => 'Quantidade', 'type' => 'number'],
                        ['name' => 'delay_unit',   'label' => 'Unidade',    'type' => 'select', 'options' => ['seconds' => 'Segundos', 'minutes' => 'Minutos', 'hours' => 'Horas', 'days' => 'Dias']],
                    ],
                ],
                'condition' => [
                    'label'  => __('Condição', 'zap-whatsapp-automation'),
                    'icon'   => '🔀',
                    'color'  => '#8b5cf6',
                    'fields' => [
                        ['name' => 'condition_type', 'label' => 'Tipo', 'type' => 'select', 'options' => [
                            'has_tag'          => 'Tem Tag',
                            'course_enrolled'  => 'Inscrito em Curso',
                            'course_completed' => 'Curso Concluído',
                            'link_clicked'     => 'Clicou em Link',
                            'video_watched'    => 'Assistiu Vídeo',
                        ]],
                        ['name' => 'value', 'label' => 'Valor', 'type' => 'text'],
                    ],
                ],
                'add_tag' => [
                    'label'  => __('Adicionar Tag', 'zap-whatsapp-automation'),
                    'icon'   => '🏷️',
                    'color'  => '#059669',
                    'fields' => [
                        ['name' => 'tag', 'label' => 'Tag (slug)', 'type' => 'text'],
                    ],
                ],
                'remove_tag' => [
                    'label'  => __('Remover Tag', 'zap-whatsapp-automation'),
                    'icon'   => '🗑️',
                    'color'  => '#dc2626',
                    'fields' => [
                        ['name' => 'tag', 'label' => 'Tag (slug)', 'type' => 'text'],
                    ],
                ],
                'webhook' => [
                    'label'  => __('Webhook', 'zap-whatsapp-automation'),
                    'icon'   => '🌐',
                    'color'  => '#0891b2',
                    'fields' => [
                        ['name' => 'url',    'label' => 'URL',          'type' => 'text'],
                        ['name' => 'method', 'label' => 'Método',       'type' => 'select', 'options' => ['POST' => 'POST', 'GET' => 'GET', 'PUT' => 'PUT', 'PATCH' => 'PATCH']],
                        ['name' => 'body',   'label' => 'Body (JSON)',  'type' => 'textarea'],
                    ],
                ],
                'ai_agent' => [
                    'label'  => __('Agente IA', 'zap-whatsapp-automation'),
                    'icon'   => '🤖',
                    'color'  => '#7c3aed',
                    'fields' => [
                        ['name' => 'agent_id', 'label' => 'ID do Agente', 'type' => 'number'],
                        ['name' => 'prompt',   'label' => 'Prompt',       'type' => 'textarea'],
                    ],
                ],
                'end' => [
                    'label'  => __('Fim', 'zap-whatsapp-automation'),
                    'icon'   => '🏁',
                    'color'  => '#64748b',
                    'fields' => [],
                ],
            ],
        ]); ?>;
        </script>
        <?php
    }
}
