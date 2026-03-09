<?php
namespace LeadsSaaS\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Automacao {

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_automacoes';
    }

    public static function all(): array {
        global $wpdb;
        $table = esc_sql( self::table() );
        return $wpdb->get_results( "SELECT * FROM `$table` ORDER BY created_at DESC", ARRAY_A ) ?: [];
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        $table = self::table();
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A );
        if ( ! $row ) return null;
        $row['acoes_json'] = $row['acoes_json'] ? json_decode( $row['acoes_json'], true ) : [];
        return $row;
    }

    public static function find_by_trigger( string $trigger ): array {
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM $table WHERE trigger_key = %s AND ativo = 1", $trigger ),
            ARRAY_A
        ) ?: [];
        foreach ( $rows as &$row ) {
            $row['acoes_json'] = $row['acoes_json'] ? json_decode( $row['acoes_json'], true ) : [];
        }
        return $rows;
    }

    public static function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'nome'        => sanitize_text_field( $data['nome'] ?? '' ),
            'trigger_key' => sanitize_key( $data['trigger'] ?? '' ),
            'acoes_json'  => isset( $data['acoes'] ) ? wp_json_encode( $data['acoes'] ) : '[]',
            'ativo'       => isset( $data['ativo'] ) ? (int) $data['ativo'] : 1,
            'created_at'  => current_time( 'mysql' ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $fields = [];
        if ( isset( $data['nome'] ) )    $fields['nome']        = sanitize_text_field( $data['nome'] );
        if ( isset( $data['trigger'] ) ) $fields['trigger_key'] = sanitize_key( $data['trigger'] );
        if ( isset( $data['acoes'] ) )   $fields['acoes_json']  = wp_json_encode( $data['acoes'] );
        if ( isset( $data['ativo'] ) )   $fields['ativo']       = (int) $data['ativo'];
        if ( empty( $fields ) ) return false;
        return (bool) $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), [ 'id' => $id ] );
    }
}
