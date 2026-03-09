<?php
namespace LeadsSaaS\Models;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lead {

    private static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'leads';
    }

    private static function tags_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_tags';
    }

    private static function relations_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'lead_tag_relations';
    }

    public static function count( int $lista_id = 0 ): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        if ( $lista_id > 0 ) {
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE lista_id = %d", $lista_id ) );
        }
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$table`" );
    }

    public static function count_today(): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        $today = current_time( 'Y-m-d' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE DATE(created_at) = %s", $today ) );
    }

    public static function count_this_month(): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        $year  = (int) current_time( 'Y' );
        $month = (int) current_time( 'm' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d", $year, $month ) );
    }

    public static function count_last_month(): int {
        global $wpdb;
        $table = esc_sql( self::table() );
        $date  = new \DateTime( current_time( 'mysql' ) );
        $date->modify( '-1 month' );
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `$table` WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d", (int) $date->format( 'Y' ), (int) $date->format( 'm' ) ) );
    }

    public static function per_list(): array {
        global $wpdb;
        $leads_table = esc_sql( self::table() );
        $lists_table = esc_sql( $wpdb->prefix . 'lead_listas' );
        return $wpdb->get_results(
            "SELECT l.id, l.nome, COUNT(ld.id) AS total FROM `$lists_table` l LEFT JOIN `$leads_table` ld ON ld.lista_id = l.id GROUP BY l.id ORDER BY total DESC",
            ARRAY_A
        ) ?: [];
    }

    public static function recent( int $limit = 5 ): array {
        global $wpdb;
        $table      = esc_sql( self::table() );
        $list_table = esc_sql( $wpdb->prefix . 'lead_listas' );
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT ld.*, l.nome AS lista_nome FROM `$table` ld LEFT JOIN `$list_table` l ON l.id = ld.lista_id ORDER BY ld.created_at DESC LIMIT %d", $limit ),
            ARRAY_A
        ) ?: [];
    }

    public static function all( array $args = [] ): array {
        global $wpdb;
        $table      = esc_sql( self::table() );
        $list_table = esc_sql( $wpdb->prefix . 'lead_listas' );

        $limit   = isset( $args['per_page'] ) ? (int) $args['per_page'] : 20;
        $offset  = isset( $args['page'] )     ? ( (int) $args['page'] - 1 ) * $limit : 0;
        $where   = '1=1';
        $params  = [];

        if ( ! empty( $args['lista_id'] ) ) {
            $where   .= ' AND ld.lista_id = %d';
            $params[] = (int) $args['lista_id'];
        }

        if ( ! empty( $args['search'] ) ) {
            $like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where   .= ' AND (ld.nome LIKE %s OR ld.email LIKE %s)';
            $params[] = $like;
            $params[] = $like;
        }

        $order_col = in_array( $args['orderby'] ?? '', [ 'nome', 'email', 'created_at' ], true )
            ? $args['orderby'] : 'created_at';
        $order_dir = strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC';

        $params[] = $limit;
        $params[] = $offset;

        $sql = "SELECT ld.*, l.nome AS lista_nome FROM `$table` ld LEFT JOIN `$list_table` l ON l.id = ld.lista_id WHERE $where ORDER BY ld.$order_col $order_dir LIMIT %d OFFSET %d";

        if ( $params ) {
            return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ) ?: [];
        }
        return $wpdb->get_results( $sql, ARRAY_A ) ?: [];
    }

    public static function find( int $id ): ?array {
        global $wpdb;
        $table      = esc_sql( self::table() );
        $list_table = esc_sql( $wpdb->prefix . 'lead_listas' );
        $lead = $wpdb->get_row(
            $wpdb->prepare( "SELECT ld.*, l.nome AS lista_nome FROM `$table` ld LEFT JOIN `$list_table` l ON l.id = ld.lista_id WHERE ld.id = %d", $id ),
            ARRAY_A
        );
        if ( ! $lead ) return null;
        $lead['tags']       = self::get_tags( $id );
        $lead['campos_json'] = $lead['campos_json'] ? json_decode( $lead['campos_json'], true ) : [];
        return $lead;
    }

    public static function create( array $data ): int {
        global $wpdb;
        $wpdb->insert( self::table(), [
            'lista_id'    => (int) ( $data['lista_id'] ?? 0 ),
            'nome'        => sanitize_text_field( $data['nome'] ?? '' ),
            'email'       => sanitize_email( $data['email'] ?? '' ),
            'telefone'    => sanitize_text_field( $data['telefone'] ?? '' ),
            'campos_json' => isset( $data['campos_json'] ) ? wp_json_encode( $data['campos_json'] ) : null,
            'origem'      => sanitize_text_field( $data['origem'] ?? 'manual' ),
            'created_at'  => current_time( 'mysql' ),
        ] );
        return (int) $wpdb->insert_id;
    }

    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $fields = [];
        if ( isset( $data['nome'] ) )         $fields['nome']         = sanitize_text_field( $data['nome'] );
        if ( isset( $data['email'] ) )        $fields['email']        = sanitize_email( $data['email'] );
        if ( isset( $data['telefone'] ) )     $fields['telefone']     = sanitize_text_field( $data['telefone'] );
        if ( isset( $data['lista_id'] ) )     $fields['lista_id']     = (int) $data['lista_id'];
        if ( isset( $data['campos_json'] ) )  $fields['campos_json']  = wp_json_encode( $data['campos_json'] );
        if ( empty( $fields ) ) return false;
        return (bool) $wpdb->update( self::table(), $fields, [ 'id' => $id ] );
    }

    public static function delete( int $id ): bool {
        global $wpdb;
        $wpdb->delete( self::relations_table(), [ 'lead_id' => $id ] );
        return (bool) $wpdb->delete( self::table(), [ 'id' => $id ] );
    }

    public static function get_tags( int $lead_id ): array {
        global $wpdb;
        $rel   = self::relations_table();
        $tags  = self::tags_table();
        return $wpdb->get_results(
            $wpdb->prepare( "SELECT t.* FROM $tags t INNER JOIN $rel r ON r.tag_id = t.id WHERE r.lead_id = %d", $lead_id ),
            ARRAY_A
        ) ?: [];
    }

    public static function add_tag( int $lead_id, int $tag_id ): void {
        global $wpdb;
        $wpdb->replace( self::relations_table(), [
            'lead_id' => $lead_id,
            'tag_id'  => $tag_id,
        ] );
    }

    public static function remove_tag( int $lead_id, int $tag_id ): void {
        global $wpdb;
        $wpdb->delete( self::relations_table(), [
            'lead_id' => $lead_id,
            'tag_id'  => $tag_id,
        ] );
    }

    public static function count_by_tag( int $tag_id ): int {
        global $wpdb;
        $rel = self::relations_table();
        return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $rel WHERE tag_id = %d", $tag_id ) );
    }
}
