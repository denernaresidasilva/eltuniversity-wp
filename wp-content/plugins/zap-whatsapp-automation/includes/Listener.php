<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Listener {

    public static function init() {
        add_action('zap_evento', [self::class, 'handle'], 10, 1);
    }

    /**
     * Recebe eventos do Zap Tutor Events
     */
    public static function handle($payload) {

        Logger::debug('Evento recebido no Listener', [
            'payload' => $payload,
        ]);

        // 1. Validar user_id
        if (empty($payload['user_id'])) {
            error_log('[ZAP WhatsApp] Evento sem user_id: ' . wp_json_encode($payload));
            Logger::debug('Falha no evento: user_id ausente', [
                'payload' => $payload,
            ]);
            return;
        }
        
        $user_id = absint($payload['user_id']);
        
        // 2. Verificar se usuário existe no WordPress
        $user = get_userdata($user_id);
        if (!$user) {
            error_log("[ZAP WhatsApp] User ID {$user_id} não existe no WordPress");
            Logger::debug('Falha no evento: usuário inexistente', [
                'user_id' => $user_id,
            ]);
            return;
        }
        
        // 3. Validar evento
        if (empty($payload['event'])) {
            error_log("[ZAP WhatsApp] Evento sem tipo para user {$user_id}");
            Logger::debug('Falha no evento: tipo de evento ausente', [
                'user_id' => $user_id,
                'payload' => $payload,
            ]);
            return;
        }

        // ✅ DEBUG: confirma que o evento chegou
        Logger::debug('Evento recebido no ZapWA', $payload);

        // 🔍 Buscar mensagens configuradas para este evento
        $messages = Helpers::get_messages_by_event($payload['event']);

        if (empty($messages)) {
            Logger::debug('Nenhuma mensagem configurada para evento', [
                'event' => $payload['event']
            ]);
            return;
        }

        // 📞 Buscar telefone do usuário
        $phone = Helpers::get_user_phone($user_id);

        if (!$phone) {
            error_log("[ZAP WhatsApp] User ID {$user_id} ({$user->user_email}) não tem telefone cadastrado");
            Logger::debug('Usuário sem telefone', [
                'user_id' => $user_id,
                'user_email' => $user->user_email
            ]);
            return;
        }
        
        // 4. Validar telefone
        if (!self::is_valid_phone($phone)) {
            error_log("[ZAP WhatsApp] Telefone inválido para user ID {$user_id}: {$phone}");
            Logger::debug('Falha no evento: telefone inválido', [
                'user_id' => $user_id,
                'phone' => $phone,
            ]);
            return;
        }
        
        // 5. Log do processamento
        error_log("[ZAP WhatsApp] Processando evento '{$payload['event']}' para user {$user_id} - Telefone: {$phone}");

        foreach ($messages as $message) {

            $queued = Queue::add([
                'message_id' => $message->ID, // ✅ CORRETO
                'user_id'    => $user_id,
                'phone'      => $phone,
                'event'      => $payload['event'],
                'context'    => $payload['context'] ?? [],
                'delay'      => 0,
            ]);

            if ($queued) {
                Logger::debug('Mensagem enviada para fila', [
                    'message_id' => $message->ID,
                    'event'      => $payload['event'],
                    'phone'      => $phone,
                    'user_name'  => $user->display_name,
                ]);
            } else {
                Logger::debug('Erro ao enfileirar mensagem', [
                    'message_id' => $message->ID,
                    'event'      => $payload['event'],
                    'phone'      => $phone,
                    'user_name'  => $user->display_name,
                ]);
                error_log("[ZAP WhatsApp] Falha ao enfileirar mensagem para user {$user_id} no evento {$payload['event']}");
            }
        }
    }
    
    /**
     * Valida formato do telefone
     * 
     * @param string $phone Telefone formatado
     * @return bool
     */
    private static function is_valid_phone($phone) {
        // Remove tudo exceto números
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Telefone deve ter pelo menos 10 dígitos (incluindo DDD)
        return strlen($digits) >= 10;
    }
}
