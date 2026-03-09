<?php
namespace LeadsSaaS\Public_;

use LeadsSaaS\Models\Lista;
use LeadsSaaS\Services\LeadService;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FormShortcode {

    public static function init(): void {
        add_shortcode( 'wplm_form', [ self::class, 'render' ] );

        // REST endpoint for public form submission (no auth required)
        add_action( 'rest_api_init', function() {
            register_rest_route( 'leads/v1', '/form/submit', [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle_submit' ],
                'permission_callback' => '__return_true',
            ] );
        } );

        // Enqueue public assets
        add_action( 'wp_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
    }

    public static function enqueue_assets(): void {
        if ( ! is_singular() ) return;

        global $post;
        if ( ! $post || ! has_shortcode( $post->post_content, 'wplm_form' ) ) return;

        wp_enqueue_style(
            'leads-saas-public',
            LEADS_SAAS_URL . 'assets/css/public.css',
            [],
            LEADS_SAAS_VERSION
        );

        wp_enqueue_script(
            'leads-saas-public',
            LEADS_SAAS_URL . 'assets/js/public-form.js',
            [],
            LEADS_SAAS_VERSION,
            true
        );

        wp_localize_script( 'leads-saas-public', 'LeadsSaaSPublic', [
            'apiUrl' => esc_url_raw( rest_url( 'leads/v1/form/submit' ) ),
            'nonce'  => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    public static function render( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $id   = (int) $atts['id'];
        if ( ! $id ) {
            return '<p class="wplm-error">ID da lista não informado.</p>';
        }

        $lista = Lista::find( $id );
        if ( ! $lista ) {
            return '<p class="wplm-error">Lista não encontrada.</p>';
        }

        $schema = $lista['form_schema_json'] ? json_decode( $lista['form_schema_json'], true ) : [];
        $fields = $schema['fields'] ?? self::default_fields();

        ob_start();
        ?>
        <div class="wplm-form-wrap" id="wplm-form-<?php echo esc_attr( $id ); ?>">
            <form class="wplm-form" data-lista-id="<?php echo esc_attr( $id ); ?>" novalidate>
                <?php wp_nonce_field( 'wplm_form_submit', 'wplm_nonce' ); ?>
                <?php foreach ( $fields as $field ) : ?>
                    <?php echo self::render_field( $field ); ?>
                <?php endforeach; ?>
                <div class="wplm-field">
                    <button type="submit" class="wplm-btn-submit">Enviar</button>
                </div>
                <div class="wplm-messages" aria-live="polite"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_field( array $field ): string {
        $type     = esc_attr( $field['type']     ?? 'text' );
        $label    = esc_html( $field['label']    ?? '' );
        $name     = esc_attr( $field['name']     ?? '' );
        $required = ! empty( $field['required'] );
        $req_attr = $required ? 'required' : '';
        $req_mark = $required ? '<span class="wplm-req">*</span>' : '';
        $id       = 'wplm_' . $name;

        $html = '<div class="wplm-field">';
        if ( $type !== 'hidden' ) {
            $html .= "<label for=\"$id\">$label$req_mark</label>";
        }

        switch ( $type ) {
            case 'textarea':
                $html .= "<textarea id=\"$id\" name=\"$name\" $req_attr rows=\"4\"></textarea>";
                break;
            case 'select':
                $options_html = '';
                foreach ( $field['options'] ?? [] as $opt ) {
                    $options_html .= '<option value="' . esc_attr( $opt ) . '">' . esc_html( $opt ) . '</option>';
                }
                $html .= "<select id=\"$id\" name=\"$name\" $req_attr>$options_html</select>";
                break;
            case 'checkbox':
                $html = '<div class="wplm-field wplm-field--checkbox">';
                $html .= "<input type=\"checkbox\" id=\"$id\" name=\"$name\" value=\"1\" $req_attr>";
                $html .= "<label for=\"$id\">$label$req_mark</label>";
                break;
            case 'hidden':
                $html .= "<input type=\"hidden\" name=\"$name\" value=\"" . esc_attr( $field['value'] ?? '' ) . "\">";
                break;
            default:
                $html .= "<input type=\"$type\" id=\"$id\" name=\"$name\" $req_attr placeholder=\"" . esc_attr( $field['placeholder'] ?? '' ) . "\">";
                break;
        }

        $html .= '</div>';
        return $html;
    }

    private static function default_fields(): array {
        return [
            [ 'type' => 'text',  'label' => 'Nome',      'name' => 'nome',     'required' => true  ],
            [ 'type' => 'email', 'label' => 'E-mail',    'name' => 'email',    'required' => true  ],
            [ 'type' => 'tel',   'label' => 'Telefone',  'name' => 'telefone', 'required' => false ],
        ];
    }

    public static function handle_submit( \WP_REST_Request $request ): \WP_REST_Response {
        // Validate nonce
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_REST_Response( [ 'message' => 'Requisição inválida.' ], 403 );
        }

        $body     = $request->get_json_params();
        $lista_id = (int) ( $body['lista_id'] ?? 0 );
        $email    = sanitize_email( $body['email'] ?? '' );

        if ( ! $lista_id || ! $email ) {
            return new \WP_REST_Response( [ 'message' => 'Dados incompletos.' ], 422 );
        }

        $lista = Lista::find( $lista_id );
        if ( ! $lista ) {
            return new \WP_REST_Response( [ 'message' => 'Lista não encontrada.' ], 404 );
        }

        $campos = $body;
        unset( $campos['lista_id'], $campos['email'], $campos['nome'], $campos['telefone'] );

        $id = LeadService::create_from_data( [
            'lista_id'    => $lista_id,
            'nome'        => sanitize_text_field( $body['nome'] ?? '' ),
            'email'       => $email,
            'telefone'    => sanitize_text_field( $body['telefone'] ?? '' ),
            'campos_json' => $campos,
            'origem'      => 'formulario',
        ], 'formulario' );

        return new \WP_REST_Response( [
            'success' => true,
            'message' => 'Obrigado! Seu cadastro foi realizado com sucesso.',
            'lead_id' => $id,
        ], 201 );
    }
}
