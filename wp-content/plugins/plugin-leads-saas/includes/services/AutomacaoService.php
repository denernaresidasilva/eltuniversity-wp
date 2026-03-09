<?php
namespace LeadsSaaS\Services;

use LeadsSaaS\Models\Automacao;
use LeadsSaaS\Models\Lead;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AutomacaoService {

    public static function fire( string $trigger, array $context = [] ): void {
        $automacoes = Automacao::find_by_trigger( $trigger );
        foreach ( $automacoes as $automacao ) {
            self::run_acoes( $automacao['acoes_json'] ?? [], $context );
        }
    }

    private static function run_acoes( array $acoes, array $context ): void {
        foreach ( $acoes as $acao ) {
            $tipo = $acao['tipo'] ?? '';
            switch ( $tipo ) {
                case 'add_tag':
                    if ( ! empty( $context['lead_id'] ) && ! empty( $acao['tag_id'] ) ) {
                        Lead::add_tag( (int) $context['lead_id'], (int) $acao['tag_id'] );
                    }
                    break;

                case 'move_list':
                    if ( ! empty( $context['lead_id'] ) && ! empty( $acao['lista_id'] ) ) {
                        Lead::update( (int) $context['lead_id'], [ 'lista_id' => (int) $acao['lista_id'] ] );
                    }
                    break;

                case 'send_webhook':
                    if ( ! empty( $acao['url'] ) && ! empty( $context['lead_id'] ) ) {
                        $lead = Lead::find( (int) $context['lead_id'] );
                        if ( $lead ) {
                            WebhookService::send( $acao['url'], $lead );
                        }
                    }
                    break;

                default:
                    do_action( 'leads_saas_automacao_acao', $tipo, $acao, $context );
                    break;
            }
        }
    }
}
