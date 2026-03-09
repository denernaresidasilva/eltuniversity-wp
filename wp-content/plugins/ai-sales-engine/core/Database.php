<?php
/**
 * Thin wrapper around $wpdb with helper methods.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Database {

    public static function init(): void {
        // Intentionally empty – all methods use global $wpdb directly.
    }

    /**
     * Return the $wpdb instance.
     */
    public static function wpdb(): \wpdb {
        global $wpdb;
        return $wpdb;
    }

    /**
     * Return a prefixed table name.
     */
    public static function table( string $name ): string {
        global $wpdb;
        return $wpdb->prefix . $name;
    }

    /**
     * Insert a row and return the new ID (or false on error).
     *
     * @param string $table  Unprefixed table name.
     * @param array  $data   Associative array of column => value.
     * @return int|false
     */
    public static function insert( string $table, array $data ) {
        global $wpdb;
        $result = $wpdb->insert( $wpdb->prefix . $table, $data );
        if ( false === $result ) {
            return false;
        }
        return (int) $wpdb->insert_id;
    }

    /**
     * Update rows matching $where and return affected row count (or false).
     *
     * @param string $table  Unprefixed table name.
     * @param array  $data   Columns to update.
     * @param array  $where  WHERE conditions.
     * @return int|false
     */
    public static function update( string $table, array $data, array $where ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . $table, $data, $where );
    }

    /**
     * Delete rows matching $where.
     *
     * @param string $table  Unprefixed table name.
     * @param array  $where  WHERE conditions.
     * @return int|false
     */
    public static function delete( string $table, array $where ) {
        global $wpdb;
        return $wpdb->delete( $wpdb->prefix . $table, $where );
    }

    /**
     * Return a single row as an associative array or null.
     *
     * @param string $sql  Prepared SQL string.
     * @return array<string,mixed>|null
     */
    public static function get_row( string $sql ): ?array {
        global $wpdb;
        $row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $row ?: null;
    }

    /**
     * Return multiple rows as an array of associative arrays.
     *
     * @param string $sql  Prepared SQL string.
     * @return array<int,array<string,mixed>>
     */
    public static function get_results( string $sql ): array {
        global $wpdb;
        return $wpdb->get_results( $sql, ARRAY_A ) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    /**
     * Return a single column value or null.
     *
     * @param string $sql Prepared SQL string.
     */
    public static function get_var( string $sql ): mixed {
        global $wpdb;
        return $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }
}
