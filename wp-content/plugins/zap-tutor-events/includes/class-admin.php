<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public static function init() {

        // Garante que a classe de teste esteja carregada
        $test_file = ZAP_EVENTS_PATH . 'includes/class-admin-test.php';
        if (file_exists($test_file)) {
            require_once $test_file;
        }

        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'zap-tutor-events') === false) {
            return;
        }

        wp_enqueue_style(
            'zap-events-admin',
            ZAP_EVENTS_URL . 'assets/admin.css',
            [],
            ZAP_EVENTS_VERSION
        );
    }

    public static function menu() {

        // Fallback de segurança
        if (!class_exists(__NAMESPACE__ . '\\Admin_Test')) {
            return;
        }

        add_menu_page(
            'ZAP Tutor Events',
            'ZAP Tutor Events',
            'manage_options',
            'zap-tutor-events',
            [Admin_Test::class, 'render'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'zap-tutor-events',
            'Teste de Eventos',
            'Teste de Eventos',
            'manage_options',
            'zap-tutor-events',
            [Admin_Test::class, 'render']
        );

        add_submenu_page(
            'zap-tutor-events',
            'Logs',
            'Logs',
            'manage_options',
            'zap-tutor-events-logs',
            [self::class, 'logs_page']
        );
    }

    public static function logs_page() {
        global $wpdb;

        // Get filters
        $event_filter = isset($_GET['event_filter']) ? sanitize_text_field($_GET['event_filter']) : '';
        $user_filter = isset($_GET['user_filter']) ? absint($_GET['user_filter']) : 0;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $per_page = isset($_GET['per_page']) ? absint($_GET['per_page']) : 50;
        $paged = isset($_GET['paged']) ? absint($_GET['paged']) : 1;

        // Handle CSV export with CSRF protection
        if (isset($_GET['export_csv'])) {
            check_admin_referer('zap_export_logs', 'zap_export_nonce');
            self::export_logs_csv($event_filter, $user_filter, $date_from, $date_to);
            return;
        }

        $filters = [];
        if ($event_filter) $filters['event_key'] = $event_filter;
        if ($user_filter) $filters['user_id'] = $user_filter;
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;

        $total_logs = Logger::get_count($filters);
        $logs = Logger::get_logs($filters, $per_page, $paged);
        $total_pages = ceil($total_logs / $per_page);

        $all_events = Events::registry();

        ?>
        <div class="wrap">
            <h1>Logs de Eventos</h1>

            <!-- Filters -->
            <div class="tablenav top">
                <form method="get" style="display: inline-block;">
                    <input type="hidden" name="page" value="zap-tutor-events-logs">
                    <?php wp_nonce_field('zap_export_logs', 'zap_export_nonce'); ?>
                    
                    <select name="event_filter">
                        <option value="">Todos os eventos</option>
                        <?php foreach ($all_events as $key => $label): ?>
                            <option value="<?php echo esc_attr($key); ?>" <?php selected($event_filter, $key); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <input type="number" 
                           name="user_filter" 
                           placeholder="User ID" 
                           value="<?php echo $user_filter ?: ''; ?>" 
                           style="width: 100px;">

                    <input type="date" 
                           name="date_from" 
                           value="<?php echo esc_attr($date_from); ?>" 
                           placeholder="Data Inicial">

                    <input type="date" 
                           name="date_to" 
                           value="<?php echo esc_attr($date_to); ?>" 
                           placeholder="Data Final">

                    <select name="per_page">
                        <option value="50" <?php selected($per_page, 50); ?>>50 por página</option>
                        <option value="100" <?php selected($per_page, 100); ?>>100 por página</option>
                        <option value="200" <?php selected($per_page, 200); ?>>200 por página</option>
                    </select>

                    <input type="submit" class="button" value="Filtrar">
                    <a href="<?php echo admin_url('admin.php?page=zap-tutor-events-logs'); ?>" class="button">Limpar</a>
                    <button type="submit" name="export_csv" value="1" class="button button-primary">Exportar CSV</button>
                </form>
            </div>

            <p>Total de registros: <strong><?php echo number_format_i18n($total_logs); ?></strong></p>

            <?php if (empty($logs)): ?>
                <p>Nenhum evento registrado com os filtros selecionados.</p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Evento</th>
                            <th>User ID</th>
                            <th>Usuário</th>
                            <th>Data</th>
                            <th>Contexto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): 
                            $user_data = get_userdata($log->user_id);
                            $user_name = $user_data ? $user_data->display_name : 'N/A';
                            $event_label = $all_events[$log->event_key] ?? $log->event_key;
                        ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td>
                                    <strong><?php echo esc_html($event_label); ?></strong><br>
                                    <code style="font-size: 11px;"><?php echo esc_html($log->event_key); ?></code>
                                </td>
                                <td><?php echo esc_html($log->user_id); ?></td>
                                <td><?php echo esc_html($user_name); ?></td>
                                <td><?php echo esc_html(date_i18n('d/m/Y H:i:s', strtotime($log->created_at))); ?></td>
                                <td>
                                    <pre style="max-width:400px;white-space:pre-wrap;font-size:11px;"><?php echo esc_html($log->context); ?></pre>
                                </td>
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
                                'page'         => 'zap-tutor-events-logs',
                                'event_filter' => $event_filter,
                                'user_filter'  => $user_filter,
                                'date_from'    => $date_from,
                                'date_to'      => $date_to,
                                'per_page'     => $per_page,
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

    /**
     * Export logs to CSV
     */
    private static function export_logs_csv($event_filter, $user_filter, $date_from, $date_to) {
        
        $filters = [];
        if ($event_filter) $filters['event_key'] = $event_filter;
        if ($user_filter) $filters['user_id'] = $user_filter;
        if ($date_from) $filters['date_from'] = $date_from;
        if ($date_to) $filters['date_to'] = $date_to;

        $logs = Logger::get_logs($filters, 10000, 1); // Max 10k rows for export

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=zap-events-logs-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, ['ID', 'Evento', 'User ID', 'Usuário', 'Data', 'Contexto']);
        
        // Rows
        $all_events = Events::registry();
        foreach ($logs as $log) {
            $user_data = get_userdata($log->user_id);
            $user_name = $user_data ? $user_data->display_name : 'N/A';
            $event_label = $all_events[$log->event_key] ?? $log->event_key;
            
            fputcsv($output, [
                $log->id,
                $event_label,
                $log->user_id,
                $user_name,
                $log->created_at,
                $log->context,
            ]);
        }
        
        fclose($output);
        exit;
    }
}
