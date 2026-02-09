<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Admin_Test {

    public static function render() {

        if (isset($_POST['zap_test_event'])) {
            self::dispatch_test_event();
            echo '<div class="notice notice-success"><p>Evento de teste disparado com sucesso.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Teste de Eventos – ZAP Tutor Events</h1>

            <p>Este teste dispara um evento fixo para validação da automação.</p>

            <table class="widefat striped">
                <tr>
                    <th>Evento</th>
                    <td><code>zap_test_event</code></td>
                </tr>
                <tr>
                    <th>User ID</th>
                    <td><code>1</code></td>
                </tr>
                <tr>
                    <th>Contexto (JSON)</th>
                    <td>
                        <pre>{
  "source": "admin_test",
  "description": "Evento de teste padrão",
  "timestamp": "<?php echo current_time('mysql'); ?>"
}</pre>
                    </td>
                </tr>
            </table>

            <form method="post" style="margin-top:20px;">
                <?php wp_nonce_field('zap_test_event'); ?>
                <input type="submit" name="zap_test_event" class="button button-primary" value="Gerar Evento de Teste">
            </form>
        </div>
        <?php
    }

    private static function dispatch_test_event() {

        if (!wp_verify_nonce($_POST['_wpnonce'], 'zap_test_event')) {
            return;
        }

        $context = [
            'source'      => 'admin_test',
            'description' => 'Evento de teste padrão',
            'timestamp'   => current_time('mysql'),
        ];

        Dispatcher::dispatch(
            'zap_test_event',
            1,
            $context
        );
    }
}
