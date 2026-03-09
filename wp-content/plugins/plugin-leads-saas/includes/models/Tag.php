<?php
namespace LeadsSaaS\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Tag {

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_tags';
    }

    public static function all(): array {
        global $wpdb;
        $table = esc_sql( self::table() );
        return $wpdb->get_results( "SELECT * FROM `$table` ORDER BY nome ASC", ARRAY_A ) ?: [];
    }

    public static function count(): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table`" );
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ), ARRAY_A ) ?: null;
    }

    public static function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'nome' => sanitize_text_field( $data['nome'] ?? '' ),
            'cor'  => sanitize_hex_color( $data['cor'] ?? '#6366f1' ) ?: '#6366f1',
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $fields = [];
        if ( isset( $data['nome'] ) ) $fields['nome'] = sanitize_text_field( $data['nome'] );
        if ( isset( $data['cor'] ) )  $fields['cor']  = sanitize_hex_color( $data['cor'] ) ?: '#6366f1';
        if ( empty( $fields ) ) return false;
        return (bool) $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'lead_tag_relations', [ 'tag_id' => $id ] );
        return (bool) $wpdb->delete( self::table(), [ 'id' => $id ] );
    }
}
