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
            <h1 class="aise-page-title">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e( 'Settings', 'ai-sales-engine' ); ?>
            </h1>

            <form method="post" class="aise-settings-form">
                <?php wp_nonce_field( 'aise_save_settings', 'aise_settings_nonce' ); ?>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'API Keys', 'ai-sales-engine' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><label for="openai_key"><?php esc_html_e( 'OpenAI API Key', 'ai-sales-engine' ); ?></label></th>
                            <td>
                                <input type="password" id="openai_key" name="openai_key"
                                       value="<?php echo esc_attr( $settings['openai_key'] ?? '' ); ?>"
                                       class="regular-text" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whatsapp_token"><?php esc_html_e( 'WhatsApp Cloud API Token', 'ai-sales-engine' ); ?></label></th>
                            <td>
                                <input type="password" id="whatsapp_token" name="whatsapp_token"
                                       value="<?php echo esc_attr( $settings['whatsapp_token'] ?? '' ); ?>"
                                       class="regular-text" autocomplete="off" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="whatsapp_phone_id"><?php esc_html_e( 'WhatsApp Phone ID', 'ai-sales-engine' ); ?></label></th>
                            <td>
                                <input type="text" id="whatsapp_phone_id" name="whatsapp_phone_id"
                                       value="<?php echo esc_attr( $settings['whatsapp_phone_id'] ?? '' ); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>
                        <tr>
                            <th><label for="instagram_token"><?php esc_html_e( 'Instagram Graph API Token', 'ai-sales-engine' ); ?></label></th>
                            <td>
                                <input type="password" id="instagram_token" name="instagram_token"
                                       value="<?php echo esc_attr( $settings['instagram_token'] ?? '' ); ?>"
                                       class="regular-text" autocomplete="off" />
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'Tracking', 'ai-sales-engine' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e( 'Front-end Tracker', 'ai-sales-engine' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="tracker_enabled" value="1"
                                           <?php checked( ! empty( $settings['tracker_enabled'] ) ); ?> />
                                    <?php esc_html_e( 'Enable the ai-tracker.js on all public pages', 'ai-sales-engine' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="aise-card">
                    <h2><?php esc_html_e( 'Lead Scoring Rules', 'ai-sales-engine' ); ?></h2>
                    <table class="form-table">
                        <?php
                        $scoring = $settings['scoring_rules'] ?? [];
                        $events  = [
                            'page_visit'         => __( 'Page Visit',          'ai-sales-engine' ),
                            'message_reply'      => __( 'Message Reply',       'ai-sales-engine' ),
                            'webinar_completed'  => __( 'Webinar Completed',   'ai-sales-engine' ),
                            'purchase_completed' => __( 'Purchase Completed',  'ai-sales-engine' ),
                        ];
                        foreach ( $events as $key => $label ) :
                            $value = isset( $scoring[ $key ] ) ? (int) $scoring[ $key ] : 0;
                            ?>
                            <tr>
                                <th><label for="score_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
                                <td>
                                    <input type="number" id="score_<?php echo esc_attr( $key ); ?>"
                                           name="scoring_rules[<?php echo esc_attr( $key ); ?>]"
                                           value="<?php echo esc_attr( $value ); ?>"
                                           class="small-text" min="0" step="1" />
                                    <?php esc_html_e( 'points', 'ai-sales-engine' ); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>

                <?php submit_button( __( 'Save Settings', 'ai-sales-engine' ) ); ?>
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
            __( 'Settings saved.', 'ai-sales-engine' ),
            'updated'
        );

        settings_errors( 'ai_sales_engine' );
    }
}
