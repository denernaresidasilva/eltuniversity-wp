<?php
namespace WebinarPlataforma\Services;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Integration layer between wp-webinar-plataforma and plugin-leads-saas.
 * All methods degrade gracefully when the Lead SaaS plugin is not active.
 */
class LeadsSaaSIntegration {

    /** Check whether the Lead SaaS plugin classes are available. */
    public static function is_available(): bool {
        return class_exists( 'LeadsSaaS\\Models\\Lista' )
            && class_exists( 'LeadsSaaS\\Models\\Lead' )
            && class_exists( 'LeadsSaaS\\Models\\Tag' )
            && class_exists( 'LeadsSaaS\\Services\\LeadService' );
    }

    /**
     * Create a Lead SaaS list and return its ID.
     * Returns 0 if Lead SaaS is not available.
     *
     * @param string $nome  List name.
     * @return int  Created list ID, or 0 on failure.
     */
    public static function create_list( string $nome ): int {
        if ( ! self::is_available() ) {
            return 0;
        }
        return (int) \LeadsSaaS\Models\Lista::create( [ 'nome' => $nome ] );
    }

    /**
     * Delete a Lead SaaS list by ID.
     * Returns false if plugin not available or list not empty.
     *
     * @param int $lista_id
     * @return bool
     */
    public static function delete_list( int $lista_id ): bool {
        if ( ! self::is_available() || $lista_id <= 0 ) {
            return false;
        }
        $count = \LeadsSaaS\Models\Lead::count( $lista_id );
        if ( $count > 0 ) {
            return false;
        }
        return \LeadsSaaS\Models\Lista::delete( $lista_id );
    }

    /**
     * Count leads in a Lead SaaS list.
     *
     * @param int $lista_id
     * @return int
     */
    public static function count_leads_in_list( int $lista_id ): int {
        if ( ! self::is_available() || $lista_id <= 0 ) {
            return 0;
        }
        return \LeadsSaaS\Models\Lead::count( $lista_id );
    }

    /**
     * Create or update a Lead SaaS lead in the given list.
     * If a lead with the same email already exists in that list, returns existing ID.
     * Otherwise creates a new lead.
     *
     * @param int    $lista_id  Lead SaaS list ID.
     * @param string $email
     * @param string $nome
     * @param string $telefone
     * @return int  Lead ID, or 0 on failure / plugin not available.
     */
    public static function upsert_lead( int $lista_id, string $email, string $nome, string $telefone = '' ): int {
        if ( ! self::is_available() || $lista_id <= 0 || ! $email ) {
            return 0;
        }

        global $wpdb;
        $leads_table = $wpdb->prefix . 'leads';

        // Try to find existing lead in this list.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $existing_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM `{$leads_table}` WHERE lista_id = %d AND email = %s LIMIT 1",
            $lista_id,
            $email
        ) );

        if ( $existing_id > 0 ) {
            return $existing_id;
        }

        return \LeadsSaaS\Services\LeadService::create_from_data(
            [
                'lista_id' => $lista_id,
                'nome'     => $nome,
                'email'    => $email,
                'telefone' => $telefone,
            ],
            'webinar'
        );
    }

    /**
     * Move a lead from one Lead SaaS list to another.
     *
     * @param int $lead_id
     * @param int $dest_lista_id
     * @return bool
     */
    public static function move_lead_to_list( int $lead_id, int $dest_lista_id ): bool {
        if ( ! self::is_available() || $lead_id <= 0 || $dest_lista_id <= 0 ) {
            return false;
        }
        return \LeadsSaaS\Models\Lead::update( $lead_id, [ 'lista_id' => $dest_lista_id ] );
    }

    /**
     * Move ALL leads from one Lead SaaS list to another.
     *
     * @param int $from_lista_id
     * @param int $to_lista_id
     * @return int  Number of leads moved.
     */
    public static function move_all_leads( int $from_lista_id, int $to_lista_id ): int {
        if ( ! self::is_available() || $from_lista_id <= 0 || $to_lista_id <= 0 ) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'leads';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $affected = (int) $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$table}` SET lista_id = %d WHERE lista_id = %d",
                $to_lista_id,
                $from_lista_id
            )
        );

        return $affected;
    }

    /**
     * Find or create a tag by name in Lead SaaS, then attach it to a lead.
     *
     * @param int    $lead_id
     * @param string $tag_nome  Tag name (e.g. "viu_oferta", "nao_compareceu").
     */
    public static function attach_tag_by_name( int $lead_id, string $tag_nome ): void {
        if ( ! self::is_available() || $lead_id <= 0 || ! $tag_nome ) {
            return;
        }

        global $wpdb;
        $tags_table = $wpdb->prefix . 'lead_tags';

        // Find existing tag by slug/nome.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $tag_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM `{$tags_table}` WHERE nome = %s LIMIT 1",
            $tag_nome
        ) );

        // Create tag if not found.
        if ( ! $tag_id ) {
            $tag_id = (int) \LeadsSaaS\Models\Tag::create( [ 'nome' => $tag_nome ] );
        }

        if ( $tag_id > 0 ) {
            \LeadsSaaS\Services\LeadService::attach_tag( $lead_id, $tag_id );
        }
    }

    /**
     * Get all Lead SaaS lists (for UI dropdowns).
     *
     * @return array
     */
    public static function get_all_lists(): array {
        if ( ! self::is_available() ) {
            return [];
        }
        return \LeadsSaaS\Models\Lista::all( [ 'per_page' => 200 ] );
    }

    /**
     * Get all Lead SaaS tags (for UI dropdowns).
     *
     * @return array
     */
    public static function get_all_tags(): array {
        if ( ! self::is_available() ) {
            return [];
        }
        return \LeadsSaaS\Models\Tag::all();
    }
}
