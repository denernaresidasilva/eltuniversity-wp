<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

class QueuePage {

    public static function render() {
        global $wpdb;
        $table = $wpdb->prefix . 'zapwa_queue';

        // Handle clear action
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

        // Summary counts
        $counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as cnt FROM `{$table}` GROUP BY status"
        );
        $summary = [];
        foreach ($counts as $row) {
            $summary[$row->status] = (int) $row->cnt;
        }
        ?>
        <div class="wrap">
            <h1>Fila de Mensagens</h1>

            <!-- Summary cards -->
            <div style="display:flex;gap:15px;flex-wrap:wrap;margin:15px 0;">
                <?php
                $status_labels = [
                    'pending'   => ['label' => 'Pendente',   'color' => '#f39c12'],
                    'completed' => ['label' => 'Concluído',  'color' => '#27ae60'],
                    'failed'    => ['label' => 'Falhou',     'color' => '#e74c3c'],
                ];
                $total_all = array_sum(array_column($counts, 'cnt'));
                ?>
                <div style="background:white;border:1px solid #ccc;border-radius:6px;padding:15px 25px;text-align:center;min-width:110px;">
                    <div style="font-size:24px;font-weight:bold;"><?php echo number_format_i18n($total_all); ?></div>
                    <div style="color:#666;font-size:13px;">Total</div>
                </div>
                <?php foreach ($status_labels as $slug => $info): ?>
                    <div style="background:white;border:2px solid <?php echo $info['color']; ?>;border-radius:6px;padding:15px 25px;text-align:center;min-width:110px;">
                        <div style="font-size:24px;font-weight:bold;color:<?php echo $info['color']; ?>;">
                            <?php echo number_format_i18n($summary[$slug] ?? 0); ?>
                        </div>
                        <div style="color:#666;font-size:13px;"><?php echo $info['label']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Filters & actions -->
            <div class="tablenav top" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <form method="get" style="display:inline-flex;gap:8px;align-items:center;">
                    <input type="hidden" name="page" value="zap-wa-queue">
                    <select name="status">
                        <option value="">Todos os status</option>
                        <?php foreach ($status_labels as $slug => $info): ?>
                            <option value="<?php echo esc_attr($slug); ?>" <?php selected($status_filter, $slug); ?>>
                                <?php echo esc_html($info['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" class="button" value="Filtrar">
                    <?php if ($status_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=zap-wa-queue'); ?>" class="button">Limpar Filtro</a>
                    <?php endif; ?>
                </form>

                <form method="post" style="display:inline-flex;align-items:center;">
                    <?php wp_nonce_field('zapwa_clear_queue_action', 'zapwa_queue_nonce'); ?>
                    <button type="submit" name="zapwa_clear_queue" value="1" class="button button-secondary"
                        onclick="return confirm('Remover itens concluídos e com falha?');">
                        🗑️ Limpar Concluídos/Falhos
                    </button>
                </form>
            </div>

            <p>Total de itens: <strong><?php echo number_format_i18n($total); ?></strong></p>

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
                            $status_colors = [
                                'pending'   => '#f39c12',
                                'completed' => '#27ae60',
                                'failed'    => '#e74c3c',
                            ];
                            $color = $status_colors[$item->status] ?? '#999';
                        ?>
                            <tr>
                                <td><?php echo esc_html($item->id); ?></td>
                                <td>
                                    <span style="background:<?php echo $color; ?>;color:white;padding:2px 8px;border-radius:3px;font-size:12px;">
                                        <?php echo esc_html($item->status); ?>
                                    </span>
                                </td>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            $base_url = add_query_arg([
                                'page'   => 'zap-wa-queue',
                                'status' => $status_filter,
                            ], admin_url('admin.php'));

                            echo paginate_links([
                                'base'      => add_query_arg('paged', '%#%', $base_url),
                                'format'    => '',
                                'current'   => $paged,
                                'total'     => $total_pages,
                                'prev_text' => '«',
                                'next_text' => '»',
                            ]);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
