<?php
namespace WebinarPlataforma\Public_;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class GeradorPaginas {

    public static function init(): void {
        // Nothing needed on init for now
    }

    /**
     * Create webinar pages (webinar page + registration page) for a given webinar ID.
     */
    public static function criar_paginas( int $webinar_id ): void {
        global $wpdb;

        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d",
            $webinar_id
        ) );

        if ( ! $webinar ) {
            return;
        }

        $slug_base = sanitize_title( $webinar->nome );

        // Webinar page
        if ( ! $webinar->pagina_webinar_id ) {
            $pagina_id = wp_insert_post( [
                'post_title'   => esc_html( $webinar->nome ),
                'post_name'    => 'webinar/' . $slug_base,
                'post_content' => '[webinar_player id="' . $webinar_id . '"]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [ '_wp_webinar_id' => $webinar_id ],
            ] );

            if ( $pagina_id && ! is_wp_error( $pagina_id ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'webinars',
                    [ 'pagina_webinar_id' => $pagina_id ],
                    [ 'id' => $webinar_id ],
                    [ '%d' ],
                    [ '%d' ]
                );
            }
        }

        // Registration page
        if ( ! $webinar->pagina_inscricao_id ) {
            $inscricao_id = wp_insert_post( [
                'post_title'   => 'Inscrição – ' . esc_html( $webinar->nome ),
                'post_name'    => 'webinar/' . $slug_base . '/inscricao',
                'post_content' => '[webinar_inscricao id="' . $webinar_id . '"]',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'meta_input'   => [ '_wp_webinar_inscricao_id' => $webinar_id ],
            ] );

            if ( $inscricao_id && ! is_wp_error( $inscricao_id ) ) {
                $wpdb->update(
                    $wpdb->prefix . 'webinars',
                    [ 'pagina_inscricao_id' => $inscricao_id ],
                    [ 'id' => $webinar_id ],
                    [ '%d' ],
                    [ '%d' ]
                );
            }
        }
    }
}
