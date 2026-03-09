<?php
/*
Plugin Name: Supermembros - Receber Webhook de Qualquer Plataforma
Description: Plugin para Receber Webhooks de Qualquer Plataforma e Cadastrar Novos Alunos na Supermembros
Version: 3.0
Author: Raul Julio da Cruz
*/

// Se este arquivo é chamado diretamente, aborta.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('WEBHOOK_RECEIVER_VERSION', '3.3.0');
define('WEBHOOK_RECEIVER_PATH', plugin_dir_path(__FILE__));
define('WEBHOOK_RECEIVER_URL', plugin_dir_url(__FILE__));

class Webhook_Receiver {

    /**
     * ========================================
     * INICIALIZAR PLUGIN
     * ========================================
     */
    public function __construct() {
        // Registrar o endpoint para receber webhooks
        add_action('rest_api_init', array($this, 'register_webhook_endpoints'));
        
        // Adicionar menu de administração
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Inicializar configurações
        add_action('admin_init', array($this, 'initialize_settings'));
        
        // Criar tabela personalizada na ativação
        register_activation_hook(__FILE__, array($this, 'create_tables'));

        // Adicionar AJAX para gerenciar webhooks
        add_action('wp_ajax_webhook_receiver_save_webhook', array($this, 'ajax_save_webhook'));
        add_action('wp_ajax_webhook_receiver_delete_webhook', array($this, 'ajax_delete_webhook'));
        
        // AJAX para order bumps
        add_action('wp_ajax_webhook_receiver_save_order_bump', array($this, 'ajax_save_order_bump'));
        add_action('wp_ajax_webhook_receiver_delete_order_bump', array($this, 'ajax_delete_order_bump'));
        add_action('wp_ajax_webhook_receiver_get_order_bumps', array($this, 'ajax_get_order_bumps'));
        
        // AJAX para cursos principais
        add_action('wp_ajax_webhook_receiver_save_main_courses', array($this, 'ajax_save_main_courses'));
        add_action('wp_ajax_webhook_receiver_get_main_courses', array($this, 'ajax_get_main_courses'));
        add_action('wp_ajax_webhook_receiver_get_webhooks_list', array($this, 'ajax_get_webhooks_list'));
        
        // AJAX para escutar webhook
        add_action('wp_ajax_webhook_receiver_listen_webhook', array($this, 'ajax_listen_webhook'));
        
        // AJAX para obter dados do webhook
        add_action('wp_ajax_webhook_receiver_get_webhook_data', array($this, 'ajax_get_webhook_data'));
        
        // AJAX para matricular aluno manualmente
        add_action('wp_ajax_webhook_receiver_manual_enroll', array($this, 'ajax_manual_enroll_student'));
	add_option('webhook_receiver_user_email_subject', 'Bem-vindo! Seus dados de acesso');
	add_option('webhook_receiver_user_email_template', 'Olá <strong>(nome)</strong>,<br>Sua conta foi criada! ...');

        
        // ========================================
        // GERENCIAMENTO DE CURSOS DOS USUÁRIOS
        // ========================================
        
        // Adicionar campos de cursos personalizados nos perfis de usuário
        add_action('show_user_profile', array($this, 'custom_user_courses_field'));
        add_action('edit_user_profile', array($this, 'custom_user_courses_field'));
        add_action('user_new_form', array($this, 'custom_user_courses_field_new_user'));
        
        // Salvar cursos personalizados
        add_action('edit_user_profile_update', array($this, 'custom_save_user_courses'));
        add_action('user_register', array($this, 'custom_save_user_courses'));
        
        // Adicionar coluna personalizada na lista de usuários
        add_filter('manage_users_columns', array($this, 'custom_user_courses_column'));
        add_action('manage_users_custom_column', array($this, 'custom_user_courses_column_content'), 10, 3);
    }

    /**
     * ========================================
     * GERENCIAMENTO DE CURSOS DOS USUÁRIOS
     * ========================================
     */

    /**
     * Função para gerenciar cursos dos usuários
     */
    public function custom_user_courses_field($user) {
        // Obtenha todos os cursos disponíveis
        $courses = get_posts(array('post_type' => 'courses', 'numberposts' => -1));

        // Obtenha os cursos nos quais o usuário está matriculado via Tutor LMS
        $tutor_courses = function_exists('tutor_utils') ? tutor_utils()->get_enrolled_courses_ids_by_user($user->ID) : array();

        // Obtenha os cursos armazenados no meta '_user_courses'
        $meta_courses = get_user_meta($user->ID, '_user_courses', true);
        if (!is_array($meta_courses)) {
            $meta_courses = array();
        }

        // Combine os cursos do Tutor LMS com os cursos armazenados no meta
        $user_courses = array_unique(array_merge($tutor_courses, $meta_courses));

        // Nonce de segurança
        wp_nonce_field('save_custom_user_courses', '_custom_user_courses_nonce');

        // Início da interface
        echo '<h3>' . __('Cursos Supermembros', 'webhook-receiver') . '</h3>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="custom_user_courses">' . __('Cursos', 'webhook-receiver') . '</label></th>';
        echo '<td>';

        // Botões de Selecionar Todos e Remover Todos
        echo '<p>';
        echo '<button type="button" id="select-all-courses">' . __('Selecionar Todos', 'webhook-receiver') . '</button>';
        echo ' ';
        echo '<button type="button" id="deselect-all-courses">' . __('Remover Todos', 'webhook-receiver') . '</button>';
        echo '</p>';

        // Listagem dos cursos com checkbox
        foreach ($courses as $course) {
            $checked = in_array($course->ID, $user_courses) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" class="course-checkbox" name="custom_user_courses[]" value="' . esc_attr($course->ID) . '" ' . $checked . '> ' . esc_html($course->post_title) . '</label><br>';
        }

        echo '</td>';
        echo '</tr>';
        echo '</table>';

        // Script para manipular "Selecionar Todos" e "Remover Todos"
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function() {
                var selectAllBtn = document.getElementById('select-all-courses');
                var deselectAllBtn = document.getElementById('deselect-all-courses');
                
                if (selectAllBtn) {
                    selectAllBtn.addEventListener('click', function() {
                        // Marca todos os checkboxes de cursos
                        var checkboxes = document.querySelectorAll('.course-checkbox');
                        checkboxes.forEach(function(checkbox) {
                            checkbox.checked = true;
                        });
                    });
                }
                
                if (deselectAllBtn) {
                    deselectAllBtn.addEventListener('click', function() {
                        // Desmarca todos os checkboxes de cursos
                        var checkboxes = document.querySelectorAll('.course-checkbox');
                        checkboxes.forEach(function(checkbox) {
                            checkbox.checked = false;
                        });
                    });
                }
            });
        </script>
        <?php
    }

    /**
     * ================================================================================
     * ADICIONA O CAMPO DE CURSOS PERSONALIZADOS NA CRIAÇÃO DO USUÁRIO
     * ================================================================================
     */
    public function custom_user_courses_field_new_user($user) {
        // Obtenha todos os cursos disponíveis
        $courses = get_posts(array('post_type' => 'courses', 'numberposts' => -1));

        // Nonce de segurança
        wp_nonce_field('save_custom_user_courses', '_custom_user_courses_nonce');

        echo '<h3>' . __('Cursos Supermembros', 'webhook-receiver') . '</h3>';
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="custom_user_courses">' . __('Cursos', 'webhook-receiver') . '</label></th>';
        echo '<td>';

        foreach ($courses as $course) {
            echo '<label><input type="checkbox" name="custom_user_courses[]" value="' . esc_attr($course->ID) . '"> ' . esc_html($course->post_title) . '</label><br>';
        }

        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }

    /**
     * ================================================================================
     * FUNÇÃO PARA MATRICULAR E DESMATRICULAR USUÁRIOS EM CURSOS AO ATUALIZAR O PERFIL
     * ================================================================================
     */
    public function custom_save_user_courses($user_id) {
        global $wpdb;

        // Verifique se o usuário atual tem a capacidade de editar usuários
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Verifique o nonce de segurança
        if (!isset($_POST['_custom_user_courses_nonce']) || !wp_verify_nonce($_POST['_custom_user_courses_nonce'], 'save_custom_user_courses')) {
            return false;
        }

        // Obtenha os IDs dos cursos submetidos
        $new_courses = isset($_POST['custom_user_courses']) ? array_map('intval', $_POST['custom_user_courses']) : array();

        // Obtenha os cursos atuais do usuário
        $current_courses = get_user_meta($user_id, '_user_courses', true);
        if (!is_array($current_courses)) {
            $current_courses = array();
        }

        // Matricular o usuário nos novos cursos
        if (function_exists('tutils')) {
            foreach ($new_courses as $course_id) {
                if (!in_array($course_id, $current_courses)) {
                    tutils()->do_enroll($course_id, 0, $user_id);
                }
            }
        }

        // Desmatricular o usuário dos cursos removidos
        foreach ($current_courses as $course_id) {
            if (!in_array($course_id, $new_courses)) {
                $enrollment_id = $wpdb->get_var($wpdb->prepare("
                    SELECT ID FROM {$wpdb->posts} 
                    WHERE post_type = 'tutor_enrolled' 
                    AND post_parent = %d 
                    AND post_author = %d
                ", $course_id, $user_id));

                if ($enrollment_id) {
                    wp_delete_post($enrollment_id, true);
                }
            }
        }

        // Atualize o meta do usuário com os cursos inscritos
        update_user_meta($user_id, '_user_courses', $new_courses);
    }

    /**
     * ================================================================================
     * FUNÇÃO PARA ADICIONAR UMA COLUNA PERSONALIZADA NA TABELA DE USUÁRIOS
     * ================================================================================
     */
    public function custom_user_courses_column($columns) {
        $columns['user_courses'] = __('Cursos', 'webhook-receiver');
        return $columns;
    }

    /**
     * ================================================================================
     * FUNÇÃO PARA PREENCHER A TABELA DE CURSOS NA TABELA DE USUÁRIOS
     * ================================================================================
     */
    public function custom_user_courses_column_content($value, $column_name, $user_id) {
        if ('user_courses' == $column_name) {
            // Obtenha os cursos nos quais o usuário está matriculado diretamente pelo Tutor LMS
            $enrolled_courses = function_exists('tutor_utils') ? tutor_utils()->get_enrolled_courses_ids_by_user($user_id) : array();

            // Verifique se o meta '_user_courses' tem cursos adicionais
            $meta_courses = get_user_meta($user_id, '_user_courses', true);
            if (!is_array($meta_courses)) {
                $meta_courses = array();
            }

            // Combine os cursos do meta com os cursos matriculados diretamente pelo Tutor LMS
            $all_courses = array_unique(array_merge($enrolled_courses, $meta_courses));

            if (!empty($all_courses)) {
                $course_titles = array();
                foreach ($all_courses as $course_id) {
                    $course = get_post($course_id);
                    if ($course) {
                        $course_titles[] = $course->post_title;
                    }
                }
                $value = implode(', ', $course_titles);
            } else {
                $value = __('Nenhum', 'webhook-receiver');
            }
        }
        return $value;
    }

    /**
     * ========================================
     * FUNCIONALIDADES DE WEBHOOK
     * ========================================
     */

    /**
     * Registrar os endpoints REST API para receber webhooks
     */
    public function register_webhook_endpoints() {
        // Endpoint principal (mantido para compatibilidade)
        register_rest_route('webhook-receiver/v1', '/receive', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_webhook'),
            'permission_callback' => '__return_true'
        ));
        
        // Endpoints para webhooks específicos
        register_rest_route('webhook-receiver/v1', '/receive/(?P<webhook_id>[a-zA-Z0-9_-]+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_specific_webhook'),
            'permission_callback' => '__return_true',
            'args' => array(
                'webhook_id' => array(
                    'validate_callback' => function($param) {
                        return is_string($param);
                    }
                ),
            ),
        ));
    }

    /**
     * ================================================================================
     * PROCESSAR OS DADOS RECEBIDOS VIA WEBHOOK ESPECÍFICO
     * ================================================================================
     */
    public function process_specific_webhook($request) {
        // Obter o ID do webhook da URL
        $webhook_id = $request->get_param('webhook_id');
        
        // Obter os dados do webhook configurado
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhook_config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
        
        // Se o webhook não existir, retornar erro
        if (!$webhook_config) {
            return new WP_Error('invalid_webhook', 'Webhook não encontrado', array('status' => 404));
        }
        
        // Obter os dados do corpo da requisição
        $body = $request->get_body();
        $data = json_decode($body, true);
        
        // Salvar dados brutos do webhook se for a primeira vez
        if (empty($webhook_config->webhook_data)) {
            // Verificar se o body é um JSON válido antes de salvar
            $test_decode = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $wpdb->update(
                    $table_name,
                    array('webhook_data' => $body),
                    array('webhook_id' => $webhook_id)
                );
                
                // Log para debug
                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                    $this->log_webhook("Dados do webhook salvos para ID: {$webhook_id}");
                }
            } else {
                // Log de erro se JSON inválido
                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                    $this->log_webhook("ERRO: JSON inválido recebido para webhook {$webhook_id}: " . json_last_error_msg());
                }
            }
        }
        
        // Log dos dados recebidos se ativado
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("Webhook ID: {$webhook_id}\n" . $body);
        }
        
        // Verificar se os dados são válidos
        if (!$data) {
            return new WP_Error('invalid_data', 'Dados inválidos ou malformados', array('status' => 400));
        }
        
        // Tentar mapear os dados automaticamente
        $mapped_data = $this->map_webhook_data($data, $webhook_id);
        
        if (!$mapped_data) {
            return new WP_Error('mapping_failed', 'Não foi possível mapear os dados do webhook', array('status' => 400));
        }
        
        // Processar a venda/transação mapeada
        $result = $this->process_mapped_sale($mapped_data, $webhook_config, $data);
        
        // Executar ações personalizadas
        do_action('webhook_receiver_processed', $data, $webhook_config);
        
        // Retornar resposta de sucesso
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Webhook processado com sucesso',
            'result' => $result,
            'mapped_data' => $mapped_data
        ), 200);
    }

    /**
     * ================================================================================
     * PROCESSAR OS DADOS RECEBIDOS VIA WEBHOOK ORIGINAL COMPATIVEL
     * ================================================================================
     */
    public function process_webhook($request) {
        // Obter os dados do corpo da requisição
        $body = $request->get_body();
        $data = json_decode($body, true);
        
        // Log dos dados recebidos se ativado
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook($body);
        }
        
        // Verificar se os dados são válidos
        if (!$data || !isset($data['event']) || !isset($data['data'])) {
            return new WP_Error('invalid_data', 'Dados inválidos ou malformados', array('status' => 400));
        }
        
        // Processar com base no tipo de evento
        switch ($data['event']) {
            case 'venda_aprovada':
                $result = $this->process_approved_sale($data);
                break;
            default:
                return new WP_Error('unknown_event', 'Tipo de evento desconhecido', array('status' => 400));
        }
        
        // Executar ações personalizadas
        do_action('webhook_receiver_processed', $data);
        
        // Retornar resposta de sucesso
        return new WP_REST_Response(array(
            'success' => true,
            'message' => 'Webhook processado com sucesso',
            'result' => $result
        ), 200);
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DO WEBHOOK AUTOMATICAMENTE BASEADO NA ESTRUTURA
     * ================================================================================
     */
    public function map_webhook_data($data, $webhook_id) {
        // Log para debug
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("MAPEAMENTO: Iniciando mapeamento automático para webhook {$webhook_id}");
        }
        
        $mapped_data = array();
        
        // Detectar tipo de plataforma e mapear campos
        $platform = $this->detect_platform($data);
        
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("MAPEAMENTO: Plataforma detectada: {$platform}");
        }
        
        switch ($platform) {
            case 'kiwify':
                $mapped_data = $this->map_kiwify_data($data);
                break;
            case 'hotmart':
                $mapped_data = $this->map_hotmart_data($data);
                break;
            case 'eduzz':
                $mapped_data = $this->map_eduzz_data($data);
                break;
            case 'cakto':
                $mapped_data = $this->map_cakto_data($data);
                break;
            case 'ticto':
                $mapped_data = $this->map_ticto_data($data);
                break;
            case 'herospark':
                $mapped_data = $this->map_herospark_data($data);
                break;
            case 'generic':
            default:
                $mapped_data = $this->map_generic_data($data);
                break;
        }
        
        // Log do resultado do mapeamento
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("MAPEAMENTO: Dados mapeados: " . print_r($mapped_data, true));
        }
        
        return $mapped_data;
    }
    
    /**
     * ================================================================================
     * DETECTAR PLATAFORMA BASEADO NA ESTRUTURA DOS DADOS
     * ================================================================================
     */
    public function detect_platform($data) {
        // Kiwify - tem Customer, Product, webhook_event_type
        if (isset($data['Customer']) && isset($data['Product']) && isset($data['webhook_event_type'])) {
            return 'kiwify';
        }
        
        // Hotmart - tem data.buyer, data.product
        if (isset($data['data']['buyer']) && isset($data['data']['product'])) {
            return 'hotmart';
        }
        
        // Eduzz - tem cliente, produto
        if (isset($data['cliente']) && isset($data['produto'])) {
            return 'eduzz';
        }
        
        // Cakto - tem customer, product
        if (isset($data['customer']) && isset($data['product']) && isset($data['event_type'])) {
            return 'cakto';
        }
        
        // Ticto - tem comprador, item
        if (isset($data['comprador']) && isset($data['item'])) {
            return 'ticto';
        }
        
        // HeroSpark - tem buyer, offer
        if (isset($data['buyer']) && isset($data['offer'])) {
            return 'herospark';
        }
        
        // Formato original esperado - tem event e data
        if (isset($data['event']) && isset($data['data']) && $data['event'] === 'venda_aprovada') {
            return 'generic';
        }
        
        return 'generic';
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA KIWIFY
     * ================================================================================
     */
    public function map_kiwify_data($data) {
        // Verificar se é uma venda aprovada
        if (!isset($data['webhook_event_type']) || $data['webhook_event_type'] !== 'order_approved') {
            return false;
        }
        
        if (!isset($data['Customer']) || !isset($data['Product'])) {
            return false;
        }
        
        $customer = $data['Customer'];
        $product = $data['Product'];
        
        $mapped = array(
            'first_name' => isset($customer['first_name']) ? $customer['first_name'] : explode(' ', $customer['full_name'])[0],
            'last_name' => isset($customer['last_name']) ? $customer['last_name'] : implode(' ', array_slice(explode(' ', $customer['full_name']), 1)),
            'payer_email' => $customer['email'],
            'cpf' => isset($customer['CPF']) ? $customer['CPF'] : '',
            'telefone' => isset($customer['mobile']) ? $customer['mobile'] : '',
            'product_name' => $product['product_name'],
            'product_id' => $product['product_id'],
            'product_sku' => isset($product['product_sku']) ? $product['product_sku'] : $product['product_id'],
            'product_price' => isset($data['Commissions']['charge_amount']) ? $data['Commissions']['charge_amount'] / 100 : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['payment_method']) ? $data['payment_method'] : 'unknown',
            'parcelas' => isset($data['installments']) ? $data['installments'] : 1,
            'produtos_adicionais' => array(), // Kiwify não tem order bumps no webhook padrão
            'delivery_link' => isset($data['access_url']) ? $data['access_url'] : ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA HOTMART
     * ================================================================================
     */
    public function map_hotmart_data($data) {
        if (!isset($data['data']['buyer']) || !isset($data['data']['product'])) {
            return false;
        }
        
        $buyer = $data['data']['buyer'];
        $product = $data['data']['product'];
        
        $mapped = array(
            'first_name' => isset($buyer['name']) ? explode(' ', $buyer['name'])[0] : '',
            'last_name' => isset($buyer['name']) ? implode(' ', array_slice(explode(' ', $buyer['name']), 1)) : '',
            'payer_email' => $buyer['email'],
            'cpf' => isset($buyer['checkout_phone']) ? $buyer['checkout_phone'] : '',
            'telefone' => isset($buyer['phone']) ? $buyer['phone'] : '',
            'product_name' => $product['name'],
            'product_id' => $product['id'],
            'product_sku' => isset($product['sku']) ? $product['sku'] : $product['id'],
            'product_price' => isset($data['data']['purchase']['price']['value']) ? $data['data']['purchase']['price']['value'] : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['data']['purchase']['payment']['type']) ? $data['data']['purchase']['payment']['type'] : 'unknown',
            'parcelas' => isset($data['data']['purchase']['payment']['installments_number']) ? $data['data']['purchase']['payment']['installments_number'] : 1,
            'produtos_adicionais' => array(),
            'delivery_link' => ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA EDUZZ
     * ================================================================================
     */
    public function map_eduzz_data($data) {
        if (!isset($data['cliente']) || !isset($data['produto'])) {
            return false;
        }
        
        $cliente = $data['cliente'];
        $produto = $data['produto'];
        
        $mapped = array(
            'first_name' => isset($cliente['nome']) ? explode(' ', $cliente['nome'])[0] : '',
            'last_name' => isset($cliente['nome']) ? implode(' ', array_slice(explode(' ', $cliente['nome']), 1)) : '',
            'payer_email' => $cliente['email'],
            'cpf' => isset($cliente['doc']) ? $cliente['doc'] : '',
            'telefone' => isset($cliente['telefone']) ? $cliente['telefone'] : '',
            'product_name' => $produto['nome'],
            'product_id' => $produto['id'],
            'product_sku' => isset($produto['codigo']) ? $produto['codigo'] : $produto['id'],
            'product_price' => isset($data['venda']['valor']) ? $data['venda']['valor'] : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['venda']['meio_pagamento']) ? $data['venda']['meio_pagamento'] : 'unknown',
            'parcelas' => isset($data['venda']['parcelas']) ? $data['venda']['parcelas'] : 1,
            'produtos_adicionais' => array(),
            'delivery_link' => ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA CAKTO
     * ================================================================================
     */
    public function map_cakto_data($data) {
        if (!isset($data['customer']) || !isset($data['product'])) {
            return false;
        }
        
        $customer = $data['customer'];
        $product = $data['product'];
        
        $mapped = array(
            'first_name' => isset($customer['first_name']) ? $customer['first_name'] : explode(' ', $customer['name'])[0],
            'last_name' => isset($customer['last_name']) ? $customer['last_name'] : implode(' ', array_slice(explode(' ', $customer['name']), 1)),
            'payer_email' => $customer['email'],
            'cpf' => isset($customer['cpf']) ? $customer['cpf'] : '',
            'telefone' => isset($customer['phone']) ? $customer['phone'] : '',
            'product_name' => $product['name'],
            'product_id' => $product['id'],
            'product_sku' => isset($product['sku']) ? $product['sku'] : $product['id'],
            'product_price' => isset($data['transaction']['amount']) ? $data['transaction']['amount'] : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['transaction']['payment_method']) ? $data['transaction']['payment_method'] : 'unknown',
            'parcelas' => isset($data['transaction']['installments']) ? $data['transaction']['installments'] : 1,
            'produtos_adicionais' => array(),
            'delivery_link' => ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA TICTO
     * ================================================================================
     */
    public function map_ticto_data($data) {
        if (!isset($data['comprador']) || !isset($data['item'])) {
            return false;
        }
        
        $comprador = $data['comprador'];
        $item = $data['item'];
        
        $mapped = array(
            'first_name' => isset($comprador['nome']) ? explode(' ', $comprador['nome'])[0] : '',
            'last_name' => isset($comprador['nome']) ? implode(' ', array_slice(explode(' ', $comprador['nome']), 1)) : '',
            'payer_email' => $comprador['email'],
            'cpf' => isset($comprador['documento']) ? $comprador['documento'] : '',
            'telefone' => isset($comprador['telefone']) ? $comprador['telefone'] : '',
            'product_name' => $item['nome'],
            'product_id' => $item['id'],
            'product_sku' => isset($item['codigo']) ? $item['codigo'] : $item['id'],
            'product_price' => isset($data['pedido']['valor_total']) ? $data['pedido']['valor_total'] : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['pagamento']['forma']) ? $data['pagamento']['forma'] : 'unknown',
            'parcelas' => isset($data['pagamento']['parcelas']) ? $data['pagamento']['parcelas'] : 1,
            'produtos_adicionais' => array(),
            'delivery_link' => ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS DA HEROSPARK
     * ================================================================================
     */
    public function map_herospark_data($data) {
        if (!isset($data['buyer']) || !isset($data['offer'])) {
            return false;
        }
        
        $buyer = $data['buyer'];
        $offer = $data['offer'];
        
        $mapped = array(
            'first_name' => isset($buyer['first_name']) ? $buyer['first_name'] : explode(' ', $buyer['name'])[0],
            'last_name' => isset($buyer['last_name']) ? $buyer['last_name'] : implode(' ', array_slice(explode(' ', $buyer['name']), 1)),
            'payer_email' => $buyer['email'],
            'cpf' => isset($buyer['cpf']) ? $buyer['cpf'] : '',
            'telefone' => isset($buyer['phone']) ? $buyer['phone'] : '',
            'product_name' => $offer['name'],
            'product_id' => $offer['id'],
            'product_sku' => isset($offer['sku']) ? $offer['sku'] : $offer['id'],
            'product_price' => isset($data['order']['total_amount']) ? $data['order']['total_amount'] : 0,
            'product_quantity' => 1,
            'product_category' => 'digital',
            'payment_method' => isset($data['payment']['method']) ? $data['payment']['method'] : 'unknown',
            'parcelas' => isset($data['payment']['installments']) ? $data['payment']['installments'] : 1,
            'produtos_adicionais' => array(),
            'delivery_link' => ''
        );
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * MAPEAR DADOS GENERICO (AUTOMATICO)
     * ================================================================================
     */
    public function map_generic_data($data) {
        // Se já está no formato esperado
        if (isset($data['event']) && isset($data['data']) && $data['event'] === 'venda_aprovada') {
            return $data['data'];
        }
        
        // Tentar mapear automaticamente procurando campos comuns
        $mapped = array();
        
        // Buscar email recursivamente
        $email = $this->find_field_recursive($data, array('email', 'payer_email', 'e_mail', 'mail'));
        if (!$email) {
            return false; // Email é obrigatório
        }
        
        $mapped['payer_email'] = $email;
        
        // Buscar nome
        $first_name = $this->find_field_recursive($data, array('first_name', 'nome', 'name', 'full_name'));
        if ($first_name) {
            if (strpos($first_name, ' ') !== false) {
                $name_parts = explode(' ', $first_name);
                $mapped['first_name'] = $name_parts[0];
                $mapped['last_name'] = implode(' ', array_slice($name_parts, 1));
            } else {
                $mapped['first_name'] = $first_name;
                $mapped['last_name'] = '';
            }
        } else {
            $mapped['first_name'] = 'Cliente';
            $mapped['last_name'] = '';
        }
        
        // Buscar outros campos
        $mapped['cpf'] = $this->find_field_recursive($data, array('cpf', 'document', 'doc', 'CPF')) ?: '';
        $mapped['telefone'] = $this->find_field_recursive($data, array('telefone', 'phone', 'mobile', 'celular')) ?: '';
        
        // Produto
        $mapped['product_name'] = $this->find_field_recursive($data, array('product_name', 'nome', 'name', 'title')) ?: 'Produto Digital';
        $mapped['product_id'] = $this->find_field_recursive($data, array('product_id', 'id', 'codigo')) ?: uniqid();
        $mapped['product_sku'] = $this->find_field_recursive($data, array('product_sku', 'sku', 'codigo')) ?: $mapped['product_id'];
        $mapped['product_price'] = floatval($this->find_field_recursive($data, array('product_price', 'price', 'valor', 'amount')) ?: 0);
        $mapped['product_quantity'] = intval($this->find_field_recursive($data, array('product_quantity', 'quantity', 'quantidade')) ?: 1);
        $mapped['product_category'] = 'digital';
        
        // Pagamento
        $mapped['payment_method'] = $this->find_field_recursive($data, array('payment_method', 'metodo_pagamento', 'forma_pagamento')) ?: 'unknown';
        $mapped['parcelas'] = intval($this->find_field_recursive($data, array('parcelas', 'installments', 'installment')) ?: 1);
        
        $mapped['produtos_adicionais'] = array();
        $mapped['delivery_link'] = '';
        
        return $mapped;
    }
    
    /**
     * ================================================================================
     * BUSCAR CAMPO RECURSIVAMENTE EM UM AWAY
     * ================================================================================
     */
    private function find_field_recursive($data, $fields) {
        foreach ($fields as $field) {
            $result = $this->search_recursive($data, $field);
            if ($result !== null) {
                return $result;
            }
        }
        return null;
    }
    
    /**
     * ================================================================================
     * BUSCAR RECURSIVAMENTE EM UM AWAY
     * ================================================================================
     */
    private function search_recursive($array, $key) {
        if (!is_array($array)) {
            return null;
        }
        
        // Busca direta
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        // Busca case-insensitive
        foreach ($array as $k => $v) {
            if (strtolower($k) === strtolower($key)) {
                return $v;
            }
        }
        
        // Busca recursiva
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = $this->search_recursive($item, $key);
                if ($result !== null) {
                    return $result;
                }
            }
        }
        
        return null;
    }
    
    /**
     * ================================================================================
     * PROCESSAR VENDAS COM DADOS MAPEADOS
     * ================================================================================
     */
    public function process_mapped_sale($mapped_data, $webhook_config, $original_data) {
        global $wpdb;
        
        // Preparar dados para inserção na tabela de vendas
        $sale_record = array(
            'event_type' => 'venda_aprovada',
            'payment_method' => $mapped_data['payment_method'],
            'customer_name' => $mapped_data['first_name'] . ' ' . $mapped_data['last_name'],
            'customer_email' => $mapped_data['payer_email'],
            'customer_cpf' => $mapped_data['cpf'],
            'customer_phone' => $mapped_data['telefone'],
            'product_id' => $mapped_data['product_id'],
            'product_name' => $mapped_data['product_name'],
            'product_sku' => $mapped_data['product_sku'],
            'product_price' => $mapped_data['product_price'],
            'product_quantity' => $mapped_data['product_quantity'],
            'product_category' => $mapped_data['product_category'],
            'additional_products' => json_encode($mapped_data['produtos_adicionais']),
            'payment_installments' => $mapped_data['parcelas'],
            'delivery_link' => $mapped_data['delivery_link'],
            'created_at' => current_time('mysql'),
            'raw_data' => json_encode($original_data),
            'webhook_id' => $webhook_config->webhook_id
        );
        
        // Inserir na tabela do banco de dados
        $table_name = $wpdb->prefix . 'webhook_sales';
        $wpdb->insert($table_name, $sale_record);
        $sale_id = $wpdb->insert_id;
        
        // Log da venda processada
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("VENDA PROCESSADA: ID {$sale_id} - Cliente: {$mapped_data['payer_email']} - Produto: {$mapped_data['product_name']}");
        }
        
        // Verificar se a criação de usuários está ativada
        if (get_option('webhook_receiver_create_users', 'no') === 'yes') {
            // Obter cursos principais do webhook
            $courses_table = $wpdb->prefix . 'webhook_receiver_courses';
            $course_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT course_id FROM $courses_table WHERE webhook_id = %s",
                $webhook_config->webhook_id
            ));
            
            // Se não houver cursos na nova tabela, tentar usar o campo antigo (compatibilidade)
            if (empty($course_ids) && $webhook_config->course_id > 0) {
                $course_ids = array($webhook_config->course_id);
            }
            
            $this->maybe_create_user($mapped_data, $course_ids, $webhook_config);
        }
        	
        // Ações personalizadas para vendas aprovadas
        do_action('webhook_receiver_sale_approved', $mapped_data, $sale_id, $webhook_config);
        
        return array(
            'sale_id' => $sale_id,
            'status' => 'processed',
            'mapped_data' => $mapped_data
        );
    }
    
    /**
     * ================================================================================
     * PROCESSAR VENDA APROVADA
     * ================================================================================
     */
    public function process_approved_sale($data, $webhook_config = null) {
        global $wpdb;
        
        // Extrair dados principais
        $sale_data = $data['data'];
        $payment_method = isset($data['payment_method']) ? $data['payment_method'] : '';
        
        // Preparar dados para inserção
        $sale_record = array(
            'event_type' => $data['event'],
            'payment_method' => $payment_method,
            'customer_name' => $sale_data['first_name'] . ' ' . $sale_data['last_name'],
            'customer_email' => $sale_data['payer_email'],
            'customer_cpf' => $sale_data['cpf'],
            'customer_phone' => $sale_data['telefone'],
            'product_id' => $sale_data['product_id'],
            'product_name' => $sale_data['product_name'],
            'product_sku' => $sale_data['product_sku'],
            'product_price' => $sale_data['product_price'],
            'product_quantity' => $sale_data['product_quantity'],
            'product_category' => isset($sale_data['product_category']) ? $sale_data['product_category'] : '',
            'additional_products' => isset($sale_data['produtos_adicionais']) ? json_encode($sale_data['produtos_adicionais']) : '',
            'payment_installments' => isset($sale_data['parcelas']) ? $sale_data['parcelas'] : 1,
            'delivery_link' => isset($sale_data['delivery_link']) ? $sale_data['delivery_link'] : '',
            'created_at' => current_time('mysql'),
            'raw_data' => json_encode($data),
            'webhook_id' => $webhook_config ? $webhook_config->webhook_id : ''
        );
        
        // Inserir na tabela do banco de dados
        $table_name = $wpdb->prefix . 'webhook_sales';
        $wpdb->insert($table_name, $sale_record);
        $sale_id = $wpdb->insert_id;
        
        // Verificar se a criação de usuários está ativada
        if (get_option('webhook_receiver_create_users', 'no') === 'yes') {
            if ($webhook_config) {
                // Obter cursos principais do webhook
                $courses_table = $wpdb->prefix . 'webhook_receiver_courses';
                $course_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT course_id FROM $courses_table WHERE webhook_id = %s",
                    $webhook_config->webhook_id
                ));
                
                // Se não houver cursos na nova tabela, tentar usar o campo antigo (compatibilidade)
                if (empty($course_ids) && $webhook_config->course_id > 0) {
                    $course_ids = array($webhook_config->course_id);
                }
                
                $this->maybe_create_user($sale_data, $course_ids, $webhook_config);
            } else {
                // Caso contrário, usar os cursos configurados nas configurações gerais
                $course_ids = get_option('webhook_receiver_auto_enroll_courses', array());
                $this->maybe_create_user($sale_data, $course_ids);
            }
        }
        
        // Ações personalizadas para vendas aprovadas
        if ($webhook_config) {
            do_action('webhook_receiver_sale_approved', $sale_data, $sale_id, $webhook_config);
        } else {
            do_action('webhook_receiver_sale_approved', $sale_data, $sale_id);
        }
        
        return array(
            'sale_id' => $sale_id,
            'status' => 'processed'
        );
    }
    
    /**
     * ================================================================================
     * CRIAR USUÁRIO APARTIR DOS DADOS DE VENDAS SE NÃO EXISTIR
     * ================================================================================
     */
    public function maybe_create_user($sale_data, $course_ids = array(), $webhook_config = null) {
        // Verificar se já existe usuário com este e-mail
        if (!email_exists($sale_data['payer_email'])) {
            // Verificar se existe uma senha padrão configurada
            $default_password = get_option('webhook_receiver_default_password', '');
            
            // Se não houver senha padrão, gerar senha aleatória
            $password = !empty($default_password) ? $default_password : wp_generate_password(12, true);
            
            // Criar usuário
            $user_id = wp_create_user(
                sanitize_user($sale_data['payer_email']),
                $password,
                $sale_data['payer_email']
            );
            
            if (!is_wp_error($user_id)) {
                // Atualizar informações do usuário
                wp_update_user(array(
                    'ID' => $user_id,
                    'first_name' => $sale_data['first_name'],
                    'last_name' => $sale_data['last_name'],
                    'display_name' => $sale_data['first_name'] . ' ' . $sale_data['last_name']
                ));
               
                // Salvar metadados adicionais
                update_user_meta($user_id, 'cpf', $sale_data['cpf']);
                update_user_meta($user_id, 'telefone', $sale_data['telefone']);
                
                // Matricular usuário nos cursos principais
                if (!empty($course_ids)) {
                    $this->enroll_user_in_courses($user_id, $course_ids);
                }
                
                // Processar order bumps se houver
                if ($webhook_config) {
                    $this->process_order_bumps_enrollment($user_id, $sale_data, $webhook_config);
                }
                
                // Notificar administrador se configurado
                if (get_option('webhook_receiver_notify_admin', 'no') === 'yes') {
                    $admin_email = get_option('admin_email');
                    $subject = 'Novo usuário criado a partir de webhook';
                    $message = 'Um novo usuário foi criado a partir de uma venda: ' . $sale_data['first_name'] . ' ' . $sale_data['last_name'] . ' (' . $sale_data['payer_email'] . ')';
                    
                    wp_mail($admin_email, $subject, $message);
                }
                
		// Enviar e-mail para o usuário se configurado (usando função nova)
		if (get_option('webhook_receiver_notify_user', 'no') === 'yes') {
  		 $this->send_user_credentials_email($user_id, $password, $course_ids);
		}
                
                return $user_id;
            }
        } else {
            // Se o usuário já existe, apenas matriculá-lo nos cursos
            $user = get_user_by('email', $sale_data['payer_email']);
            if ($user) {
                if (!empty($course_ids)) {
                    $this->enroll_user_in_courses($user->ID, $course_ids);
                }
                
                // Processar order bumps se houver
                if ($webhook_config) {
                    $this->process_order_bumps_enrollment($user->ID, $sale_data, $webhook_config);
                }
                
                return $user->ID;
            }
        }
        
        return false;
    }
    
    /**
     * ================================================================================
     * PROCESSAR MATRICULAS DE ORDER BUMPS
     * ================================================================================
     */
    private function process_order_bumps_enrollment($user_id, $sale_data, $webhook_config) {
        // Log de debug
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("DEBUG: Iniciando processamento de order bumps para usuário $user_id");
            $this->log_webhook("DEBUG: Dados de produtos adicionais: " . print_r($sale_data['produtos_adicionais'], true));
        }
        
        // Verificar se há produtos adicionais
        if (empty($sale_data['produtos_adicionais']) || !is_array($sale_data['produtos_adicionais'])) {
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Nenhum produto adicional encontrado");
            }
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_order_bumps';
        
        // Obter configurações de order bumps para este webhook
        $order_bump_configs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s",
            $webhook_config->webhook_id
        ));
        
        if (empty($order_bump_configs)) {
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Nenhuma configuração de order bump encontrada para webhook: " . $webhook_config->webhook_id);
            }
            return;
        }
        
        // Log das configurações encontradas
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("DEBUG: Configurações de order bump encontradas: " . print_r($order_bump_configs, true));
        }
        
        // Criar um mapa de product_id => course_id para fácil acesso
        $order_bump_map = array();
        foreach ($order_bump_configs as $config) {
            // Adicionar múltiplas variações de ID para aumentar chances de match
            $order_bump_map[strval($config->product_id)] = $config->course_id;
            $order_bump_map[intval($config->product_id)] = $config->course_id;
            
            // Log de debug
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Mapeamento adicionado - ID: {$config->product_id} => Curso: {$config->course_id}");
            }
        }
        
        // Processar cada produto adicional
        $courses_to_enroll = array();
        foreach ($sale_data['produtos_adicionais'] as $produto_adicional) {
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Processando produto adicional: " . print_r($produto_adicional, true));
            }
            
            $matched = false;
            
            // Verificar por ID do produto (tentar várias chaves possíveis)
            $possible_id_keys = array('id', 'ID', 'product_id', 'produto_id', 'sku', 'SKU');
            foreach ($possible_id_keys as $key) {
                if (isset($produto_adicional[$key])) {
                    $product_id = $produto_adicional[$key];
                    
                    // Tentar match direto
                    if (isset($order_bump_map[$product_id])) {
                        $courses_to_enroll[] = $order_bump_map[$product_id];
                        $matched = true;
                        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                            $this->log_webhook("DEBUG: Match encontrado por ID ($key): $product_id => Curso: " . $order_bump_map[$product_id]);
                        }
                        break;
                    }
                    
                    // Tentar match como string
                    if (isset($order_bump_map[strval($product_id)])) {
                        $courses_to_enroll[] = $order_bump_map[strval($product_id)];
                        $matched = true;
                        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                            $this->log_webhook("DEBUG: Match encontrado por ID string ($key): $product_id => Curso: " . $order_bump_map[strval($product_id)]);
                        }
                        break;
                    }
                    
                    // Tentar match como inteiro
                    if (isset($order_bump_map[intval($product_id)])) {
                        $courses_to_enroll[] = $order_bump_map[intval($product_id)];
                        $matched = true;
                        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                            $this->log_webhook("DEBUG: Match encontrado por ID int ($key): $product_id => Curso: " . $order_bump_map[intval($product_id)]);
                        }
                        break;
                    }
                }
            }
            
            // Se não encontrou por ID, tentar por nome do produto
            if (!$matched) {
                $possible_name_keys = array('nome', 'name', 'product_name', 'produto_nome', 'title');
                foreach ($possible_name_keys as $key) {
                    if (isset($produto_adicional[$key])) {
                        $product_name = $produto_adicional[$key];
                        
                        foreach ($order_bump_configs as $config) {
                            // Comparação case-insensitive e com trim
                            if (strcasecmp(trim($config->product_name), trim($product_name)) === 0) {
                                $courses_to_enroll[] = $config->course_id;
                                $matched = true;
                                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                                    $this->log_webhook("DEBUG: Match encontrado por nome ($key): $product_name => Curso: {$config->course_id}");
                                }
                                break 2;
                            }
                            
                            // Tentar match parcial se o nome contém o configurado
                            if (stripos(trim($product_name), trim($config->product_name)) !== false || 
                                stripos(trim($config->product_name), trim($product_name)) !== false) {
                                $courses_to_enroll[] = $config->course_id;
                                $matched = true;
                                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                                    $this->log_webhook("DEBUG: Match parcial encontrado por nome ($key): $product_name => Curso: {$config->course_id}");
                                }
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if (!$matched && get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Nenhum match encontrado para produto adicional: " . print_r($produto_adicional, true));
            }
        }
        
        // Matricular o usuário nos cursos dos order bumps
        if (!empty($courses_to_enroll)) {
            $courses_to_enroll = array_unique($courses_to_enroll); // Remover duplicatas
            
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Matriculando usuário $user_id nos cursos de order bump: " . implode(', ', $courses_to_enroll));
            }
            
            $this->enroll_user_in_courses($user_id, $courses_to_enroll);
            
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("Order bumps processados com sucesso para o usuário $user_id: " . implode(', ', $courses_to_enroll));
            }
        } else {
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Nenhum curso de order bump para matricular");
            }
        }
    }
    
    /**
     * ================================================================================
     * FUNÇÃO AUXILIAR PARA TESTAR ORDER BUMPS MANUALMENTE
     * ================================================================================
     */
    public function test_order_bump_processing($webhook_id, $test_data = null) {
        if (!$test_data) {
            // Dados de teste padrão
            $test_data = array(
                'produtos_adicionais' => array(
                    array('id' => 'OB001', 'nome' => 'Order Bump Teste'),
                    array('id' => '123', 'nome' => 'Outro Produto')
                )
            );
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhook_config = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
        
        if ($webhook_config) {
            $this->process_order_bumps_enrollment(1, $test_data, $webhook_config);
        }
    }
    
    /**
     * ========================================
     * MATRICULAR USUÁRIOS EM CURSOS ESPECIFICOS
     * ========================================
     */
    public function enroll_user_in_courses($user_id, $course_ids = array()) {
        if (!empty($course_ids) && is_array($course_ids)) {
            // Verificar se a função do Tutor LMS está disponível
            if (function_exists('tutils')) {
                foreach ($course_ids as $course_id) {
                    if (!empty($course_id)) {
                        tutils()->do_enroll($course_id, 0, $user_id);
                    }
                }
            }
            
            // Atualizar meta do usuário (compatibilidade com o tema)
            $current_courses = get_user_meta($user_id, '_user_courses', true);
            if (!is_array($current_courses)) {
                $current_courses = array();
            }
            
            // Combinar cursos existentes com novos cursos
            $updated_courses = array_unique(array_merge($current_courses, $course_ids));
            update_user_meta($user_id, '_user_courses', $updated_courses);
        }
    }
    
    /**
     * ========================================
     * REGISTRAR OS DADOS RECEBIDOS EM LOGS
     * ========================================
     */
    public function log_webhook($data) {
        $log_dir = WEBHOOK_RECEIVER_PATH . 'logs';
        
        // Criar diretório de logs se não existir
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Criar arquivo .htaccess para proteger a pasta de logs
        if (!file_exists($log_dir . '/.htaccess')) {
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
        }
        
        // Nome do arquivo de log com data
        $log_file = $log_dir . '/webhook-' . date('Y-m-d') . '.log';
        
        // Formatar entrada de log
        $log_entry = '[' . date('Y-m-d H:i:s') . '] ' . $data . PHP_EOL;
        
        // Adicionar ao arquivo de log
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * ========================================
     * CRIAR TABELAS NECESSÁRIAS NA ATIVAÇÃO DO PLUGIN
     * ========================================
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabela de vendas
        $table_name = $wpdb->prefix . 'webhook_sales';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            payment_method varchar(50) NOT NULL,
            customer_name varchar(100) NOT NULL,
            customer_email varchar(100) NOT NULL,
            customer_cpf varchar(20) NOT NULL,
            customer_phone varchar(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            product_name varchar(255) NOT NULL,
            product_sku varchar(50) NOT NULL,
            product_price decimal(10,2) NOT NULL,
            product_quantity int(11) NOT NULL,
            product_category varchar(100) NOT NULL,
            additional_products text,
            payment_installments int(11) NOT NULL,
            delivery_link text,
            created_at datetime NOT NULL,
            raw_data longtext NOT NULL,
            webhook_id varchar(50) DEFAULT '',
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Tabela de configurações de webhooks (atualizada com campo webhook_data)
        $webhooks_table = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhooks_sql = "CREATE TABLE IF NOT EXISTS $webhooks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(50) NOT NULL,
            webhook_name varchar(100) NOT NULL,
            course_id bigint(20) NOT NULL DEFAULT 0,
            webhook_data longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY webhook_id (webhook_id)
        ) $charset_collate;";
        
        // NOVA TABELA: Cursos principais do webhook
        $webhook_courses_table = $wpdb->prefix . 'webhook_receiver_courses';
        $webhook_courses_sql = "CREATE TABLE IF NOT EXISTS $webhook_courses_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(50) NOT NULL,
            course_id bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id)
        ) $charset_collate;";
        
        // NOVA TABELA: Configurações de order bumps por webhook
        $order_bumps_table = $wpdb->prefix . 'webhook_receiver_order_bumps';
        $order_bumps_sql = "CREATE TABLE IF NOT EXISTS $order_bumps_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(50) NOT NULL,
            product_id varchar(100) NOT NULL,
            product_name varchar(255) NOT NULL,
            course_id bigint(20) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY webhook_id (webhook_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($webhooks_sql);
        dbDelta($webhook_courses_sql);
        dbDelta($order_bumps_sql);
        
        // Verificar se a coluna webhook_data existe e adicioná-la se não existir
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = %s AND column_name = 'webhook_data'",
            $webhooks_table
        ));
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $webhooks_table ADD COLUMN webhook_data longtext DEFAULT NULL");
        }
        
        // Migrar dados existentes de course_id único para múltiplos cursos
        $existing_webhooks = $wpdb->get_results("SELECT * FROM $webhooks_table WHERE course_id > 0");
        foreach ($existing_webhooks as $webhook) {
            // Verificar se já foi migrado
            $already_migrated = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $webhook_courses_table WHERE webhook_id = %s",
                $webhook->webhook_id
            ));
            
            if (!$already_migrated && $webhook->course_id > 0) {
                $wpdb->insert(
                    $webhook_courses_table,
                    array(
                        'webhook_id' => $webhook->webhook_id,
                        'course_id' => $webhook->course_id,
                        'created_at' => $webhook->created_at
                    )
                );
            }
        }
        
        // Adicionar opções padrão
        add_option('webhook_receiver_enable_logs', 'yes');
        add_option('webhook_receiver_create_users', 'no');
        add_option('webhook_receiver_notify_admin', 'no');
        add_option('webhook_receiver_notify_user', 'no');
        add_option('webhook_receiver_auto_enroll_courses', array());
        add_option('webhook_receiver_default_password', '');
    }
    
    /**
     * ========================================
     * ADICIONAR MENU DE ADMINISTRAÇÃO
     * ========================================
     */
    public function add_admin_menu() {
        add_menu_page(
            'Integração Plataformas',
            'Webhook Geral',
            'manage_options',
            'webhook-receiver',
            array($this, 'admin_page'),
            'dashicons-migrate',
            26
        );
        
        add_submenu_page(
            'webhook-receiver',
            'Configurações',
            'Configurações',
            'manage_options',
            'webhook-receiver-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'webhook-receiver',
            'Webhooks Únicos',
            'Webhooks Únicos',
            'manage_options',
            'webhook-receiver-endpoints',
            array($this, 'webhooks_page')
        );
        
        add_submenu_page(
            'webhook-receiver',
            'Vendas',
            'Vendas',
            'manage_options',
            'webhook-receiver-sales',
            array($this, 'sales_page')
        );
        
        add_submenu_page(
            'webhook-receiver',
            'Debug',
            'Debug',
            'manage_options',
            'webhook-receiver-debug',
            array($this, 'debug_page')
        );
        
        add_submenu_page(
            'webhook-receiver',
            'Matricular Aluno',
            '🎓 Matricular Aluno',
            'manage_options',
            'webhook-receiver-manual-enroll',
            array($this, 'manual_enroll_page')
        );
    }
    

 /**
     * ========================================
     * PÁGINA PRINCIPAL DA ADMINISTRAÇÃO
     * ========================================
     */
    public function admin_page() {
        ?>
        <div class="wrap webhook-receiver-admin">
            <div class="webhook-header">
                <h1><span class="webhook-icon">🔗</span> Supermembros Webhook</h1>
                <p class="webhook-subtitle">Sistema inteligente de recepção e processamento de webhooks</p>
            </div>
            
            <div class="webhook-card main-card">
                <div class="card-header">
                    <h2><span class="icon">📋</span> Instruções de Uso</h2>
                </div>
                <div class="card-content">
                    <div class="instruction-box">
                        <p>Configure sua plataforma para enviar webhooks para a seguinte URL:</p>
                        <div class="url-display">
                            <code><?php echo esc_url(rest_url('webhook-receiver/v1/receive')); ?></code>
                            <button class="copy-btn" onclick="copyToClipboard('<?php echo esc_url(rest_url('webhook-receiver/v1/receive')); ?>')">
                                <span class="copy-icon">📋</span> Copiar
                            </button>
                        </div>
                        
                        <p>Ou crie webhooks específicos para cursos na página <a href="<?php echo admin_url('admin.php?page=webhook-receiver-endpoints'); ?>" class="webhook-link">Webhooks</a>.</p>
                    </div>
                    
                    <div class="platforms-section">
                        <h3><span class="icon">🚀</span> Plataformas Suportadas Automaticamente</h3>
                        <div class="platforms-grid">
                            <div class="platform-card kiwify">
                                <div class="platform-header">
                                    <span class="platform-emoji">🥝</span>
                                    <h4>Kiwify</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">order_approved</span></p>
                                    <p><strong>Detecta:</strong> Customer, Product, webhook_event_type</p>
                                    <p class="platform-desc">Mapeia automaticamente dados do cliente e produto</p>
                                </div>
                            </div>
                            
                            <div class="platform-card hotmart">
                                <div class="platform-header">
                                    <span class="platform-emoji">🔥</span>
                                    <h4>Hotmart</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">Qualquer</span></p>
                                    <p><strong>Detecta:</strong> data.buyer, data.product</p>
                                    <p class="platform-desc">Suporte completo para estrutura Hotmart</p>
                                </div>
                            </div>
                            
                            <div class="platform-card eduzz">
                                <div class="platform-header">
                                    <span class="platform-emoji">📚</span>
                                    <h4>Eduzz</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">Qualquer</span></p>
                                    <p><strong>Detecta:</strong> cliente, produto</p>
                                    <p class="platform-desc">Mapeia campos cliente e produto da Eduzz</p>
                                </div>
                            </div>
                            
                            <div class="platform-card cakto">
                                <div class="platform-header">
                                    <span class="platform-emoji">🎯</span>
                                    <h4>Cakto</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">Qualquer</span></p>
                                    <p><strong>Detecta:</strong> customer, product, event_type</p>
                                    <p class="platform-desc">Estrutura customer/product da Cakto</p>
                                </div>
                            </div>
                            
                            <div class="platform-card ticto">
                                <div class="platform-header">
                                    <span class="platform-emoji">🎫</span>
                                    <h4>Ticto</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">Qualquer</span></p>
                                    <p><strong>Detecta:</strong> comprador, item</p>
                                    <p class="platform-desc">Mapeia comprador e item automaticamente</p>
                                </div>
                            </div>
                            
                            <div class="platform-card herospark">
                                <div class="platform-header">
                                    <span class="platform-emoji">⚡</span>
                                    <h4>HeroSpark</h4>
                                </div>
                                <div class="platform-info">
                                    <p><strong>Evento:</strong> <span class="event-tag">Qualquer</span></p>
                                    <p><strong>Detecta:</strong> buyer, offer</p>
                                    <p class="platform-desc">Suporte para buyer/offer do HeroSpark</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="requirements-section">
                        <h3><span class="icon">📋</span> Campos Obrigatórios Mínimos</h3>
                        <div class="requirements-grid">
                            <div class="requirement-item">
                                <span class="req-icon">📧</span>
                                <div>
                                    <strong>Email</strong>
                                    <p>Qualquer campo contendo email do cliente</p>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <span class="req-icon">👤</span>
                                <div>
                                    <strong>Nome</strong>
                                    <p>Nome ou nome completo do cliente</p>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <span class="req-icon">📦</span>
                                <div>
                                    <strong>Produto</strong>
                                    <p>Nome ou ID do produto</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tip-box">
                        <span class="tip-icon">💡</span>
                        <p><strong>Dica:</strong> O sistema detecta automaticamente a plataforma e mapeia os campos necessários. Não é preciso configurar nada especial!</p>
                    </div>
                    
                    <details class="legacy-format">
                        <summary>Formato de dados original (ainda suportado)</summary>
                        <div class="code-block">
                            <pre><code>{
    "event":"venda_aprovada",
    "payment_method":"credit_card",
    "data":{
        "first_name":"João",
        "last_name":"Silva",
        "cpf":"123.456.789-00",
        "telefone":"(11) 99999-9999",
        "payer_email":"teste@example.com",
        "product_name":"Produto de Teste",
        "product_price":97,
        "product_sku":"TESTE123",
        "product_quantity":1,
        "product_category":"educacao",
        "delivery_link":"https://seusite.com/entregavel/123",
        "produtos_adicionais":[
            {"id":"1","nome":"Order Bump Teste","preco":47,"quantidade":1}
        ],
        "metodo_pagamento":"Cartão de crédito",
        "parcelas":1,
        "product_id":9452176
    }
}</code></pre>
                        </div>
                    </details>
                </div>
            </div>
            
            <div class="webhook-card stats-card">
                <div class="card-header">
                    <h3><span class="icon">📊</span> Resumo de Vendas</h3>
                </div>
                <div class="card-content">
                    <?php
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'webhook_sales';
                    $total_sales = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                    $total_revenue = $wpdb->get_var("SELECT SUM(product_price * product_quantity) FROM $table_name");
                    ?>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $total_sales; ?></div>
                            <div class="stat-label">Total de Vendas</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">R$ <?php echo number_format($total_revenue, 2, ',', '.'); ?></div>
                            <div class="stat-label">Receita Total</div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="<?php echo admin_url('admin.php?page=webhook-receiver-sales'); ?>" class="webhook-btn primary">
                            <span class="btn-icon">👁️</span> Ver Todas as Vendas
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-admin {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
	    color: #ffffff;
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .webhook-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
        }

        .main-card {
            border-left: 4px solid #667eea;
        }

        .stats-card {
            border-left: 4px solid #48bb78;
        }

        .card-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2, .card-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
        }

        .icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }

        .card-content {
            padding: 30px;
        }

        .instruction-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .url-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #1a202c;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .url-display code {
            flex: 1;
            color: #68d391;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.9rem;
            background: none;
            padding: 0;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .copy-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .copy-icon {
            font-size: 0.9rem;
        }

        .webhook-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .webhook-link:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .platforms-section {
            margin-bottom: 30px;
        }

        .platforms-section h3 {
            font-size: 1.3rem;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .platforms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .platform-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .platform-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .platform-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .platform-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .platform-emoji {
            font-size: 1.8rem;
            margin-right: 12px;
        }

        .platform-header h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .platform-info p {
            margin: 8px 0;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .event-tag {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #2d3748;
        }

        .platform-desc {
            font-size: 0.85rem !important;
            color: #718096 !important;
            font-style: italic;
        }

        .requirements-section {
            margin-bottom: 30px;
        }

        .requirements-section h3 {
            font-size: 1.3rem;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 3px solid #48bb78;
        }

        .req-icon {
            font-size: 1.5rem;
        }

        .requirement-item strong {
            color: #2d3748;
            font-size: 0.95rem;
        }

        .requirement-item p {
            margin: 5px 0 0 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .tip-box {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .tip-icon {
            font-size: 1.5rem;
            margin-top: 2px;
        }

        .tip-box p {
            margin: 0;
            color: #742a2a;
            font-weight: 500;
        }

        .legacy-format {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .legacy-format summary {
            cursor: pointer;
            font-weight: 600;
            color: #4a5568;
            padding: 5px 0;
        }

        .legacy-format summary:hover {
            color: #2d3748;
        }

        .code-block {
            margin-top: 15px;
        }

        .code-block pre {
            background: #1a202c;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-item {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #718096;
            font-weight: 500;
        }

        .action-buttons {
            text-align: center;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-icon {
            font-size: 1rem;
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .webhook-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .webhook-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-admin {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .platforms-grid {
                grid-template-columns: 1fr;
            }

            .requirements-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .url-display {
                flex-direction: column;
                align-items: stretch;
            }

            .card-content {
                padding: 20px;
            }
        }
        </style>

        <script>
        function copyToClipboard(text) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showNotification('URL copiada para a área de transferência!');
                });
            } else {
                // Fallback para navegadores mais antigos
                const textArea = document.createElement('textarea');
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showNotification('URL copiada para a área de transferência!');
            }
        }

        function showNotification(message) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #48bb78;
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(72, 187, 120, 0.3);
                z-index: 10000;
                font-weight: 600;
                animation: slideInFromRight 0.3s ease-out;
            `;
            notification.textContent = message;
            
            // Adicionar animação CSS
            if (!document.getElementById('notification-styles')) {
                const style = document.createElement('style');
                style.id = 'notification-styles';
                style.textContent = `
                    @keyframes slideInFromRight {
                        from {
                            transform: translateX(100%);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideInFromRight 0.3s ease-out reverse';
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
        </script>
        <?php
    }
    
    /**
     * ========================================
     * PÁGINA DE DEBUG PARA TESTAR ORDER BUMPS
     * ========================================
     */
    public function debug_page() {
        global $wpdb;
        
        // Processar teste se enviado
        if (isset($_POST['test_order_bump']) && check_admin_referer('test_order_bump', 'test_nonce')) {
            $webhook_id = sanitize_key($_POST['webhook_id']);
            $product_id = sanitize_text_field($_POST['product_id']);
            $product_name = sanitize_text_field($_POST['product_name']);
            
            $test_data = array(
                'produtos_adicionais' => array(
                    array(
                        'id' => $product_id,
                        'nome' => $product_name
                    )
                )
            );
            
            echo '<div class="webhook-notice info"><span class="notice-icon">ℹ️</span><p>Testando order bump...</p></div>';
            
            // Ativar logs temporariamente
            $original_log_setting = get_option('webhook_receiver_enable_logs');
            update_option('webhook_receiver_enable_logs', 'yes');
            
            // Executar teste
            $this->test_order_bump_processing($webhook_id, $test_data);
            
            // Restaurar configuração de logs
            update_option('webhook_receiver_enable_logs', $original_log_setting);
            
            echo '<div class="webhook-notice success"><span class="notice-icon">✅</span><p>Teste concluído! Verifique os logs para detalhes.</p></div>';
        }
        
        // Obter webhooks e order bumps
        $webhooks_table = $wpdb->prefix . 'webhook_receiver_endpoints';
        $order_bumps_table = $wpdb->prefix . 'webhook_receiver_order_bumps';
        $webhooks = $wpdb->get_results("SELECT * FROM $webhooks_table ORDER BY webhook_name ASC");
        
        ?>
        <div class="wrap webhook-receiver-debug">
            <div class="webhook-header">
                <h1><span class="webhook-icon">🔍</span> Debug - Supermembros Webhook</h1>
                <p class="webhook-subtitle">Ferramentas de diagnóstico e teste</p>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">📊</span> Verificar Última Venda Recebida</h2>
                </div>
                <div class="card-content">
                    <?php
                    $sales_table = $wpdb->prefix . 'webhook_sales';
                    $last_sale = $wpdb->get_row("SELECT * FROM $sales_table ORDER BY id DESC LIMIT 1");
                    
                    if ($last_sale) {
                        $raw_data = json_decode($last_sale->raw_data, true);
                        
                        // Tentar detectar plataforma nos dados brutos
                        $detected_platform = 'Não detectado';
                        if ($raw_data) {
                            $temp_platform = $this->detect_platform($raw_data);
                            $detected_platform = ucfirst($temp_platform);
                        }
                        ?>
                        <div class="sale-info-grid">
                            <div class="info-item">
                                <span class="info-label">ID da Venda</span>
                                <span class="info-value">#<?php echo $last_sale->id; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data</span>
                                <span class="info-value"><?php echo $last_sale->created_at; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Cliente</span>
                                <span class="info-value"><?php echo esc_html($last_sale->customer_email); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Webhook</span>
                                <span class="info-value"><?php echo esc_html($last_sale->webhook_id ?: 'Padrão'); ?></span>
                            </div>
                            <div class="info-item full-width">
                                <span class="info-label">Plataforma Detectada</span>
                                <span class="platform-badge"><?php echo esc_html($detected_platform); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($raw_data['data']['produtos_adicionais'])) : ?>
                            <div class="additional-products">
                                <h4><span class="icon">📦</span> Produtos Adicionais Recebidos</h4>
                                <div class="code-display">
                                    <pre><code><?php echo esc_html(print_r($raw_data['data']['produtos_adicionais'], true)); ?></code></pre>
                                </div>
                            </div>
                        <?php else : ?>
                            <div class="empty-state">
                                <span class="empty-icon">📦</span>
                                <p>Nenhum produto adicional nesta venda.</p>
                            </div>
                        <?php endif; ?>
                        
                        <details class="raw-data-details">
                            <summary><span class="icon">🔍</span> Dados Completos</summary>
                            <div class="code-display">
                                <pre><code><?php echo esc_html(print_r($raw_data, true)); ?></code></pre>
                            </div>
                        </details>
                    <?php } else { ?>
                        <div class="empty-state">
                            <span class="empty-icon">📊</span>
                            <p>Nenhuma venda encontrada.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">🧪</span> Teste de Mapeamento de Plataformas</h2>
                </div>
                <div class="card-content">
                    <p class="section-description">Use esta seção para testar como diferentes payloads seriam mapeados pelo sistema.</p>
                    
                    <form method="post" class="webhook-form">
                        <?php wp_nonce_field('test_mapping', 'test_mapping_nonce'); ?>
                        
                        <div class="form-group">
                            <label for="test_json" class="form-label">
                                <span class="icon">📝</span> JSON de Teste
                            </label>
                            <textarea name="test_json" id="test_json" rows="10" class="form-textarea" placeholder='Cole aqui o JSON do webhook para testar o mapeamento automático...'></textarea>
                            <p class="form-description">Cole um payload de webhook para ver como seria mapeado</p>
                        </div>
                        
                        <button type="submit" name="test_mapping" class="webhook-btn primary">
                            <span class="btn-icon">🧪</span> Testar Mapeamento
                        </button>
                    </form>
                    
                    <?php
                    if (isset($_POST['test_mapping']) && check_admin_referer('test_mapping', 'test_mapping_nonce')) {
                        $test_json = sanitize_textarea_field($_POST['test_json']);
                        $test_data = json_decode($test_json, true);
                        
                        if ($test_data && json_last_error() === JSON_ERROR_NONE) {
                            echo '<div class="test-results">';
                            echo '<h4><span class="icon">📋</span> Resultado do Teste</h4>';
                            
                            $detected_platform = $this->detect_platform($test_data);
                            echo '<div class="platform-detection">';
                            echo '<span class="detection-label">Plataforma Detectada:</span>';
                            echo '<span class="platform-badge">' . ucfirst($detected_platform) . '</span>';
                            echo '</div>';
                            
                            $mapped_data = $this->map_webhook_data($test_data, 'teste');
                            
                            if ($mapped_data) {
                                echo '<div class="mapped-data">';
                                echo '<h5>Dados Mapeados:</h5>';
                                echo '<div class="mapping-table">';
                                
                                $important_fields = array(
                                    'first_name' => 'Nome',
                                    'last_name' => 'Sobrenome',
                                    'payer_email' => 'Email',
                                    'cpf' => 'CPF',
                                    'telefone' => 'Telefone',
                                    'product_name' => 'Nome do Produto',
                                    'product_id' => 'ID do Produto',
                                    'product_price' => 'Preço',
                                    'payment_method' => 'Método de Pagamento'
                                );
                                
                                foreach ($important_fields as $field => $label) {
                                    $value = isset($mapped_data[$field]) ? $mapped_data[$field] : 'N/A';
                                    echo '<div class="mapping-row">';
                                    echo '<span class="mapping-field">' . $label . '</span>';
                                    echo '<span class="mapping-value">' . esc_html($value) . '</span>';
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                                
                                echo '<details class="raw-mapped-details">';
                                echo '<summary>Dados Completos Mapeados</summary>';
                                echo '<div class="code-display">';
                                echo '<pre><code>' . esc_html(print_r($mapped_data, true)) . '</code></pre>';
                                echo '</div>';
                                echo '</details>';
                                echo '</div>';
                            } else {
                                echo '<div class="error-message">';
                                echo '<span class="error-icon">❌</span>';
                                echo '<p><strong>Erro:</strong> Não foi possível mapear os dados. Verifique se o JSON contém os campos necessários (especialmente email).</p>';
                                echo '</div>';
                            }
                        } else {
                            echo '<div class="error-message">';
                            echo '<span class="error-icon">❌</span>';
                            echo '<p><strong>Erro:</strong> JSON inválido. Verifique a sintaxe.</p>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">🔍</span> Verificar Dados de Webhooks</h2>
                </div>
                <div class="card-content">
                    <?php
                    $webhooks_table = $wpdb->prefix . 'webhook_receiver_endpoints';
                    $webhooks_with_data = $wpdb->get_results("SELECT webhook_id, webhook_name, webhook_data FROM $webhooks_table WHERE webhook_data IS NOT NULL AND webhook_data != ''");
                    
                    if (!empty($webhooks_with_data)) :
                    ?>
                        <div class="webhooks-data-table">
                            <div class="table-header">
                                <div class="header-cell">Webhook</div>
                                <div class="header-cell">Status dos Dados JSON</div>
                                <div class="header-cell">Tamanho dos Dados</div>
                                <div class="header-cell">Ações</div>
                            </div>
                            <?php foreach ($webhooks_with_data as $webhook_data) : 
                                $json_test = json_decode($webhook_data->webhook_data, true);
                                $json_valid = json_last_error() === JSON_ERROR_NONE;
                                $data_size = strlen($webhook_data->webhook_data);
                            ?>
                                <div class="table-row">
                                    <div class="table-cell">
                                        <strong><?php echo esc_html($webhook_data->webhook_name); ?></strong>
                                        <br>
                                        <small class="webhook-id"><?php echo esc_html($webhook_data->webhook_id); ?></small>
                                    </div>
                                    <div class="table-cell">
                                        <?php if ($json_valid) : ?>
                                            <span class="status-badge valid">✓ JSON Válido</span>
                                        <?php else : ?>
                                            <span class="status-badge invalid">✗ JSON Inválido</span>
                                            <br>
                                            <small class="error-msg"><?php echo json_last_error_msg(); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="table-cell">
                                        <span class="data-size"><?php echo number_format($data_size); ?> bytes</span>
                                    </div>
                                    <div class="table-cell">
                                        <button type="button" class="webhook-btn secondary small preview-webhook-data" 
                                                data-webhook-id="<?php echo esc_attr($webhook_data->webhook_id); ?>"
                                                data-webhook-name="<?php echo esc_attr($webhook_data->webhook_name); ?>">
                                            <span class="btn-icon">👁️</span> Visualizar
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="empty-state">
                            <span class="empty-icon">🔍</span>
                            <p>Nenhum webhook com dados encontrado.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Modal para visualizar dados do webhook -->
            <div id="webhook-data-modal" class="webhook-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><span class="icon">🔍</span> Dados do Webhook - <span id="preview-webhook-name" class="highlight-text"></span></h2>
                        <button type="button" class="close-modal-btn">✕</button>
                    </div>
                    <div class="modal-body">
                        <div class="code-display">
                            <pre id="webhook-data-display">Carregando...</pre>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="webhook-btn secondary close-modal">Fechar</button>
                    </div>
                </div>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">⚙️</span> Configurações de Order Bumps</h2>
                </div>
                <div class="card-content">
                    <?php foreach ($webhooks as $webhook) : 
                        $order_bumps = $wpdb->get_results($wpdb->prepare(
                            "SELECT * FROM $order_bumps_table WHERE webhook_id = %s",
                            $webhook->webhook_id
                        ));
                        
                        if (!empty($order_bumps)) :
                    ?>
                        <div class="order-bump-section">
                            <h3><span class="icon">🔗</span> <?php echo esc_html($webhook->webhook_name); ?> <small class="webhook-id">(<?php echo esc_html($webhook->webhook_id); ?>)</small></h3>
                            <div class="order-bumps-table">
                                <div class="table-header">
                                    <div class="header-cell">ID/SKU Configurado</div>
                                    <div class="header-cell">Nome Configurado</div>
                                    <div class="header-cell">Curso</div>
                                </div>
                                <?php foreach ($order_bumps as $ob) : 
                                    $course = get_post($ob->course_id);
                                ?>
                                    <div class="table-row">
                                        <div class="table-cell">
                                            <code class="product-code"><?php echo esc_html($ob->product_id); ?></code>
                                        </div>
                                        <div class="table-cell">
                                            <?php echo esc_html($ob->product_name); ?>
                                        </div>
                                        <div class="table-cell">
                                            <?php echo $course ? esc_html($course->post_title) : '<span class="error-text">Curso não encontrado</span>'; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">🧪</span> Testar Order Bump</h2>
                </div>
                <div class="card-content">
                    <form method="post" class="webhook-form">
                        <?php wp_nonce_field('test_order_bump', 'test_nonce'); ?>
                        
                        <div class="form-group">
                            <label for="webhook_id" class="form-label">
                                <span class="icon">🔗</span> Webhook
                            </label>
                            <select name="webhook_id" id="webhook_id" class="form-select" required>
                                <option value="">Selecione um webhook</option>
                                <?php foreach ($webhooks as $webhook) : ?>
                                    <option value="<?php echo esc_attr($webhook->webhook_id); ?>">
                                        <?php echo esc_html($webhook->webhook_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="product_id" class="form-label">ID do Produto</label>
                                <input type="text" name="product_id" id="product_id" class="form-input" required>
                                <p class="form-description">ID do produto adicional para testar</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="product_name" class="form-label">Nome do Produto</label>
                                <input type="text" name="product_name" id="product_name" class="form-input" required>
                                <p class="form-description">Nome do produto adicional para testar</p>
                            </div>
                        </div>
                        
                        <button type="submit" name="test_order_bump" class="webhook-btn primary">
                            <span class="btn-icon">🧪</span> Testar Order Bump
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="webhook-card">
                <div class="card-header">
                    <h2><span class="icon">📄</span> Logs Recentes</h2>
                </div>
                <div class="card-content">
                    <?php
                    $log_dir = WEBHOOK_RECEIVER_PATH . 'logs';
                    $log_file = $log_dir . '/webhook-' . date('Y-m-d') . '.log';
                    
                    if (file_exists($log_file)) {
                        $logs = file_get_contents($log_file);
                        $lines = explode("\n", $logs);
                        $recent_lines = array_slice($lines, -50); // Últimas 50 linhas
                        ?>
                        <div class="logs-container">
                            <div class="code-display">
                                <pre><?php echo esc_html(implode("\n", $recent_lines)); ?></pre>
                            </div>
                        </div>
                        <div class="logs-actions">
                            <a href="<?php echo admin_url('admin.php?page=webhook-receiver-debug&download_log=today'); ?>" class="webhook-btn secondary">
                                <span class="btn-icon">📥</span> Baixar Log Completo
                            </a>
                        </div>
                    <?php } else { ?>
                        <div class="empty-state">
                            <span class="empty-icon">📄</span>
                            <p>Nenhum log encontrado para hoje. Certifique-se de que os logs estão ativados nas configurações.</p>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-debug {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
	    color: #ffffff;
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #667eea;
        }

        .webhook-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
        }

        .card-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2, .card-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
        }

        .icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }

        .card-content {
            padding: 30px;
        }

        .webhook-notice {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid;
            animation: slideInFromLeft 0.3s ease-out;
        }

        .webhook-notice.info {
            background: #e6fffa;
            border-color: #38b2ac;
            color: #234e52;
        }

        .webhook-notice.success {
            background: #f0fff4;
            border-color: #48bb78;
            color: #22543d;
        }

        .notice-icon {
            font-size: 1.2rem;
        }

        .sale-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-item {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #667eea;
        }

        .info-item.full-width {
            grid-column: 1 / -1;
        }

        .info-label {
            display: block;
            font-size: 0.85rem;
            color: #718096;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .info-value {
            display: block;
            font-size: 1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .platform-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .additional-products {
            margin: 25px 0;
        }

        .additional-products h4 {
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .code-display {
            background: #1a202c;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
        }

        .code-display pre {
            margin: 0;
            color: #e2e8f0;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #718096;
        }

        .empty-icon {
            font-size: 3rem;
            display: block;
            margin-bottom: 15px;
            opacity: 0.6;
        }

        .empty-state p {
            font-size: 1.1rem;
            margin: 0;
        }

        .raw-data-details {
            margin-top: 25px;
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .raw-data-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #4a5568;
            padding: 5px 0;
            transition: color 0.3s ease;
        }

        .raw-data-details summary:hover {
            color: #2d3748;
        }

        .raw-data-details[open] summary {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .section-description {
            color: #718096;
            margin-bottom: 25px;
            font-size: 1rem;
        }

        .webhook-form {
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-textarea, .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-textarea {
            min-height: 200px;
            font-family: 'Fira Code', 'Consolas', monospace;
            resize: vertical;
        }

        .form-textarea:focus, .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-description {
            margin: 8px 0 0 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .webhook-btn.secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .webhook-btn.secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        .webhook-btn.small {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn-icon {
            font-size: 1rem;
        }

        .test-results {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-top: 25px;
        }

        .test-results h4 {
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .platform-detection {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
        }

        .detection-label {
            font-weight: 600;
            color: #4a5568;
        }

        .mapped-data h5 {
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .mapping-table {
            background: #f7fafc;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .mapping-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .mapping-row:last-child {
            border-bottom: none;
        }

        .mapping-field {
            font-weight: 600;
            color: #4a5568;
            flex: 1;
        }

        .mapping-value {
            color: #2d3748;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.9rem;
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .raw-mapped-details {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }

        .raw-mapped-details summary {
            cursor: pointer;
            font-weight: 600;
            color: #4a5568;
            padding: 5px 0;
        }

        .error-message {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            background: #fed7d7;
            border: 1px solid #fc8181;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }

        .error-icon {
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .error-message p {
            margin: 0;
            color: #742a2a;
        }

        .webhooks-data-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table-header {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .header-cell {
            padding: 15px;
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 1.5fr 1fr 1fr;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .table-row:hover {
            background: #f7fafc;
        }

        .table-cell {
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .webhook-id {
            color: #718096;
            font-size: 0.8rem;
            font-family: 'Fira Code', 'Consolas', monospace;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-badge.valid {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-badge.invalid {
            background: #fed7d7;
            color: #742a2a;
        }

        .error-msg {
            color: #e53e3e;
            font-size: 0.75rem;
            margin-top: 4px;
        }

        .data-size {
            font-family: 'Fira Code', 'Consolas', monospace;
            background: #f7fafc;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        /* Modal */
        .webhook-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideInFromBottom 0.3s ease-out;
        }

        .modal-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
            flex: 1;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #718096;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .close-modal-btn:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        .highlight-text {
            color: #667eea;
            font-weight: 700;
        }

        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            background: #f7fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            align-items: center;
        }

        .order-bump-section {
            margin-bottom: 30px;
        }

        .order-bump-section h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .order-bumps-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .product-code {
            background: #1a202c;
            color: #68d391;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
        }

        .error-text {
            color: #e53e3e;
            font-style: italic;
        }

        .logs-container {
            margin-bottom: 20px;
        }

        .logs-actions {
            text-align: center;
        }

        /* Animações */
        @keyframes slideInFromLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translate(-50%, -40%) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-debug {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .sale-info-grid {
                grid-template-columns: 1fr;
            }

            .mapping-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .platform-detection {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .table-header, .table-row {
                grid-template-columns: 1fr;
                text-align: left;
            }

            .table-cell {
                padding: 10px 15px;
                border-bottom: 1px solid #e2e8f0;
            }

            .table-row .table-cell:last-child {
                border-bottom: none;
            }

            .modal-content {
                width: 95%;
                margin: 20px 0;
                max-height: calc(100vh - 40px);
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 20px;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Visualizar dados do webhook na página de debug
            $('.preview-webhook-data').on('click', function() {
                var webhookId = $(this).data('webhook-id');
                var webhookName = $(this).data('webhook-name');
                
                $('#preview-webhook-name').text(webhookName);
                $('#webhook-data-display').text('Carregando...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'webhook_receiver_get_webhook_data',
                        webhook_id: webhookId,
                        nonce: '<?php echo wp_create_nonce('webhook_receiver_get_data'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#webhook-data-display').text(response.data.formatted_data);
                        } else {
                            $('#webhook-data-display').text('Erro: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $('#webhook-data-display').text('Erro ao carregar dados.');
                    }
                });
                
                $('#webhook-data-modal').fadeIn(300);
            });
            
            // Fechar modal na página de debug
            $(document).on('click', '.close-modal, .close-modal-btn, .modal-backdrop', function() {
                $('#webhook-data-modal').fadeOut(300);
            });
        });
        </script>
        <?php
    }
    
   /**
     * ========================================
     * PÁGINA DE GERENCIAMENTO DE WEBHOOKS
     * ========================================
     */
    public function webhooks_page() {
        // Incluir scripts e estilos necessários
        wp_enqueue_script('jquery');
        ?><div class="wrap webhook-receiver-webhooks"><br><br>
            <div class="wrap webhook-receiver-debug">           

            <div class="webhook-card settings-card">
                <div class="card-content">
                    <form method="post" action="options.php" class="settings-form">
                        <?php
                        settings_fields('webhook_receiver_settings');
                        do_settings_sections('webhook_receiver_settings');
                        ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="webhook-btn primary">
                                <span class="btn-icon">💾</span> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-settings {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .settings-card {
            border-left: 4px solid #ed8936;
        }

        .card-content {
            padding: 30px;
        }

        .settings-form .form-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .settings-form .form-table th {
            padding: 15px;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        }

        .settings-form .form-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .settings-form input[type="text"],
        .settings-form select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .settings-form input[type="text"]:focus,
        .settings-form select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .settings-form .description {
            color: #718096;
            font-size: 0.85rem;
            margin-top: 5px;
            font-style: italic;
        }

        .form-actions {
            text-align: center;
            padding: 20px 0;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-icon {
            font-size: 1rem;
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-settings {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .card-content {
                padding: 20px;
            }
        }
        </style><div class="wrap webhook-receiver-webhooks">
            <div class="wrap webhook-receiver-debug">
            
            <div class="webhook-card create-card">
                <div class="card-header">
                    <h2><span class="icon">➕</span> Criar Novo Webhook</h2>
                </div>
                <div class="card-content">
                    <form id="webhook-form" class="webhook-form">
                        <input type="hidden" name="action" value="webhook_receiver_save_webhook">
                        <?php wp_nonce_field('webhook_receiver_save_webhook', 'webhook_nonce'); ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="webhook-name" class="form-label">
                                    <span class="icon">🏷️</span> Nome do Webhook
                                </label>
                                <input type="text" id="webhook-name" name="webhook_name" class="form-input" required>
                                <p class="form-description">Um nome descritivo para identificar este webhook (ex: "Hotmart - Curso Básico").</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="webhook-id" class="form-label">
                                    <span class="icon">🔑</span> ID do Webhook
                                </label>
                                <input type="text" id="webhook-id" name="webhook_id" class="form-input" required pattern="[a-zA-Z0-9_-]+">
                                <p class="form-description">Um identificador único para este webhook (apenas letras, números, traços e sublinhados).</p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <span class="icon">📚</span> Cursos Principais
                            </label>
                            <div class="courses-container">
                                <?php
                                $courses = get_posts(array('post_type' => 'courses', 'numberposts' => -1));
                                if (!empty($courses)) {
                                    foreach ($courses as $course) {
                                        echo '<label class="course-checkbox">';
                                        echo '<input type="checkbox" name="course_ids[]" value="' . esc_attr($course->ID) . '">';
                                        echo '<span class="checkmark"></span>';
                                        echo '<span class="course-title">' . esc_html($course->post_title) . '</span>';
                                        echo '</label>';
                                    }
                                } else {
                                    echo '<div class="empty-state"><span class="empty-icon">📚</span><p>Nenhum curso encontrado.</p></div>';
                                }
                                ?>
                            </div>
                            <div class="course-actions">
                                <button type="button" id="select-all-courses" class="webhook-btn secondary small">Selecionar Todos</button>
                                <button type="button" id="deselect-all-courses" class="webhook-btn secondary small">Desmarcar Todos</button>
                            </div>
                            <p class="form-description">Selecione os cursos nos quais o usuário será matriculado ao comprar o produto principal.</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="webhook-btn primary">
                                <span class="btn-icon">✨</span> Criar Webhook
                            </button>
                            <div class="loading-spinner">
                                <div class="spinner"></div>
                                <span>Criando webhook...</span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="webhook-card list-card">
                <div class="card-header">
                    <h2><span class="icon">📋</span> Webhooks Existentes</h2>
                </div>
                <div class="card-content">
                    <div id="webhooks-list">
                        <?php $this->display_webhooks_list(); ?>
                    </div>
                </div>
            </div>
            
            <!-- Modal para gerenciar cursos principais -->
            <div id="main-courses-modal" class="webhook-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <div class="modal-header">
                        <h2><span class="icon">📚</span> Gerenciar Cursos Principais - <span id="modal-main-webhook-name" class="highlight-text"></span></h2>
                        <button type="button" class="close-modal-btn">✕</button>
                    </div>
                    <input type="hidden" id="modal-main-webhook-id" value="">
                    
                    <div class="modal-body">
                        <div class="main-courses-form">
                            <h3>Selecione os Cursos Principais</h3>
                            <div class="courses-container modal-courses">
                                <?php
                                if (!empty($courses)) {
                                    foreach ($courses as $course) {
                                        echo '<label class="course-checkbox">';
                                        echo '<input type="checkbox" class="main-course-checkbox" name="main_course_ids[]" value="' . esc_attr($course->ID) . '">';
                                        echo '<span class="checkmark"></span>';
                                        echo '<span class="course-title">' . esc_html($course->post_title) . '</span>';
                                        echo '</label>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="course-actions">
                                <button type="button" id="select-all-main-courses" class="webhook-btn secondary small">Selecionar Todos</button>
                                <button type="button" id="deselect-all-main-courses" class="webhook-btn secondary small">Desmarcar Todos</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" id="save-main-courses" class="webhook-btn primary">
                            <span class="btn-icon">💾</span> Salvar Cursos Principais
                        </button>
                        <div class="loading-spinner modal-spinner">
                            <div class="spinner"></div>
                            <span>Salvando...</span>
                        </div>
                        <button type="button" class="webhook-btn secondary close-modal">Cancelar</button>
                    </div>
                </div>
            </div>
            
            <!-- Modal para gerenciar order bumps -->
            <div id="order-bumps-modal" class="webhook-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content large">
                    <div class="modal-header">
                        <h2><span class="icon">🛍️</span> Gerenciar Order Bumps - <span id="modal-webhook-name" class="highlight-text"></span></h2>
                        <button type="button" class="close-modal-btn">✕</button>
                    </div>
                    <input type="hidden" id="modal-webhook-id" value="">
                    
                    <div class="modal-body">
                        <div class="order-bump-form">
                            <h3><span class="icon">➕</span> Adicionar Order Bump</h3>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="order-bump-product-id" class="form-label">ID/SKU do Produto</label>
                                    <input type="text" id="order-bump-product-id" class="form-input">
                                    <p class="form-description">ID ou SKU do produto adicional (order bump)</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order-bump-product-name" class="form-label">Nome do Produto</label>
                                    <input type="text" id="order-bump-product-name" class="form-input">
                                    <p class="form-description">Nome descritivo do order bump</p>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order-bump-course-id" class="form-label">Curso a Liberar</label>
                                    <select id="order-bump-course-id" class="form-select">
                                        <option value="">Selecione um curso</option>
                                        <?php
                                        foreach ($courses as $course) {
                                            echo '<option value="' . esc_attr($course->ID) . '">' . esc_html($course->post_title) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <button type="button" id="add-order-bump" class="webhook-btn primary">
                                    <span class="btn-icon">➕</span> Adicionar Order Bump
                                </button>
                                <div class="loading-spinner">
                                    <div class="spinner"></div>
                                    <span>Adicionando...</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-bumps-list">
                            <h3><span class="icon">📋</span> Order Bumps Configurados</h3>
                            <div id="order-bumps-table"></div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="webhook-btn secondary close-modal">Fechar</button>
                    </div>
                </div>
            </div>
            
            <!-- Modal para mostrar estrutura JSON -->
            <div id="json-structure-modal" class="webhook-modal">
                <div class="modal-backdrop"></div>
                <div class="modal-content large">
                    <div class="modal-header">
                        <h2><span class="icon">🔍</span> Estrutura do JSON - <span id="modal-json-webhook-name" class="highlight-text"></span></h2>
                        <button type="button" class="close-modal-btn">✕</button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="json-structure-content">
                            <h3>Dados recebidos pelo webhook:</h3>
                            <div class="code-display">
                                <pre id="json-structure-display">Carregando dados...</pre>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="webhook-btn secondary close-modal">Fechar</button>
                    </div>
                </div>
            </div>
        </div>

        <style>
      .webhook-receiver-admin {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
	    color: #ffffff;
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .webhook-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 35px rgba(0, 0, 0, 0.12);
        }

        .main-card {
            border-left: 4px solid #667eea;
        }

        .stats-card {
            border-left: 4px solid #48bb78;
        }

        .card-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2, .card-header h3 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
        }

        .icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }

        .card-content {
            padding: 30px;
        }

        .instruction-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .url-display {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 15px 0;
            padding: 15px;
            background: #1a202c;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .url-display code {
            flex: 1;
            color: #68d391;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.9rem;
            background: none;
            padding: 0;
        }

        .copy-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .copy-btn:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .copy-icon {
            font-size: 0.9rem;
        }

        .webhook-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .webhook-link:hover {
            color: #5a67d8;
            text-decoration: underline;
        }

        .platforms-section {
            margin-bottom: 30px;
        }

        .platforms-section h3 {
            font-size: 1.3rem;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .platforms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .platform-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            background: white;
            position: relative;
            overflow: hidden;
        }

        .platform-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .platform-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .platform-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .platform-emoji {
            font-size: 1.8rem;
            margin-right: 12px;
        }

        .platform-header h4 {
            margin: 0;
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .platform-info p {
            margin: 8px 0;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .event-tag {
            background: #e2e8f0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #2d3748;
        }

        .platform-desc {
            font-size: 0.85rem !important;
            color: #718096 !important;
            font-style: italic;
        }

        .requirements-section {
            margin-bottom: 30px;
        }

        .requirements-section h3 {
            font-size: 1.3rem;
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .requirements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            border-left: 3px solid #48bb78;
        }

        .req-icon {
            font-size: 1.5rem;
        }

        .requirement-item strong {
            color: #2d3748;
            font-size: 0.95rem;
        }

        .requirement-item p {
            margin: 5px 0 0 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .tip-box {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .tip-icon {
            font-size: 1.5rem;
            margin-top: 2px;
        }

        .tip-box p {
            margin: 0;
            color: #742a2a;
            font-weight: 500;
        }

        .legacy-format {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
        }

        .legacy-format summary {
            cursor: pointer;
            font-weight: 600;
            color: #4a5568;
            padding: 5px 0;
        }

        .legacy-format summary:hover {
            color: #2d3748;
        }

        .code-block {
            margin-top: 15px;
        }

        .code-block pre {
            background: #1a202c;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-item {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .stat-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 0.95rem;
            color: #718096;
            font-weight: 500;
        }

        .action-buttons {
            text-align: center;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        .btn-icon {
            font-size: 1rem;
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .webhook-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .webhook-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-admin {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .platforms-grid {
                grid-template-columns: 1fr;
            }

            .requirements-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .url-display {
                flex-direction: column;
                align-items: stretch;
            }

            .card-content {
                padding: 20px;
            }
        }

        .create-card {
            border-left: 4px solid #48bb78;
        }

        .list-card {
            border-left: 4px solid #667eea;
        }

        .webhook-form {
            background: #f7fafc;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-description {
            margin: 8px 0 0 0;
            font-size: 0.85rem;
            color: #718096;
        }

        .courses-container {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            max-height: 250px;
            overflow-y: auto;
            padding: 15px;
            margin-bottom: 15px;
        }

        .course-checkbox {
            display: flex;
            align-items: center;
            padding: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 6px;
            margin: 0 0 5px 0;
            padding: 8px 12px;
        }

        .course-checkbox:hover {
            background: #f7fafc;
        }

        .course-checkbox input[type="checkbox"] {
            opacity: 0;
            position: absolute;
            width: 0;
            height: 0;
        }

        .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 4px;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .course-checkbox input[type="checkbox"]:checked + .checkmark {
            background: #667eea;
            border-color: #667eea;
        }

        .course-checkbox input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            color: white;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .course-title {
            color: #2d3748;
            font-weight: 500;
        }

        .course-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .webhook-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .webhook-btn:hover::before {
            left: 100%;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .webhook-btn.secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .webhook-btn.secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        .webhook-btn.small {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn-icon {
            font-size: 1rem;
        }

        .form-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .loading-spinner {
            display: none;
            align-items: center;
            gap: 10px;
            color: #718096;
            font-size: 0.9rem;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modais */
        .webhook-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideInFromBottom 0.3s ease-out;
        }

        .modal-content.large {
            max-width: 1000px;
        }

        .modal-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
            flex: 1;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #718096;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .close-modal-btn:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        .highlight-text {
            color: #667eea;
            font-weight: 700;
        }

        .modal-body {
            padding: 30px;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            background: #f7fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            align-items: center;
        }

        .main-courses-form, .order-bump-form {
            background: #f7fafc;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 25px;
        }

        .main-courses-form h3, .order-bump-form h3 {
            margin: 0 0 20px 0;
            color: #2d3748;
            font-weight: 600;
        }

        .modal-courses {
            max-height: 200px;
        }

        .modal-spinner {
            display: none;
        }

        .order-bumps-list {
            margin-top: 30px;
        }

        .order-bumps-list h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .json-structure-content h3 {
            color: #2d3748;
            margin-bottom: 15px;
            font-weight: 600;
        }

        /* Tabela de webhooks */
        .webhooks-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .webhooks-table th,
        .webhooks-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .webhooks-table th {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }

        .webhooks-table tr:hover {
            background: #f7fafc;
        }

        .webhook-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-configured {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-waiting {
            background: #fef5e7;
            color: #744210;
        }

        .webhook-url {
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.8rem;
            background: #f7fafc;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .action-btn.copy {
            background: #e6fffa;
            color: #234e52;
            border: 1px solid #81e6d9;
        }

        .action-btn.copy:hover {
            background: #b2f5ea;
        }

        .action-btn.manage {
            background: #e6f3ff;
            color: #2c5282;
            border: 1px solid #90cdf4;
        }

        .action-btn.manage:hover {
            background: #bee3f8;
        }

        .action-btn.listen {
            background: #fef5e7;
            color: #744210;
            border: 1px solid #f6e05e;
        }

        .action-btn.listen:hover {
            background: #faf089;
        }

        .action-btn.delete {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .action-btn.delete:hover {
            background: #feb2b2;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translate(-50%, -40%) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .webhook-card:nth-child(2) {
            animation-delay: 0.1s;
        }

        .webhook-card:nth-child(3) {
            animation-delay: 0.2s;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-webhooks {
                margin: 0 -10px;
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .modal-content {
                width: 95%;
                margin: 20px 0;
                max-height: calc(100vh - 40px);
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 20px;
            }

            .webhooks-table {
                font-size: 0.9rem;
            }

            .webhooks-table th,
            .webhooks-table td {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .course-actions {
                flex-direction: column;
            }
        }
        </style>
            
                   <script>
        jQuery(document).ready(function($) {
            // Variável para controlar o spinner
            var isSubmitting = false;
            
            // Gerar ID do webhook com base no nome
            $('#webhook-name').on('blur', function() {
                if ($('#webhook-id').val() === '') {
                    var webhookName = $(this).val();
                    var webhookId = webhookName
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    $('#webhook-id').val(webhookId);
                }
            });
            
            // Botões de selecionar/desmarcar todos os cursos
            $('#select-all-courses').on('click', function() {
                $('input[name="course_ids[]"]').prop('checked', true);
                animateCheckboxes($('input[name="course_ids[]"]'));
            });
            
            $('#deselect-all-courses').on('click', function() {
                $('input[name="course_ids[]"]').prop('checked', false);
            });
            
            // Função para animar checkboxes
            function animateCheckboxes(checkboxes) {
                checkboxes.each(function(index) {
                    var checkbox = $(this);
                    setTimeout(function() {
                        checkbox.closest('.course-checkbox').addClass('animate-check');
                        setTimeout(function() {
                            checkbox.closest('.course-checkbox').removeClass('animate-check');
                        }, 200);
                    }, index * 50);
                });
            }
            
            // Enviar formulário via AJAX
            $('#webhook-form').on('submit', function(e) {
                e.preventDefault();
                
                if (isSubmitting) return;
                
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var spinner = form.find('.loading-spinner');
                
                // Verificar se pelo menos um curso foi selecionado
                if ($('input[name="course_ids[]"]:checked').length === 0) {
                    showNotification('Selecione pelo menos um curso principal!', 'error');
                    return;
                }
                
                isSubmitting = true;
                submitBtn.hide();
                spinner.show();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            form.find('input[type=text]').val('');
                            form.find('input[type=checkbox]').prop('checked', false);
                            $('#webhooks-list').html(response.data.html);
                            showNotification('Webhook criado com sucesso!', 'success');
                        } else {
                            showNotification('Erro ao criar webhook: ' + response.data.message, 'error');
                        }
                    },
                    error: function() {
                        showNotification('Erro ao comunicar com o servidor.', 'error');
                    },
                    complete: function() {
                        isSubmitting = false;
                        submitBtn.show();
                        spinner.hide();
                    }
                });
            });
            
            // Gerenciar cursos principais
            $(document).on('click', '.manage-main-courses', function(e) {
                e.preventDefault();
                var webhookId = $(this).data('webhook-id');
                var webhookName = $(this).data('webhook-name');
                
                $('#modal-main-webhook-id').val(webhookId);
                $('#modal-main-webhook-name').text(webhookName);
                
                // Carregar cursos principais existentes
                loadMainCourses(webhookId);
                
                $('#main-courses-modal').fadeIn(300);
            });
            
            // Função para mostrar notificações
            function showNotification(message, type = 'info') {
                var bgColor = '#48bb78';
                var icon = '✅';
                
                if (type === 'error') {
                    bgColor = '#f56565';
                    icon = '❌';
                } else if (type === 'warning') {
                    bgColor = '#ed8936';
                    icon = '⚠️';
                } else if (type === 'info') {
                    bgColor = '#4299e1';
                    icon = 'ℹ️';
                }
                
                var notification = $('<div class="webhook-notification">' + icon + ' ' + message + '</div>');
                notification.css({
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    background: bgColor,
                    color: 'white',
                    padding: '15px 20px',
                    borderRadius: '8px',
                    boxShadow: '0 4px 20px rgba(0,0,0,0.3)',
                    zIndex: 100001,
                    fontWeight: '600',
                    transform: 'translateX(100%)',
                    transition: 'transform 0.3s ease-out'
                });
                
                $('body').append(notification);
                
                setTimeout(function() {
                    notification.css('transform', 'translateX(0)');
                }, 100);
                
                setTimeout(function() {
                    notification.css('transform', 'translateX(100%)');
                    setTimeout(function() {
                        notification.remove();
                    }, 300);
                }, 3000);
            }
                
                // Salvar cursos principais
                $('#save-main-courses').on('click', function() {
                    var webhookId = $('#modal-main-webhook-id').val();
                    var selectedCourses = [];
                    
                    $('.main-course-checkbox:checked').each(function() {
                        selectedCourses.push($(this).val());
                    });
                    
                    if (selectedCourses.length === 0) {
                        alert('Selecione pelo menos um curso principal!');
                        return;
                    }
                    
                    var spinner = $(this).siblings('.spinner');
                    spinner.css('visibility', 'visible');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_save_main_courses',
                            webhook_id: webhookId,
                            course_ids: selectedCourses,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_main_courses'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Atualizar lista de webhooks
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'webhook_receiver_get_webhooks_list',
                                        nonce: '<?php echo wp_create_nonce('webhook_receiver_main_courses'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            $('#webhooks-list').html(response.data.html);
                                        }
                                    }
                                });
                                
                                alert('Cursos principais atualizados com sucesso!');
                                $('#main-courses-modal').hide();
                            } else {
                                alert('Erro: ' + response.data.message);
                            }
                        },
                        complete: function() {
                            spinner.css('visibility', 'hidden');
                        }
                    });
                });
                
                // Função para carregar cursos principais
                function loadMainCourses(webhookId) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_get_main_courses',
                            webhook_id: webhookId,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_main_courses'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                // Desmarcar todos primeiro
                                $('.main-course-checkbox').prop('checked', false);
                                
                                // Marcar os cursos salvos
                                response.data.course_ids.forEach(function(courseId) {
                                    $('.main-course-checkbox[value="' + courseId + '"]').prop('checked', true);
                                });
                            }
                        }
                    });
                }
                
                // Fechar modais
                $('.close-modal, .modal-backdrop').on('click', function() {
                    $('#order-bumps-modal, #main-courses-modal, #json-structure-modal').hide();
                });
                
                // Gerenciar order bumps
                $(document).on('click', '.manage-order-bumps', function(e) {
                    e.preventDefault();
                    var webhookId = $(this).data('webhook-id');
                    var webhookName = $(this).data('webhook-name');
                    
                    $('#modal-webhook-id').val(webhookId);
                    $('#modal-webhook-name').text(webhookName);
                    
                    // Carregar order bumps existentes
                    loadOrderBumps(webhookId);
                    
                    $('#order-bumps-modal').show();
                });
                
                // Adicionar order bump
                $('#add-order-bump').on('click', function() {
                    var webhookId = $('#modal-webhook-id').val();
                    var productId = $('#order-bump-product-id').val();
                    var productName = $('#order-bump-product-name').val();
                    var courseId = $('#order-bump-course-id').val();
                    
                    if (!productId || !productName || !courseId) {
                        alert('Preencha todos os campos!');
                        return;
                    }
                    
                    var spinner = $(this).siblings('.spinner');
                    spinner.css('visibility', 'visible');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_save_order_bump',
                            webhook_id: webhookId,
                            product_id: productId,
                            product_name: productName,
                            course_id: courseId,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_order_bump'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#order-bump-product-id').val('');
                                $('#order-bump-product-name').val('');
                                $('#order-bump-course-id').val('');
                                loadOrderBumps(webhookId);
                                alert('Order bump adicionado com sucesso!');
                            } else {
                                alert('Erro: ' + response.data.message);
                            }
                        },
                        complete: function() {
                            spinner.css('visibility', 'hidden');
                        }
                    });
                });
                
                // Deletar order bump
                $(document).on('click', '.delete-order-bump', function() {
                    if (!confirm('Tem certeza que deseja excluir este order bump?')) {
                        return;
                    }
                    
                    var orderBumpId = $(this).data('id');
                    var webhookId = $('#modal-webhook-id').val();
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_delete_order_bump',
                            order_bump_id: orderBumpId,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_order_bump'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                loadOrderBumps(webhookId);
                            }
                        }
                    });
                });
                
                // Função para carregar order bumps
                function loadOrderBumps(webhookId) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_get_order_bumps',
                            webhook_id: webhookId,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_order_bump'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#order-bumps-table').html(response.data.html);
                            }
                        }
                    });
                }
                
                // Deletar webhook via AJAX
                $(document).on('click', '.delete-webhook', function(e) {
                    e.preventDefault();
                    
                    if (confirm('Tem certeza que deseja excluir este webhook?')) {
                        var webhookId = $(this).data('webhook-id');
                        var spinner = $(this).siblings('.spinner');
                        spinner.css('visibility', 'visible');
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'webhook_receiver_delete_webhook',
                                webhook_id: webhookId,
                                nonce: $('#webhook_nonce').val()
                            },
                            success: function(response) {
                                if (response.success) {
                                    $('#webhooks-list').html(response.data.html);
                                    alert('Webhook excluído com sucesso!');
                                } else {
                                    alert('Erro ao excluir webhook: ' + response.data.message);
                                }
                            },
                            error: function() {
                                alert('Erro ao comunicar com o servidor.');
                            },
                            complete: function() {
                                spinner.css('visibility', 'hidden');
                            }
                        });
                    }
                });
                
                // Copiar URL
                $(document).on('click', '.copy-url', function() {
                    var url = $(this).data('url');
                    var tempInput = $('<input>');
                    $('body').append(tempInput);
                    tempInput.val(url).select();
                    document.execCommand('copy');
                    tempInput.remove();
                    
                    var button = $(this);
                    button.text('Copiado!');
                    setTimeout(function() {
                        button.text('Copiar');
                    }, 2000);
                });
                
                // Escutar webhook
                $(document).on('click', '.listen-webhook', function() {
                    var button = $(this);
                    var webhookId = button.data('webhook-id');
                    var originalText = button.text();
                    
                    function checkWebhook() {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'webhook_receiver_listen_webhook',
                                webhook_id: webhookId,
                                nonce: '<?php echo wp_create_nonce('webhook_receiver_listen'); ?>'
                            },
                            success: function(response) {
                                if (response.success && response.data.webhook_received) {
                                    clearInterval(interval);
                                    location.reload();
                                }
                            }
                        });
                    }
                    
                    var interval = setInterval(checkWebhook, 5000);
                    
                    setTimeout(function() {
                        clearInterval(interval);
                        button.prop('disabled', false).text(originalText);
                    }, 120000); // 2 minutos
                    
                    button.prop('disabled', true).text('Escutando...');
                });
                
                // Mostrar estrutura JSON
                $(document).on('click', '.show-json-structure', function() {
                    var webhookId = $(this).data('webhook-id');
                    var webhookName = $(this).data('webhook-name');
                    
                    $('#modal-json-webhook-name').text(webhookName);
                    $('#json-structure-display').html('<p>Carregando dados...</p>');
                    
                    // Buscar dados do webhook via AJAX
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'webhook_receiver_get_webhook_data',
                            webhook_id: webhookId,
                            nonce: '<?php echo wp_create_nonce('webhook_receiver_get_data'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#json-structure-display').html('<pre>' + response.data.formatted_data + '</pre>');
                            } else {
                                $('#json-structure-display').html('<p style="color: red;">Erro: ' + response.data.message + '</p>');
                            }
                        },
                        error: function() {
                            $('#json-structure-display').html('<p style="color: red;">Erro ao carregar dados do webhook.</p>');
                        }
                    });
                    
                    $('#json-structure-modal').show();
                });
            });
            </script>
        </div>
        <?php
    }
    
/**
     * ========================================
     * EXIBIR LISTA DE WEBHOOKS (DESIGN MODERNO)
     * ========================================
     */
    public function display_webhooks_list() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhook_courses_table = $wpdb->prefix . 'webhook_receiver_courses';
        $order_bumps_table = $wpdb->prefix . 'webhook_receiver_order_bumps';
        $webhooks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY webhook_name ASC");
        
        if (empty($webhooks)) {
            echo '<div class="empty-state"><span class="empty-icon">🔗</span><p>Nenhum webhook configurado.</p></div>';
            return;
        }
        
        ?>
        <div class="webhooks-grid">
            <?php foreach ($webhooks as $webhook) : 
                // Obter cursos principais
                $course_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT course_id FROM $webhook_courses_table WHERE webhook_id = %s",
                    $webhook->webhook_id
                ));
                
                $course_titles = array();
                foreach ($course_ids as $course_id) {
                    $course = get_post($course_id);
                    if ($course) {
                        $course_titles[] = $course->post_title;
                    }
                }
                
                $courses_display = !empty($course_titles) ? implode(', ', $course_titles) : 'Nenhum curso configurado';
                $courses_count = count($course_titles);
                
                // Contar order bumps
                $order_bumps_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $order_bumps_table WHERE webhook_id = %s",
                    $webhook->webhook_id
                ));
                
                $webhook_url = rest_url('webhook-receiver/v1/receive/' . $webhook->webhook_id);
                
                // Verificar se webhook recebeu dados
                $has_webhook_data = !empty($webhook->webhook_data);
            ?>
                <div class="webhook-item-card">
                    <div class="webhook-item-header">
                        <h3 class="webhook-name">
                            <span class="icon">🔗</span>
                            <?php echo esc_html($webhook->webhook_name); ?>
                        </h3>
                        <div class="webhook-status-badge">
                            <?php if ($has_webhook_data) : ?>
                                <span class="webhook-status status-configured">✓ Configurado</span>
                            <?php else : ?>
                                <span class="webhook-status status-waiting">⏳ Aguardando</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="webhook-item-content">
                        <div class="webhook-info-grid">
                            <div class="info-section">
                                <span class="info-label">ID do Webhook</span>
                                <code class="webhook-id-display"><?php echo esc_html($webhook->webhook_id); ?></code>
                            </div>
                            
                            <div class="info-section">
                                <span class="info-label">Cursos Principais</span>
                                <div class="courses-info">
                                    <span class="courses-count"><?php echo $courses_count; ?> curso(s)</span>
                                    <button type="button" class="manage-btn small manage-main-courses" 
                                            data-webhook-id="<?php echo esc_attr($webhook->webhook_id); ?>"
                                            data-webhook-name="<?php echo esc_attr($webhook->webhook_name); ?>">
                                        <span class="btn-icon">⚙️</span> Gerenciar
                                    </button>
                                </div>
                            </div>
                            
                            <div class="info-section">
                                <span class="info-label">Order Bumps</span>
                                <div class="order-bumps-info">
                                    <span class="order-bumps-count"><?php echo $order_bumps_count; ?> configurado(s)</span>
                                    <button type="button" class="manage-btn small manage-order-bumps" 
                                            data-webhook-id="<?php echo esc_attr($webhook->webhook_id); ?>"
                                            data-webhook-name="<?php echo esc_attr($webhook->webhook_name); ?>">
                                        <span class="btn-icon">🛍️</span> Gerenciar
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="webhook-url-section">
                            <span class="info-label">URL do Webhook</span>
                            <div class="url-display-container">
                                <code class="webhook-url-code"><?php echo esc_url($webhook_url); ?></code>
                                <button type="button" class="copy-url-btn copy-url" data-url="<?php echo esc_url($webhook_url); ?>">
                                    <span class="btn-icon">📋</span>
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($has_webhook_data) : ?>
                            <div class="webhook-actions">
                                <button type="button" class="action-btn primary show-json-structure" 
                                        data-webhook-id="<?php echo esc_attr($webhook->webhook_id); ?>"
                                        data-webhook-name="<?php echo esc_attr($webhook->webhook_name); ?>">
                                    <span class="btn-icon">🔍</span> Ver JSON
                                </button>
                            </div>
                        <?php else : ?>
                            <div class="webhook-actions">
                                <button type="button" class="action-btn secondary listen-webhook" 
                                        data-webhook-id="<?php echo esc_attr($webhook->webhook_id); ?>">
                                    <span class="btn-icon">👂</span> Escutar Webhook
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="webhook-item-footer">
                        <button type="button" class="delete-btn delete-webhook" 
                                data-webhook-id="<?php echo esc_attr($webhook->webhook_id); ?>">
                            <span class="btn-icon">🗑️</span> Excluir
                        </button>
                        <span class="spinner" style="display: none;"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <style>
        .webhooks-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .webhook-item-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .webhook-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .webhook-item-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .webhook-name {
            margin: 0;
            font-size: 1.2rem;
            color: #2d3748;
            font-weight: 600;
        }

        .icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }

        .webhook-status-badge {
            display: flex;
            align-items: center;
        }

        .webhook-status {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-configured {
            background: #c6f6d5;
            color: #22543d;
        }

        .status-waiting {
            background: #fef5e7;
            color: #744210;
        }

        .webhook-item-content {
            padding: 20px;
        }

        .webhook-info-grid {
            margin-bottom: 20px;
        }

        .info-section {
            margin-bottom: 15px;
        }

        .info-label {
            display: block;
            font-size: 0.85rem;
            color: #718096;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .webhook-id-display {
            background: #1a202c;
            color: #68d391;
            padding: 6px 10px;
            border-radius: 4px;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.85rem;
        }

        .courses-info, .order-bumps-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .courses-count, .order-bumps-count {
            color: #4a5568;
            font-weight: 500;
        }

        .manage-btn {
            background: #e6f3ff;
            color: #2c5282;
            border: 1px solid #90cdf4;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .manage-btn:hover {
            background: #bee3f8;
            transform: translateY(-1px);
        }

        .webhook-url-section {
            margin-bottom: 20px;
        }

        .url-display-container {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f7fafc;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .webhook-url-code {
            flex: 1;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.8rem;
            color: #4a5568;
            background: none;
            word-break: break-all;
        }

        .copy-url-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 6px 8px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .copy-url-btn:hover {
            background: #5a67d8;
            transform: scale(1.05);
        }

        .webhook-actions {
            text-align: center;
            margin-bottom: 15px;
        }

        .action-btn {
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .action-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .action-btn.secondary {
            background: #fef5e7;
            color: #744210;
            border: 1px solid #f6e05e;
        }

        .action-btn.secondary:hover {
            background: #faf089;
            transform: translateY(-1px);
        }

        .webhook-item-footer {
            background: #f7fafc;
            padding: 15px 20px;
            border-top: 1px solid #e2e8f0;
            text-align: right;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delete-btn {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .delete-btn:hover {
            background: #feb2b2;
            transform: translateY(-1px);
        }

        .btn-icon {
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-icon {
            font-size: 4rem;
            display: block;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .empty-state p {
            font-size: 1.2rem;
            margin: 0;
        }

        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid #e2e8f0;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhooks-grid {
                grid-template-columns: 1fr;
            }

            .webhook-item-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .url-display-container {
                flex-direction: column;
                align-items: stretch;
            }

            .copy-url-btn {
                align-self: center;
            }

            .webhook-item-footer {
                flex-direction: column;
                gap: 10px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * AJAX PARA ESCUTAR WEBHOOKS
     * ========================================
     */
    public function ajax_listen_webhook() {
        check_ajax_referer('webhook_receiver_listen', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        
        if (empty($webhook_id)) {
            wp_send_json_error(array('message' => 'ID do webhook inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
        
        if ($webhook && !empty($webhook->webhook_data)) {
            wp_send_json_success(array('webhook_received' => true));
        } else {
            wp_send_json_success(array('webhook_received' => false));
        }
    }
    
    /**
     * ========================================
     * AJAX PARA OBTER DADOS DE WEBHOOKS
     * ========================================
     */
    public function ajax_get_webhook_data() {
        check_ajax_referer('webhook_receiver_get_data', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        
        if (empty($webhook_id)) {
            wp_send_json_error(array('message' => 'ID do webhook inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        
        $webhook = $wpdb->get_row($wpdb->prepare(
            "SELECT webhook_data FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
        
        if (!$webhook || empty($webhook->webhook_data)) {
            wp_send_json_error(array('message' => 'Dados do webhook não encontrados.'));
            return;
        }
        
        // Verificar se os dados são um JSON válido
        $json_data = json_decode($webhook->webhook_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(array('message' => 'Dados JSON inválidos: ' . json_last_error_msg()));
            return;
        }
        
        wp_send_json_success(array(
            'webhook_data' => $webhook->webhook_data,
            'formatted_data' => json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        ));
    }
    
    /**
     * ========================================
     * AJAX PARA SALVAR WEBHOOKS
     * ========================================
     */
    public function ajax_save_webhook() {
        check_ajax_referer('webhook_receiver_save_webhook', 'webhook_nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_name = sanitize_text_field($_POST['webhook_name']);
        $webhook_id = sanitize_key($_POST['webhook_id']);
        $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
        
        if (empty($webhook_name) || empty($webhook_id) || empty($course_ids)) {
            wp_send_json_error(array('message' => 'Todos os campos obrigatórios devem ser preenchidos.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $courses_table = $wpdb->prefix . 'webhook_receiver_courses';
        
        // Verificar se o ID já existe
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE webhook_id = %s", $webhook_id));
        if ($exists) {
            wp_send_json_error(array('message' => 'Este ID de webhook já está em uso. Por favor, escolha outro.'));
            return;
        }
        
        // Inserir o webhook (course_id agora é 0 por padrão)
        $result = $wpdb->insert(
            $table_name,
            array(
                'webhook_id' => $webhook_id,
                'webhook_name' => $webhook_name,
                'course_id' => 0, // Mantido para compatibilidade
                'webhook_data' => null, // Inicialmente vazio
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao salvar webhook no banco de dados.'));
            return;
        }
        
        // Inserir os cursos
        foreach ($course_ids as $course_id) {
            $wpdb->insert(
                $courses_table,
                array(
                    'webhook_id' => $webhook_id,
                    'course_id' => $course_id,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        // Retornar HTML atualizado da lista de webhooks
        ob_start();
        $this->display_webhooks_list();
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * ========================================
     * AJAX PARA EXCLUIR WEBHOOKS
     * ========================================
     */
    public function ajax_delete_webhook() {
        check_ajax_referer('webhook_receiver_save_webhook', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        
        if (empty($webhook_id)) {
            wp_send_json_error(array('message' => 'ID do webhook inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $courses_table = $wpdb->prefix . 'webhook_receiver_courses';
        $order_bumps_table = $wpdb->prefix . 'webhook_receiver_order_bumps';
        
        // Deletar cursos associados
        $wpdb->delete($courses_table, array('webhook_id' => $webhook_id));
        
        // Deletar order bumps associados
        $wpdb->delete($order_bumps_table, array('webhook_id' => $webhook_id));
        
        // Deletar o webhook
        $result = $wpdb->delete($table_name, array('webhook_id' => $webhook_id));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao excluir webhook do banco de dados.'));
            return;
        }
        
        // Retornar HTML atualizado da lista de webhooks
        ob_start();
        $this->display_webhooks_list();
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * ========================================
     * AJAX PARA SALVAR CURSOS PRINCIPAIS
     * ========================================
     */
    public function ajax_save_main_courses() {
        check_ajax_referer('webhook_receiver_main_courses', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : array();
        
        if (empty($webhook_id) || empty($course_ids)) {
            wp_send_json_error(array('message' => 'Dados inválidos.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_courses';
        
        // Remover cursos existentes
        $wpdb->delete($table_name, array('webhook_id' => $webhook_id));
        
        // Adicionar novos cursos
        foreach ($course_ids as $course_id) {
            $wpdb->insert(
                $table_name,
                array(
                    'webhook_id' => $webhook_id,
                    'course_id' => $course_id,
                    'created_at' => current_time('mysql')
                )
            );
        }
        
        wp_send_json_success();
    }
    
    /**
     * ========================================
     * AJAX PARA OBTER CURSOS PRINCIPAIS
     * ========================================
     */
    public function ajax_get_main_courses() {
        check_ajax_referer('webhook_receiver_main_courses', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        
        if (empty($webhook_id)) {
            wp_send_json_error(array('message' => 'ID do webhook inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_courses';
        
        $course_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT course_id FROM $table_name WHERE webhook_id = %s",
            $webhook_id
        ));
        
        wp_send_json_success(array('course_ids' => $course_ids));
    }
    
    /**
     * ========================================
     * AJAX PARA OBTER LISTA DE WEBHOOKS ATUALIZADA
     * ========================================
     */
    public function ajax_get_webhooks_list() {
        check_ajax_referer('webhook_receiver_main_courses', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        ob_start();
        $this->display_webhooks_list();
        $html = ob_get_clean();
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * ========================================
     * AJAX PARA SALVAR ORDER BUMPS
     * ========================================
     */
    public function ajax_save_order_bump() {
        check_ajax_referer('webhook_receiver_order_bump', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        $product_id = sanitize_text_field($_POST['product_id']);
        $product_name = sanitize_text_field($_POST['product_name']);
        $course_id = intval($_POST['course_id']);
        
        if (empty($webhook_id) || empty($product_id) || empty($product_name) || empty($course_id)) {
            wp_send_json_error(array('message' => 'Todos os campos são obrigatórios.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_order_bumps';
        
        // Verificar se já existe
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE webhook_id = %s AND product_id = %s",
            $webhook_id, $product_id
        ));
        
        if ($exists) {
            wp_send_json_error(array('message' => 'Este ID de produto já está configurado para este webhook.'));
            return;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'webhook_id' => $webhook_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'course_id' => $course_id,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao salvar order bump.'));
            return;
        }
        
        wp_send_json_success();
    }
    
    /**
     * ========================================
     * AJAX PARA DELETAR ORDER BUMPS
     * ========================================
     */
    public function ajax_delete_order_bump() {
        check_ajax_referer('webhook_receiver_order_bump', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $order_bump_id = intval($_POST['order_bump_id']);
        
        if (empty($order_bump_id)) {
            wp_send_json_error(array('message' => 'ID inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_order_bumps';
        
        $result = $wpdb->delete($table_name, array('id' => $order_bump_id));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao excluir order bump.'));
            return;
        }
        
        wp_send_json_success();
    }
    
    /**
     * ========================================
     * AJAX PARA LISTAR ORDER BUMPS
     * ========================================
     */
    public function ajax_get_order_bumps() {
        check_ajax_referer('webhook_receiver_order_bump', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permissão negada.'));
            return;
        }
        
        $webhook_id = sanitize_key($_POST['webhook_id']);
        
        if (empty($webhook_id)) {
            wp_send_json_error(array('message' => 'ID do webhook inválido.'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_order_bumps';
        
        $order_bumps = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE webhook_id = %s ORDER BY product_name ASC",
            $webhook_id
        ));
        
        ob_start();
        
        if (empty($order_bumps)) {
            echo '<div class="empty-state"><span class="empty-icon">🛍️</span><p>Nenhum order bump configurado.</p></div>';
        } else {
            ?>
            <div class="order-bumps-modern-table">
                <div class="table-header">
                    <div class="header-cell">ID/SKU do Produto</div>
                    <div class="header-cell">Nome do Produto</div>
                    <div class="header-cell">Curso a Liberar</div>
                    <div class="header-cell">Ações</div>
                </div>
                <div class="table-body">
                    <?php foreach ($order_bumps as $order_bump) : 
                        $course = get_post($order_bump->course_id);
                        $course_title = $course ? $course->post_title : 'Curso não encontrado';
                    ?>
                        <div class="table-row">
                            <div class="table-cell">
                                <code class="product-sku"><?php echo esc_html($order_bump->product_id); ?></code>
                            </div>
                            <div class="table-cell">
                                <span class="product-name"><?php echo esc_html($order_bump->product_name); ?></span>
                            </div>
                            <div class="table-cell">
                                <span class="course-name <?php echo $course ? '' : 'error'; ?>"><?php echo esc_html($course_title); ?></span>
                            </div>
                            <div class="table-cell">
                                <button type="button" class="delete-order-bump-btn delete-order-bump" data-id="<?php echo $order_bump->id; ?>">
                                    <span class="btn-icon">🗑️</span> Excluir
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <style>
            .order-bumps-modern-table {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }

            .order-bumps-modern-table .table-header {
                display: grid;
                grid-template-columns: 1fr 2fr 2fr 1fr;
                background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
                border-bottom: 1px solid #e2e8f0;
            }

            .order-bumps-modern-table .header-cell {
                padding: 15px;
                font-weight: 600;
                color: #2d3748;
                font-size: 0.9rem;
            }

            .order-bumps-modern-table .table-body {
                max-height: 300px;
                overflow-y: auto;
            }

            .order-bumps-modern-table .table-row {
                display: grid;
                grid-template-columns: 1fr 2fr 2fr 1fr;
                border-bottom: 1px solid #e2e8f0;
                transition: all 0.3s ease;
            }

            .order-bumps-modern-table .table-row:hover {
                background: #f7fafc;
            }

            .order-bumps-modern-table .table-cell {
                padding: 15px;
                display: flex;
                align-items: center;
            }

            .product-sku {
                background: #1a202c;
                color: #68d391;
                padding: 4px 8px;
                border-radius: 4px;
                font-family: 'Fira Code', 'Consolas', monospace;
                font-size: 0.85rem;
            }

            .product-name {
                color: #2d3748;
                font-weight: 500;
            }

            .course-name {
                color: #4a5568;
            }

            .course-name.error {
                color: #e53e3e;
                font-style: italic;
            }

            .delete-order-bump-btn {
                background: #fed7d7;
                color: #742a2a;
                border: 1px solid #fc8181;
                padding: 6px 10px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 0.8rem;
                font-weight: 500;
                transition: all 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 4px;
            }

            .delete-order-bump-btn:hover {
                background: #feb2b2;
                transform: translateY(-1px);
            }

            /* Responsividade */
            @media (max-width: 768px) {
                .order-bumps-modern-table .table-header,
                .order-bumps-modern-table .table-row {
                    grid-template-columns: 1fr;
                    text-align: left;
                }

                .order-bumps-modern-table .table-cell {
                    padding: 10px 15px;
                    border-bottom: 1px solid #e2e8f0;
                }

                .order-bumps-modern-table .table-row .table-cell:last-child {
                    border-bottom: none;
                }
            }
            </style>
            <?php
        }
        
        $html = ob_get_clean();
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * ========================================
     * PÁGINA DE CONFIGURAÇÕES (DESIGN MODERNO)
     * ========================================
     */
    public function settings_page() {
        ?>
        <div class="wrap webhook-receiver-settings">
            <div class="webhook-header">
                <h1><span class="webhook-icon">⚙️</span> Configurações do Webhook Receiver</h1>
                <p class="webhook-subtitle">Personalize o comportamento do sistema</p>
            </div>
            
            <div class="webhook-card settings-card">
                <div class="card-content">
                    <form method="post" action="options.php" class="settings-form">
                        <?php
                        settings_fields('webhook_receiver_settings');
                        do_settings_sections('webhook_receiver_settings');
                        ?>
                        
                        <div class="form-actions">
                            <button type="submit" class="webhook-btn primary">
                                <span class="btn-icon">💾</span> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-settings {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .settings-card {
            border-left: 4px solid #ed8936;
        }

        .card-content {
            padding: 30px;
        }

        .settings-form .form-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .settings-form .form-table th {
            padding: 15px;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        }

        .settings-form .form-table td {
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .settings-form input[type="text"],
        .settings-form select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .settings-form input[type="text"]:focus,
        .settings-form select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .settings-form .description {
            color: #718096;
            font-size: 0.85rem;
            margin-top: 5px;
            font-style: italic;
        }

        .form-actions {
            text-align: center;
            padding: 20px 0;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-icon {
            font-size: 1rem;
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-settings {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .card-content {
                padding: 20px;
            }
        }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * PÁGINA DE LISTAGEM DE VENDAS (DESIGN MODERNO)
     * ========================================
     */
    public function sales_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_sales';
        
        // Paginação
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Filtro por webhook
        $webhook_filter = isset($_GET['webhook_filter']) ? sanitize_text_field($_GET['webhook_filter']) : '';
        $where_clause = '';
        
        if (!empty($webhook_filter)) {
            $where_clause = $wpdb->prepare("WHERE webhook_id = %s", $webhook_filter);
        }
        
        // Total de registros
        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where_clause");
        $total_pages = ceil($total_items / $per_page);
        
        // Obter vendas
        $sales = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );
        
        // Obter todos os webhooks para o filtro
        $webhooks_table = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhooks = $wpdb->get_results("SELECT * FROM $webhooks_table ORDER BY webhook_name ASC");
        
        ?>
        <div class="wrap webhook-receiver-sales">
            <div class="webhook-header">
                <h1><span class="webhook-icon">💰</span> Vendas Recebidas</h1>
                <p class="webhook-subtitle">Histórico completo de transações processadas</p>
            </div>
            
            <div class="webhook-card filters-card">
                <div class="card-header">
                    <h2><span class="icon">🔍</span> Filtros</h2>
                </div>
                <div class="card-content">
                    <form method="get" action="" class="filters-form">
                        <input type="hidden" name="page" value="webhook-receiver-sales">
                        
                        <div class="filter-group">
                            <label for="webhook_filter" class="filter-label">
                                <span class="icon">🔗</span> Filtrar por Webhook
                            </label>
                            <select name="webhook_filter" id="webhook_filter" class="filter-select">
                                <option value="">Todos os webhooks</option>
                                <option value="" <?php selected($webhook_filter, ''); ?>>Webhook Padrão</option>
                                <?php foreach ($webhooks as $webhook) : ?>
                                    <option value="<?php echo esc_attr($webhook->webhook_id); ?>" <?php selected($webhook_filter, $webhook->webhook_id); ?>>
                                        <?php echo esc_html($webhook->webhook_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="webhook-btn primary small">
                                <span class="btn-icon">🔍</span> Filtrar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="webhook-card sales-card">
                <div class="card-header">
                    <h2><span class="icon">📊</span> Lista de Vendas</h2>
                    <div class="sales-summary">
                        <span class="summary-item">
                            <strong><?php echo $total_items; ?></strong> vendas encontradas
                        </span>
                    </div>
                </div>
                <div class="card-content">
                    <?php if (empty($sales)) : ?>
                        <div class="empty-state">
                            <span class="empty-icon">💰</span>
                            <p>Nenhuma venda encontrada.</p>
                        </div>
                    <?php else : ?>
                        <div class="sales-grid">
                            <?php foreach ($sales as $sale) : 
                                $webhook_name = '';
                                if (!empty($sale->webhook_id)) {
                                    $webhook = $wpdb->get_var($wpdb->prepare(
                                        "SELECT webhook_name FROM $webhooks_table WHERE webhook_id = %s",
                                        $sale->webhook_id
                                    ));
                                    $webhook_name = $webhook ? $webhook : $sale->webhook_id;
                                } else {
                                    $webhook_name = 'Webhook Padrão';
                                }
                            ?>
                                <div class="sale-card">
                                    <div class="sale-header">
                                        <div class="sale-id">
                                            <span class="id-label">Venda</span>
                                            <span class="id-number">#<?php echo $sale->id; ?></span>
                                        </div>
                                        <div class="sale-date">
                                            <?php echo date_i18n('d/m/Y H:i', strtotime($sale->created_at)); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="sale-content">
                                        <div class="customer-info">
                                            <h4 class="customer-name"><?php echo esc_html($sale->customer_name); ?></h4>
                                            <p class="customer-email"><?php echo esc_html($sale->customer_email); ?></p>
                                        </div>
                                        
                                        <div class="product-info">
                                            <h5 class="product-name"><?php echo esc_html($sale->product_name); ?></h5>
                                            <div class="product-details">
                                                <span class="product-sku">SKU: <?php echo esc_html($sale->product_sku); ?></span>
                                                <span class="product-price">R$ <?php echo number_format($sale->product_price, 2, ',', '.'); ?></span>
                                                <span class="product-quantity">Qtd: <?php echo $sale->product_quantity; ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="payment-info">
                                            <span class="payment-method"><?php echo esc_html($sale->payment_method); ?></span>
                                            <span class="payment-installments"><?php echo $sale->payment_installments; ?>x</span>
                                        </div>
                                        
                                        <div class="webhook-info">
                                            <span class="webhook-badge"><?php echo esc_html($webhook_name); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="sale-footer">
                                        <button type="button" class="view-details-btn view-details" data-id="<?php echo $sale->id; ?>">
                                            <span class="btn-icon">👁️</span> Ver Detalhes
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Modal de detalhes expandido -->
                                <div id="sale-details-<?php echo $sale->id; ?>" class="sale-details-modal" style="display: none;">
                                    <div class="modal-backdrop"></div>
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h2><span class="icon">📊</span> Detalhes da Venda #<?php echo $sale->id; ?></h2>
                                            <button type="button" class="close-modal-btn">✕</button>
                                        </div>
                                        
                                        <div class="modal-body">
                                            <div class="details-grid">
                                                <div class="details-section">
                                                    <h3><span class="icon">👤</span> Informações do Cliente</h3>
                                                    <div class="details-list">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Nome:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->customer_name); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">E-mail:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->customer_email); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">CPF:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->customer_cpf); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Telefone:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->customer_phone); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="details-section">
                                                    <h3><span class="icon">📦</span> Informações do Produto</h3>
                                                    <div class="details-list">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Nome:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->product_name); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">ID:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->product_id); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">SKU:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->product_sku); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Preço:</span>
                                                            <span class="detail-value">R$ <?php echo number_format($sale->product_price, 2, ',', '.'); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Quantidade:</span>
                                                            <span class="detail-value"><?php echo $sale->product_quantity; ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Categoria:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->product_category); ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($sale->additional_products)) : 
                                                    $additional = json_decode($sale->additional_products, true);
                                                    if (!empty($additional)) : ?>
                                                        <div class="details-section full-width">
                                                            <h3><span class="icon">🛍️</span> Produtos Adicionais</h3>
                                                            <div class="additional-products-list">
                                                                <?php foreach ($additional as $add_product) : ?>
                                                                    <div class="additional-product-item">
                                                                        <span class="product-name"><?php echo esc_html($add_product['nome']); ?></span>
                                                                        <span class="product-price">R$ <?php echo number_format($add_product['preco'], 2, ',', '.'); ?></span>
                                                                        <span class="product-quantity">x <?php echo $add_product['quantidade']; ?></span>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <div class="details-section">
                                                    <h3><span class="icon">💳</span> Informações de Pagamento</h3>
                                                    <div class="details-list">
                                                        <div class="detail-item">
                                                            <span class="detail-label">Método:</span>
                                                            <span class="detail-value"><?php echo esc_html($sale->payment_method); ?></span>
                                                        </div>
                                                        <div class="detail-item">
                                                            <span class="detail-label">Parcelas:</span>
                                                            <span class="detail-value"><?php echo $sale->payment_installments; ?>x</span>
                                                        </div>
                                                        <?php if (!empty($sale->delivery_link)) : ?>
                                                            <div class="detail-item">
                                                                <span class="detail-label">Link de Entrega:</span>
                                                                <a href="<?php echo esc_url($sale->delivery_link); ?>" target="_blank" class="detail-link">
                                                                    <?php echo esc_html($sale->delivery_link); ?>
                                                                </a>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="details-section full-width">
                                                    <h3><span class="icon">📄</span> Dados Brutos</h3>
                                                    <div class="raw-data-container">
                                                        <textarea readonly class="raw-data-textarea"><?php echo esc_textarea($sale->raw_data); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="modal-footer">
                                            <button type="button" class="webhook-btn secondary close-modal">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($total_pages > 1) : ?>
                            <div class="pagination-wrapper">
                                <div class="pagination-info">
                                    <span>Mostrando <?php echo min($per_page, $total_items - $offset); ?> de <?php echo $total_items; ?> vendas</span>
                                </div>
                                <div class="pagination-controls">
                                    <?php
                                    echo paginate_links(array(
                                        'base' => add_query_arg('paged', '%#%'),
                                        'format' => '',
                                        'prev_text' => '‹ Anterior',
                                        'next_text' => 'Próxima ›',
                                        'total' => $total_pages,
                                        'current' => $current_page,
                                        'type' => 'list'
                                    ));
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-sales {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }

        .filters-card {
            border-left: 4px solid #ed8936;
        }

        .sales-card {
            border-left: 4px solid #48bb78;
        }

        .card-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
        }

        .icon {
            font-size: 1.2rem;
            margin-right: 8px;
            vertical-align: middle;
        }

        .sales-summary {
            color: #718096;
            font-size: 0.9rem;
        }

        .summary-item {
            background: white;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .card-content {
            padding: 30px;
        }

        .filters-form {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .filter-group {
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }

        .filter-select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
            background: white;
            min-width: 200px;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .webhook-btn.secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .webhook-btn.secondary:hover {
            background: #cbd5e0;
            transform: translateY(-1px);
        }

        .webhook-btn.small {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn-icon {
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #718096;
        }

        .empty-icon {
            font-size: 4rem;
            display: block;
            margin-bottom: 20px;
            opacity: 0.6;
        }

        .empty-state p {
            font-size: 1.2rem;
            margin: 0;
        }

        .sales-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 25px;
        }

        .sale-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .sale-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .sale-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .sale-id {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .id-label {
            font-size: 0.8rem;
            color: #718096;
            font-weight: 500;
        }

        .id-number {
            font-size: 1.1rem;
            font-weight: 700;
            color: #2d3748;
        }

        .sale-date {
            font-size: 0.85rem;
            color: #718096;
        }

        .sale-content {
            padding: 20px;
        }

        .customer-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .customer-name {
            margin: 0 0 5px 0;
            font-size: 1.1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .customer-email {
            margin: 0;
            font-size: 0.9rem;
            color: #718096;
        }

        .product-info {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .product-name {
            margin: 0 0 8px 0;
            font-size: 1rem;
            color: #2d3748;
            font-weight: 600;
        }

        .product-details {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .product-sku,
        .product-price,
        .product-quantity {
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .product-sku {
            background: #1a202c;
            color: #68d391;
            font-family: 'Fira Code', 'Consolas', monospace;
        }

        .product-price {
            background: #c6f6d5;
            color: #22543d;
        }

        .product-quantity {
            background: #e6f3ff;
            color: #2c5282;
        }

        .payment-info {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .payment-method,
        .payment-installments {
            font-size: 0.85rem;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }

        .payment-method {
            background: #fef5e7;
            color: #744210;
        }

        .payment-installments {
            background: #e2e8f0;
            color: #4a5568;
        }

        .webhook-info {
            margin-bottom: 15px;
        }

        .webhook-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .sale-footer {
            padding: 15px 20px;
            background: #f7fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .view-details-btn {
            background: #e6f3ff;
            color: #2c5282;
            border: 1px solid #90cdf4;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .view-details-btn:hover {
            background: #bee3f8;
            transform: translateY(-1px);
        }

        /* Modal de detalhes */
        .sale-details-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 100000;
            animation: fadeIn 0.3s ease-out;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            animation: slideInFromBottom 0.3s ease-out;
        }

        .modal-header {
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 25px 30px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            color: #2d3748;
            font-weight: 600;
            flex: 1;
        }

        .close-modal-btn {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #718096;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .close-modal-btn:hover {
            background: #e2e8f0;
            color: #2d3748;
        }

        .modal-body {
            padding: 30px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .details-section {
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .details-section.full-width {
            grid-column: 1 / -1;
        }

        .details-section h3 {
            margin: 0 0 15px 0;
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .details-list {
            space-y: 10px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #4a5568;
            flex: 1;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 500;
            text-align: right;
        }

        .detail-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .detail-link:hover {
            text-decoration: underline;
        }

        .additional-products-list {
            space-y: 10px;
        }

        .additional-product-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .additional-product-item .product-name {
            flex: 1;
            font-weight: 600;
            color: #2d3748;
        }

        .additional-product-item .product-price {
            background: #c6f6d5;
            color: #22543d;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 10px;
        }

        .additional-product-item .product-quantity {
            background: #e2e8f0;
            color: #4a5568;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .raw-data-container {
            margin-top: 10px;
        }

        .raw-data-textarea {
            width: 100%;
            height: 200px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-family: 'Fira Code', 'Consolas', monospace;
            font-size: 0.8rem;
            background: #1a202c;
            color: #e2e8f0;
            resize: vertical;
        }

        .modal-footer {
            background: #f7fafc;
            padding: 20px 30px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            align-items: center;
        }

        .pagination-wrapper {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .pagination-info {
            color: #718096;
            font-size: 0.9rem;
        }

        .pagination-controls .page-numbers {
            display: inline-flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 5px;
        }

        .pagination-controls .page-numbers li {
            margin: 0;
        }

        .pagination-controls .page-numbers a,
        .pagination-controls .page-numbers span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination-controls .page-numbers a {
            background: white;
            color: #4a5568;
        }

        .pagination-controls .page-numbers a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
            transform: translateY(-1px);
        }

        .pagination-controls .page-numbers .current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Animações */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInFromBottom {
            from {
                opacity: 0;
                transform: translate(-50%, -40%) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%) scale(1);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .webhook-card {
            animation: fadeInUp 0.6s ease-out;
        }

        .sale-card {
            animation: fadeInUp 0.4s ease-out;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .webhook-receiver-sales {
                margin: 0 -10px;
                padding: 15px;
            }

            .webhook-header {
                padding: 30px 15px;
            }

            .webhook-header h1 {
                font-size: 2rem;
            }

            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-select {
                min-width: auto;
            }

            .sales-grid {
                grid-template-columns: 1fr;
            }

            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .product-details {
                flex-direction: column;
                gap: 8px;
            }

            .details-grid {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 20px 0;
                max-height: calc(100vh - 40px);
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 20px;
            }

            .pagination-wrapper {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .detail-value {
                text-align: left;
            }
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('.view-details').on('click', function() {
                var id = $(this).data('id');
                $('#sale-details-' + id).fadeIn(300);
            });
            
            $('.close-modal, .close-modal-btn, .modal-backdrop').on('click', function() {
                $('.sale-details-modal').fadeOut(300);
            });
        });
        </script>
        <?php
    }
    
    /**
     * ========================================
     * INICIALIZAR CONFIGURAÇÕES
     * ========================================
     */
    public function initialize_settings() {
		register_setting('webhook_receiver_settings', 'webhook_receiver_user_email_subject');
		register_setting('webhook_receiver_settings', 'webhook_receiver_user_email_template', array(
    		'sanitize_callback' => 'wp_kses_post'
			));
		add_settings_field(
   		 'webhook_receiver_user_email_subject',
   		 'Assunto do E-mail ao Usuário',
   		 array($this, 'user_email_subject_callback'),
   		 'webhook_receiver_settings',
   		 'webhook_receiver_general_section'
			);
			add_settings_field(
  		  'webhook_receiver_user_email_template',
    		'Template do E-mail ao Usuário',
    		array($this, 'user_email_template_callback'),
    		'webhook_receiver_settings',
    		'webhook_receiver_general_section'
		);



        register_setting('webhook_receiver_settings', 'webhook_receiver_enable_logs');
        register_setting('webhook_receiver_settings', 'webhook_receiver_create_users');
        register_setting('webhook_receiver_settings', 'webhook_receiver_notify_admin');
        register_setting('webhook_receiver_settings', 'webhook_receiver_notify_user');
        register_setting('webhook_receiver_settings', 'webhook_receiver_auto_enroll_courses');
        register_setting('webhook_receiver_settings', 'webhook_receiver_default_password');
        
        add_settings_section(
            'webhook_receiver_general_section',
            'Configurações Gerais',
            array($this, 'general_section_callback'),
            'webhook_receiver_settings'
        );
        
        add_settings_field(
            'webhook_receiver_enable_logs',
            'Ativar Logs',
            array($this, 'enable_logs_callback'),
            'webhook_receiver_settings',
            'webhook_receiver_general_section'
        );
        
        add_settings_field(
            'webhook_receiver_create_users',
            'Criar Usuários',
            array($this, 'create_users_callback'),
            'webhook_receiver_settings',
            'webhook_receiver_general_section'
        );
        
        add_settings_field(
            'webhook_receiver_default_password',
            'Senha Padrão',
            array($this, 'default_password_callback'),
            'webhook_receiver_settings',
            'webhook_receiver_general_section'
        );
        
        add_settings_field(
            'webhook_receiver_notify_admin',
            'Notificar Administrador',
            array($this, 'notify_admin_callback'),
            'webhook_receiver_settings',
            'webhook_receiver_general_section'
        );
        
        add_settings_field(
            'webhook_receiver_notify_user',
            'Notificar Usuário',
            array($this, 'notify_user_callback'),
            'webhook_receiver_settings',
            'webhook_receiver_general_section'
        );
        
       
    }
  /**
     * ========================================
     * CALLBACK DOS CAMPOS
     * ========================================
     */

public function user_email_subject_callback() {
    $subject = get_option('webhook_receiver_user_email_subject', 'Bem-vindo! Seus dados de acesso');
    echo '<input type="text" name="webhook_receiver_user_email_subject" value="' . esc_attr($subject) . '" class="settings-input" style="width:100%">';
}
public function user_email_template_callback() {
    $template = get_option('webhook_receiver_user_email_template',
        'Olá <strong>(nome)</strong>,<br>
Sua conta foi criada.<br><br>
<strong>Usuário:</strong> (email)<br>
<strong>Senha:</strong> (senha)<br>
Você ganhou acesso ao curso: <strong>(curso_nome)</strong><br>
Acesse: <a href="(login_url)">(login_url)</a><br><br>
(site_name)'
    );
    wp_editor(
        $template,
        'webhook_receiver_user_email_template',
        array(
            'textarea_name' => 'webhook_receiver_user_email_template',
            'media_buttons' => false,
            'textarea_rows' => 10,
            'teeny'         => false,
            'tinymce'       => array(
                'resize' => false,
                'wp_autoresize_on' => true,
            )
        )
    );
    echo '<p class="settings-description">Códigos: <strong>(nome)</strong>, <strong>(email)</strong>, <strong>(senha)</strong>, <strong>(curso_nome)</strong>, <strong>(login_url)</strong>, <strong>(site_name)</strong></p>';
}
    
    /**
     * ========================================
     * CALLBACK PARA SESSÃO DE CONFIGURAÇÕES GERAIS
     * ========================================
     */
    public function general_section_callback() {
        echo '<div class="settings-section-description">';
        echo '<p>Configure as opções gerais do Webhook Receiver.</p>';
        echo '</div>';
    }
    
    /**
     * ========================================
     * CALLBACK PARA SESSÃO DE CONFIGURAÇÕES DE CURSOS
     * ========================================
     */
    public function courses_section_callback() {
        echo '<div class="settings-section-description">';
        echo '<p>Configure quais cursos serão automaticamente atribuídos aos usuários criados pelo webhook padrão.</p>';
        echo '<p>Para webhooks específicos por curso, utilize a página <a href="' . admin_url('admin.php?page=webhook-receiver-endpoints') . '" class="settings-link">Webhooks</a>.</p>';
        echo '</div>';
        
        ?>
        <style>
        .settings-section-description {
            background: #e6f3ff;
            border: 1px solid #90cdf4;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .settings-section-description p {
            margin: 5px 0;
            color: #2c5282;
        }

        .settings-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .settings-link:hover {
            text-decoration: underline;
        }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * CALLBACK PARA O CAMPO DE ATIVAR LOGS
     * ========================================
     */
    public function enable_logs_callback() {
        $enable_logs = get_option('webhook_receiver_enable_logs', 'yes');
        ?>
        <div class="settings-field-container">
            <select name="webhook_receiver_enable_logs" class="settings-select">
                <option value="yes" <?php selected($enable_logs, 'yes'); ?>>✅ Sim</option>
                <option value="no" <?php selected($enable_logs, 'no'); ?>>❌ Não</option>
            </select>
            <p class="settings-description">Ativar o registro de logs de todos os webhooks recebidos.</p>
        </div>

        <style>
        .settings-field-container {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .settings-select {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: white;
            min-width: 150px;
            transition: all 0.3s ease;
        }

        .settings-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .settings-description {
            margin: 10px 0 0 0;
            color: #718096;
            font-size: 0.85rem;
            font-style: italic;
        }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * CALLBACK PARA O CAMPO DE CRIAR USUÁRIOS
     * ========================================
     */
    public function create_users_callback() {
        $create_users = get_option('webhook_receiver_create_users', 'no');
        ?>
        <div class="settings-field-container">
            <select name="webhook_receiver_create_users" class="settings-select">
                <option value="yes" <?php selected($create_users, 'yes'); ?>>✅ Sim</option>
                <option value="no" <?php selected($create_users, 'no'); ?>>❌ Não</option>
            </select>
            <p class="settings-description">Criar automaticamente usuários no WordPress a partir dos dados da venda.</p>
        </div>
        <?php
    }
    
    /**
     * ========================================
     * CALLBACK PARA O CAMPO DE SENHA PADRÃO
     * ========================================
     */
    public function default_password_callback() {
        $default_password = get_option('webhook_receiver_default_password', '');
        ?>
        <div class="settings-field-container">
            <input type="text" name="webhook_receiver_default_password" value="<?php echo esc_attr($default_password); ?>" class="settings-input" placeholder="Deixe em branco para senha aleatória">
            <p class="settings-description">Define uma senha padrão para novos usuários criados pelo webhook. Se deixado em branco, uma senha aleatória será gerada.</p>
        </div>

        <style>
        .settings-input {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.95rem;
            background: white;
            min-width: 250px;
            transition: all 0.3s ease;
        }

        .settings-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        </style>
        <?php
    }
    
    /**
     * ========================================
     * CALLBACK PARA O CAMPO DE NOTIFICAR ADM
     * ========================================
     */
    public function notify_admin_callback() {
        $notify_admin = get_option('webhook_receiver_notify_admin', 'no');
        ?>
        <div class="settings-field-container">
            <select name="webhook_receiver_notify_admin" class="settings-select">
                <option value="yes" <?php selected($notify_admin, 'yes'); ?>>✅ Sim</option>
                <option value="no" <?php selected($notify_admin, 'no'); ?>>❌ Não</option>
            </select>
            <p class="settings-description">Enviar e-mail para o administrador quando um novo usuário for criado.</p>
        </div>
        <?php
    }
    
    /**
     * ========================================
     * CALLBACK PARA O CAMPO DE NOTIFICAR USER
     * ========================================
     */
    public function notify_user_callback() {
        $notify_user = get_option('webhook_receiver_notify_user', 'no');
        ?>
        <div class="settings-field-container">
            <select name="webhook_receiver_notify_user" class="settings-select">
                <option value="yes" <?php selected($notify_user, 'yes'); ?>>✅ Sim</option>
                <option value="no" <?php selected($notify_user, 'no'); ?>>❌ Não</option>
            </select>
            <p class="settings-description">Enviar e-mail para o usuário com seus dados de acesso quando for criado.</p>
        </div>
        <?php
    }
   // ==========================
    // NOVO: envio de email ao usuário de acesso
    // ==========================
  public function send_user_credentials_email($user_id, $password, $courses = array()) {
    $user = get_user_by('id', $user_id);
    if (!$user) return;

    $site_name = get_bloginfo('name');
    $login_url = wp_login_url();
    $subject = get_option('webhook_receiver_user_email_subject', 'Bem-vindo! Seus dados de acesso');
    $message = get_option('webhook_receiver_user_email_template',
        'Olá <strong>(nome)</strong>,<br>
Sua conta foi criada.<br>
<strong>Usuário:</strong> (email)<br>
<strong>Senha:</strong> (senha)<br>
<br>
Acesse: <a href="(login_url)">Área de login</a> <br>
Ou clique no link mágico: <a href="(link-magico)">Entrar sem senha</a> <br>
(site_name)'
    );
    $first_name = get_user_meta($user_id, 'first_name', true);
    $last_name  = get_user_meta($user_id, 'last_name', true);
    $nome = trim(($first_name ? $first_name : '').' '.($last_name ? $last_name : ''));
    $cursos_nomes = array();
    if (!empty($courses)) {
        foreach ($courses as $course_id) {
            $curso = get_post($course_id);
            if ($curso) $cursos_nomes[] = $curso->post_title;
        }
    }
    $curso_nome = implode(', ', $cursos_nomes);

    // GERAR LINK MÁGICO
    if (class_exists('\HandyMagicLogin\Utils')) {
        $magic_link = \HandyMagicLogin\Utils::get_login_link($user_id, $login_url, 60); // (login_url) = wp-login.php, expira em 60 min
    } else {
        $magic_link = $login_url; // fallback
    }

    $vars = array(
        '(nome)'        => esc_html($nome),
        '(email)'       => $user->user_email,
        '(senha)'       => esc_html($password),
        '(curso_nome)'  => esc_html($curso_nome),
        '(login_url)'   => $login_url,
        '(site_name)'   => $site_name,
        '(link-magico)' => esc_url($magic_link),
    );
    $subject = str_replace(array_keys($vars), array_values($vars), $subject);
    $message = str_replace(array_keys($vars), array_values($vars), $message);

    $headers = array('Content-Type: text/html; charset=UTF-8','From: '.$site_name.' <'.get_option('admin_email').'>');
    wp_mail($user->user_email, $subject, $message, $headers);
}

/**
 * ========================================
 * CALLBACK PARA O CAMPO DE CURSOS PARA MATRICULA AUTOMÁTICA
 * ========================================
 */
public function auto_enroll_courses_callback() {
    // Obter todos os cursos disponíveis
    $courses = get_posts(array('post_type' => 'courses', 'numberposts' => -1));
    
    // Obter cursos selecionados nas configurações
    $selected_courses = get_option('webhook_receiver_auto_enroll_courses', array());
    if (!is_array($selected_courses)) {
        $selected_courses = array();
    }
    
    // CSS moderno e animações
    echo '<style>
    .webhook-courses-container {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        padding: 24px;
        margin: 16px 0;
        border: 1px solid #e5e7eb;
        transition: all 0.3s ease;
    }
    
    .webhook-courses-container:hover {
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }
    
    .webhook-button-group {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .webhook-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        outline: none;
        position: relative;
        overflow: hidden;
    }
    
    .webhook-btn::before {
        content: "";
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s ease;
    }
    
    .webhook-btn:hover::before {
        left: 100%;
    }
    
    .webhook-btn-select {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    }
    
    .webhook-btn-select:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        transform: translateY(-1px);
    }
    
    .webhook-btn-remove {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    
    .webhook-btn-remove:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
        transform: translateY(-1px);
    }
    
    .webhook-courses-list {
        background: #f9fafb;
        border-radius: 8px;
        padding: 16px;
        margin: 16px 0;
        border: 1px solid #e5e7eb;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .webhook-courses-list::-webkit-scrollbar {
        width: 6px;
    }
    
    .webhook-courses-list::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }
    
    .webhook-courses-list::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
        transition: background 0.2s ease;
    }
    
    .webhook-courses-list::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    .webhook-course-item {
        display: flex;
        align-items: center;
        padding: 12px;
        margin: 4px 0;
        background: white;
        border-radius: 6px;
        border: 1px solid transparent;
        transition: all 0.2s ease;
        cursor: pointer;
        position: relative;
    }
    
    .webhook-course-item:hover {
        background: #f8fafc;
        border-color: #e2e8f0;
        transform: translateX(4px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }
    
    .webhook-course-item.selected {
        background: #eff6ff;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    
    .webhook-course-checkbox {
        width: 18px;
        height: 18px;
        margin-right: 12px;
        cursor: pointer;
        accent-color: #3b82f6;
        transition: transform 0.2s ease;
    }
    
    .webhook-course-checkbox:hover {
        transform: scale(1.1);
    }
    
    .webhook-course-checkbox:checked {
        animation: checkboxPulse 0.3s ease;
    }
    
    @keyframes checkboxPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .webhook-course-label {
        font-size: 14px;
        color: #374151;
        font-weight: 500;
        cursor: pointer;
        flex: 1;
        transition: color 0.2s ease;
    }
    
    .webhook-course-item:hover .webhook-course-label {
        color: #1f2937;
    }
    
    .webhook-course-item.selected .webhook-course-label {
        color: #1d4ed8;
        font-weight: 600;
    }
    
    .webhook-no-courses {
        text-align: center;
        color: #6b7280;
        font-style: italic;
        padding: 40px 20px;
        background: white;
        border-radius: 6px;
        border: 2px dashed #d1d5db;
    }
    
    .webhook-description {
        background: #f0f9ff;
        border: 1px solid #0ea5e9;
        border-radius: 8px;
        padding: 16px;
        margin-top: 20px;
        color: #0c4a6e;
        font-size: 14px;
        line-height: 1.5;
        position: relative;
    }
    
    .webhook-description::before {
        content: "ℹ️";
        font-size: 16px;
        margin-right: 8px;
    }
    
    .webhook-fade-in {
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .webhook-courses-counter {
        display: inline-block;
        background: #e5e7eb;
        color: #374151;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        margin-left: 8px;
        transition: all 0.2s ease;
    }
    
    .webhook-courses-counter.selected {
        background: #3b82f6;
        color: white;
    }
    
    @media (max-width: 768px) {
        .webhook-courses-container {
            padding: 16px;
            margin: 8px 0;
        }
        
        .webhook-button-group {
            flex-direction: column;
        }
        
        .webhook-btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>';
    
    // Container principal com classe para animação
    echo '<div class="webhook-courses-container webhook-fade-in">';
    
    // Botões de Selecionar Todos e Remover Todos
    echo '<div class="webhook-button-group">';
    echo '<button type="button" id="select-all-courses" class="webhook-btn webhook-btn-select">';
    echo '<span>✓</span>' . __('Selecionar Todos', 'webhook-receiver');
    echo '</button>';
    echo '<button type="button" id="remove-all-courses" class="webhook-btn webhook-btn-remove">';
    echo '<span>✗</span>' . __('Remover Todos', 'webhook-receiver');
    echo '</button>';
    $total_courses = count($courses);
    $selected_count = count($selected_courses);
    echo '<span class="webhook-courses-counter' . ($selected_count > 0 ? ' selected' : '') . '" id="courses-counter">';
    echo sprintf(__('%d de %d selecionados', 'webhook-receiver'), $selected_count, $total_courses);
    echo '</span>';
    echo '</div>';
    
    // Listagem dos cursos com checkbox
    echo '<div class="webhook-courses-list">';
    if (!empty($courses)) {
        foreach ($courses as $course) {
            $checked = in_array($course->ID, $selected_courses) ? 'checked="checked"' : '';
            $selected_class = $checked ? ' selected' : '';
            echo '<div class="webhook-course-item' . $selected_class . '">';
            echo '<input type="checkbox" name="webhook_receiver_auto_enroll_courses[]" ';
            echo 'value="' . esc_attr($course->ID) . '" id="course_' . esc_attr($course->ID) . '" ';
            echo $checked . ' class="webhook-course-checkbox">';
            echo '<label for="course_' . esc_attr($course->ID) . '" class="webhook-course-label">';
            echo esc_html($course->post_title);
            echo '</label>';
            echo '</div>';
        }
    } else {
        echo '<div class="webhook-no-courses">';
        echo __('Nenhum curso encontrado.', 'webhook-receiver');
        echo '</div>';
    }
    echo '</div>';
    
    echo '<div class="webhook-description">';
    echo __('Selecione os cursos que serão automaticamente atribuídos aos usuários criados pelo webhook padrão.', 'webhook-receiver');
    echo '</div>';
    
    echo '</div>'; // Fecha container principal
    
    // Script para manipular "Selecionar Todos" e "Remover Todos" com melhorias visuais
    ?>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select-all-courses');
        const removeAllBtn = document.getElementById('remove-all-courses');
        const checkboxes = document.querySelectorAll('input[name="webhook_receiver_auto_enroll_courses[]"]');
        const courseItems = document.querySelectorAll('.webhook-course-item');
        const counter = document.getElementById('courses-counter');
        
        function updateCounter() {
            const totalCourses = checkboxes.length;
            const selectedCourses = document.querySelectorAll('input[name="webhook_receiver_auto_enroll_courses[]"]:checked').length;
            
            if (counter) {
                counter.textContent = selectedCourses + ' de ' + totalCourses + ' selecionados';
                counter.classList.toggle('selected', selectedCourses > 0);
            }
        }
        
        function updateItemAppearance(checkbox) {
            const item = checkbox.closest('.webhook-course-item');
            if (item) {
                item.classList.toggle('selected', checkbox.checked);
            }
        }
        
        // Selecionar todos
        if (selectAllBtn) {
            selectAllBtn.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) {
                    if (!checkbox.checked) {
                        checkbox.checked = true;
                        checkbox.dispatchEvent(new Event('change'));
                        updateItemAppearance(checkbox);
                    }
                });
                updateCounter();
                
                // Efeito visual no botão
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        }
        
        // Remover todos
        if (removeAllBtn) {
            removeAllBtn.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) {
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        checkbox.dispatchEvent(new Event('change'));
                        updateItemAppearance(checkbox);
                    }
                });
                updateCounter();
                
                // Efeito visual no botão
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 150);
            });
        }
        
        // Adicionar event listeners para checkboxes individuais
        checkboxes.forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                updateItemAppearance(this);
                updateCounter();
            });
        });
        
        // Permitir clicar no item inteiro para marcar/desmarcar
        courseItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                if (e.target.type !== 'checkbox' && e.target.tagName !== 'LABEL') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                        updateItemAppearance(checkbox);
                        updateCounter();
                    }
                }
            });
        });
        
        // Inicializar contador
        updateCounter();
    });
    </script>
    <?php
}

    /**
     * ========================================
     * PÁGINA DE MATRÍCULA MANUAL DE ALUNOS
     * ========================================
     */
    public function manual_enroll_page() {
        $courses = get_posts(array(
            'post_type'   => 'courses',
            'numberposts' => -1,
            'post_status' => array('publish', 'future', 'draft'),
            'orderby'     => 'title',
            'order'       => 'ASC',
        ));
        ?>
        <div class="wrap webhook-receiver-enroll">
            <div class="webhook-header">
                <h1><span class="webhook-icon">🎓</span> Matricular Aluno</h1>
                <p class="webhook-subtitle">Cadastre e matricule um aluno manualmente nos cursos</p>
            </div>

            <div id="enroll-notice" style="display:none;"></div>

            <div class="webhook-card enroll-card">
                <div class="card-header">
                    <h2><span class="icon">📝</span> Dados do Aluno</h2>
                </div>
                <div class="card-content">
                    <form id="manual-enroll-form">
                        <?php wp_nonce_field('webhook_receiver_manual_enroll', 'enroll_nonce'); ?>

                        <div class="enroll-fields-grid">
                            <div class="enroll-field-group">
                                <label for="enroll-name">Nome Completo <span class="required">*</span></label>
                                <input type="text" id="enroll-name" name="enroll_name" class="enroll-input" placeholder="Nome do aluno" required />
                            </div>

                            <div class="enroll-field-group">
                                <label for="enroll-email">E-mail <span class="required">*</span></label>
                                <input type="email" id="enroll-email" name="enroll_email" class="enroll-input" placeholder="email@exemplo.com" required />
                            </div>

                            <div class="enroll-field-group">
                                <label for="enroll-phone">Telefone</label>
                                <input type="text" id="enroll-phone" name="enroll_phone" class="enroll-input" placeholder="(11) 99999-9999" />
                            </div>

                            <div class="enroll-field-group">
                                <label for="enroll-password">Senha <span class="required">*</span></label>
                                <div class="password-wrapper">
                                    <input type="password" id="enroll-password" name="enroll_password" class="enroll-input" placeholder="Senha de acesso" required />
                                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" title="Mostrar/Ocultar senha">👁</button>
                                </div>
                                <small class="field-hint">Deixe em branco para gerar senha automática</small>
                            </div>
                        </div>

                        <div class="enroll-courses-section">
                            <h3><span class="icon">📚</span> Selecionar Cursos
                                <span class="course-counter" id="course-counter">(0 selecionado(s))</span>
                            </h3>
                            <p class="form-description">Selecione os cursos nos quais o aluno será matriculado.</p>

                            <?php if (!empty($courses)) : ?>
                                <div class="enroll-course-search">
                                    <input type="text" id="course-search-input" placeholder="🔍 Pesquisar cursos..." class="enroll-input" />
                                </div>
                                <div class="enroll-course-actions">
                                    <button type="button" class="webhook-btn small secondary" id="select-all-courses">Selecionar Todos</button>
                                    <button type="button" class="webhook-btn small secondary" id="deselect-all-courses">Desmarcar Todos</button>
                                </div>
                                <div class="enroll-courses-list">
                                    <?php foreach ($courses as $course) : ?>
                                        <div class="enroll-course-item" data-title="<?php echo esc_attr(strtolower($course->post_title)); ?>">
                                            <label class="enroll-course-label">
                                                <input type="checkbox" name="enroll_courses[]" value="<?php echo esc_attr($course->ID); ?>" class="enroll-course-checkbox" />
                                                <span class="enroll-course-title"><?php echo esc_html($course->post_title); ?></span>
                                                <span class="enroll-course-status status-<?php echo esc_attr($course->post_status); ?>"><?php echo esc_html(ucfirst($course->post_status)); ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <p class="no-courses-msg">Nenhum curso encontrado. Crie cursos no Tutor LMS primeiro.</p>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" id="enroll-submit-btn" class="webhook-btn primary large">
                                <span class="btn-icon">🎓</span> Matricular Aluno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <style>
        .webhook-receiver-enroll {
            background: #f8fafc;
            margin: 0 -20px 0 -12px;
            padding: 20px;
            min-height: 100vh;
        }

        .webhook-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            color: white;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .webhook-header h1 {
            font-size: 2.5rem;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .webhook-icon {
            font-size: 2.8rem;
            margin-right: 15px;
            vertical-align: middle;
        }

        .webhook-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            font-weight: 300;
        }

        .webhook-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .enroll-card {
            border-left: 4px solid #667eea;
        }

        .card-header {
            background: linear-gradient(135deg, #f6f9fc 0%, #edf2f7 100%);
            padding: 20px 30px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.3rem;
            color: #2d3748;
        }

        .card-content {
            padding: 30px;
        }

        .enroll-fields-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .enroll-field-group {
            display: flex;
            flex-direction: column;
        }

        .enroll-field-group label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 6px;
        }

        .enroll-input {
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #2d3748;
            transition: border-color 0.2s;
            width: 100%;
            box-sizing: border-box;
        }

        .enroll-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.15);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .enroll-input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 0;
            line-height: 1;
        }

        .field-hint {
            font-size: 0.8rem;
            color: #718096;
            margin-top: 4px;
        }

        .required {
            color: #e53e3e;
        }

        .enroll-courses-section {
            border-top: 1px solid #e2e8f0;
            padding-top: 25px;
            margin-bottom: 30px;
        }

        .enroll-courses-section h3 {
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .course-counter {
            font-size: 0.85rem;
            color: #667eea;
            font-weight: 600;
            margin-left: 8px;
        }

        .form-description {
            color: #718096;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .enroll-course-search {
            margin-bottom: 12px;
        }

        .enroll-course-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .enroll-courses-list {
            max-height: 360px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 8px;
            background: #f8fafc;
        }

        .enroll-course-item {
            border-radius: 8px;
            transition: background 0.15s;
        }

        .enroll-course-item:hover {
            background: #edf2f7;
        }

        .enroll-course-label {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            cursor: pointer;
            width: 100%;
        }

        .enroll-course-checkbox {
            width: 16px;
            height: 16px;
            accent-color: #667eea;
            flex-shrink: 0;
        }

        .enroll-course-title {
            flex: 1;
            font-size: 0.95rem;
            color: #2d3748;
        }

        .enroll-course-status {
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-publish { background: #c6f6d5; color: #276749; }
        .status-draft   { background: #fef3c7; color: #92400e; }
        .status-future  { background: #bee3f8; color: #2a69ac; }

        .no-courses-msg {
            color: #718096;
            font-style: italic;
        }

        .form-actions {
            display: flex;
            justify-content: flex-start;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .webhook-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .webhook-btn.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102,126,234,0.35);
        }

        .webhook-btn.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102,126,234,0.45);
        }

        .webhook-btn.primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .webhook-btn.secondary {
            background: #edf2f7;
            color: #4a5568;
            border: 1px solid #e2e8f0;
        }

        .webhook-btn.secondary:hover {
            background: #e2e8f0;
        }

        .webhook-btn.small {
            padding: 6px 14px;
            font-size: 0.85rem;
        }

        .webhook-btn.large {
            padding: 14px 32px;
            font-size: 1.05rem;
        }

        .btn-icon { font-size: 1.1rem; }

        #enroll-notice {
            margin-bottom: 20px;
            padding: 14px 20px;
            border-radius: 10px;
            font-weight: 500;
        }

        #enroll-notice.success {
            background: #c6f6d5;
            color: #276749;
            border-left: 4px solid #48bb78;
        }

        #enroll-notice.error {
            background: #fed7d7;
            color: #9b2c2c;
            border-left: 4px solid #fc8181;
        }
        </style>

        <script>
        (function($) {
            // Toggle password visibility
            window.togglePasswordVisibility = function() {
                var input = document.getElementById('enroll-password');
                input.type = (input.type === 'password') ? 'text' : 'password';
            };

            // Course search filter
            $('#course-search-input').on('input', function() {
                var term = $(this).val().toLowerCase();
                $('.enroll-course-item').each(function() {
                    var title = $(this).data('title') || '';
                    $(this).toggle(title.indexOf(term) !== -1);
                });
            });

            // Select / Deselect all
            $('#select-all-courses').on('click', function() {
                $('.enroll-course-item:visible .enroll-course-checkbox').prop('checked', true);
                updateCounter();
            });

            $('#deselect-all-courses').on('click', function() {
                $('.enroll-course-item:visible .enroll-course-checkbox').prop('checked', false);
                updateCounter();
            });

            // Counter
            function updateCounter() {
                var count = $('.enroll-course-checkbox:checked').length;
                $('#course-counter').text('(' + count + ' selecionado(s))');
            }

            $(document).on('change', '.enroll-course-checkbox', updateCounter);

            // Form submit
            $('#manual-enroll-form').on('submit', function(e) {
                e.preventDefault();

                var $btn = $('#enroll-submit-btn');
                var $notice = $('#enroll-notice');

                $notice.hide().removeClass('success error');
                $btn.prop('disabled', true).html('<span class="btn-icon">⏳</span> Matriculando...');

                var courses = [];
                $('.enroll-course-checkbox:checked').each(function() {
                    courses.push($(this).val());
                });

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'webhook_receiver_manual_enroll',
                        enroll_nonce: $('#enroll_nonce').val(),
                        enroll_name:  $('#enroll-name').val(),
                        enroll_email: $('#enroll-email').val(),
                        enroll_phone: $('#enroll-phone').val(),
                        enroll_password: $('#enroll-password').val(),
                        enroll_courses: courses
                    },
                    success: function(response) {
                        if (response.success) {
                            $notice.addClass('success').html('✅ ' + response.data.message).show();
                            $('#manual-enroll-form')[0].reset();
                            $('.enroll-course-checkbox').prop('checked', false);
                            updateCounter();
                        } else {
                            $notice.addClass('error').html('❌ ' + (response.data ? response.data.message : 'Erro desconhecido.')).show();
                        }
                    },
                    error: function() {
                        $notice.addClass('error').html('❌ Erro de comunicação. Tente novamente.').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<span class="btn-icon">🎓</span> Matricular Aluno');
                        $('html, body').animate({ scrollTop: $notice.offset().top - 80 }, 400);
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * ========================================
     * AJAX: MATRICULAR ALUNO MANUALMENTE
     * ========================================
     */
    public function ajax_manual_enroll_student() {
        // Capturar qualquer saída acidental que possa corromper a resposta JSON
        ob_start();

        // Verificar nonce
        if (!isset($_POST['enroll_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['enroll_nonce'])), 'webhook_receiver_manual_enroll')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Requisição inválida. Por favor, recarregue a página.'));
            return;
        }

        // Verificar permissões
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Você não tem permissão para realizar esta ação.'));
            return;
        }

        // Sanitizar entradas
        $name     = isset($_POST['enroll_name'])     ? sanitize_text_field(wp_unslash($_POST['enroll_name']))     : '';
        $email    = isset($_POST['enroll_email'])    ? sanitize_email(wp_unslash($_POST['enroll_email']))         : '';
        $phone    = isset($_POST['enroll_phone'])    ? sanitize_text_field(wp_unslash($_POST['enroll_phone']))    : '';
        $password = isset($_POST['enroll_password']) ? wp_unslash($_POST['enroll_password'])                      : '';
        $courses  = isset($_POST['enroll_courses'])  ? array_map('absint', (array) $_POST['enroll_courses'])      : array();

        // Validar campos obrigatórios
        if (empty($name)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'O nome do aluno é obrigatório.'));
            return;
        }

        if (empty($email) || !is_email($email)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Informe um e-mail válido.'));
            return;
        }

        // Gerar senha automática se não foi informada
        if (empty($password)) {
            $password = wp_generate_password(12, true);
        }

        $is_new_user = false;

        if (!email_exists($email)) {
            // Criar novo usuário
            $username = sanitize_user($email);
            // Garantir que o nome de usuário seja único
            if (username_exists($username)) {
                $username = $username . '_' . time();
            }

            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                ob_end_clean();
                wp_send_json_error(array('message' => 'Erro ao criar usuário: ' . $user_id->get_error_message()));
                return;
            }

            // Atualizar nome e dados do usuário
            $name_parts  = explode(' ', $name, 2);
            $first_name  = $name_parts[0];
            $last_name   = isset($name_parts[1]) ? $name_parts[1] : '';

            wp_update_user(array(
                'ID'           => $user_id,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $name,
            ));

            if (!empty($phone)) {
                update_user_meta($user_id, 'telefone', $phone);
            }

            $is_new_user = true;

        } else {
            // Usuário já existe — apenas matricular
            $user    = get_user_by('email', $email);
            $user_id = $user->ID;
        }

        // Matricular nos cursos selecionados
        if (!empty($courses)) {
            $this->enroll_user_in_courses($user_id, $courses);
            $enrolled_count = count($courses);
        } else {
            $enrolled_count = 0;
        }

        // Montar mensagem de retorno
        if ($is_new_user) {
            $msg = sprintf(
                'Aluno <strong>%s</strong> criado e matriculado em %d curso(s) com sucesso.',
                esc_html($name),
                $enrolled_count
            );
        } else {
            $msg = sprintf(
                'Aluno <strong>%s</strong> (já existente) matriculado em %d curso(s) com sucesso.',
                esc_html($name),
                $enrolled_count
            );
        }

        ob_end_clean();
        wp_send_json_success(array('message' => $msg, 'user_id' => $user_id));
    }

} // end class Webhook_Receiver

// Inicializar o plugin
$webhook_receiver = new Webhook_Receiver();