<?php
/*
Plugin Name: Supermembros - Receber Webhook de Qualquer Plataforma
Description: Plugin para Receber Webhooks de Qualquer Plataforma e Cadastrar/Remover Alunos na Supermembros
Version: 3.1
Author: Raul Julio da Cruz
*/

// Se este arquivo é chamado diretamente, aborta.
if (!defined('WPINC')) {
    die;
}

// Definir constantes
define('WEBHOOK_RECEIVER_VERSION', '3.3.1');
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
            case 'compra_reembolsada':
                $result = $this->process_refunded_sale($data);
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
     * ==========================================
     * FORMATAÇÃO DE TELEFONE - FUNÇÕES AUXILIARES
     * ==========================================
     */

    /**
     * Limpar telefone (apenas números)
     */
    private function clean_phone($phone) {
        return preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Remover DDI brasileiro (55) se presente
     */
    private function remove_ddi($phone) {
        $clean = $this->clean_phone($phone);
        
        // Remover DDI se houver
        if (strlen($clean) > 11 && substr($clean, 0, 2) === '55') {
            return substr($clean, 2);
        }
        
        return $clean;
    }

    /**
     * Validar telefone brasileiro
     */
    private function is_valid_phone($phone) {
        $clean = $this->remove_ddi($phone);
        
        // Validar tamanho (10 ou 11 dígitos)
        return (strlen($clean) === 10 || strlen($clean) === 11);
    }

    /**
     * Formatar telefone com máscara brasileira
     */
    private function format_phone_mask($phone) {
        $clean = $this->remove_ddi($phone);
        
        if (strlen($clean) === 11) {
            // Celular: (11) 99988-7766
            return sprintf('(%s) %s-%s',
                substr($clean, 0, 2),
                substr($clean, 2, 5),
                substr($clean, 7, 4)
            );
        } elseif (strlen($clean) === 10) {
            // Fixo: (11) 3222-1100
            return sprintf('(%s) %s-%s',
                substr($clean, 0, 2),
                substr($clean, 2, 4),
                substr($clean, 6, 4)
            );
        }
        
        return $phone; // Retorna original se inválido
    }

    /**
     * Formatar telefone para WhatsApp
     */
    private function format_phone_whatsapp($phone) {
        $clean = $this->remove_ddi($phone);
        
        // Adicionar DDI 55
        if (strlen($clean) === 10 || strlen($clean) === 11) {
            return '55' . $clean;
        }
        
        return '';
    }

    /**
     * Salvar telefone com múltiplos formatos
     */
    private function save_phone_multiple_formats($user_id, $phone_raw) {
        if (empty($phone_raw)) {
            return;
        }
        
        // Validar
        if (!$this->is_valid_phone($phone_raw)) {
            $this->log_webhook("AVISO: Telefone inválido recebido: $phone_raw (deve ter 10 ou 11 dígitos após remover código do país)");
            return;
        }
        
        // Remover DDI para formato BR
        $phone_br = $this->remove_ddi($phone_raw);
        
        // Salvar múltiplos formatos
        update_user_meta($user_id, 'telefone_original', $phone_raw);
        update_user_meta($user_id, 'telefone_limpo', $phone_br);
        update_user_meta($user_id, 'telefone_whatsapp', $this->format_phone_whatsapp($phone_raw));
        update_user_meta($user_id, 'telefone_formatado', $this->format_phone_mask($phone_br));
        
        // Backward compatibility (campo antigo)
        update_user_meta($user_id, 'telefone', $phone_br);
        
        // Log
        $this->log_webhook(sprintf(
            "Telefone salvo - Original: %s | Limpo: %s | WhatsApp: 55%s",
            $phone_raw,
            $phone_br,
            $phone_br
        ));
    }
    
    /**
     * ================================================================================
     * BUSCAR CAMPO RECURSIVAMENTE EM UM ARRAY
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
     * BUSCAR RECURSIVAMENTE EM UM ARRAY
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
            
            // Verificar tipo de webhook (matrícula ou desmatrícula)
            $enrollment_type = isset($webhook_config->enrollment_type) ? $webhook_config->enrollment_type : 'enroll';
            
            if ($enrollment_type === 'unenroll') {
                // Desmatricular usuário
                $this->maybe_unenroll_user($mapped_data, $course_ids, $webhook_config);
            } else {
                // Matricular usuário (comportamento padrão)
                $this->maybe_create_user($mapped_data, $course_ids, $webhook_config);
            }
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
                
                // Verificar tipo de webhook (matrícula ou desmatrícula)
                $enrollment_type = isset($webhook_config->enrollment_type) ? $webhook_config->enrollment_type : 'enroll';
                
                if ($enrollment_type === 'unenroll') {
                    // Desmatricular usuário
                    $this->maybe_unenroll_user($sale_data, $course_ids, $webhook_config);
                } else {
                    // Matricular usuário (comportamento padrão)
                    $this->maybe_create_user($sale_data, $course_ids, $webhook_config);
                }
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
     * PROCESSAR VENDA REEMBOLSADA
     * ================================================================================
     */
    public function process_refunded_sale($data) {
        // Pegue o e-mail do comprador nos dados recebidos
        $email = '';
        if (isset($data['data']['buyer']['email'])) {
            $email = $data['data']['buyer']['email'];
        } elseif (isset($data['payer_email'])) {
            $email = $data['payer_email'];
        }

        if (!$email) {
            return array('success' => false, 'message' => 'Email do comprador não encontrado no webhook.');
        }

        // Procure o usuário no WordPress pelo e-mail
        $user = get_user_by('email', $email);
        if ($user) {
            // Deleta o usuário e seus dados
            require_once(ABSPATH.'wp-admin/includes/user.php');
            wp_delete_user($user->ID);
            return array('success' => true, 'message' => 'Usuário excluído pelo reembolso.', 'user_id' => $user->ID);
        } else {
            return array('success' => false, 'message' => 'Usuário não encontrado pelo email.');
        }
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
                $this->save_phone_multiple_formats($user_id, $sale_data['telefone']);
                
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
     * DESMATRICULAR USUÁRIO DOS CURSOS ESPECIFICADOS
     * ================================================================================
     */
    public function maybe_unenroll_user($sale_data, $course_ids = array(), $webhook_config = null) {
        // Verificar se existe usuário com este e-mail
        $user = get_user_by('email', $sale_data['payer_email']);
        
        if (!$user) {
            // Log se usuário não existir
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DESMATRÍCULA: Usuário não encontrado - Email: {$sale_data['payer_email']}");
            }
            return false;
        }
        
        $user_id = $user->ID;
        
        // Log de início
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("DESMATRÍCULA: Iniciando para usuário {$user_id} - Email: {$sale_data['payer_email']}");
        }
        
        // Desmatricular usuário dos cursos principais
        if (!empty($course_ids)) {
            $this->unenroll_user_from_courses($user_id, $course_ids);
        }
        
        // Processar desmatrícula de order bumps se houver
        if ($webhook_config) {
            $this->process_order_bumps_unenrollment($user_id, $sale_data, $webhook_config);
        }
        
        // Notificar administrador se configurado
        if (get_option('webhook_receiver_notify_admin', 'no') === 'yes') {
            $admin_email = get_option('admin_email');
            $subject = 'Usuário desmatriculado via webhook';
            $message = 'O usuário foi desmatriculado dos cursos: ' . $sale_data['first_name'] . ' ' . $sale_data['last_name'] . ' (' . $sale_data['payer_email'] . ')';
            
            wp_mail($admin_email, $subject, $message);
        }
        
        return $user_id;
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
     * PROCESSAR DESMATRÍCULAS DE ORDER BUMPS
     * ================================================================================
     */
    private function process_order_bumps_unenrollment($user_id, $sale_data, $webhook_config) {
        // Log de debug
        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
            $this->log_webhook("DEBUG: Iniciando processamento de desmatrícula de order bumps para usuário $user_id");
            $this->log_webhook("DEBUG: Dados de produtos adicionais: " . print_r($sale_data['produtos_adicionais'], true));
        }
        
        // Verificar se há produtos adicionais
        if (empty($sale_data['produtos_adicionais']) || !is_array($sale_data['produtos_adicionais'])) {
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Nenhum produto adicional encontrado para desmatrícula");
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
        
        // Criar um mapa de product_id => course_id
        $order_bump_map = array();
        foreach ($order_bump_configs as $config) {
            $order_bump_map[strval($config->product_id)] = $config->course_id;
            $order_bump_map[intval($config->product_id)] = $config->course_id;
        }
        
        // Processar cada produto adicional
        $courses_to_unenroll = array();
        foreach ($sale_data['produtos_adicionais'] as $produto_adicional) {
            $matched = false;
            
            // Verificar por ID do produto
            $possible_id_keys = array('id', 'ID', 'product_id', 'produto_id', 'sku', 'SKU');
            foreach ($possible_id_keys as $key) {
                if (isset($produto_adicional[$key])) {
                    $product_id = $produto_adicional[$key];
                    
                    if (isset($order_bump_map[$product_id])) {
                        $courses_to_unenroll[] = $order_bump_map[$product_id];
                        $matched = true;
                        break;
                    }
                    
                    if (isset($order_bump_map[strval($product_id)])) {
                        $courses_to_unenroll[] = $order_bump_map[strval($product_id)];
                        $matched = true;
                        break;
                    }
                    
                    if (isset($order_bump_map[intval($product_id)])) {
                        $courses_to_unenroll[] = $order_bump_map[intval($product_id)];
                        $matched = true;
                        break;
                    }
                }
            }
            
            // Se não encontrou por ID, tentar por nome
            if (!$matched) {
                $possible_name_keys = array('nome', 'name', 'product_name', 'produto_nome', 'title');
                foreach ($possible_name_keys as $key) {
                    if (isset($produto_adicional[$key])) {
                        $product_name = $produto_adicional[$key];
                        
                        foreach ($order_bump_configs as $config) {
                            if (strcasecmp(trim($config->product_name), trim($product_name)) === 0) {
                                $courses_to_unenroll[] = $config->course_id;
                                $matched = true;
                                break 2;
                            }
                            
                            if (stripos(trim($product_name), trim($config->product_name)) !== false || 
                                stripos(trim($config->product_name), trim($product_name)) !== false) {
                                $courses_to_unenroll[] = $config->course_id;
                                $matched = true;
                                break 2;
                            }
                        }
                    }
                }
            }
        }
        
        // Desmatricular o usuário dos cursos dos order bumps
        if (!empty($courses_to_unenroll)) {
            $courses_to_unenroll = array_unique($courses_to_unenroll);
            
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("DEBUG: Desmatriculando usuário $user_id dos cursos de order bump: " . implode(', ', $courses_to_unenroll));
            }
            
            $this->unenroll_user_from_courses($user_id, $courses_to_unenroll);
            
            if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                $this->log_webhook("Order bumps desmatriculados com sucesso para o usuário $user_id: " . implode(', ', $courses_to_unenroll));
            }
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
                        
                        // Log
                        if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                            $course = get_post($course_id);
                            $course_name = $course ? $course->post_title : "ID: $course_id";
                            $this->log_webhook("MATRÍCULA: Usuário $user_id matriculado no curso $course_name");
                        }
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
     * DESMATRICULAR USUÁRIOS DE CURSOS ESPECIFICOS
     * ========================================
     */
    public function unenroll_user_from_courses($user_id, $course_ids = array()) {
        if (empty($course_ids) || !is_array($course_ids)) {
            return;
        }
        
        global $wpdb;
        
        foreach ($course_ids as $course_id) {
            if (empty($course_id)) {
                continue;
            }
            
            // Buscar o enrollment_id na tabela de posts do WordPress
            $enrollment_id = $wpdb->get_var($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts} 
                WHERE post_type = 'tutor_enrolled' 
                AND post_parent = %d 
                AND post_author = %d
            ", $course_id, $user_id));
            
            if ($enrollment_id) {
                // Deletar o post de matrícula
                wp_delete_post($enrollment_id, true);
                
                // Log
                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                    $course = get_post($course_id);
                    $course_name = $course ? $course->post_title : "ID: $course_id";
                    $this->log_webhook("DESMATRÍCULA: Usuário $user_id desmatriculado do curso $course_name (Enrollment ID: $enrollment_id)");
                }
            } else {
                // Log se não encontrou matrícula
                if (get_option('webhook_receiver_enable_logs', 'yes') === 'yes') {
                    $course = get_post($course_id);
                    $course_name = $course ? $course->post_title : "ID: $course_id";
                    $this->log_webhook("DESMATRÍCULA: Matrícula não encontrada para usuário $user_id no curso $course_name");
                }
            }
        }
        
        // Atualizar meta do usuário
        $current_courses = get_user_meta($user_id, '_user_courses', true);
        if (!is_array($current_courses)) {
            $current_courses = array();
        }
        
        // Remover os cursos do array
        $updated_courses = array_diff($current_courses, $course_ids);
        update_user_meta($user_id, '_user_courses', $updated_courses);
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
        
        // Tabela de configurações de webhooks (atualizada com campo enrollment_type)
        $webhooks_table = $wpdb->prefix . 'webhook_receiver_endpoints';
        $webhooks_sql = "CREATE TABLE IF NOT EXISTS $webhooks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            webhook_id varchar(50) NOT NULL,
            webhook_name varchar(100) NOT NULL,
            course_id bigint(20) NOT NULL DEFAULT 0,
            enrollment_type varchar(20) NOT NULL DEFAULT 'enroll',
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
        
        // Verificar se a coluna enrollment_type existe e adicioná-la se não existir
        $enrollment_type_exists = $wpdb->get_results($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
             WHERE table_name = %s AND column_name = 'enrollment_type'",
            $webhooks_table
        ));
        
        if (empty($enrollment_type_exists)) {
            $wpdb->query("ALTER TABLE $webhooks_table ADD COLUMN enrollment_type varchar(20) NOT NULL DEFAULT 'enroll'");
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
            'Integração',
            'Checkout',
            'manage_options',
            'webhook-receiver-endpoints',
            array($this, 'webhooks_page'),
            'dashicons-migrate',
            26
        );
        
        
    }
    
    /**
     * ========================================
     * PÁGINA DE GERENCIAMENTO DE WEBHOOKS
     * ========================================
     */
    public function webhooks_page() {
        // Incluir scripts e estilos necessários
        wp_enqueue_script('jquery');
        ?>
        <div class="wrap webhook-receiver-webhooks"><br><br>
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
        </style>
        
        <div class="wrap webhook-receiver-webhooks">
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
                                <span class="icon">🎯</span> Tipo de Webhook
                            </label>
                            <div class="enrollment-type-container">
                                <label class="enrollment-type-option">
                                    <input type="radio" name="enrollment_type" value="enroll" checked>
                                    <span class="option-content">
                                        <span class="option-icon">✅</span>
                                        <span class="option-text">
                                            <strong>Matrícula</strong>
                                            <small>Matricular usuários nos cursos selecionados</small>
                                        </span>
                                    </span>
                                </label>
                                <label class="enrollment-type-option">
                                    <input type="radio" name="enrollment_type" value="unenroll">
                                    <span class="option-content">
                                        <span class="option-icon">❌</span>
                                        <span class="option-text">
                                            <strong>Desmatrícula</strong>
                                            <small>Desmatricular usuários dos cursos selecionados</small>
                                        </span>
                                    </span>
                                </label>
                            </div>
                            <p class="form-description">Escolha se este webhook irá matricular ou desmatricular usuários dos cursos.</p>
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
                            <p class="form-description">Selecione os cursos para matricular/desmatricular o usuário.</p>
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
                        <h2><span class="icon">📄</span> Estrutura do JSON - <span id="modal-json-webhook-name" class="highlight-text"></span></h2>
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

        .enrollment-type-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        .enrollment-type-option {
            position: relative;
            cursor: pointer;
        }

        .enrollment-type-option input[type="radio"] {
            opacity: 0;
            position: absolute;
            width: 0;
            height: 0;
        }

        .option-content {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            transition: all 0.3s ease;
        }

        .enrollment-type-option:hover .option-content {
            border-color: #cbd5e0;
            background: #f7fafc;
        }

        .enrollment-type-option input[type="radio"]:checked + .option-content {
            border-color: #667eea;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .option-icon {
            font-size: 1.5rem;
        }

        .option-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .option-text strong {
            color: #2d3748;
            font-size: 0.95rem;
        }

        .option-text small {
            color: #718096;
            font-size: 0.85rem;
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

        @media (max-width: 768px) {
            .webhook-receiver-webhooks {
                margin: 0 -10px;
                padding: 15px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .enrollment-type-container {
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

            .course-actions {
                flex-direction: column;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function ($) {
            var isSubmitting = false;
            
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
            
            $('#select-all-courses').on('click', function() {
                $('input[name="course_ids[]"]').prop('checked', true);
                animateCheckboxes($('input[name="course_ids[]"]'));
            });
            
            $('#deselect-all-courses').on('click', function() {
                $('input[name="course_ids[]"]').prop('checked', false);
            });
            
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
            
            $('#webhook-form').on('submit', function(e) {
                e.preventDefault();
                
                if (isSubmitting) return;
                
                var form = $(this);
                var submitBtn = form.find('button[type="submit"]');
                var spinner = form.find('.loading-spinner');
                
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
                            form.find('input[type=radio][value="enroll"]').prop('checked', true);
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
            
            $(document).on('click', '.manage-main-courses', function(e) {
                e.preventDefault();
                var webhookId = $(this).data('webhook-id');
                var webhookName = $(this).data('webhook-name');
                
                $('#modal-main-webhook-id').val(webhookId);
                $('#modal-main-webhook-name').text(webhookName);
                
                loadMainCourses(webhookId);
                
                $('#main-courses-modal').fadeIn(300);
            });
            
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
                
                var spinner = $(this).siblings('.modal-spinner');
                spinner.css('display', 'flex');
                
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
                            
                            showNotification('Cursos principais atualizados com sucesso!', 'success');
                            $('#main-courses-modal').hide();
                        } else {
                            alert('Erro: ' + response.data.message);
                        }
                    },
                    complete: function() {
                        spinner.css('display', 'none');
                    }
                });
            });
            
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
                            $('.main-course-checkbox').prop('checked', false);
                            
                            response.data.course_ids.forEach(function(courseId) {
                                $('.main-course-checkbox[value="' + courseId + '"]').prop('checked', true);
                            });
                        }
                    }
                });
            }
            
            $('.close-modal, .close-modal-btn, .modal-backdrop').on('click', function() {
                $('#order-bumps-modal, #main-courses-modal, #json-structure-modal').hide();
            });
            
            $(document).on('click', '.manage-order-bumps', function(e) {
                e.preventDefault();
                var webhookId = $(this).data('webhook-id');
                var webhookName = $(this).data('webhook-name');
                
                $('#modal-webhook-id').val(webhookId);
                $('#modal-webhook-name').text(webhookName);
                
                loadOrderBumps(webhookId);
                
                $('#order-bumps-modal').show();
            });
            
            $('#add-order-bump').on('click', function() {
                var webhookId = $('#modal-webhook-id').val();
                var productId = $('#order-bump-product-id').val();
                var productName = $('#order-bump-product-name').val();
                var courseId = $('#order-bump-course-id').val();
                
                if (!productId || !productName || !courseId) {
                    alert('Preencha todos os campos!');
                    return;
                }
                
                var spinner = $(this).siblings('.loading-spinner');
                spinner.css('display', 'flex');
                
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
                            showNotification('Order bump adicionado com sucesso!', 'success');
                        } else {
                            alert('Erro: ' + response.data.message);
                        }
                    },
                    complete: function() {
                        spinner.css('display', 'none');
                    }
                });
            });
            
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
            
            $(document).on('click', '.delete-webhook', function(e) {
                e.preventDefault();
                
                if (confirm('Tem certeza que deseja excluir este webhook?')) {
                    var webhookId = $(this).data('webhook-id');
                    var spinner = $(this).siblings('.spinner');
                    spinner.css('display', 'inline-block');
                    
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
                                showNotification('Webhook excluído com sucesso!', 'success');
                            } else {
                                alert('Erro ao excluir webhook: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('Erro ao comunicar com o servidor.');
                        },
                        complete: function() {
                            spinner.css('display', 'none');
                        }
                    });
                }
            });
            
            $(document).on('click', '.copy-url', function() {
                var url = $(this).data('url');
                var tempInput = $('<input>');
                $('body').append(tempInput);
                tempInput.val(url).select();
                document.execCommand('copy');
                tempInput.remove();
                
                var button = $(this);
                var originalText = button.text();
                button.text('Copiado!');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
            
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
                }, 120000);
                
                button.prop('disabled', true).text('Escutando...');
            });
            
            $(document).on('click', '.show-json-structure', function() {
                var webhookId = $(this).data('webhook-id');
                var webhookName = $(this).data('webhook-name');
                
                $('#modal-json-webhook-name').text(webhookName);
                $('#json-structure-display').html('<p>Carregando dados...</p>');
                
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
            
            $('#select-all-main-courses').on('click', function() {
                $('.main-course-checkbox').prop('checked', true);
            });
            
            $('#deselect-all-main-courses').on('click', function() {
                $('.main-course-checkbox').prop('checked', false);
            });
        });
        </script>
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
                
                $order_bumps_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $order_bumps_table WHERE webhook_id = %s",
                    $webhook->webhook_id
                ));
                
                $webhook_url = rest_url('webhook-receiver/v1/receive/' . $webhook->webhook_id);
                $has_webhook_data = !empty($webhook->webhook_data);
                
                $enrollment_type = isset($webhook->enrollment_type) ? $webhook->enrollment_type : 'enroll';
                $enrollment_label = $enrollment_type === 'unenroll' ? 'Desmatrícula' : 'Matrícula';
                $enrollment_icon = $enrollment_type === 'unenroll' ? '❌' : '✅';
                $enrollment_class = $enrollment_type === 'unenroll' ? 'type-unenroll' : 'type-enroll';
            ?>
                <div class="webhook-item-card">
                    <div class="webhook-item-header">
                        <h3 class="webhook-name">
                            <span class="icon">🔗</span>
                            <?php echo esc_html($webhook->webhook_name); ?>
                        </h3>
                        <div class="webhook-badges">
                            <span class="webhook-type-badge <?php echo $enrollment_class; ?>">
                                <?php echo $enrollment_icon; ?> <?php echo $enrollment_label; ?>
                            </span>
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
                                    <span class="btn-icon">📄</span> Ver JSON
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
        }

        .webhook-name {
            margin: 0 0 10px 0;
            font-size: 1.2rem;
            color: #2d3748;
            font-weight: 600;
        }

        .webhook-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .webhook-type-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .webhook-type-badge.type-enroll {
            background: #d1fae5;
            color: #065f46;
        }

        .webhook-type-badge.type-unenroll {
            background: #fee2e2;
            color: #991b1b;
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
        $enrollment_type = isset($_POST['enrollment_type']) ? sanitize_text_field($_POST['enrollment_type']) : 'enroll';
        
        if (empty($webhook_name) || empty($webhook_id) || empty($course_ids)) {
            wp_send_json_error(array('message' => 'Todos os campos obrigatórios devem ser preenchidos.'));
            return;
        }
        
        if (!in_array($enrollment_type, array('enroll', 'unenroll'))) {
            $enrollment_type = 'enroll';
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'webhook_receiver_endpoints';
        $courses_table = $wpdb->prefix . 'webhook_receiver_courses';
        
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE webhook_id = %s", $webhook_id));
        if ($exists) {
            wp_send_json_error(array('message' => 'Este ID de webhook já está em uso. Por favor, escolha outro.'));
            return;
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'webhook_id' => $webhook_id,
                'webhook_name' => $webhook_name,
                'course_id' => 0,
                'enrollment_type' => $enrollment_type,
                'webhook_data' => null,
                'created_at' => current_time('mysql')
            )
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao salvar webhook no banco de dados.'));
            return;
        }
        
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
        
        $wpdb->delete($courses_table, array('webhook_id' => $webhook_id));
        $wpdb->delete($order_bumps_table, array('webhook_id' => $webhook_id));
        $result = $wpdb->delete($table_name, array('webhook_id' => $webhook_id));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Erro ao excluir webhook do banco de dados.'));
            return;
        }
        
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
        
        $wpdb->delete($table_name, array('webhook_id' => $webhook_id));
        
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
     * MAPEAR DADOS DO WEBHOOK
     * ========================================
     */
    public function map_webhook_data($data, $webhook_id) {
        return $this->map_generic_data($data);
    }
    
    /**
     * ========================================
     * PÁGINA ADMIN PRINCIPAL
     * ========================================
     */
    public function admin_page() {
        echo '<div class="wrap"><h1>Webhook Receiver - Documentação em breve</h1></div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE CONFIGURAÇÕES
     * ========================================
     */
    public function settings_page() {
        echo '<div class="wrap"><h1>Configurações - Implementar interface</h1></div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE VENDAS
     * ========================================
     */
    public function sales_page() {
        echo '<div class="wrap"><h1>Vendas - Implementar lista de vendas</h1></div>';
    }
    
    /**
     * ========================================
     * PÁGINA DE DEBUG
     * ========================================
     */
    public function debug_page() {
        echo '<div class="wrap"><h1>Debug - Implementar ferramentas de debug</h1></div>';
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
    }
    
    public function general_section_callback() {
        echo '<p>Configure as opções gerais do webhook receiver.</p>';
    }
    
    public function enable_logs_callback() {
        $value = get_option('webhook_receiver_enable_logs', 'yes');
        echo '<select name="webhook_receiver_enable_logs">';
        echo '<option value="yes"' . selected($value, 'yes', false) . '>Sim</option>';
        echo '<option value="no"' . selected($value, 'no', false) . '>Não</option>';
        echo '</select>';
    }
    
    public function create_users_callback() {
        $value = get_option('webhook_receiver_create_users', 'no');
        echo '<select name="webhook_receiver_create_users">';
        echo '<option value="yes"' . selected($value, 'yes', false) . '>Sim</option>';
        echo '<option value="no"' . selected($value, 'no', false) . '>Não</option>';
        echo '</select>';
    }
    
    public function default_password_callback() {
        $value = get_option('webhook_receiver_default_password', '');
        echo '<input type="text" name="webhook_receiver_default_password" value="' . esc_attr($value) . '" />';
    }
    
    public function notify_admin_callback() {
        $value = get_option('webhook_receiver_notify_admin', 'no');
        echo '<select name="webhook_receiver_notify_admin">';
        echo '<option value="yes"' . selected($value, 'yes', false) . '>Sim</option>';
        echo '<option value="no"' . selected($value, 'no', false) . '>Não</option>';
        echo '</select>';
    }
    
    public function notify_user_callback() {
        $value = get_option('webhook_receiver_notify_user', 'no');
        echo '<select name="webhook_receiver_notify_user">';
        echo '<option value="yes"' . selected($value, 'yes', false) . '>Sim</option>';
        echo '<option value="no"' . selected($value, 'no', false) . '>Não</option>';
        echo '</select>';
    }
    
    public function user_email_subject_callback() {
        $subject = get_option('webhook_receiver_user_email_subject', 'Bem-vindo! Seus dados de acesso');
        echo '<input type="text" name="webhook_receiver_user_email_subject" value="' . esc_attr($subject) . '" class="regular-text" />';
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
        echo '<p class="description">Códigos: <strong>(nome)</strong>, <strong>(email)</strong>, <strong>(senha)</strong>, <strong>(curso_nome)</strong>, <strong>(login_url)</strong>, <strong>(site_name)</strong></p>';
    }
    
    /**
     * ==========================
     * ENVIO DE EMAIL AO USUÁRIO DE ACESSO
     * ==========================
     */
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

        $magic_link = $login_url;
        if (class_exists('\HandyMagicLogin\Utils')) {
            $magic_link = \HandyMagicLogin\Utils::get_login_link($user_id, $login_url, 60);
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
}

// Inicializar o plugin
$webhook_receiver = new Webhook_Receiver();