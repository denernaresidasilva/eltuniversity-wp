<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Helpers {

    /**
     * Initialize hooks
     */
    public static function init() {
        // Clear cache when user meta is updated
        add_action('updated_user_meta', [__CLASS__, 'on_user_meta_updated'], 10, 4);
    }

    /**
     * Clear phone cache when user meta is updated
     */
    public static function on_user_meta_updated($meta_id, $user_id, $meta_key, $meta_value) {
        // Lista de campos que afetam o telefone
        $phone_fields = [
            'billing_phone',
            'shipping_phone',
            'phone_number',
            'phone',
            'whatsapp_phone',
            'mobile_phone'
        ];
        
        if (in_array($meta_key, $phone_fields)) {
            self::clear_phone_cache($user_id);
            Logger::debug('Cache de telefone limpo', [
                'user_id' => $user_id,
                'meta_key' => $meta_key
            ]);
        }
    }

    /**
     * Busca mensagens ativas vinculadas a um evento
     */
    public static function get_messages_by_event($event_key) {

        $args = [
            'post_type'      => 'zapwa_message',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_zapwa_type',
                    'value' => 'trigger',
                ],
                [
                    'key'   => '_zapwa_event',
                    'value' => $event_key,
                ],
                [
                    'key'   => '_zapwa_active',
                    'value' => '1',
                ],
            ],
        ];

        return get_posts($args);
    }

    /**
     * Retorna telefone do usuário com busca inteligente
     * 
     * PRIORIDADE:
     * 1. WooCommerce billing_phone
     * 2. WooCommerce shipping_phone  
     * 3. Tutor LMS phone_number
     * 4. Campo genérico phone
     * 5. WhatsApp específico whatsapp_phone
     * 6. Mobile phone mobile_phone
     * 
     * @param int $user_id ID do usuário
     * @return string|null Telefone formatado ou null
     */
    public static function get_user_phone($user_id) {

        // ✅ 1. CACHE - Evitar buscar no banco a cada vez
        $cache_key = 'zapwa_phone_' . $user_id;
        $cached_phone = wp_cache_get($cache_key);
        
        if ($cached_phone !== false) {
            return $cached_phone ?: null;
        }

        // ✅ 2. BUSCAR EM MÚLTIPLAS FONTES (ordem de prioridade)
        $sources = [
            'billing_phone',      // WooCommerce (principal)
            'shipping_phone',     // WooCommerce (alternativo)
            'phone_number',       // Tutor LMS (alunos)
            'phone',              // Campo genérico
            'whatsapp_phone',     // Campo específico WhatsApp
            'mobile_phone',       // Telefone móvel
        ];

        $phone = null;
        $found_source = null;

        foreach ($sources as $source) {
            $raw_phone = get_user_meta($user_id, $source, true);
            
            if (!empty($raw_phone)) {
                // Remove tudo que não é número
                $clean_phone = preg_replace('/\D/', '', $raw_phone);
                
                // ✅ 3. VALIDAÇÃO DE FORMATO
                if (self::is_valid_phone($clean_phone)) {
                    $phone = self::format_phone($clean_phone);
                    $found_source = $source;
                    
                    Logger::debug('Telefone encontrado', [
                        'user_id' => $user_id,
                        'source' => $source,
                        'raw' => $raw_phone,
                        'formatted' => $phone
                    ]);
                    
                    break;
                }
            }
        }

        // ✅ 4. CACHE POR 5 MINUTOS
        wp_cache_set($cache_key, $phone ?: '', '', 300);

        // ✅ 5. LOG SE NÃO ENCONTROU
        if (!$phone) {
            Logger::debug('Nenhum telefone válido encontrado', [
                'user_id' => $user_id,
                'sources_checked' => $sources
            ]);
            
            // Notificar admin (opcional)
            self::notify_missing_phone($user_id);
        }

        return $phone;
    }

    /**
     * Valida se telefone tem formato aceitável
     * 
     * @param string $phone Telefone apenas números
     * @return bool
     */
    private static function is_valid_phone($phone) {
        $length = strlen($phone);
        
        // Aceitar:
        // 10 dígitos: DDD + 8 dígitos (fixo)
        // 11 dígitos: DDD + 9 dígitos (móvel)
        // 12 dígitos: DDI (55) + DDD + 8 dígitos
        // 13 dígitos: DDI (55) + DDD + 9 dígitos
        // 14 dígitos: DDI + DDD + 9 dígitos (outros países)
        
        return $length >= 10 && $length <= 14;
    }

    /**
     * Formata telefone para WhatsApp (adiciona DDI se necessário)
     * 
     * @param string $phone Telefone limpo (só números)
     * @return string Telefone formatado com DDI
     */
    private static function format_phone($phone) {
        $length = strlen($phone);
        
        // Se tem 10 ou 11 dígitos, adicionar DDI do Brasil (55)
        if ($length === 10 || $length === 11) {
            $phone = '55' . $phone;
        }
        
        // Se começa com 0, remover (formato antigo)
        if (substr($phone, 0, 1) === '0') {
            $phone = substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Notifica admin sobre telefone faltante (executar 1x por usuário)
     * 
     * @param int $user_id ID do usuário sem telefone
     */
    private static function notify_missing_phone($user_id) {
        // Verificar se já notificou este usuário
        $notified_key = 'zapwa_notified_missing_phone_' . $user_id;
        
        if (get_transient($notified_key)) {
            return;
        }
        
        // Marcar como notificado (por 7 dias)
        set_transient($notified_key, true, 7 * DAY_IN_SECONDS);
        
        // Enviar email para admin (opcional, pode desabilitar)
        $send_notifications = get_option('zapwa_notify_missing_phones', false);
        
        if (!$send_notifications) {
            return;
        }
        
        $user = get_userdata($user_id);
        $admin_email = get_option('admin_email');
        
        $subject = '[ZapWA] Usuário sem telefone';
        $message = sprintf(
            "Olá,\n\nO usuário %s (ID: %d, Email: %s) tentou receber uma mensagem WhatsApp mas não possui telefone cadastrado.\n\nAcesse o perfil do usuário para adicionar um telefone.",
            $user->display_name,
            $user_id,
            $user->user_email
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Limpa cache de telefone de um usuário
     * (usar quando atualizar telefone do usuário)
     * 
     * @param int $user_id ID do usuário
     */
    public static function clear_phone_cache($user_id) {
        $cache_key = 'zapwa_phone_' . $user_id;
        wp_cache_delete($cache_key);
    }

    /**
     * Busca telefones faltantes (para relatório)
     * 
     * @return array Lista de user_ids sem telefone
     */
    public static function get_users_without_phone() {
        global $wpdb;
        
        // Buscar usuários que receberam eventos mas não têm telefone
        $table_name = $wpdb->prefix . 'zap_wa_logs';
        
        $results = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT DISTINCT user_id 
                FROM `{$table_name}`
                WHERE phone IS NULL OR phone = %s
                ORDER BY created_at DESC
                LIMIT %d",
                '',
                50
            )
        );
        
        return $results ?: [];
    }
}
