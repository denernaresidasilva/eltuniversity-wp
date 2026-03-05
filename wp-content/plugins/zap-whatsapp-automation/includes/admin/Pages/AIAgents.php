<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class AIAgents {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sem permissão', 'zap-whatsapp-automation'));
        }

        if (!class_exists('\ZapWA\AI_Agent')) {
            echo '<div class="wrap"><p>' . esc_html__('Módulo de IA não disponível.', 'zap-whatsapp-automation') . '</p></div>';
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_ai_agents';
        $action_notice = '';

        // Handle create / delete
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['zapwa_ai_action'])
            && check_admin_referer('zapwa_ai_agent_action')
        ) {
            if (!current_user_can('manage_options')) { return; }
            $ai_action = sanitize_key(wp_unslash($_POST['zapwa_ai_action']));

            if ($ai_action === 'create') {
                $wpdb->insert($table, [
                    'name'           => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
                    'provider'       => sanitize_key(wp_unslash($_POST['provider'] ?? 'openai')),
                    'model'          => sanitize_text_field(wp_unslash($_POST['model'] ?? 'gpt-4o-mini')),
                    'system_prompt'  => sanitize_textarea_field(wp_unslash($_POST['system_prompt'] ?? '')),
                    'temperature'    => min(2, max(0, (float) ($_POST['temperature'] ?? 0.7))),
                    'memory_enabled' => isset($_POST['memory_enabled']) ? 1 : 0,
                    'voice_enabled'  => isset($_POST['voice_enabled']) ? 1 : 0,
                    'created_at'     => current_time('mysql'),
                ], ['%s','%s','%s','%s','%f','%d','%d','%s']);
                $action_notice = esc_html__('Agente criado com sucesso.', 'zap-whatsapp-automation');
            } elseif ($ai_action === 'delete') {
                $agent_id = absint($_POST['agent_id'] ?? 0);
                if ($agent_id) {
                    $wpdb->delete($table, ['id' => $agent_id], ['%d']);
                    $action_notice = esc_html__('Agente excluído.', 'zap-whatsapp-automation');
                }
            }
        }

        $agents = \ZapWA\AI_Agent::get_all_agents();
        ?>
        <div class="wrap zapwa-page">
            <div class="zapwa-admin-header">
                <div>
                    <h1>🤖 <?php esc_html_e('Agentes de IA', 'zap-whatsapp-automation'); ?></h1>
                    <p class="sub"><?php esc_html_e('Configure agentes de IA para automações inteligentes', 'zap-whatsapp-automation'); ?></p>
                </div>
            </div>

            <?php if ($action_notice): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($action_notice); ?></p></div>
            <?php endif; ?>

            <!-- Create new agent -->
            <div class="zapwa-card" style="margin-bottom:20px;">
                <div class="zapwa-card-hdr">➕ <?php esc_html_e('Novo Agente', 'zap-whatsapp-automation'); ?></div>
                <div class="zapwa-card-body">
                    <form method="post">
                        <?php wp_nonce_field('zapwa_ai_agent_action'); ?>
                        <input type="hidden" name="zapwa_ai_action" value="create">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Nome', 'zap-whatsapp-automation'); ?></th>
                                <td><input type="text" name="name" required class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Provedor', 'zap-whatsapp-automation'); ?></th>
                                <td>
                                    <select name="provider">
                                        <option value="openai">OpenAI</option>
                                        <option value="gemini">Google Gemini</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Modelo', 'zap-whatsapp-automation'); ?></th>
                                <td><input type="text" name="model" value="gpt-4o-mini" class="regular-text" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('System Prompt', 'zap-whatsapp-automation'); ?></th>
                                <td><textarea name="system_prompt" rows="4" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Temperature', 'zap-whatsapp-automation'); ?></th>
                                <td><input type="number" name="temperature" value="0.7" min="0" max="2" step="0.1" /></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Memória', 'zap-whatsapp-automation'); ?></th>
                                <td><label><input type="checkbox" name="memory_enabled" value="1" /> <?php esc_html_e('Habilitar memória de conversa', 'zap-whatsapp-automation'); ?></label></td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Voz (ElevenLabs)', 'zap-whatsapp-automation'); ?></th>
                                <td><label><input type="checkbox" name="voice_enabled" value="1" /> <?php esc_html_e('Gerar áudio da resposta', 'zap-whatsapp-automation'); ?></label></td>
                            </tr>
                        </table>
                        <p><button type="submit" class="zapwa-btn zapwa-btn-primary">➕ <?php esc_html_e('Criar Agente', 'zap-whatsapp-automation'); ?></button></p>
                    </form>
                </div>
            </div>

            <!-- Agents list -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr">🤖 <?php esc_html_e('Agentes Configurados', 'zap-whatsapp-automation'); ?></div>
                <div class="zapwa-card-body" style="padding:0;">
                    <?php if (empty($agents)): ?>
                        <p style="padding:20px;text-align:center;color:#888;"><?php esc_html_e('Nenhum agente configurado ainda.', 'zap-whatsapp-automation'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped" style="border:none;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?php esc_html_e('Nome', 'zap-whatsapp-automation'); ?></th>
                                    <th><?php esc_html_e('Provedor', 'zap-whatsapp-automation'); ?></th>
                                    <th><?php esc_html_e('Modelo', 'zap-whatsapp-automation'); ?></th>
                                    <th><?php esc_html_e('Memória', 'zap-whatsapp-automation'); ?></th>
                                    <th><?php esc_html_e('Voz', 'zap-whatsapp-automation'); ?></th>
                                    <th><?php esc_html_e('Ações', 'zap-whatsapp-automation'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent): ?>
                                    <tr>
                                        <td><?php echo esc_html((string)$agent->id); ?></td>
                                        <td><strong><?php echo esc_html($agent->name); ?></strong></td>
                                        <td><?php echo esc_html(strtoupper($agent->provider)); ?></td>
                                        <td><code><?php echo esc_html($agent->model); ?></code></td>
                                        <td><?php echo $agent->memory_enabled ? '✅' : '❌'; ?></td>
                                        <td><?php echo $agent->voice_enabled ? '✅' : '❌'; ?></td>
                                        <td>
                                            <form method="post" style="display:inline;" onsubmit="return confirm('<?php echo esc_js(__('Excluir este agente?', 'zap-whatsapp-automation')); ?>');">
                                                <?php wp_nonce_field('zapwa_ai_agent_action'); ?>
                                                <input type="hidden" name="zapwa_ai_action" value="delete">
                                                <input type="hidden" name="agent_id" value="<?php echo esc_attr((string)$agent->id); ?>">
                                                <button type="submit" class="zapwa-btn zapwa-btn-secondary" style="background:#dc2626;border-color:#dc2626;color:#fff;padding:4px 10px;font-size:.8em;">
                                                    🗑️ <?php esc_html_e('Excluir', 'zap-whatsapp-automation'); ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
