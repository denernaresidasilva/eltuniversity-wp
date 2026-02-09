<?php
/*
Plugin Name: ZAPMEMBROS - Vendas Plataformas
Plugin URI: http://api.supermembros.com.br/
Description: Com esse Plugin Todas as Vendas Realizadas através de um Plataforma de Vendas ou Checkout Próprio podem ser notificadas pelo WhatsApp usando a API ZapMembros
Version: 1.0
Author: Raul Cruz
Author URI: http://api.supermembros.com.br/
License: 
License URI: 
*/

// Prevenir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

class WebhookWhatsAppNotifications {
    private $table_name;
    private $charset_collate;

    public function __construct() {
        global $wpdb;
        
        $this->table_name = $wpdb->prefix . 'webhook_whatsapp';
        $this->charset_collate = $wpdb->get_charset_collate();

        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_update_webhook', array($this, 'ajax_update_webhook'));
        add_action('wp_ajax_delete_webhook', array($this, 'ajax_delete_webhook'));
        add_action('wp_ajax_listen_webhook', array($this, 'ajax_listen_webhook'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function activate_plugin() {
        global $wpdb;

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            endpoint_key varchar(50) NOT NULL,
            webhook_data longtext,
            message_template text NOT NULL,
            second_message_template text,
            phone_field varchar(100),
            add_ddi tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY endpoint_key (endpoint_key)
        ) {$this->charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        if (!get_option('whatsapp_instance_id')) {
            add_option('whatsapp_instance_id', '');
        }
        if (!get_option('whatsapp_api_key')) {
            add_option('whatsapp_api_key', '');
        }
        if (!get_option('whatsapp_codigo_pais')) {
            add_option('whatsapp_codigo_pais', '+55');
        }
    }

    private function process_message_variables($message, $data) {
        return preg_replace_callback('/\(([\w.]+)\)/', function($matches) use ($data) {
            $path = $matches[1];
            $value = $this->extract_value_from_json($data, $path);
            return $value !== null ? $value : $matches[0];
        }, $message);
    }

    private function format_phone_with_ddi($number, $add_ddi) {
        $number = preg_replace('/[^0-9]/', '', $number);
        
        if ($add_ddi) {
            if (substr($number, 0, 2) === '55') {
                $number = substr($number, 2);
            }
            $codigo_pais = get_option('whatsapp_codigo_pais', '+55');
            $codigo_pais = str_replace('+', '', $codigo_pais);
            $number = $codigo_pais . $number;
        }
        
        error_log('Webhook WhatsApp - Número formatado: ' . $number . ' (DDI: ' . ($add_ddi ? 'sim' : 'não') . ')');
        return $number;
    }

    private function extract_value_from_json($data, $field_path) {
        $parts = explode('.', $field_path);
        $current = $data;
        
        foreach ($parts as $part) {
            if (!is_array($current) || !isset($current[$part])) {
                return null;
            }
            $current = $current[$part];
        }

        if (is_null($current)) {
            return null;
        }

        if (is_numeric($current) || is_string($current)) {
            return (string)$current;
        }

        if (is_array($current) || is_object($current)) {
            return json_encode($current, JSON_UNESCAPED_UNICODE);
        }

        return null;
    }

    private function send_whatsapp_message($phone_number, $message) {
        $instance_id = get_option('whatsapp_instance_id');
        $api_key = get_option('whatsapp_api_key');

        if (empty($instance_id) || empty($api_key)) {
            error_log('Webhook WhatsApp Error: Configurações da API não definidas');
            return false;
        }

        $api_url = "https://api.supermembros.com.br/message/sendText/{$instance_id}";
        $data = array(
            'number' => $phone_number,
            'text' => $message,
            'linkPreview' => true
        );

        error_log('Webhook WhatsApp - Request Data: ' . print_r($data, true));

        $response = wp_remote_post($api_url, array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'apikey' => $api_key
            ),
            'body' => json_encode($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('Webhook WhatsApp Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        error_log('Webhook WhatsApp Response: Code=' . $response_code . ', Body=' . $response_body);

        return ($response_code >= 200 && $response_code < 300);
    }

    public function handle_webhook($request) {
        $key = $request->get_param('key');
        $json_data = $request->get_body();
        $data = json_decode($json_data, true);

        if (!$data) {
            return new WP_Error('invalid_json', 'Invalid JSON data', array('status' => 400));
        }

        global $wpdb;
        $webhook_config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE endpoint_key = %s",
            $key
        ));

        if (!$webhook_config) {
            return new WP_Error('invalid_webhook', 'Invalid webhook key', array('status' => 404));
        }

        if (empty($webhook_config->webhook_data)) {
            $wpdb->update(
                $this->table_name,
                array('webhook_data' => $json_data),
                array('endpoint_key' => $key)
            );
            return new WP_REST_Response(array(
                'status' => 'success',
                'message' => 'First webhook data saved'
            ), 200);
        }

        if (empty($webhook_config->phone_field)) {
            return new WP_Error('phone_field_not_configured', 'Phone field not configured', array('status' => 400));
        }

        $phone_number = $this->extract_value_from_json($data, $webhook_config->phone_field);
        
        if (!$phone_number) {
            return new WP_Error('invalid_phone', 'Phone number not found in data', array('status' => 400));
        }

        $phone_number = $this->format_phone_with_ddi($phone_number, $webhook_config->add_ddi == 1);
        
        // Processamento da primeira mensagem
        $message = $this->process_message_variables($webhook_config->message_template, $data);
        $first_message_sent = $this->send_whatsapp_message($phone_number, $message);
        error_log('Webhook WhatsApp - Status primeira mensagem: ' . ($first_message_sent ? 'Enviada' : 'Falhou'));

        // Processamento da segunda mensagem (se existir)
        $second_message_sent = false;
        if (!empty($webhook_config->second_message_template)) {
            sleep(5); // Atraso de 5 segundos
            $second_message = $this->process_message_variables($webhook_config->second_message_template, $data);
            $second_message_sent = $this->send_whatsapp_message($phone_number, $second_message);
            error_log('Webhook WhatsApp - Status segunda mensagem: ' . ($second_message_sent ? 'Enviada' : 'Falhou'));
        }

        return new WP_REST_Response(array(
            'status' => 'completed',
            'first_message' => $first_message_sent ? 'sent' : 'failed',
            'second_message' => !empty($webhook_config->second_message_template) 
                ? ($second_message_sent ? 'sent' : 'failed') 
                : 'not_configured'
        ), 200);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook != 'toplevel_page_webhook-whatsapp') {
            return;
        }

        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_style('wp-jquery-ui-dialog');

        wp_register_script('webhook-whatsapp-admin', '', array('jquery', 'jquery-ui-dialog'), '1.0.0', true);
        wp_enqueue_script('webhook-whatsapp-admin');

        wp_localize_script('webhook-whatsapp-admin', 'webhookWhatsApp', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webhook_nonce')
        ));

        $script = $this->get_admin_script();
        wp_add_inline_script('webhook-whatsapp-admin', $script);
    }

    private function get_admin_script() {
        return <<<'JS'
jQuery(document).ready(function($) {
    $("#webhook-edit-dialog").dialog({
        autoOpen: false,
        modal: true,
        width: 500,
        buttons: {
            "Salvar": function() {
                var dialog = $(this);
                var webhookId = dialog.data('webhook-id');
                var phoneField = $("#edit-phone-field").val();
                var messageTemplate = $("#edit-message-template").val();
                var secondMessageTemplate = $("#edit-second-message-template").val();
                var addDdi = $("input[name='edit-add-ddi']:checked").val() === "1";

                $.ajax({
                    url: webhookWhatsApp.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'update_webhook',
                        nonce: webhookWhatsApp.nonce,
                        webhook_id: webhookId,
                        phone_field: phoneField,
                        message_template: messageTemplate,
                        second_message_template: secondMessageTemplate,
                        add_ddi: addDdi ? 1 : 0
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erro ao atualizar webhook: ' + response.data.message);
                        }
                    }
                });
            },
            "Cancelar": function() {
                $(this).dialog("close");
            }
        }
    });

    $(".edit-webhook").click(function() {
        var webhookId = $(this).data('webhook-id');
        var phoneField = $(this).data('phone-field');
        var messageTemplate = $(this).data('message-template');
        var secondMessageTemplate = $(this).data('second-message-template');
        var addDdi = $(this).data('add-ddi') === '1';
        var jsonData = $(this).data('json-data');

        $("#edit-phone-field").val(phoneField);
        $("#edit-message-template").val(messageTemplate);
        $("#edit-second-message-template").val(secondMessageTemplate);
        
        if (addDdi) {
            $("#edit-add-ddi-yes").prop('checked', true);
        } else {
            $("#edit-add-ddi-no").prop('checked', true);
        }

        if (jsonData) {
            try {
                var jsonObj = JSON.parse(jsonData);
                $(".json-preview-section").show();
                $("#json-preview").html('<pre>' + JSON.stringify(jsonObj, null, 2) + '</pre>');
                
                var variableExplanation = '<div class="variable-explanation">' +
                    '<p><strong>Como usar variáveis na mensagem:</strong></p>' +
                    '<p>Use parênteses para incluir dados do JSON. Exemplo: (Customer.full_name)</p>' +
                    '<p>Estrutura do JSON disponível acima.</p>' +
                    '</div>';
                $("#json-preview").after(variableExplanation);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                $(".json-preview-section").hide();
            }
        } else {
            $(".json-preview-section").hide();
        }

        var dialog = $("#webhook-edit-dialog");
        dialog.data('webhook-id', webhookId).dialog("open");
    });

    $(".delete-webhook").click(function() {
        if (!confirm('Tem certeza que deseja excluir este webhook?')) {
            return;
        }

        var webhookId = $(this).data('webhook-id');

        $.ajax({
            url: webhookWhatsApp.ajaxUrl,
            type: 'POST',
            data: {
                action: 'delete_webhook',
                nonce: webhookWhatsApp.nonce,
                webhook_id: webhookId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Erro ao excluir webhook: ' + response.data.message);
                }
            }
        });
    });

    $(".listen-webhook").click(function() {
        var button = $(this);
        var webhookId = button.data('webhook-id');
        
        function checkWebhook() {
            $.ajax({
                url: webhookWhatsApp.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'listen_webhook',
                    nonce: webhookWhatsApp.nonce,
                    webhook_id: webhookId
                },
                success: function(response) {
                    if (response.success && response.data.webhook_received) {
                        location.reload();
                    }
                }
            });
        }

        var interval = setInterval(checkWebhook, 5000);
        
        setTimeout(function() {
            clearInterval(interval);
            button.prop('disabled', false).text('Escutar Webhook');
        }, 120000);

        button.prop('disabled', true).text('Escutando...');
    });

    // Adicionar handler para o botão show-json-structure
    $(".show-json-structure").click(function() {
        var webhookId = $(this).data('webhook-id');
        var structureDiv = $("#json-structure-" + webhookId);
        
        if (structureDiv.is(":visible")) {
            structureDiv.slideUp();
        } else {
            structureDiv.slideDown();
        }
    });
});
JS;
    }

    public function ajax_update_webhook() {
        check_ajax_referer('webhook_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }

        $webhook_id = intval($_POST['webhook_id']);
        $phone_field = sanitize_text_field($_POST['phone_field']);
        $message_template = sanitize_textarea_field($_POST['message_template']);
        $second_message_template = sanitize_textarea_field($_POST['second_message_template']);
        $add_ddi = isset($_POST['add_ddi']) ? intval($_POST['add_ddi']) : 0;
        
        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array(
                'phone_field' => $phone_field,
                'message_template' => $message_template,
                'second_message_template' => $second_message_template,
                'add_ddi' => $add_ddi
            ),
            array('id' => $webhook_id),
            array('%s', '%s', '%s', '%d'),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao atualizar webhook'));
        } else {
            wp_send_json_success(array('message' => 'Webhook atualizado com sucesso'));
        }
    }

    public function ajax_delete_webhook() {
        check_ajax_referer('webhook_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }

        $webhook_id = intval($_POST['webhook_id']);
        
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $webhook_id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao excluir webhook'));
        } else {
            wp_send_json_success(array('message' => 'Webhook excluído com sucesso'));
        }
    }

    public function ajax_listen_webhook() {
        check_ajax_referer('webhook_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada'));
        }

        $webhook_id = intval($_POST['webhook_id']);
        
        global $wpdb;
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $webhook_id
        ));
        
        if ($webhook && !empty($webhook->webhook_data)) {
            wp_send_json_success(array('webhook_received' => true));
        } else {
            wp_send_json_success(array('webhook_received' => false));
        }
    }

    public function register_settings() {
        register_setting('webhook_whatsapp_settings', 'whatsapp_instance_id');
        register_setting('webhook_whatsapp_settings', 'whatsapp_api_key');
        register_setting('webhook_whatsapp_settings', 'whatsapp_codigo_pais');
    }

    public function add_admin_menu() {
        add_menu_page(
            'ZapMembros Webhook',
            'ZapMembros Webhook',
            'manage_options',
            'webhook-whatsapp',
            array($this, 'render_admin_page'),
            'dashicons-networking'
        );
    }

    public function register_rest_routes() {
        register_rest_route('webhook-whatsapp/v1', '/receive/(?P<key>[a-zA-Z0-9-]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_webhook'),
            'permission_callback' => '__return_true'
        ));
    }

    public function render_admin_page() {
        global $wpdb;
        
        if (isset($_POST['action']) && $_POST['action'] === 'create_webhook') {
            check_admin_referer('create_webhook');
            
            $endpoint_key = sanitize_text_field($_POST['endpoint_key']);
            $phone_field = !empty($_POST['phone_field']) ? sanitize_text_field($_POST['phone_field']) : '';
            $message_template = sanitize_textarea_field($_POST['message_template']);
            $second_message_template = sanitize_textarea_field($_POST['second_message_template']);
            $add_ddi = isset($_POST['add_ddi']) ? intval($_POST['add_ddi']) : 0;

            $wpdb->insert(
                $this->table_name,
                array(
                    'endpoint_key' => $endpoint_key,
                    'phone_field' => $phone_field,
                    'message_template' => $message_template,
                    'second_message_template' => $second_message_template,
                    'add_ddi' => $add_ddi
                )
            );
        }

        $webhooks = $wpdb->get_results("SELECT * FROM {$this->table_name}");
        ?>
        <div class="wrap">
            <h1>Configurações API Zap Membros Webhook</h1>

            <!-- Modal de Edição -->
            <div id="webhook-edit-dialog" style="display:none;">
                <div class="webhook-edit-form">
                    <div class="form-group">
                        <label for="edit-phone-field">Campo do Telefone:</label>
                        <input type="text" id="edit-phone-field" 
                               placeholder="Ex: Customer.mobile" 
                               class="regular-text">
                        <p class="description">Caminho do campo de telefone no JSON (usando pontos)</p>
                    </div>
                    <div class="form-group">
                        <label>Formato do Número:</label><br>
                        <label>
                            <input type="radio" id="edit-add-ddi-yes" name="edit-add-ddi" value="1" required>
                            Adicionar DDI ao número
                        </label><br>
                        <label>
                            <input type="radio" id="edit-add-ddi-no" name="edit-add-ddi" value="0" required>
                            Manter número original
                        </label>
                        <p class="description">Escolha se o número deve incluir o DDI ou não</p>
                    </div>
                    <div class="form-group">
                        <label for="edit-message-template">Mensagem Principal:</label>
                        <textarea id="edit-message-template" 
                                  class="large-text" 
                                  rows="5"></textarea>
                        <p class="description">Use variáveis entre parênteses para incluir dados do JSON</p>
                    </div>
                    <div class="form-group">
                        <label for="edit-second-message-template">Segunda Mensagem (Opcional):</label>
                        <textarea id="edit-second-message-template" 
                                  class="large-text" 
                                  rows="5"></textarea>
                        <p class="description">Essa mensagem será enviada após a primeira</p>
                    </div>
                    <div class="json-preview-section" style="display:none;">
                        <h3>Estrutura do JSON recebido:</h3>
                        <div id="json-preview"></div>
                    </div>
                </div>
            </div>

            <!-- Configurações da API -->
            <h2>Configurações da API</h2>
            <form method="post" action="options.php">
                <?php 
                settings_fields('webhook_whatsapp_settings');
                do_settings_sections('webhook_whatsapp_settings');
                ?>
                <table class="form-table">
                    <tr>
                        <th>ID da Instância</th>
                        <td>
                            <input type="text" name="whatsapp_instance_id" 
                                   value="<?php echo esc_attr(get_option('whatsapp_instance_id')); ?>" 
                                   class="regular-text">
                            <p class="description">ID da instância do WhatsApp para conectar</p>
                        </td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td>
                            <input type="password" name="whatsapp_api_key" 
                                   value="<?php echo esc_attr(get_option('whatsapp_api_key')); ?>" 
                                   class="regular-text">
                            <p class="description">Chave de autenticação da API (apikey)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Código do País</th>
                        <td>
                            <input type="text" name="whatsapp_codigo_pais" 
                                   value="<?php echo esc_attr(get_option('whatsapp_codigo_pais', '+55')); ?>" 
                                   class="small-text">
                            <p class="description">Código do país para números de telefone (ex: +55 para Brasil)</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Salvar Configurações'); ?>
            </form>

            <!-- Novo Webhook -->
            <h2>Adicionar Novo Webhook</h2>
            <form method="post" action="">
                <?php wp_nonce_field('create_webhook'); ?>
                <input type="hidden" name="action" value="create_webhook">
                <table class="form-table">
                    <tr>
                        <th>Chave do Endpoint</th>
                        <td>
                            <input type="text" name="endpoint_key" required 
                                   class="regular-text" 
                                   pattern="[a-zA-Z0-9-]+">
                            <p class="description">Este será o identificador único do seu webhook</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Campo do Telefone</th>
                        <td>
                            <input type="text" name="phone_field" 
                                   class="regular-text" 
                                   placeholder="Ex: Customer.mobile">
                            <p class="description">Caminho do campo de telefone no JSON (usando pontos)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Formato do Número</th>
                        <td>
                            <label>
                                <input type="radio" name="add_ddi" value="1" required>
                                Adicionar DDI ao número
                            </label><br>
                            <label>
                                <input type="radio" name="add_ddi" value="0" required>
                                Manter número original
                            </label>
                            <p class="description">Escolha se o número deve incluir o DDI ou não</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Mensagem Principal</th>
                        <td>
                            <textarea name="message_template" required 
                                      class="large-text" 
                                      rows="5"></textarea>
                            <p class="description">Use variáveis entre parênteses para incluir dados do JSON na mensagem.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Segunda Mensagem (Opcional)</th>
                        <td>
                            <textarea name="second_message_template" 
                                      class="large-text" 
                                      rows="5"></textarea>
                            <p class="description">Essa mensagem será enviada após a primeira.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Adicionar Webhook'); ?>
            </form>

            <!-- Lista de Webhooks -->
            <h2>Webhooks Configurados</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Endpoint</th>
                        <th>URL do Webhook</th>
                        <th>Campo do Telefone</th>
                        <th>Formato do Número</th>
                        <th>Mensagem Principal</th>
                        <th>Segunda Mensagem</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($webhooks as $webhook): ?>
                        <tr>
                            <td><?php echo esc_html($webhook->endpoint_key); ?></td>
                            <td><?php echo esc_url(rest_url("webhook-whatsapp/v1/receive/{$webhook->endpoint_key}")); ?></td>
                            <td>
                                <?php 
                                echo esc_html($webhook->phone_field ?: 'Não configurado');
                                if ($webhook->add_ddi) {
                                    echo ' <span class="dashicons dashicons-yes" title="DDI será adicionado"></span>';
                                }
                                ?>
                            </td>
                            <td><?php echo $webhook->add_ddi ? 'Adiciona DDI' : 'Mantém número original'; ?></td>
                            <td><?php echo esc_html($webhook->message_template); ?></td>
                            <td><?php echo esc_html($webhook->second_message_template ?: 'Não configurada'); ?></td>
                            <td>
                                <?php if (empty($webhook->webhook_data)): ?>
                                    <span style="color: orange;">Aguardando primeira chamada</span>
                                    <button class="button listen-webhook" 
                                            data-webhook-id="<?php echo esc_attr($webhook->id); ?>">
                                        Escutar Webhook
                                    </button>
                                <?php else: ?>
                                    <span style="color: green;">Configurado</span>
                                    <button class="button show-json-structure" 
                                            data-webhook-id="<?php echo esc_attr($webhook->id); ?>">
                                        Mostrar estrutura do JSON
                                    </button>
                                    <div id="json-structure-<?php echo esc_attr($webhook->id); ?>" 
                                         style="display: none;">
                                        <pre><?php echo esc_html(json_encode(json_decode($webhook->webhook_data), JSON_PRETTY_PRINT)); ?></pre>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="button edit-webhook" 
                                        data-webhook-id="<?php echo esc_attr($webhook->id); ?>"
                                        data-phone-field="<?php echo esc_attr($webhook->phone_field); ?>"
                                        data-message-template="<?php echo esc_attr($webhook->message_template); ?>"
                                        data-second-message-template="<?php echo esc_attr($webhook->second_message_template); ?>"
                                        data-add-ddi="<?php echo esc_attr($webhook->add_ddi); ?>"
                                        data-json-data='<?php echo esc_attr($webhook->webhook_data); ?>'>
                                    <span class="dashicons dashicons-edit"></span> Editar
                                </button>
                                <button class="button button-link-delete delete-webhook" 
                                        data-webhook-id="<?php echo esc_attr($webhook->id); ?>"
                                        title="Excluir webhook">
                                    <span class="dashicons dashicons-trash" style="color: #dc3232;"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

// Inicializar o plugin
$webhook_whatsapp = new WebhookWhatsAppNotifications();
?>