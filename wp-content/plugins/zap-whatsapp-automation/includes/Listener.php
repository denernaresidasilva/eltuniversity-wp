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
        Logger::log_stage(
            'evento_recebido',
            sanitize_text_field($payload['event'] ?? ''),
            absint($payload['user_id'] ?? 0),
            '',
            'Evento recebido pelo listener zap_evento'
        );

        // 1. Validar user_id
        if (empty($payload['user_id'])) {
            error_log('[ZAP WhatsApp] Evento sem user_id: ' . wp_json_encode($payload));
            Logger::debug('Falha no evento: user_id ausente', [
                'payload' => $payload,
            ]);
            Logger::log_stage('evento_erro', sanitize_text_field($payload['event'] ?? ''), 0, '', 'Evento sem user_id');
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
            Logger::log_stage('evento_erro', sanitize_text_field($payload['event'] ?? ''), $user_id, '', 'Usuário não encontrado');
            return;
        }
        
        // 3. Validar evento
        if (empty($payload['event'])) {
            error_log("[ZAP WhatsApp] Evento sem tipo para user {$user_id}");
            Logger::debug('Falha no evento: tipo de evento ausente', [
                'user_id' => $user_id,
                'payload' => $payload,
            ]);
            Logger::log_stage('evento_erro', '', $user_id, '', 'Evento sem tipo');
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
            Logger::log_stage('evento_sem_mensagem', $payload['event'], $user_id, '', 'Nenhuma automação ativa para este evento');
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
            Logger::log_stage('evento_erro', $payload['event'], $user_id, '', 'Usuário sem telefone válido');
            return;
        }
        
        // 4. Validar telefone
        if (!self::is_valid_phone($phone)) {
            error_log("[ZAP WhatsApp] Telefone inválido para user ID {$user_id}: {$phone}");
            Logger::debug('Falha no evento: telefone inválido', [
                'user_id' => $user_id,
                'phone' => $phone,
            ]);
            Logger::log_stage('evento_erro', $payload['event'], $user_id, $phone, 'Telefone inválido');
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
                Logger::log_stage('fila_ok', $payload['event'], $user_id, $phone, 'Mensagem enfileirada com sucesso');
            } else {
                Logger::debug('Erro ao enfileirar mensagem', [
                    'message_id' => $message->ID,
                    'event'      => $payload['event'],
                    'phone'      => $phone,
                    'user_name'  => $user->display_name,
                ]);
                error_log("[ZAP WhatsApp] Falha ao enfileirar mensagem para user {$user_id} no evento {$payload['event']}");
                Logger::log_stage('fila_erro', $payload['event'], $user_id, $phone, 'Falha ao enfileirar mensagem');
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
