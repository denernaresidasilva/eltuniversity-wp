<?php
namespace ZapWA;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Leitor de eventos da tabela zap_event_logs
 * 
 * Permite processar eventos que não foram capturados pelo hook em tempo real
 */
class EventLogReader {
    
    /**
     * Processar eventos pendentes dos últimos X minutos
     * 
     * @param int $minutes Minutos a buscar (padrão: 5)
     */
    public static function process_pending_events($minutes = 5) {
        global $wpdb;
        
        $table_logs = $wpdb->prefix . 'zap_event_logs';
        
        // Verificar se a tabela existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_logs}'") != $table_logs) {
            error_log('[ZAP WhatsApp] Tabela zap_event_logs não encontrada');
            return;
        }
        
        // Buscar eventos recentes que ainda não foram processados
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT id, event_key, user_id, context, created_at 
            FROM {$table_logs} 
            WHERE created_at > DATE_SUB(NOW(), INTERVAL %d MINUTE)
            ORDER BY created_at DESC",
            $minutes
        ));
        
        if (empty($events)) {
            return;
        }
        
        error_log('[ZAP WhatsApp] Processando ' . count($events) . ' eventos da tabela de logs');
        
        foreach ($events as $event) {
            // Verificar se já foi processado
            if (self::was_processed($event->id)) {
                continue;
            }
            
            // Construir payload
            $payload = [
                'event'   => $event->event_key,
                'user_id' => $event->user_id,
                'context' => json_decode($event->context, true) ?? [],
            ];
            
            // Processar via Listener
            Listener::handle($payload);
            
            // Marcar como processado
            self::mark_as_processed($event->id);
        }
    }
    
    /**
     * Verificar se evento já foi processado
     * 
     * @param int $event_id ID do evento
     * @return bool
     */
    private static function was_processed($event_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_processed_events';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE event_id = %d",
            $event_id
        ));
        
        return $exists > 0;
    }
    
    /**
     * Marcar evento como processado
     * 
     * @param int $event_id ID do evento
     */
    private static function mark_as_processed($event_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_processed_events';
        
        $wpdb->insert(
            $table,
            [
                'event_id'     => $event_id,
                'processed_at' => current_time('mysql'),
            ],
            ['%d', '%s']
        );
    }
    
    /**
     * Criar tabela de controle
     */
    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_processed_events';
        $charset = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id BIGINT UNSIGNED NOT NULL,
            processed_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY event_id (event_id),
            KEY processed_at (processed_at)
        ) {$charset};";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
