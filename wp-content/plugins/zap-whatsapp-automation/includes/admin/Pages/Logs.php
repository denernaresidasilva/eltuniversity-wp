<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Logs {

    public static function render() {
        global $wpdb;

        $table = $wpdb->prefix . 'zap_wa_logs';

        // Filtros - Fixed SQL injection vulnerability by using complete prepared statement
        $where_conditions = [];
        $where_values = [];

        if (!empty($_GET['status'])) {
            $where_conditions[] = 'status = %s';
            $where_values[] = sanitize_text_field($_GET['status']);
        }

        if (!empty($_GET['event'])) {
            $where_conditions[] = 'event = %s';
            $where_values[] = sanitize_text_field($_GET['event']);
        }

        if (!empty($_GET['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = intval($_GET['user_id']);
        }

        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            $query = $wpdb->prepare(
                "SELECT * FROM $table $where_clause ORDER BY created_at DESC LIMIT 100",
                ...$where_values
            );
        } else {
            // No filters applied, query without WHERE clause
            $query = "SELECT * FROM $table ORDER BY created_at DESC LIMIT 100";
        }

        $logs = $wpdb->get_results($query);
        ?>
        <div class="wrap zapwa-page">
            <h1>Logs de Envio</h1>

            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'all'): ?>
                <div class="notice notice-success is-dismissible"><p>Todos os logs foram excluídos.</p></div>
            <?php endif; ?>

            <div style="display:flex;gap:10px;align-items:center;margin-bottom:15px;flex-wrap:wrap;">
                <form method="get" style="display:inline-flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <input type="hidden" name="page" value="zap-wa-logs">

                    <select name="status">
                        <option value="">Status</option>
                        <option value="enviado" <?php selected($_GET['status'] ?? '', 'enviado'); ?>>Enviado</option>
                        <option value="erro" <?php selected($_GET['status'] ?? '', 'erro'); ?>>Erro</option>
                        <option value="pendente" <?php selected($_GET['status'] ?? '', 'pendente'); ?>>Pendente</option>
                    </select>

                    <input type="text" name="event" placeholder="Evento" value="<?php echo esc_attr($_GET['event'] ?? ''); ?>">
                    <input type="number" name="user_id" placeholder="User ID" value="<?php echo esc_attr($_GET['user_id'] ?? ''); ?>">

                    <button class="button">Filtrar</button>
                </form>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline-block;">
                    <?php wp_nonce_field('zapwa_delete_all_logs', 'zapwa_delete_logs_nonce'); ?>
                    <input type="hidden" name="action" value="zapwa_delete_all_logs">
                    <button type="submit" class="button button-secondary" style="color:#a00;border-color:#a00;" onclick="return confirm('Tem certeza que deseja excluir TODOS os logs? Esta ação não pode ser desfeita.');">
                        🗑️ Excluir Todos os Logs
                    </button>
                </form>
            </div>

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
