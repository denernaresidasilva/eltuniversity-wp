<?php
/**
 * Server-side event tracker called from REST or public pages.
 *
 * @package AISalesEngine\Core
 */

namespace AISalesEngine\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

class Tracker {

    public static function init(): void {
        // Nothing to hook here; tracking is driven by REST API and PublicTracker.
    }

    /**
     * Identify or upsert a lead by email, then dispatch an event.
     *
     * @param string $email      Lead e-mail (used for identity resolution).
     * @param string $event_name Event name.
     * @param array  $data       Additional data (name, utm_*, metadata, etc.).
     */
    public static function track( string $email, string $event_name, array $data = [] ): void {
        $email = sanitize_email( $email );

        if ( ! $email ) {
            // Anonymous tracking – just dispatch without a lead_id.
            EventDispatcher::dispatch( $event_name, 0, $data['event_value'] ?? '', $data );
            return;
        }

        // Resolve or create lead.
        $lead = \AISalesEngine\Modules\Leads\LeadManager::get_by_email( $email );

        if ( ! $lead ) {
            $lead_id = \AISalesEngine\Modules\Leads\LeadManager::create( array_merge(
                [ 'email' => $email ],
                array_intersect_key( $data, array_flip( [ 'name', 'phone', 'whatsapp', 'instagram', 'source',
                    'utm_source', 'utm_medium', 'utm_campaign' ] ) )
            ) );
        } else {
            $lead_id = (int) $lead['id'];
        }

        if ( $lead_id ) {
            EventDispatcher::dispatch(
                $event_name,
                $lead_id,
                $data['event_value'] ?? '',
                $data
            );
        }
    }
}
