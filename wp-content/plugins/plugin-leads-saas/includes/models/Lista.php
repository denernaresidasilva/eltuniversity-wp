<?php
namespace LeadsSaaS\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lista {

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_listas';
    }

    public static function all( array $args = [] ): array {
        global $wpdb;
        $limit  = isset( $args['per_page'] ) ? (int) $args['per_page'] : 100;
        $offset = isset( $args['page'] ) ? ( (int) $args['page'] - 1 ) * $limit : 0;
        $table  = esc_sql( self::table() );
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM `$table` ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset ),
            ARRAY_A
        ) ?: [];
    }

    public static function count(): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table`" );
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id ),
            ARRAY_A
        ) ?: null;
    }

    public static function find_by_webhook_key( string $key ): ?array {
        global $wpdb;
        $table = self::table();
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM $table WHERE webhook_key = %s", $key ),
            ARRAY_A
        ) ?: null;
    }

    public static function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'nome'             => sanitize_text_field( $data['nome'] ?? '' ),
            'descricao'        => sanitize_textarea_field( $data['descricao'] ?? '' ),
            'webhook_key'      => wp_generate_password( 32, false ),
            'form_schema_json' => isset( $data['form_schema_json'] ) ? wp_json_encode( $data['form_schema_json'] ) : null,
            'created_at'       => current_time( 'mysql' ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $fields = [];
        if ( isset( $data['nome'] ) )             $fields['nome']             = sanitize_text_field( $data['nome'] );
        if ( isset( $data['descricao'] ) )         $fields['descricao']         = sanitize_textarea_field( $data['descricao'] );
        if ( isset( $data['form_schema_json'] ) )  $fields['form_schema_json']  = wp_json_encode( $data['form_schema_json'] );
        if ( empty( $fields ) ) return false;
        return (bool) $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), [ 'id' => $id ] );
    }
}
