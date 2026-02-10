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

        $payload = json_decode($item->payload, true);

        $text = Variables::parse(
            $message->post_content,
            array_merge($payload, ['phone' => $item->phone])
        );

        if (!AntiSpam::can_send($item->phone)) return;

        $api = new EvolutionAPI();
        $result = $api->send_message($item->phone, $text);

        if ($result['success']) {

            Queue::done($item->id);

            Logger::log_send(
                $item->user_id,
                $item->event,
                $item->phone,
                $text,
                'enviado'
            );

        } else {

            if ($item->attempts >= 3) {
                Queue::fail($item->id);
                
                Logger::log_send(
                    $item->user_id,
                    $item->event,
                    $item->phone,
                    $text,
                    'erro'
                );
            }
        }
    }
}
