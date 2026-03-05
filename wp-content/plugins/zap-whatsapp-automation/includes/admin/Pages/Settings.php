<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Settings {

    public static function render() {

        if (!current_user_can('manage_options')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapwa_toggle_logging']) && check_admin_referer('zapwa_toggle_logging')) {
            $current = get_option('zapwa_logging_enabled', true);
            update_option('zapwa_logging_enabled', !$current);
            $enabled = !$current;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapwa_save_ai_settings']) && check_admin_referer('zapwa_save_ai_settings')) {
            if (!current_user_can('manage_options')) { return; }

            update_option('zapwa_openai_api_key',        sanitize_text_field(wp_unslash($_POST['zapwa_openai_api_key'] ?? '')));
            update_option('zapwa_gemini_api_key',         sanitize_text_field(wp_unslash($_POST['zapwa_gemini_api_key'] ?? '')));
            update_option('zapwa_elevenlabs_api_key',     sanitize_text_field(wp_unslash($_POST['zapwa_elevenlabs_api_key'] ?? '')));
            update_option('zapwa_instagram_access_token', sanitize_text_field(wp_unslash($_POST['zapwa_instagram_access_token'] ?? '')));
            update_option('zapwa_instagram_page_id',      sanitize_text_field(wp_unslash($_POST['zapwa_instagram_page_id'] ?? '')));
            $ai_saved = true;
        }

        $logging_enabled = (bool) get_option('zapwa_logging_enabled', true);
        ?>
        <div class="wrap zapwa-page">

            <!-- Page header -->
            <div class="zapwa-admin-header">
                <div>
                    <h1>⚙️ <?php esc_html_e( 'Configurações', 'zap-whatsapp-automation' ); ?></h1>
                    <p class="sub"><?php esc_html_e( 'Gerencie logs e preferências do plugin', 'zap-whatsapp-automation' ); ?></p>
                </div>
                <a href="<?php echo esc_url( admin_url('admin.php?page=zap-wa-metrics') ); ?>" class="zapwa-btn zapwa-btn-secondary" style="font-size:.85rem;">
                    ← <?php esc_html_e( 'Dashboard', 'zap-whatsapp-automation' ); ?>
                </a>
            </div>

            <?php if (isset($enabled)): ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo $enabled
                        ? '✅ ' . esc_html__( 'Logs ativados com sucesso.', 'zap-whatsapp-automation' )
                        : '🔕 ' . esc_html__( 'Logs desativados com sucesso.', 'zap-whatsapp-automation' ); ?></p>
                </div>
            <?php endif; ?>

            <!-- Logging card -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr">📋 <?php esc_html_e( 'Controle de Logs', 'zap-whatsapp-automation' ); ?></div>
                <div class="zapwa-card-body">
                    <p style="margin:0 0 14px;font-size:.9rem;color:#555;">
                        <?php esc_html_e( 'Os logs registram todas as etapas do processamento de mensagens (recebimento de eventos, envios, erros). Desativar os logs economiza espaço em disco.', 'zap-whatsapp-automation' ); ?>
                    </p>
                    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;padding:14px 18px;background:#f9fafa;border-radius:10px;border:1px solid #e8e8e8;">
                        <div style="display:flex;align-items:center;gap:12px;">
                            <span style="font-size:1.3rem;"><?php echo $logging_enabled ? '✅' : '❌'; ?></span>
                            <div>
                                <strong style="font-size:.95rem;color:#333;">
                                    <?php echo $logging_enabled
                                        ? esc_html__('Logs ATIVADOS', 'zap-whatsapp-automation')
                                        : esc_html__('Logs DESATIVADOS', 'zap-whatsapp-automation'); ?>
                                </strong>
                                <div style="font-size:.78rem;color:#888;margin-top:2px;">
                                    <?php esc_html_e( 'Status atual do sistema de logs', 'zap-whatsapp-automation' ); ?>
                                </div>
                            </div>
                        </div>
                        <form method="post">
                            <?php wp_nonce_field('zapwa_toggle_logging'); ?>
                            <input type="hidden" name="zapwa_toggle_logging" value="1">
                            <?php if ($logging_enabled): ?>
                                <button type="submit"
                                        class="zapwa-btn zapwa-btn-secondary"
                                        style="background:#c62828;border-color:#c62828;"
                                        onclick="return confirm('<?php echo esc_js(__('Desativar logs? Os logs existentes serão mantidos, mas nenhum novo registro será feito.', 'zap-whatsapp-automation')); ?>');">
                                    🔕 <?php esc_html_e( 'Desativar Logs', 'zap-whatsapp-automation' ); ?>
                                </button>
                            <?php else: ?>
                                <button type="submit" class="zapwa-btn zapwa-btn-primary">
                                    🔔 <?php esc_html_e( 'Ativar Logs', 'zap-whatsapp-automation' ); ?>
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

        </div>

        <!-- AI Settings card -->
        <div class="zapwa-card">
            <div class="zapwa-card-hdr">🤖 <?php esc_html_e('Configurações de IA', 'zap-whatsapp-automation'); ?></div>
            <div class="zapwa-card-body">
                <?php if (isset($ai_saved)): ?>
                    <div class="notice notice-success inline"><p>✅ <?php esc_html_e('Configurações de IA salvas.', 'zap-whatsapp-automation'); ?></p></div>
                <?php endif; ?>
                <form method="post">
                    <?php wp_nonce_field('zapwa_save_ai_settings'); ?>
                    <input type="hidden" name="zapwa_save_ai_settings" value="1">
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('OpenAI API Key', 'zap-whatsapp-automation'); ?></th>
                            <td><input type="password" name="zapwa_openai_api_key" class="regular-text" value="<?php echo esc_attr(get_option('zapwa_openai_api_key', '')); ?>" autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Gemini API Key', 'zap-whatsapp-automation'); ?></th>
                            <td><input type="password" name="zapwa_gemini_api_key" class="regular-text" value="<?php echo esc_attr(get_option('zapwa_gemini_api_key', '')); ?>" autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('ElevenLabs API Key', 'zap-whatsapp-automation'); ?></th>
                            <td><input type="password" name="zapwa_elevenlabs_api_key" class="regular-text" value="<?php echo esc_attr(get_option('zapwa_elevenlabs_api_key', '')); ?>" autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Instagram Access Token', 'zap-whatsapp-automation'); ?></th>
                            <td><input type="password" name="zapwa_instagram_access_token" class="regular-text" value="<?php echo esc_attr(get_option('zapwa_instagram_access_token', '')); ?>" autocomplete="off" /></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Instagram Page ID', 'zap-whatsapp-automation'); ?></th>
                            <td><input type="text" name="zapwa_instagram_page_id" class="regular-text" value="<?php echo esc_attr(get_option('zapwa_instagram_page_id', '')); ?>" /></td>
                        </tr>
                    </table>
                    <p class="submit"><button type="submit" class="zapwa-btn zapwa-btn-primary">💾 <?php esc_html_e('Salvar Configurações de IA', 'zap-whatsapp-automation'); ?></button></p>
                </form>
            </div>
        </div>

        </div>
        <?php
    }
}
