<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {

    public static function render() {

        if (isset($_POST['zapwa_save_settings'])) {
            // Verify nonce for CSRF protection
            if (!isset($_POST['zapwa_settings_nonce']) || 
                !wp_verify_nonce($_POST['zapwa_settings_nonce'], 'zapwa_settings_save')) {
                wp_die('Invalid security token');
            }

            update_option('zapwa_api_url', sanitize_text_field($_POST['zapwa_api_url']));
            update_option('zapwa_api_token', sanitize_text_field($_POST['zapwa_api_token']));
            echo '<div class="updated"><p>Configurações salvas.</p></div>';
        }

        $api_url   = get_option('zapwa_api_url');
        $api_token = get_option('zapwa_api_token');
        ?>

        <div class="wrap">
            <h1>Configurações WhatsApp (Evolution API)</h1>

            <form method="post">
                <?php wp_nonce_field('zapwa_settings_save', 'zapwa_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>URL da Evolution API</th>
                        <td>
                            <input type="text" name="zapwa_api_url" value="<?php echo esc_attr($api_url); ?>" class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>Token da API</th>
                        <td>
                            <input type="password" name="zapwa_api_token" value="<?php echo esc_attr($api_token); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <p>
                    <button class="button button-primary" name="zapwa_save_settings">
                        Salvar Configurações
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
