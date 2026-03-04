<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class QueuePage {

    public static function render() {
        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_queue';

        if (isset($_POST['zapwa_clear_queue']) && check_admin_referer('zapwa_clear_queue_action', 'zapwa_queue_nonce')) {
            if (current_user_can('manage_options')) {
                $wpdb->query("DELETE FROM `{$table}` WHERE status IN ('completed', 'failed')");
                echo '<div class="notice notice-success"><p>Itens concluídos e com falha removidos da fila.</p></div>';
            }
        }

        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $per_page      = 50;
        $paged         = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $offset        = ($paged - 1) * $per_page;

        $where = $status_filter ? $wpdb->prepare('WHERE status = %s', $status_filter) : '';

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` {$where}");
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$table}` {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        $total_pages = ceil($total / $per_page);

        $counts = $wpdb->get_results("SELECT status, COUNT(*) as cnt FROM `{$table}` GROUP BY status");
        $summary = [];
        foreach ($counts as $row) {
            $summary[$row->status] = (int) $row->cnt;
        }

        $status_labels = [
            'pending'   => ['label' => 'Pendente',  'class' => 'zapwa-status-pill--pending'],
            'completed' => ['label' => 'Concluído', 'class' => 'zapwa-status-pill--completed'],
            'failed'    => ['label' => 'Falhou',    'class' => 'zapwa-status-pill--failed'],
        ];

        $total_all = array_sum(array_column($counts, 'cnt'));
        ?>
        <div class="wrap zapwa-page">
            <div class="zapwa-admin-header">
                <div>
                    <h1>📥 <?php esc_html_e('Fila de Mensagens', 'zap-whatsapp-automation'); ?></h1>
                    <p class="sub"><?php esc_html_e('Visualize o processamento e o status de entrega da automação.', 'zap-whatsapp-automation'); ?></p>
                </div>
                <a href="<?php echo esc_url(admin_url('admin.php?page=zap-wa-metrics')); ?>" class="zapwa-btn zapwa-btn-ghost">← Dashboard</a>
            </div>

            <div class="zapwa-stats-grid">
                <div class="zapwa-stat">
                    <div class="zapwa-stat__num"><?php echo number_format_i18n($total_all); ?></div>
                    <div class="zapwa-stat__label">Total</div>
                </div>
                <?php foreach ($status_labels as $slug => $info): ?>
                    <div class="zapwa-stat">
                        <div class="zapwa-stat__num"><?php echo number_format_i18n($summary[$slug] ?? 0); ?></div>
                        <div class="zapwa-stat__label"><?php echo esc_html($info['label']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="zapwa-card">
                <div class="zapwa-card-hdr">🔎 Filtros e manutenção</div>
                <div class="zapwa-card-body zapwa-page-toolbar">
                    <form method="get" class="zapwa-filter-form">
                        <input type="hidden" name="page" value="zap-wa-queue">
                        <select name="status">
                            <option value="">Todos os status</option>
                            <?php foreach ($status_labels as $slug => $info): ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($status_filter, $slug); ?>>
                                    <?php echo esc_html($info['label']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="zapwa-btn zapwa-btn-secondary">Filtrar</button>
                        <?php if ($status_filter): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=zap-wa-queue')); ?>" class="zapwa-btn zapwa-btn-ghost" style="color:#075e54 !important;border-color:#b7d6c7;background:#fff;">Limpar</a>
                        <?php endif; ?>
                    </form>

                    <form method="post">
                        <?php wp_nonce_field('zapwa_clear_queue_action', 'zapwa_queue_nonce'); ?>
                        <button type="submit" name="zapwa_clear_queue" value="1" class="zapwa-btn zapwa-btn-secondary"
                                onclick="return confirm('Remover itens concluídos e com falha?');">
                            🗑️ Limpar concluídos/falhos
                        </button>
                    </form>
                </div>
            </div>

            <div class="zapwa-card">
                <div class="zapwa-card-hdr blue">🧾 Itens da fila (<?php echo number_format_i18n($total); ?>)</div>
                <div class="zapwa-card-body">
                    <?php if (empty($items)): ?>
                        <p>Nenhum item na fila com os filtros selecionados.</p>
                    <?php else: ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Status</th>
                                    <th>Evento</th>
                                    <th>Usuário</th>
                                    <th>Telefone</th>
                                    <th>Tentativas</th>
                                    <th>Agendado para</th>
                                    <th>Criado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item):
                                    $user = get_userdata((int) $item->user_id);
                                    $user_name = $user ? $user->display_name : "User #{$item->user_id}";
                                    $status_key = sanitize_key($item->status);
                                    $status_info = $status_labels[$status_key] ?? ['label' => ucfirst($item->status), 'class' => 'zapwa-status-pill--default'];
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($item->id); ?></td>
                                        <td><span class="zapwa-status-pill <?php echo esc_attr($status_info['class']); ?>"><?php echo esc_html($status_info['label']); ?></span></td>
                                        <td><code><?php echo esc_html($item->event); ?></code></td>
                                        <td>
                                            <?php echo esc_html($user_name); ?><br>
                                            <small>ID: <?php echo esc_html($item->user_id); ?></small>
                                        </td>
                                        <td><?php echo esc_html($item->phone); ?></td>
                                        <td><?php echo esc_html($item->attempts); ?></td>
                                        <td><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($item->run_at))); ?></td>
                                        <td><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($item->created_at))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <?php if ($total_pages > 1): ?>
                            <div class="tablenav bottom">
                                <div class="tablenav-pages">
                                    <?php
                                    $base_url = add_query_arg([
                                        'page' => 'zap-wa-queue',
                                        'status' => $status_filter,
                                    ], admin_url('admin.php'));

                                    echo paginate_links([
                                        'base' => add_query_arg('paged', '%#%', $base_url),
                                        'format' => '',
                                        'current' => $paged,
                                        'total' => $total_pages,
                                        'prev_text' => '«',
                                        'next_text' => '»',
                                    ]);
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}
