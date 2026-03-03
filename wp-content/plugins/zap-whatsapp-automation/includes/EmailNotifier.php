<?php
namespace ZapWA;

if (!defined('ABSPATH')) exit;

class EmailNotifier {

    /**
     * Extract the recipient email from the event payload.
     * Tries common keys in order; allows override via filter.
     *
     * @param array $payload
     * @return string|false Valid email address or false if none found.
     */
    public static function extract_recipient($payload) {

        $email = '';

        if (!empty($payload['email'])) {
            $email = $payload['email'];
        } elseif (!empty($payload['user_email'])) {
            $email = $payload['user_email'];
        } elseif (!empty($payload['context']['email'])) {
            $email = $payload['context']['email'];
        } elseif (!empty($payload['context']['user_email'])) {
            $email = $payload['context']['user_email'];
        } elseif (!empty($payload['context']['recipient_email'])) {
            $email = $payload['context']['recipient_email'];
        } elseif (!empty($payload['user_id'])) {
            $user = get_userdata(absint($payload['user_id']));
            if ($user) {
                $email = $user->user_email;
            }
        }

        /**
         * Filter the resolved email recipient before sending.
         *
         * @param string $email   Resolved email address (may be empty).
         * @param array  $payload The original event payload.
         */
        $email = apply_filters('zapwa_event_email_recipient', $email, $payload);

        return is_email($email) ? $email : false;
    }

    /**
     * Send an email notification for a message automation.
     *
     * @param int   $message_id  Post ID of the zapwa_message.
     * @param array $payload     Event payload.
     * @return bool True if email was sent, false otherwise.
     */
    public static function send($message_id, $payload) {

        $enabled = get_post_meta($message_id, 'zapwa_email_enabled', true);
        if ($enabled !== '1') {
            return false;
        }

        $subject = get_post_meta($message_id, 'zapwa_email_subject', true);
        $body    = get_post_meta($message_id, 'zapwa_email_body', true);
        $is_html = get_post_meta($message_id, 'zapwa_email_is_html', true);

        if (empty($subject) || empty($body)) {
            Logger::debug('E-mail ignorado: assunto ou corpo ausente', [
                'message_id' => $message_id,
            ]);
            return false;
        }

        $recipient = self::extract_recipient($payload);

        $event_key = sanitize_text_field($payload['event'] ?? '');
        $user_id   = absint($payload['user_id'] ?? 0);

        if (!$recipient) {
            Logger::debug('E-mail não enviado: destinatário não encontrado', [
                'message_id' => $message_id,
                'event'      => $event_key,
            ]);
            Logger::log_stage('email_sem_destinatario', $event_key, $user_id, '', 'Destinatário de e-mail não encontrado no payload');
            return false;
        }

        // Parse placeholders in subject and body
        $subject = Variables::parse($subject, $payload);
        $body    = Variables::parse($body, $payload);

        $headers = [];
        if ($is_html) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
        }

        Logger::debug('Enviando e-mail', [
            'message_id' => $message_id,
            'event'      => $event_key,
            'recipient'  => $recipient,
            'subject'    => $subject,
            'is_html'    => (bool) $is_html,
        ]);

        $sent = wp_mail($recipient, $subject, $body, $headers);

        if ($sent) {
            Logger::debug('E-mail enviado com sucesso', [
                'message_id' => $message_id,
                'recipient'  => $recipient,
            ]);
            Logger::log_stage('email_ok', $event_key, $user_id, '', "E-mail enviado para {$recipient}");
        } else {
            Logger::debug('Falha ao enviar e-mail', [
                'message_id' => $message_id,
                'recipient'  => $recipient,
            ]);
            Logger::log_stage('email_erro', $event_key, $user_id, '', "Falha ao enviar e-mail para {$recipient}");
        }

        return $sent;
    }
}
