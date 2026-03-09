<?php
namespace LeadsSaaS\Services;

use LeadsSaaS\Models\Lead;
use LeadsSaaS\Models\Lista;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LeadService {

    public static function create_from_data( array $data, string $origem = 'manual' ): int {
        $data['origem'] = $origem;
        $id = Lead::create( $data );

        if ( $id > 0 ) {
            AutomacaoService::fire( 'lead_created', [ 'lead_id' => $id, 'lista_id' => (int) ( $data['lista_id'] ?? 0 ) ] );
            AutomacaoService::fire( 'lead_entered_list', [ 'lead_id' => $id, 'lista_id' => (int) ( $data['lista_id'] ?? 0 ) ] );
        }

        return $id;
    }

    public static function attach_tag( int $lead_id, int $tag_id ): void {
        Lead::add_tag( $lead_id, $tag_id );
        AutomacaoService::fire( 'tag_added', [ 'lead_id' => $lead_id, 'tag_id' => $tag_id ] );
    }

    public static function get_for_list( int $lista_id, array $args = [] ): array {
        $args['lista_id'] = $lista_id;
        return Lead::all( $args );
    }
}
