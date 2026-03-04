<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class Messages {

    public static function render() {
        ?>
        <div class="wrap zapwa-page">
            <h1>Mensagens WhatsApp</h1>

            <p>
                As mensagens abaixo são registros do Post Type
                <strong>zapwa_message</strong>.
            </p>

            <a href="<?php echo admin_url('edit.php?post_type=zapwa_message'); ?>"
               class="button button-primary">
                Gerenciar Mensagens
            </a>
        </div>
        <?php
    }

    /**
     * Inject styled header + "Nova Mensagem" button on the post-list screen.
     * Hooked to admin_notices when screen is edit-zapwa_message.
     */
    public static function render_list_header() {
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'edit-zapwa_message' ) {
            return;
        }
        $new_url     = esc_url( admin_url( 'post-new.php?post_type=zapwa_message' ) );
        $metrics_url = esc_url( admin_url( 'admin.php?page=zap-wa-metrics' ) );
        ?>
        <div class="zapwa-messages-header">
            <div class="zapwa-messages-header__info">
                <span class="zapwa-messages-header__icon">💬</span>
                <div>
                    <h2 class="zapwa-messages-header__title"><?php esc_html_e( 'Mensagens WhatsApp', 'zap-whatsapp-automation' ); ?></h2>
                    <p class="zapwa-messages-header__sub"><?php esc_html_e( 'Crie e gerencie suas automações de mensagem', 'zap-whatsapp-automation' ); ?></p>
                </div>
            </div>
            <div class="zapwa-messages-header__actions">
                <a href="<?php echo $metrics_url; ?>" class="zapwa-btn zapwa-btn-ghost">
                    ← <?php esc_html_e( 'Dashboard', 'zap-whatsapp-automation' ); ?>
                </a>
                <a href="<?php echo $new_url; ?>" class="zapwa-btn zapwa-btn-new">
                    <span class="zapwa-btn-new__icon">＋</span>
                    <?php esc_html_e( 'Nova Mensagem', 'zap-whatsapp-automation' ); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
