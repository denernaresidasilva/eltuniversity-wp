<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Cron {

    public static function init() {

        // Register custom cron interval antes de agendar eventos
        add_filter('cron_schedules', [__CLASS__, 'add_cron_interval']);

        add_action('zapwa_process_queue', [__CLASS__, 'process']);

        if (!wp_next_scheduled('zapwa_process_queue')) {
            wp_schedule_event(time(), 'zapwa_every_minute', 'zapwa_process_queue');
            Logger::debug('Cron zapwa_process_queue agendado', [
                'recurrence' => 'zapwa_every_minute',
            ]);
        }
    }

    /**
     * Add custom minute interval for cron
     */
    public static function add_cron_interval($schedules) {
        $schedules['zapwa_every_minute'] = [
            'interval' => 60,
            'display'  => __('Every Minute (ZapWA)')
        ];
        
        // Adicionar intervalo customizado de 5 minutos
        $schedules['five_minutes'] = [
            'interval' => 300,
            'display'  => __('A cada 5 minutos')
        ];
        
        return $schedules;
    }

    public static function process() {

        $item = Queue::next();
        if (!$item) {
            Logger::debug('Fila vazia: nenhum item pendente para processar', [
                'current_time' => current_time('mysql'),
            ]);
            return;
        }

        Logger::debug('Iniciando processamento de item da fila', [
            'item_id' => (int) $item->id,
            'event' => $item->event,
            'user_id' => (int) $item->user_id,
            'attempts' => (int) $item->attempts,
        ]);

        Queue::attempt($item->id);

        $message = get_post($item->message_id);
        if (!$message) {
            Queue::fail($item->id);
            Logger::debug('Erro na fila: mensagem não encontrada para item', [
                'item_id' => (int) $item->id,
                'message_id' => (int) $item->message_id,
            ]);
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
            // Incrementar tentativas para evitar loop infinito
            Queue::attempt($item->id);
            
            // Recarregar item para obter attempts atualizado
            $item = Queue::get_by_id($item->id);
            
            // Verificar se item ainda existe
            if (!$item) {
                Logger::debug('Item removido durante processamento anti-spam');
                return;
            }
            
            // Se já tentou 10 vezes, considerar como falha
            if ($item->attempts >= 10) {
                Queue::fail($item->id);
                Logger::debug('Mensagem falhou por excesso de reagendamentos anti-spam', [
                    'item_id' => $item->id,
                    'phone' => $item->phone,
                    'attempts' => $item->attempts
                ]);
                return;
            }
            
            // Reagendar para 30 segundos depois
            global $wpdb;
            $table = $wpdb->prefix . 'zapwa_queue';
            $new_run_at = wp_date('Y-m-d H:i:s', current_time('timestamp') + 30);
            $wpdb->update(
                $table,
                ['run_at' => $new_run_at],
                ['id' => $item->id],
                ['%s'],
                ['%d']
            );
            
            Logger::debug('Mensagem reagendada por anti-spam', [
                'queue_id' => $item->id,
                'phone' => $item->phone,
                'attempts' => $item->attempts,
                'new_run_at' => $new_run_at
            ]);
            return;
        }

        // Enviar mensagem usando método estático
        $result = EvolutionAPI::send_message($item->phone, $text);

        if (!$result['success']) {
            Logger::debug('Erro no envio da mensagem', [
                'item_id' => (int) $item->id,
                'event' => $item->event,
                'user_id' => (int) $item->user_id,
                'phone' => $item->phone,
                'error' => $result['error'] ?? 'erro desconhecido',
            ]);
        }

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
                // Backoff exponencial: 1min, 5min, 15min
                $retry_delays = [60, 300, 900];
                $delay = $retry_delays[$item->attempts] ?? 900;
                $next_run = wp_date('Y-m-d H:i:s', current_time('timestamp') + $delay);
                
                global $wpdb;
                $table = $wpdb->prefix . 'zapwa_queue';
                $wpdb->update(
                    $table,
                    ['run_at' => $next_run],
                    ['id' => $item->id],
                    ['%s'],
                    ['%d']
                );
                
                Logger::debug('Mensagem reagendada com backoff exponencial', [
                    'item_id' => $item->id,
                    'attempts' => $item->attempts,
                    'next_run' => $next_run,
                    'delay_seconds' => $delay
                ]);
            }
        }
    }
}
