<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class Messages {

    public static function render() {
        ?>
        <div class="wrap">
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
}
