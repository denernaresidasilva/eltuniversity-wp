<?php
namespace ZapWA\Admin\Pages;

use ZapWA\ConnectionManager;

if (!defined('ABSPATH')) {
    exit;
}

class Connection {

    public static function render() {
        global $wpdb;

        // Save settings
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapwa_save_settings']) && check_admin_referer('zapwa_settings')) {
            update_option('zapwa_connection_type', sanitize_text_field($_POST['zapwa_connection_type'] ?? 'evolution'));
            update_option('zapwa_evolution_url', esc_url_raw(trim($_POST['zapwa_evolution_url'] ?? '')));
            update_option('zapwa_evolution_token', sanitize_text_field($_POST['zapwa_evolution_token'] ?? ''));
            $new_instance = sanitize_text_field($_POST['zapwa_evolution_instance'] ?? '');
            update_option('zapwa_evolution_instance', $new_instance);

            if ($new_instance) {
                $create_result = ConnectionManager::create_instance($new_instance);
                if ($create_result['success']) {
                    $msg = isset($create_result['message']) ? esc_html($create_result['message']) : 'Instância criada com sucesso!';
                    echo '<div class="notice notice-success is-dismissible"><p>✅ Configurações salvas! ' . $msg . '</p></div>';
                } else {
                    $err = isset($create_result['error']) ? esc_html($create_result['error']) : 'Erro ao criar instância';
                    echo '<div class="notice notice-warning is-dismissible"><p>⚠️ Configurações salvas, mas não foi possível criar a instância: ' . $err . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Configurações salvas com sucesso!</p></div>';
            }
        }

        $connection_type = get_option('zapwa_connection_type', 'evolution');
        $evolution_url   = get_option('zapwa_evolution_url', '');
        $evolution_token = get_option('zapwa_evolution_token', '');
        $instance_name   = get_option('zapwa_evolution_instance', '');
        $is_connected    = ConnectionManager::is_connected();
        $next_cron       = wp_next_scheduled('zapwa_process_queue');
        $wp_cron_disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        $queue_table     = $wpdb->prefix . 'zapwa_queue';
        $pending_queue   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$queue_table} WHERE status = %s", 'pending'));
        $debug_log_file  = WP_CONTENT_DIR . '/zapwa-logs/debug-' . date('Y-m-d') . '.log';

        $plugin_url = ZAP_WA_URL;
        wp_enqueue_style('zapwa-qrcode', $plugin_url . 'assets/css/qrcode.css', [], '1.0.0');
        wp_enqueue_script('zapwa-qrcode-handler', $plugin_url . 'assets/js/qrcode-handler.js', ['jquery'], '1.0.0', true);
        wp_localize_script('zapwa-qrcode-handler', 'zapwaConfig', [
            'instanceName' => esc_js($instance_name),
            'nonce'        => wp_create_nonce('zapwa_qrcode'),
        ]);
        ?>
        <div class="wrap zapwa-page">

            <!-- Page header -->
            <div class="zapwa-admin-header">
                <div>
                    <h1>🔌 <?php esc_html_e( 'Conexão WhatsApp', 'zap-whatsapp-automation' ); ?></h1>
                    <p class="sub"><?php esc_html_e( 'Configure a Evolution API e gerencie sua instância', 'zap-whatsapp-automation' ); ?></p>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <?php if ($is_connected): ?>
                        <span class="zapwa-badge ok" style="font-size:.9rem;padding:6px 14px;">🟢 <?php esc_html_e( 'Conectado', 'zap-whatsapp-automation' ); ?></span>
                    <?php else: ?>
                        <span class="zapwa-badge err" style="font-size:.9rem;padding:6px 14px;">🔴 <?php esc_html_e( 'Desconectado', 'zap-whatsapp-automation' ); ?></span>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( admin_url('admin.php?page=zap-wa-metrics') ); ?>" class="zapwa-btn zapwa-btn-secondary" style="font-size:.85rem;">
                        ← <?php esc_html_e( 'Dashboard', 'zap-whatsapp-automation' ); ?>
                    </a>
                </div>
            </div>

            <!-- Diagnostics card -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr">🩺 <?php esc_html_e( 'Diagnóstico do Processamento', 'zap-whatsapp-automation' ); ?></div>
                <div class="zapwa-card-body">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin-bottom:16px;">
                        <div class="zapwa-diag-item">
                            <span class="zapwa-diag-label">⚙️ <?php esc_html_e( 'WP-Cron', 'zap-whatsapp-automation' ); ?></span>
                            <span class="zapwa-diag-val <?php echo $wp_cron_disabled ? 'err' : 'ok'; ?>">
                                <?php echo $wp_cron_disabled ? '❌ Desabilitado' : '✅ Habilitado'; ?>
                            </span>
                        </div>
                        <div class="zapwa-diag-item">
                            <span class="zapwa-diag-label">⏰ <?php esc_html_e( 'Próxima execução', 'zap-whatsapp-automation' ); ?></span>
                            <span class="zapwa-diag-val <?php echo $next_cron ? 'ok' : 'err'; ?>">
                                <?php echo $next_cron ? esc_html(date('H:i:s', $next_cron)) : '❌ Não agendado'; ?>
                            </span>
                        </div>
                        <div class="zapwa-diag-item">
                            <span class="zapwa-diag-label">📤 <?php esc_html_e( 'Itens na fila', 'zap-whatsapp-automation' ); ?></span>
                            <span class="zapwa-diag-val <?php echo $pending_queue > 0 ? 'pnd' : 'ok'; ?>">
                                <?php echo esc_html((string) $pending_queue); ?>
                            </span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <button id="zapwa-process-queue-now" class="zapwa-btn zapwa-btn-primary">
                            ⚡ <?php esc_html_e( 'Processar fila agora', 'zap-whatsapp-automation' ); ?>
                        </button>
                        <span id="zapwa-process-queue-status" style="font-size:.88rem;"></span>
                    </div>
                    <p style="margin:12px 0 0;font-size:.78rem;color:#888;">
                        <?php esc_html_e( 'Arquivo de debug:', 'zap-whatsapp-automation' ); ?>
                        <code style="background:#f0f7f4;padding:2px 6px;border-radius:4px;"><?php echo esc_html($debug_log_file); ?></code>
                    </p>
                </div>
            </div>

            <!-- Settings form -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr">⚙️ <?php esc_html_e( 'Configurações da API', 'zap-whatsapp-automation' ); ?></div>
                <div class="zapwa-card-body">
                    <form method="post">
                        <?php wp_nonce_field('zapwa_settings'); ?>
                        <input type="hidden" name="zapwa_save_settings" value="1">

                        <div class="zapwa-conn-grid">
                            <div class="zapwa-conn-field">
                                <label class="zapwa-conn-label" for="zapwa_connection_type">
                                    <?php esc_html_e( 'Tipo de Conexão', 'zap-whatsapp-automation' ); ?>
                                </label>
                                <select id="zapwa_connection_type" name="zapwa_connection_type" class="zapwa-conn-input">
                                    <option value="evolution" <?php selected($connection_type, 'evolution'); ?>>Evolution API</option>
                                </select>
                            </div>

                            <div class="zapwa-conn-field">
                                <label class="zapwa-conn-label" for="zapwa_evolution_url">
                                    <?php esc_html_e( 'URL da Evolution API', 'zap-whatsapp-automation' ); ?>
                                </label>
                                <input type="url"
                                       id="zapwa_evolution_url"
                                       name="zapwa_evolution_url"
                                       value="<?php echo esc_attr($evolution_url); ?>"
                                       class="zapwa-conn-input"
                                       placeholder="https://evolution.seudominio.com">
                            </div>

                            <div class="zapwa-conn-field">
                                <label class="zapwa-conn-label" for="zapwa_evolution_token">
                                    <?php esc_html_e( 'API Key', 'zap-whatsapp-automation' ); ?>
                                </label>
                                <input type="text"
                                       id="zapwa_evolution_token"
                                       name="zapwa_evolution_token"
                                       value="<?php echo esc_attr($evolution_token); ?>"
                                       class="zapwa-conn-input"
                                       placeholder="sua-api-key">
                            </div>

                            <div class="zapwa-conn-field">
                                <label class="zapwa-conn-label" for="zapwa_evolution_instance">
                                    <?php esc_html_e( 'Nome da Instância', 'zap-whatsapp-automation' ); ?>
                                </label>
                                <input type="text"
                                       id="zapwa_evolution_instance"
                                       name="zapwa_evolution_instance"
                                       value="<?php echo esc_attr($instance_name); ?>"
                                       class="zapwa-conn-input"
                                       placeholder="minha-instancia">
                            </div>
                        </div>

                        <div style="margin-top:20px;padding-top:16px;border-top:1px solid #eee;">
                            <button type="submit" class="zapwa-btn zapwa-btn-primary" style="font-size:.95rem;padding:11px 24px;">
                                💾 <?php esc_html_e( 'Salvar Configurações', 'zap-whatsapp-automation' ); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($evolution_url && $evolution_token && $instance_name): ?>
            <!-- Instance management -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr">🔧 <?php esc_html_e( 'Gerenciar Instância', 'zap-whatsapp-automation' ); ?></div>
                <div class="zapwa-card-body">
                    <p style="margin:0 0 16px;font-size:.9rem;color:#555;">
                        <?php esc_html_e( 'Use o botão abaixo para criar ou reconectar a instância na Evolution API.', 'zap-whatsapp-automation' ); ?>
                    </p>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                        <button id="zapwa-create-instance" class="zapwa-btn zapwa-btn-secondary">
                            🔧 <?php esc_html_e( 'Criar / Reconectar Instância', 'zap-whatsapp-automation' ); ?>
                        </button>
                        <span id="zapwa-create-instance-status" style="font-size:.88rem;"></span>
                    </div>
                </div>
            </div>
            <script>
            (function($) {
                var instanceName = '<?php echo esc_js($instance_name); ?>';
                var nonce = '<?php echo esc_js(wp_create_nonce('zapwa_qrcode')); ?>';
                $(document).ready(function() {
                    $('#zapwa-create-instance').on('click', function(e) {
                        e.preventDefault();
                        var $btn = $(this);
                        var $status = $('#zapwa-create-instance-status');
                        $btn.prop('disabled', true).text('⏳ Criando...');
                        $status.text('').css('color','');
                        $.post(ajaxurl, {
                            action: 'zapwa_create_instance',
                            instance: instanceName,
                            nonce: nonce
                        }, function(response) {
                            if (response.success) {
                                var msg = (response.data && response.data.message) ? response.data.message : 'Instância criada com sucesso!';
                                $status.text('✅ ' + msg).css('color', '#2e7d32');
                                setTimeout(function() {
                                    $('#zapwa-refresh-qrcode').trigger('click');
                                }, 1500);
                            } else {
                                var err = (response.data && response.data.error) ? response.data.error : 'Erro ao criar instância';
                                $status.text('❌ ' + err).css('color', '#c62828');
                            }
                        }).fail(function() {
                            $status.text('❌ Erro de conexão').css('color', '#c62828');
                        }).always(function() {
                            $btn.prop('disabled', false).text('🔧 Criar / Reconectar Instância');
                        });
                    });
                });
            })(jQuery);
            </script>
            <?php endif; ?>

            <?php if ($instance_name): ?>
            <!-- QR Code card -->
            <div class="zapwa-card">
                <div class="zapwa-card-hdr blue">📱 <?php esc_html_e( 'QR Code — Conectar Dispositivo', 'zap-whatsapp-automation' ); ?></div>
                <div class="zapwa-card-body">
                    <div class="zapwa-qrcode-container" id="zapwa-qrcode-display">
                        <div class="zapwa-qrcode-image"></div>
                        <div class="zapwa-qrcode-timer" id="zapwa-qrcode-timer"></div>
                        <div class="zapwa-qrcode-status" id="zapwa-qrcode-status"></div>
                        <div class="zapwa-qrcode-instructions">
                            <h4><?php esc_html_e( 'Como conectar:', 'zap-whatsapp-automation' ); ?></h4>
                            <ol>
                                <li><?php esc_html_e( 'Abra o WhatsApp no seu celular', 'zap-whatsapp-automation' ); ?></li>
                                <li><?php esc_html_e( 'Toque em Menu ou Configurações', 'zap-whatsapp-automation' ); ?></li>
                                <li><?php esc_html_e( 'Selecione Dispositivos Conectados', 'zap-whatsapp-automation' ); ?></li>
                                <li><?php esc_html_e( 'Toque em Conectar um Dispositivo', 'zap-whatsapp-automation' ); ?></li>
                                <li><?php esc_html_e( 'Aponte a câmera para o QR Code', 'zap-whatsapp-automation' ); ?></li>
                            </ol>
                        </div>
                        <div class="zapwa-qrcode-actions">
                            <button id="zapwa-refresh-qrcode" class="zapwa-btn zapwa-btn-secondary">
                                🔄 <?php esc_html_e( 'Atualizar QR Code', 'zap-whatsapp-automation' ); ?>
                            </button>
                            <button id="zapwa-download-qrcode" class="zapwa-btn zapwa-btn-secondary">
                                💾 <?php esc_html_e( 'Baixar QR Code', 'zap-whatsapp-automation' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.wrap -->
        <script>
        (function($){
            $(document).ready(function(){
                $('#zapwa-process-queue-now').on('click', function(e){
                    e.preventDefault();
                    var $btn = $(this);
                    var $status = $('#zapwa-process-queue-status');
                    $btn.prop('disabled', true).text('⏳ Processando...');
                    $status.text('');

                    $.post(ajaxurl, {
                        action: 'zapwa_process_queue_now',
                        nonce: '<?php echo esc_js(wp_create_nonce('zapwa_qrcode')); ?>'
                    }, function(resp){
                        if (resp.success) {
                            $status.text('✅ Fila processada').css('color', '#2e7d32');
                        } else {
                            var err = (resp.data && resp.data.error) ? resp.data.error : 'Erro ao processar fila';
                            $status.text('❌ ' + err).css('color', '#c62828');
                        }
                    }).fail(function(){
                        $status.text('❌ Falha de conexão AJAX').css('color', '#c62828');
                    }).always(function(){
                        $btn.prop('disabled', false).text('⚡ Processar fila agora');
                    });
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}
