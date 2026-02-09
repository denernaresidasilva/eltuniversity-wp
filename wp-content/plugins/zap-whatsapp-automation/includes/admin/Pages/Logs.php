<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Logs {

    public static function render() {
        global $wpdb;

        $table = $wpdb->prefix . 'zap_wa_logs';

        // Filtros
        $where = 'WHERE 1=1';

        if (!empty($_GET['status'])) {
            $where .= $wpdb->prepare(' AND status = %s', $_GET['status']);
        }

        if (!empty($_GET['event'])) {
            $where .= $wpdb->prepare(' AND event = %s', $_GET['event']);
        }

        if (!empty($_GET['user_id'])) {
            $where .= $wpdb->prepare(' AND user_id = %d', $_GET['user_id']);
        }

        $logs = $wpdb->get_results(
            "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1>Logs de Envio</h1>

            <form method="get">
                <input type="hidden" name="page" value="zap-wa-logs">

                <select name="status">
                    <option value="">Status</option>
                    <option value="enviado">Enviado</option>
                    <option value="erro">Erro</option>
                    <option value="pendente">Pendente</option>
                </select>

                <input type="text" name="event" placeholder="Evento">
                <input type="number" name="user_id" placeholder="User ID">

                <button class="button">Filtrar</button>
            </form>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Usuário</th>
                        <th>Telefone</th>
                        <th>Evento</th>
                        <th>Status</th>
                        <th>Mensagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html($log->created_at); ?></td>
                            <td>
                                <?php
                                $user = get_userdata($log->user_id);
                                echo $user ? esc_html($user->display_name) : '—';
                                ?>
                            </td>
                            <td><?php echo esc_html($log->phone); ?></td>
                            <td><?php echo esc_html($log->event); ?></td>
                            <td><?php echo esc_html($log->status); ?></td>
                            <td>
                                <textarea readonly style="width:100%;height:60px;">
<?php echo esc_textarea($log->message); ?>
                                </textarea>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
