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

            // Try to auto-create instance if name is set
            if ($new_instance) {
                $create_result = ConnectionManager::create_instance($new_instance);
                if ($create_result['success']) {
                    $msg = isset($create_result['message']) ? esc_html($create_result['message']) : 'Instância criada com sucesso!';
                    echo '<div class="notice notice-success"><p>Configurações salvas! ' . $msg . '</p></div>';
                } else {
                    $err = isset($create_result['error']) ? esc_html($create_result['error']) : 'Erro ao criar instância';
                    echo '<div class="notice notice-warning"><p>Configurações salvas, mas não foi possível criar a instância: ' . $err . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-success"><p>Configurações salvas!</p></div>';
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

        // Enqueue assets
        $plugin_url = ZAP_WA_URL;
        wp_enqueue_style('zapwa-qrcode', $plugin_url . 'assets/css/qrcode.css', [], '1.0.0');
        wp_enqueue_script('zapwa-qrcode-handler', $plugin_url . 'assets/js/qrcode-handler.js', ['jquery'], '1.0.0', true);
        wp_localize_script('zapwa-qrcode-handler', 'zapwaConfig', [
            'instanceName' => esc_js($instance_name),
            'nonce'        => wp_create_nonce('zapwa_qrcode'),
        ]);
        ?>
        <div class="wrap zapwa-page">
            <h1>🔌 Conexão WhatsApp</h1>

            <p>
                Status:
                <?php if ($is_connected): ?>
                    <span class="zapwa-connection-badge connected">🟢 Conectado</span>
                <?php else: ?>
                    <span class="zapwa-connection-badge disconnected">🔴 Desconectado</span>
                <?php endif; ?>
            </p>

            <div class="notice notice-info" style="padding:10px 14px;">
                <p><strong>Diagnóstico do processamento</strong></p>
                <ul style="margin-left:20px;list-style:disc;">
                    <li><strong>WP-Cron:</strong> <?php echo $wp_cron_disabled ? '❌ Desabilitado (DISABLE_WP_CRON=true)' : '✅ Habilitado'; ?></li>
                    <li><strong>Próxima execução da fila:</strong> <?php echo $next_cron ? esc_html(date('Y-m-d H:i:s', $next_cron)) : '❌ Não agendado'; ?></li>
                    <li><strong>Itens pendentes na fila:</strong> <?php echo esc_html((string) $pending_queue); ?></li>
                    <li><strong>Arquivo de debug:</strong> <code><?php echo esc_html($debug_log_file); ?></code></li>
                </ul>
                <p>
                    <button id="zapwa-process-queue-now" class="button">⚡ Processar fila agora</button>
                    <span id="zapwa-process-queue-status" style="margin-left:10px;"></span>
                </p>
            </div>

            <form method="post">
                <?php wp_nonce_field('zapwa_settings'); ?>
                <input type="hidden" name="zapwa_save_settings" value="1">

                <table class="form-table">
                    <tr>
                        <th>Tipo de Conexão</th>
                        <td>
                            <select name="zapwa_connection_type">
                                <option value="evolution" <?php selected($connection_type, 'evolution'); ?>>Evolution API</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>URL da Evolution API</th>
                        <td><input type="url" name="zapwa_evolution_url" value="<?php echo esc_attr($evolution_url); ?>" class="regular-text" placeholder="https://evolution.seudominio.com"></td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td><input type="text" name="zapwa_evolution_token" value="<?php echo esc_attr($evolution_token); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Nome da Instância</th>
                        <td><input type="text" name="zapwa_evolution_instance" value="<?php echo esc_attr($instance_name); ?>" class="regular-text" placeholder="minha-instancia"></td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Salvar Configurações</button>
                </p>
            </form>

            <?php if ($evolution_url && $evolution_token && $instance_name): ?>
            <hr>
            <h2>⚙️ Gerenciar Instância</h2>
            <p>
                <button id="zapwa-create-instance" class="button button-secondary">🔧 Criar / Reconectar Instância</button>
                <span id="zapwa-create-instance-status" style="margin-left:10px;"></span>
            </p>
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
                                $status.text('✅ ' + msg).css('color', '#46b450');
                                // Wait briefly for Evolution API to prepare the QR code, then reload
                                setTimeout(function() {
                                    $('#zapwa-refresh-qrcode').trigger('click');
                                }, 1500); // 1.5s gives Evolution API time to generate the QR code
                            } else {
                                var err = (response.data && response.data.error) ? response.data.error : 'Erro ao criar instância';
                                $status.text('❌ ' + err).css('color', '#dc3232');
                            }
                        }).fail(function() {
                            $status.text('❌ Erro de conexão').css('color', '#dc3232');
                        }).always(function() {
                            $btn.prop('disabled', false).text('🔧 Criar / Reconectar Instância');
                        });
                    });
                });
            })(jQuery);
            </script>
            <?php endif; ?>

            <?php if ($instance_name): ?>
            <hr>
            <h2>📱 QR Code</h2>
            <div class="zapwa-qrcode-container" id="zapwa-qrcode-display">
                <div class="zapwa-qrcode-image"></div>
                <div class="zapwa-qrcode-timer" id="zapwa-qrcode-timer"></div>
                <div class="zapwa-qrcode-status" id="zapwa-qrcode-status"></div>
                <div class="zapwa-qrcode-instructions">
                    <h4>Como conectar:</h4>
                    <ol>
                        <li>Abra o WhatsApp no seu celular</li>
                        <li>Toque em <strong>Menu</strong> ou <strong>Configurações</strong></li>
                        <li>Selecione <strong>Dispositivos Conectados</strong></li>
                        <li>Toque em <strong>Conectar um Dispositivo</strong></li>
                        <li>Aponte a câmera para o QR Code</li>
                    </ol>
                </div>
                <div class="zapwa-qrcode-actions">
                    <button id="zapwa-refresh-qrcode" class="button">🔄 Atualizar QR Code</button>
                    <button id="zapwa-download-qrcode" class="button">💾 Baixar QR Code</button>
                </div>
            </div>
            <?php endif; ?>
        </div>
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
                            $status.text('✅ Fila processada').css('color', '#46b450');
                        } else {
                            var err = (resp.data && resp.data.error) ? resp.data.error : 'Erro ao processar fila';
                            $status.text('❌ ' + err).css('color', '#dc3232');
                        }
                    }).fail(function(){
                        $status.text('❌ Falha de conexão AJAX').css('color', '#dc3232');
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
