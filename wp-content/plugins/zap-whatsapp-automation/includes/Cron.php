<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class Cron {

    public static function init() {

        add_action('zapwa_process_queue', [__CLASS__, 'process']);

        if (!wp_next_scheduled('zapwa_process_queue')) {
            wp_schedule_event(time(), 'minute', 'zapwa_process_queue');
        }
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

            Logger::log(
                $item->user_id,
                $item->event,
                $item->phone,
                $text,
                'enviado'
            );

        } else {

            if ($item->attempts >= 3) {
                Queue::fail($item->id);
            }
        }
    }
}
