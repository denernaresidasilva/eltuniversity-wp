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

        if (
            empty($payload['event']) ||
            empty($payload['user_id'])
        ) {
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
        $phone = Helpers::get_user_phone($payload['user_id']);

        if (!$phone) {
            Logger::debug('Usuário sem telefone', [
                'user_id' => $payload['user_id']
            ]);
            return;
        }

        foreach ($messages as $message) {

            Queue::add([
                'message_id' => $message->ID, // ✅ CORRETO
                'user_id'    => $payload['user_id'],
                'phone'      => $phone,
                'event'      => $payload['event'],
                'context'    => $payload['context'] ?? [],
                'delay'      => 0,
            ]);

            Logger::debug('Mensagem enviada para fila', [
                'message_id' => $message->ID,
                'event'      => $payload['event'],
                'phone'      => $phone,
            ]);
        }
    }
}
