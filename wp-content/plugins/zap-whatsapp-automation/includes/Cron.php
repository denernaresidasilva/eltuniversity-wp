<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Cron {

    public static function init() {

        add_action('zapwa_process_queue', [__CLASS__, 'process']);

        if (!wp_next_scheduled('zapwa_process_queue')) {
            wp_schedule_event(time(), 'zapwa_every_minute', 'zapwa_process_queue');
        }

        // Register custom cron interval
        add_filter('cron_schedules', [__CLASS__, 'add_cron_interval']);
    }

    /**
     * Add custom minute interval for cron
     */
    public static function add_cron_interval($schedules) {
        $schedules['zapwa_every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute (ZapWA)')
        ];
        return $schedules;
    }

    public static function process() {

        $item = Queue::next();
        if (!$item) return;

        Queue::attempt($item->id);

        $message = get_post($item->message_id);
        if (!$message) {
            Queue::fail($item->id);
            return;
        }

        // Decodificar payload
        $payload = json_decode($item->payload, true);
        if (!$payload) {
            Queue::fail($item->id);
            Logger::debug('Payload inválido na fila', ['item_id' => $item->id]);
            return;
        }

        // Processar variáveis no texto
        $text = Variables::parse($message->post_content, $payload);

        // Verificar anti-spam
        if (!AntiSpam::can_send($item->phone)) {
            // Reschedule for 30 seconds later
            global $wpdb;
            $table = $wpdb->prefix . 'zapwa_queue';
            $wpdb->update(
                $table,
                ['run_at' => date('Y-m-d H:i:s', time() + 30)],
                ['id' => $item->id],
                ['%s'],
                ['%d']
            );
            Logger::debug('Mensagem reagendada por anti-spam', [
                'queue_id' => $item->id,
                'phone' => $item->phone,
                'new_run_at' => date('Y-m-d H:i:s', time() + 30)
            ]);
            return;
        }

        // Enviar mensagem usando método estático
        $result = EvolutionAPI::send_message($item->phone, $text);

        // Registrar no log
        Logger::log_send(
            $item->user_id,
            $item->event,
            $item->phone,
            $text,
            $result['success'] ? 'enviado' : 'erro',
            $result['error'] ?? null
        );

        // Atualizar status na fila
        if ($result['success']) {
            Queue::done($item->id);
            Logger::debug('Mensagem processada com sucesso', ['item_id' => $item->id]);
        } else {
            // Verificar número de tentativas
            if ($item->attempts >= 3) {
                Queue::fail($item->id);
                Logger::debug('Mensagem falhou após 3 tentativas', [
                    'item_id' => $item->id,
                    'error' => $result['error']
                ]);
            } else {
                Logger::debug('Mensagem falhada, será reprocessada', [
                    'item_id' => $item->id,
                    'attempts' => $item->attempts
                ]);
            }
        }
    }
}
