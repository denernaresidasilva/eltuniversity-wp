<?php
namespace LeadsSaaS\Integrations\Elementor;

use LeadsSaaS\Models\Lista;
use LeadsSaaS\Public_\FormShortcode;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LeadFormWidget extends \Elementor\Widget_Base {

    public function get_name(): string {
        return 'leads_saas_form';
    }

    public function get_title(): string {
        return 'Formulário de Leads';
    }

    public function get_icon(): string {
        return 'eicon-form-horizontal';
    }

    public function get_categories(): array {
        return [ 'general' ];
    }

    protected function register_controls(): void {
        $this->start_controls_section( 'section_content', [
            'label' => 'Configurações',
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ] );

        $listas = Lista::all();
        $options = [ '' => '— Selecione uma lista —' ];
        foreach ( $listas as $lista ) {
            $options[ $lista['id'] ] = esc_html( $lista['nome'] );
        }

        $this->add_control( 'lista_id', [
            'label'   => 'Lista',
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $options,
            'default' => '',
        ] );

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $lista_id = (int) ( $settings['lista_id'] ?? 0 );
        if ( ! $lista_id ) {
            echo '<p>Selecione uma lista nas configurações do widget.</p>';
            return;
        }
        echo do_shortcode( '[wplm_form id="' . esc_attr( $lista_id ) . '"]' );
    }
}
