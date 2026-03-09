<?php
/**
 * Settings admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Settings {

    public static function render(): void {
        if ( isset( $_POST['aise_settings_nonce'] )
             && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aise_settings_nonce'] ) ), 'aise_save_settings' ) ) {
            self::save();
        }

        $settings = get_option( 'ai_sales_engine_settings', [] );
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php esc_html_e( 'Configurações', 'ai-sales-engine' ); ?>
                </h1>
            </div>

            <form method="post" class="aise-settings-form">
                <?php wp_nonce_field( 'aise_save_settings', 'aise_settings_nonce' ); ?>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'Chaves de API', 'ai-sales-engine' ); ?></h2>
                    <div class="aise-form-row">
                        <label for="openai_key"><?php esc_html_e( 'Chave de API OpenAI', 'ai-sales-engine' ); ?></label>
                        <input type="password" id="openai_key" name="openai_key"
                               value="<?php echo esc_attr( $settings['openai_key'] ?? '' ); ?>"
                               class="aise-input" autocomplete="off" />
                    </div>
                    <div class="aise-form-row">
                        <label for="whatsapp_token"><?php esc_html_e( 'Token da API Cloud WhatsApp', 'ai-sales-engine' ); ?></label>
                        <input type="password" id="whatsapp_token" name="whatsapp_token"
                               value="<?php echo esc_attr( $settings['whatsapp_token'] ?? '' ); ?>"
                               class="aise-input" autocomplete="off" />
                    </div>
                    <div class="aise-form-row">
                        <label for="whatsapp_phone_id"><?php esc_html_e( 'ID do Telefone WhatsApp', 'ai-sales-engine' ); ?></label>
                        <input type="text" id="whatsapp_phone_id" name="whatsapp_phone_id"
                               value="<?php echo esc_attr( $settings['whatsapp_phone_id'] ?? '' ); ?>"
                               class="aise-input" />
                    </div>
                    <div class="aise-form-row">
                        <label for="instagram_token"><?php esc_html_e( 'Token da API Graph Instagram', 'ai-sales-engine' ); ?></label>
                        <input type="password" id="instagram_token" name="instagram_token"
                               value="<?php echo esc_attr( $settings['instagram_token'] ?? '' ); ?>"
                               class="aise-input" autocomplete="off" />
                    </div>
                </div>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'Rastreamento', 'ai-sales-engine' ); ?></h2>
                    <div class="aise-form-row">
                        <label><?php esc_html_e( 'Rastreador Front-end', 'ai-sales-engine' ); ?></label>
                        <label class="aise-checkbox-label">
                            <input type="checkbox" name="tracker_enabled" value="1"
                                   <?php checked( ! empty( $settings['tracker_enabled'] ) ); ?> />
                            <span><?php esc_html_e( 'Habilitar o ai-tracker.js em todas as páginas públicas', 'ai-sales-engine' ); ?></span>
                        </label>
                    </div>
                </div>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'Regras de Pontuação', 'ai-sales-engine' ); ?></h2>
                    <?php
                    $scoring = $settings['scoring_rules'] ?? [];
                    $events  = [
                        'page_visit'         => __( 'Visita à Página',     'ai-sales-engine' ),
                        'message_reply'      => __( 'Resposta de Mensagem', 'ai-sales-engine' ),
                        'webinar_completed'  => __( 'Webinar Concluído',    'ai-sales-engine' ),
                        'purchase_completed' => __( 'Compra Concluída',     'ai-sales-engine' ),
                    ];
                    foreach ( $events as $key => $label ) :
                        $value = isset( $scoring[ $key ] ) ? (int) $scoring[ $key ] : 0;
                        ?>
                        <div class="aise-form-row aise-form-row-inline">
                            <label for="score_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
                            <div class="aise-score-input">
                                <input type="number" id="score_<?php echo esc_attr( $key ); ?>"
                                       name="scoring_rules[<?php echo esc_attr( $key ); ?>]"
                                       value="<?php echo esc_attr( $value ); ?>"
                                       class="aise-input aise-input-sm" min="0" step="1" />
                                <span class="aise-unit"><?php esc_html_e( 'pontos', 'ai-sales-engine' ); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="aise-settings-actions">
                    <button type="submit" class="aise-btn aise-btn-primary aise-btn-lg">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e( 'Salvar Configurações', 'ai-sales-engine' ); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }

    private static function save(): void {
        $settings = get_option( 'ai_sales_engine_settings', [] );

        $settings['openai_key']        = sanitize_text_field( wp_unslash( $_POST['openai_key']        ?? '' ) );
        $settings['whatsapp_token']    = sanitize_text_field( wp_unslash( $_POST['whatsapp_token']    ?? '' ) );
        $settings['whatsapp_phone_id'] = sanitize_text_field( wp_unslash( $_POST['whatsapp_phone_id'] ?? '' ) );
        $settings['instagram_token']   = sanitize_text_field( wp_unslash( $_POST['instagram_token']   ?? '' ) );
        $settings['tracker_enabled']   = isset( $_POST['tracker_enabled'] ) ? 1 : 0;

        $scoring_raw   = (array) ( $_POST['scoring_rules'] ?? [] );
        $scoring_clean = [];
        foreach ( $scoring_raw as $key => $value ) {
            $scoring_clean[ sanitize_key( $key ) ] = absint( $value );
        }
        $settings['scoring_rules'] = $scoring_clean;

        update_option( 'ai_sales_engine_settings', $settings );

        add_settings_error(
            'ai_sales_engine',
            'saved',
            __( 'Configurações salvas.', 'ai-sales-engine' ),
            'updated'
        );

        settings_errors( 'ai_sales_engine' );
    }
}
