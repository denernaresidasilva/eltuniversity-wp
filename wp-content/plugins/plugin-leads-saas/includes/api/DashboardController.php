<?php
namespace LeadsSaaS\Api;

use LeadsSaaS\Models\Lead;
use LeadsSaaS\Models\Lista;
use LeadsSaaS\Models\Tag;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DashboardController {

    public static function register_routes(): void {
        register_rest_route( Routes::NAMESPACE, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [ self::class, 'metrics' ],
            'permission_callback' => [ Routes::class, 'auth_callback' ],
        ] );
    }

    public static function metrics(): WP_REST_Response {
        $total_leads     = Lead::count();
        $leads_hoje      = Lead::count_today();
        $leads_mes       = Lead::count_this_month();
        $leads_mes_ant   = Lead::count_last_month();
        $taxa            = $leads_mes_ant > 0 ? round( ( ( $leads_mes - $leads_mes_ant ) / $leads_mes_ant ) * 100, 1 ) : 0;

        return new WP_REST_Response( [
            'total_leads'       => $total_leads,
            'total_listas'      => Lista::count(),
            'total_tags'        => Tag::count(),
            'leads_hoje'        => $leads_hoje,
            'leads_mes'         => $leads_mes,
            'taxa_crescimento'  => $taxa,
            'leads_por_lista'   => Lead::per_list(),
            'leads_recentes'    => Lead::recent( 8 ),
        ] );
    }
}
