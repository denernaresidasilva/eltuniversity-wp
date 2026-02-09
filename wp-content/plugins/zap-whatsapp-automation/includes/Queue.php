<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

class Queue {

    public static function init() {
        add_action('zap_wa_process_queue', [self::class, 'process']);
    }

    /**
     * Adiciona item na fila
     */
    public static function add($payload) {

        $queue = get_option('zap_wa_queue', []);

        $payload['run_at'] = time() + (int) ($payload['delay'] ?? 0);
        $payload['attempts'] = $payload['attempts'] ?? 0;

        $queue[] = $payload;
        update_option('zap_wa_queue', $queue);

        if (!wp_next_scheduled('zap_wa_process_queue')) {
            wp_schedule_single_event(time() + 5, 'zap_wa_process_queue');
        }
    }

    /**
     * Processa fila
     */
    public static function process() {

        $queue = get_option('zap_wa_queue', []);
        if (empty($queue)) {
            return;
        }

        usort($queue, function ($a, $b) {
            return $a['run_at'] <=> $b['run_at'];
        });

        $item = $queue[0];

        if ($item['run_at'] > time()) {
            wp_schedule_single_event($item['run_at'], 'zap_wa_process_queue');
            return;
        }

        array_shift($queue);
        update_option('zap_wa_queue', $queue);

        self::send($item);

        if (!empty($queue)) {
            wp_schedule_single_event(time() + 5, 'zap_wa_process_queue');
        }
    }

    /**
     * Envio real da mensagem
     */
    private static function send($item) {

        $message = get_post($item['message_id']);
        if (!$message) {
            return;
        }

        $text = Variables::parse($message->post_content, $item);

        $api = new EvolutionAPI();

        $response = $api->send_message(
            $item['phone'],
            $text
        );

        // Retry automático (até 3 tentativas)
        if (!$response['success'] && $item['attempts'] < 3) {

            $item['attempts']++;
            $item['run_at'] = time() + 60;

            self::add($item);

            Logger::log(
                $item['user_id'],
                $item['event'],
                $item['phone'],
                $text,
                'retry'
            );

            return;
        }

        Logger::log(
            $item['user_id'],
            $item['event'],
            $item['phone'],
            $text,
            $response['success'] ? 'enviado' : 'erro',
            $response
        );
    }
}
