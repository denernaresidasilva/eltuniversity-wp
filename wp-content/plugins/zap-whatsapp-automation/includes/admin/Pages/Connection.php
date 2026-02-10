<?php
namespace ZapWA\Admin\Pages;

use ZapWA\ConnectionManager;

if (!defined('ABSPATH')) exit;

class Connection {

    public static function render() {
        
        // Handle form submissions
        if (isset($_POST['zapwa_save_connection'])) {
            if (!isset($_POST['zapwa_connection_nonce']) || 
                !wp_verify_nonce($_POST['zapwa_connection_nonce'], 'zapwa_connection_save')) {
                wp_die('Invalid security token');
            }

            update_option('zapwa_connection_type', sanitize_text_field($_POST['zapwa_connection_type']));
            
            if ($_POST['zapwa_connection_type'] === 'evolution') {
                update_option('zapwa_api_url', sanitize_text_field($_POST['zapwa_api_url']));
                update_option('zapwa_api_token', sanitize_text_field($_POST['zapwa_api_token']));
                update_option('zapwa_instance_name', sanitize_text_field($_POST['zapwa_instance_name']));
            } else {
                update_option('zapwa_official_phone_id', sanitize_text_field($_POST['zapwa_official_phone_id']));
                update_option('zapwa_official_access_token', sanitize_text_field($_POST['zapwa_official_access_token']));
            }
            
            echo '<div class="updated"><p>Configurações salvas.</p></div>';
        }

        // Handle create instance action
        if (isset($_POST['zapwa_create_instance'])) {
            if (!isset($_POST['zapwa_instance_nonce']) || 
                !wp_verify_nonce($_POST['zapwa_instance_nonce'], 'zapwa_create_instance')) {
                wp_die('Invalid security token');
            }

            $instance_name = sanitize_text_field($_POST['zapwa_instance_name']);
            $result = ConnectionManager::create_instance($instance_name);
            
            if ($result['success']) {
                echo '<div class="updated"><p>✅ Instância criada com sucesso! Escaneie o QR Code abaixo.</p></div>';
            } else {
                echo '<div class="error"><p>❌ Erro: ' . esc_html($result['error']) . '</p></div>';
            }
        }

        // Handle disconnect action
        if (isset($_POST['zapwa_disconnect'])) {
            if (!isset($_POST['zapwa_disconnect_nonce']) || 
                !wp_verify_nonce($_POST['zapwa_disconnect_nonce'], 'zapwa_disconnect_instance')) {
                wp_die('Invalid security token');
            }

            $instance_name = get_option('zapwa_instance_name');
            $result = ConnectionManager::disconnect_instance($instance_name);
            
            if ($result['success']) {
                echo '<div class="updated"><p>✅ Instância desconectada.</p></div>';
            }
        }

        $connection_type = get_option('zapwa_connection_type', 'evolution');
        $api_url = get_option('zapwa_api_url');
        $api_token = get_option('zapwa_api_token');
        $instance_name = get_option('zapwa_instance_name');
        $phone_id = get_option('zapwa_official_phone_id');
        $access_token = get_option('zapwa_official_access_token');
        $is_connected = ConnectionManager::is_connected();
        
        ?>
        <div class="wrap">
            <h1>🔌 Conexão WhatsApp</h1>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Status da Conexão</h2>
                <p style="font-size: 16px;">
                    <?php if ($is_connected): ?>
                        <span style="color: green;">✅ <strong>Conectado</strong></span>
                    <?php else: ?>
                        <span style="color: red;">❌ <strong>Desconectado</strong></span>
                    <?php endif; ?>
                </p>
            </div>

            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('zapwa_connection_save', 'zapwa_connection_nonce'); ?>
                
                <h2>Tipo de Conexão</h2>
                <table class="form-table">
                    <tr>
                        <th>Escolha o tipo de API</th>
                        <td>
                            <label>
                                <input type="radio" name="zapwa_connection_type" value="evolution" 
                                    <?php checked($connection_type, 'evolution'); ?> 
                                    onchange="toggleConnectionType(this.value)">
                                <strong>Evolution API</strong> (gratuita, auto-hospedada)
                            </label>
                            <br><br>
                            <label>
                                <input type="radio" name="zapwa_connection_type" value="official" 
                                    <?php checked($connection_type, 'official'); ?>
                                    onchange="toggleConnectionType(this.value)">
                                <strong>WhatsApp Business API</strong> (oficial do Meta)
                            </label>
                        </td>
                    </tr>
                </table>

                <div id="evolution-config" style="<?php echo $connection_type === 'official' ? 'display:none;' : ''; ?>">
                    <h2>⚡ Configuração Evolution API</h2>
                    <table class="form-table">
                        <tr>
                            <th>URL da Evolution API</th>
                            <td>
                                <input type="text" name="zapwa_api_url" 
                                    value="<?php echo esc_attr($api_url); ?>" 
                                    class="regular-text" 
                                    placeholder="https://evolution.seudominio.com">
                                <p class="description">URL base da sua instância Evolution API</p>
                            </td>
                        </tr>
                        <tr>
                            <th>API Key</th>
                            <td>
                                <input type="password" name="zapwa_api_token" 
                                    value="<?php echo esc_attr($api_token); ?>" 
                                    class="regular-text">
                                <p class="description">Token de autenticação (apikey)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Nome da Instância</th>
                            <td>
                                <input type="text" name="zapwa_instance_name" 
                                    value="<?php echo esc_attr($instance_name); ?>" 
                                    class="regular-text" 
                                    placeholder="minha-instancia">
                                <p class="description">Nome único para identificar esta conexão</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="official-config" style="<?php echo $connection_type === 'evolution' ? 'display:none;' : ''; ?>">
                    <h2>📱 Configuração WhatsApp Business API (Meta)</h2>
                    <table class="form-table">
                        <tr>
                            <th>Phone Number ID</th>
                            <td>
                                <input type="text" name="zapwa_official_phone_id" 
                                    value="<?php echo esc_attr($phone_id); ?>" 
                                    class="regular-text" 
                                    placeholder="102134567890123">
                                <p class="description">ID do número de telefone (obtido no Facebook Developers)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Access Token</th>
                            <td>
                                <input type="password" name="zapwa_official_access_token" 
                                    value="<?php echo esc_attr($access_token); ?>" 
                                    class="large-text">
                                <p class="description">Token permanente gerado no Meta Business</p>
                            </td>
                        </tr>
                    </table>
                    <div class="card">
                        <h3>📖 Como obter as credenciais:</h3>
                        <ol>
                            <li>Acesse <a href="https://developers.facebook.com/" target="_blank">Facebook Developers</a></li>
                            <li>Crie um app com WhatsApp Business Platform</li>
                            <li>Em "WhatsApp > Configuração rápida", copie o Phone Number ID</li>
                            <li>Gere um Access Token permanente em "WhatsApp > Configuração"</li>
                        </ol>
                    </div>
                </div>

                <p class="submit">
                    <button class="button button-primary" name="zapwa_save_connection">
                        💾 Salvar Configurações
                    </button>
                </p>
            </form>

            <?php if ($connection_type === 'evolution' && $api_url && $api_token): ?>
                <hr style="margin: 40px 0;">
                
                <h2>📲 Gerenciar Instância Evolution</h2>
                
                <?php if (!$is_connected && $instance_name): ?>
                    <form method="post" id="create-instance-form">
                        <?php wp_nonce_field('zapwa_create_instance', 'zapwa_instance_nonce'); ?>
                        <input type="hidden" name="zapwa_instance_name" value="<?php echo esc_attr($instance_name); ?>">
                        <button type="submit" name="zapwa_create_instance" class="button button-primary button-hero">
                            🚀 Criar Instância e Gerar QR Code
                        </button>
                    </form>

                    <div id="qrcode-container" style="margin-top: 20px; display: none;">
                        <div class="card" style="max-width: 400px; text-align: center;">
                            <h3>📱 Escaneie o QR Code</h3>
                            <div id="qrcode-image"></div>
                            <p>Abra o WhatsApp > Aparelhos conectados > Conectar aparelho</p>
                            <button type="button" class="button" onclick="refreshQRCode()">🔄 Atualizar QR Code</button>
                        </div>
                    </div>
                <?php elseif ($is_connected): ?>
                    <div class="card" style="max-width: 600px;">
                        <h3 style="color: green;">✅ Instância Conectada</h3>
                        <p>Sua instância <strong><?php echo esc_html($instance_name); ?></strong> está conectada e pronta para enviar mensagens.</p>
                        
                        <form method="post" style="margin-top: 15px;">
                            <?php wp_nonce_field('zapwa_disconnect_instance', 'zapwa_disconnect_nonce'); ?>
                            <button type="submit" name="zapwa_disconnect" class="button button-secondary" 
                                onclick="return confirm('Tem certeza que deseja desconectar esta instância?')">
                                🔌 Desconectar Instância
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <script>
        function toggleConnectionType(type) {
            if (type === 'evolution') {
                document.getElementById('evolution-config').style.display = 'block';
                document.getElementById('official-config').style.display = 'none';
            } else {
                document.getElementById('evolution-config').style.display = 'none';
                document.getElementById('official-config').style.display = 'block';
            }
        }

        function refreshQRCode() {
            const instanceName = '<?php echo esc_js($instance_name); ?>';
            const apiUrl = '<?php echo esc_js($api_url); ?>';
            const apiToken = '<?php echo esc_js($api_token); ?>';
            
            document.getElementById('qrcode-image').innerHTML = '<p>Carregando QR Code...</p>';
            
            fetch(apiUrl + '/instance/connect/' + instanceName, {
                headers: {
                    'apikey': apiToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.qrcode && data.qrcode.base64) {
                    document.getElementById('qrcode-image').innerHTML = 
                        '<img src="' + data.qrcode.base64 + '" style="max-width: 300px;">';
                } else {
                    document.getElementById('qrcode-image').innerHTML = 
                        '<p style="color:red;">❌ Erro ao gerar QR Code</p>';
                }
            })
            .catch(error => {
                document.getElementById('qrcode-image').innerHTML = 
                    '<p style="color:red;">❌ Erro: ' + error.message + '</p>';
            });
        }

        // Auto-load QR code after instance creation
        <?php if (isset($_POST['zapwa_create_instance']) && isset($result) && $result['success']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('qrcode-container').style.display = 'block';
            refreshQRCode();
        });
        <?php endif; ?>
        </script>

        <style>
        .card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-top: 20px;
        }
        .card h3 {
            margin-top: 0;
        }
        </style>
        <?php
    }
}
