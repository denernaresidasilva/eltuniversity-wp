<?php
namespace SmartWebinar\Frontend;

if ( ! defined( 'ABSPATH' ) ) exit;

class Chat {

    public static function init(): void {
        // REST API handles chat; static init not needed.
    }

    public function render( int $webinar_id, string $session_id ): string {
        $user    = wp_get_current_user();
        $name    = $user->ID ? esc_attr( $user->display_name ) : '';
        ob_start();
        ?>
        <div class="sw-chat-container">
            <div class="sw-chat-header">
                <span class="dashicons dashicons-format-chat"></span>
                <?php esc_html_e( 'Chat ao Vivo', 'smart-webinar' ); ?>
                <span class="sw-chat-count" id="sw-chat-count-<?php echo absint( $webinar_id ); ?>">0 <?php esc_html_e( 'participantes', 'smart-webinar' ); ?></span>
            </div>
            <div class="sw-chat-messages" id="sw-chat-messages-<?php echo absint( $webinar_id ); ?>">
                <!-- Messages populated by JS -->
            </div>
            <div class="sw-chat-input-row">
                <?php if ( ! $user->ID ) : ?>
                <p class="sw-chat-login">
                    <a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>">
                        <?php esc_html_e( 'Faça login para participar do chat', 'smart-webinar' ); ?>
                    </a>
                </p>
                <?php else : ?>
                <input type="text" id="sw-chat-input-<?php echo absint( $webinar_id ); ?>"
                       class="sw-chat-input"
                       placeholder="<?php esc_attr_e( 'Digite sua mensagem…', 'smart-webinar' ); ?>"
                       maxlength="500"
                       data-webinar-id="<?php echo absint( $webinar_id ); ?>"
                       data-session-id="<?php echo esc_attr( $session_id ); ?>">
                <button type="button" class="sw-chat-send"
                        data-webinar-id="<?php echo absint( $webinar_id ); ?>">
                    <?php esc_html_e( 'Enviar', 'smart-webinar' ); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
