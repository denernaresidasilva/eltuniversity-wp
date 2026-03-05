<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Logs {

    public static function render() {
        global $wpdb;

        $table = $wpdb->prefix . 'zap_wa_logs';

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
            $query = "SELECT * FROM $table ORDER BY created_at DESC LIMIT 100";
        }

        $logs = $wpdb->get_results($query);

        $status_map = [
            'enviado'  => ['label' => 'Enviado', 'class' => 'zapwa-status-pill--enviado'],
            'erro'     => ['label' => 'Erro', 'class' => 'zapwa-status-pill--erro'],
            'pendente' => ['label' => 'Pendente', 'class' => 'zapwa-status-pill--pending'],
        ];

        $total_logs = count($logs);
        $count_ok = 0;
        $count_error = 0;
        $count_pending = 0;

        foreach ($logs as $log) {
            if ($log->status === 'enviado') {
                $count_ok++;
            } elseif ($log->status === 'erro') {
                $count_error++;
            } elseif ($log->status === 'pendente') {
                $count_pending++;
            }
        }
        ?>
        <div class="wrap zapwa-page">
            <div class="zapwa-admin-header">
                <div>
                    <h1>📜 <?php esc_html_e('Logs de Envio', 'zap-whatsapp-automation'); ?></h1>
                    <p class="sub"><?php esc_html_e('Acompanhe status, eventos e erros em tempo real.', 'zap-whatsapp-automation'); ?></p>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zap-wa-metrics')); ?>" class="zapwa-btn zapwa-btn-ghost">← Dashboard</a>
            </div>

            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'all'): ?>
                <div class="notice notice-success is-dismissible"><p>Todos os logs foram excluídos.</p></div>
            <?php endif; ?>

            <div class="zapwa-stats-grid">
                <div class="zapwa-stat zapwa-stat--info"><div class="zapwa-stat__num"><?php echo number_format_i18n($total_logs); ?></div><div class="zapwa-stat__label">Exibidos</div></div>
                <div class="zapwa-stat zapwa-stat--ok"><div class="zapwa-stat__num"><?php echo number_format_i18n($count_ok); ?></div><div class="zapwa-stat__label">Enviados</div></div>
                <div class="zapwa-stat zapwa-stat--warn"><div class="zapwa-stat__num"><?php echo number_format_i18n($count_pending); ?></div><div class="zapwa-stat__label">Pendentes</div></div>
                <div class="zapwa-stat zapwa-stat--error"><div class="zapwa-stat__num"><?php echo number_format_i18n($count_error); ?></div><div class="zapwa-stat__label">Com erro</div></div>
            </div>

            <div class="zapwa-card">
                <div class="zapwa-card-hdr">🔎 Filtros e ações</div>
                <div class="zapwa-card-body">
                    <div class="zapwa-page-toolbar">
                        <form method="get" class="zapwa-filter-form">
                            <input type="hidden" name="page" value="zap-wa-logs">

                            <select name="status">
                                <option value="">Status</option>
                                <option value="enviado" <?php selected($_GET['status'] ?? '', 'enviado'); ?>>Enviado</option>
                                <option value="erro" <?php selected($_GET['status'] ?? '', 'erro'); ?>>Erro</option>
                                <option value="pendente" <?php selected($_GET['status'] ?? '', 'pendente'); ?>>Pendente</option>
                            </select>

                            <input type="text" name="event" placeholder="Evento" value="<?php echo esc_attr($_GET['event'] ?? ''); ?>">
                            <input type="number" name="user_id" placeholder="User ID" value="<?php echo esc_attr($_GET['user_id'] ?? ''); ?>">

                            <button class="zapwa-btn zapwa-btn-secondary" type="submit">Filtrar</button>
                        </form>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <?php wp_nonce_field('zapwa_delete_all_logs', 'zapwa_delete_logs_nonce'); ?>
                            <input type="hidden" name="action" value="zapwa_delete_all_logs">
                            <button type="submit" class="zapwa-btn zapwa-btn-secondary" style="background:#a00;border-color:#a00;" onclick="return confirm('Tem certeza que deseja excluir TODOS os logs? Esta ação não pode ser desfeita.');">🗑️ Excluir todos</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="zapwa-card">
                <div class="zapwa-card-hdr blue">🧾 Histórico</div>
                <div class="zapwa-card-body">
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
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6">Nenhum log encontrado para os filtros informados.</td></tr>
                            <?php else: ?>
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
                                        <td><code><?php echo esc_html($log->event); ?></code></td>
                                        <td>
                                            <?php
                                            $status = sanitize_key($log->status);
                                            $badge  = $status_map[$status] ?? ['label' => ucfirst($log->status), 'class' => 'zapwa-status-pill--default'];
                                            ?>
                                            <span class="zapwa-status-pill <?php echo esc_attr($badge['class']); ?>"><?php echo esc_html($badge['label']); ?></span>
                                        </td>
                                        <td>
                                            <textarea readonly class="zapwa-log-message"><?php echo esc_textarea($log->message); ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
