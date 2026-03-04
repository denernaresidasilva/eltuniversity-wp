<?php
namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Admin {

    public static function init() {

        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_post_zap_events_delete_all_logs', [self::class, 'handle_delete_all_logs']);
        add_action('admin_post_zap_events_delete_webhook_logs', [self::class, 'handle_delete_webhook_logs']);
        WebhooksPage::init();
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

    /**
     * Return plugin navigation tabs.
     *
     * @return array<int, array<string, string>>
     */
    public static function get_navigation_tabs() {
        return [
            [ 'page' => 'zap-tutor-events',              'icon' => '📊', 'label' => 'Dashboard'       ],
            [ 'page' => 'zap-tutor-events-webhooks',     'icon' => '🔗', 'label' => 'Webhooks'        ],
            [ 'page' => 'zap-tutor-events-logs',         'icon' => '📋', 'label' => 'Logs'            ],
            [ 'page' => 'zap-tutor-events-webhook-logs', 'icon' => '📡', 'label' => 'Logs de Webhook' ],
            [ 'page' => 'zap-tutor-events-settings',     'icon' => '⚙️', 'label' => 'Configurações'   ],
        ];
    }

    /**
     * Render app-like desktop sidebar and mobile bottom nav.
     *
     * @param string $active_page Current page slug (e.g. 'zap-tutor-events-logs').
     */
    public static function render_tab_nav( $active_page = '' ) {
        $tabs = self::get_navigation_tabs();

        $current_index = -1;
        foreach ( $tabs as $i => $tab ) {
            if ( $tab['page'] === $active_page ) {
                $current_index = $i;
                break;
            }
        }

        echo '<nav class="zap-tab-nav zap-tab-nav--desktop" aria-label="' . esc_attr__( 'Navegação do Plugin', 'zap-tutor-events' ) . '">';
        echo '<div class="zap-tab-nav__title">⚡ ZAP Tutor Events</div>';
        foreach ( $tabs as $i => $tab ) {
            $is_active = ( $tab['page'] === $active_page );
            $url   = admin_url( 'admin.php?page=' . $tab['page'] );
            $class = 'zap-tab-nav__item' . ( $is_active ? ' zap-tab-nav__item--active' : '' );
            if ( $i > 0 ) {
                echo '<span class="zap-tab-nav__sep" aria-hidden="true"></span>';
            }
            printf(
                '<a href="%s" class="%s"%s>%s %s</a>',
                esc_url( $url ),
                esc_attr( $class ),
                $is_active ? ' aria-current="page"' : '',
                $tab['icon'],
                esc_html( $tab['label'] )
            );
        }
        echo '</nav>';

        echo '<nav class="zap-tab-nav zap-tab-nav--mobile" aria-label="' . esc_attr__( 'Navegação móvel do Plugin', 'zap-tutor-events' ) . '">';
        foreach ( $tabs as $tab ) {
            $is_active = ( $tab['page'] === $active_page );
            $url   = admin_url( 'admin.php?page=' . $tab['page'] );
            $class = 'zap-tab-nav__item' . ( $is_active ? ' zap-tab-nav__item--active' : '' );
            printf(
                '<a href="%s" class="%s"%s><span class="zap-tab-nav__icon">%s</span><span class="zap-tab-nav__label">%s</span></a>',
                esc_url( $url ),
                esc_attr( $class ),
                $is_active ? ' aria-current="page"' : '',
                $tab['icon'],
                esc_html( $tab['label'] )
            );
        }
        echo '</nav>';

        // Floating arrows
        $prev_tab = ( $current_index > 0 ) ? $tabs[ $current_index - 1 ] : null;
        $next_tab = ( $current_index >= 0 && $current_index < count( $tabs ) - 1 ) ? $tabs[ $current_index + 1 ] : null;

        if ( $prev_tab || $next_tab ) {
            echo '<div class="zap-floating-nav" aria-hidden="true">';
            if ( $prev_tab ) {
                printf(
                    '<a href="%s" class="zap-floating-nav__btn zap-floating-nav__btn--prev" title="%s" aria-label="%s">&#8592;<span class="zap-floating-nav__tooltip">%s %s</span></a>',
                    esc_url( admin_url( 'admin.php?page=' . $prev_tab['page'] ) ),
                    esc_attr( $prev_tab['label'] ),
                    esc_attr( sprintf( __( 'Ir para %s', 'zap-tutor-events' ), $prev_tab['label'] ) ),
                    $prev_tab['icon'],
                    esc_html( $prev_tab['label'] )
                );
            }
            if ( $next_tab ) {
                printf(
                    '<a href="%s" class="zap-floating-nav__btn zap-floating-nav__btn--next" title="%s" aria-label="%s">&#8594;<span class="zap-floating-nav__tooltip">%s %s</span></a>',
                    esc_url( admin_url( 'admin.php?page=' . $next_tab['page'] ) ),
                    esc_attr( $next_tab['label'] ),
                    esc_attr( sprintf( __( 'Ir para %s', 'zap-tutor-events' ), $next_tab['label'] ) ),
                    $next_tab['icon'],
                    esc_html( $next_tab['label'] )
                );
            }
            echo '</div>';
        }
    }

    public static function menu() {

        // Main menu opens Dashboard first
        add_menu_page(
            'ZAP Tutor Events',
            'ZAP Tutor Events',
            'manage_options',
            'zap-tutor-events',
            [Dashboard::class, 'render_dashboard'],
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'zap-tutor-events',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'zap-tutor-events',
            [Dashboard::class, 'render_dashboard']
        );

        // Webhooks management (new dedicated tab)
        add_submenu_page(
            'zap-tutor-events',
            'Webhooks',
            '🔗 Webhooks',
            'manage_options',
            'zap-tutor-events-webhooks',
            [WebhooksPage::class, 'render']
        );

        add_submenu_page(
            'zap-tutor-events',
            'Logs',
            'Logs',
            'manage_options',
            'zap-tutor-events-logs',
            [self::class, 'logs_page']
        );

        add_submenu_page(
            'zap-tutor-events',
            'Logs de Webhook',
            'Logs de Webhook',
            'manage_options',
            'zap-tutor-events-webhook-logs',
            [self::class, 'webhook_logs_page']
        );
    }

    public static function logs_page() {
        global $wpdb;

        // Read and sanitize filter parameters (used by both display and export)
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
        <div class="wrap zap-events-page">

            <!-- Page header -->
            <div class="zap-events-header">
                <div class="zap-events-header__info">
                    <span class="zap-events-header__icon">📋</span>
                    <div>
                        <h1 class="zap-events-header__title"><?php esc_html_e( 'Logs de Eventos', 'zap-tutor-events' ); ?></h1>
                        <p class="zap-events-header__sub"><?php printf( esc_html__( '%s registros encontrados', 'zap-tutor-events' ), number_format_i18n( $total_logs ) ); ?></p>
                    </div>
                </div>
                <div class="zap-events-header__nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events' ) ); ?>" class="zap-nav-btn">← Dashboard</a>
                </div>
            </div>

            <?php Admin::render_tab_nav( 'zap-tutor-events-logs' ); ?>

            <?php if (!get_option('zap_events_log_enabled', true)): ?>
                <div class="notice notice-warning is-dismissible">
                    <p>⚠️ <?php esc_html_e('Os logs de eventos estão desativados. Ative em Configurações para voltar a registrar novos eventos.', 'zap-tutor-events'); ?></p>
                </div>
            <?php endif; ?>

            <?php
            $active_filters = [];
            if ($event_filter) {
                $active_filters[] = 'Evento: ' . ($all_events[$event_filter] ?? $event_filter);
            }
            if ($user_filter) {
                $active_filters[] = 'User ID: ' . $user_filter;
            }
            if ($date_from || $date_to) {
                $active_filters[] = 'Período: ' . ($date_from ?: '...') . ' → ' . ($date_to ?: '...');
            }
            ?>

            <?php if (!empty($active_filters)): ?>
                <div class="zap-active-filters" aria-label="<?php esc_attr_e('Filtros ativos', 'zap-tutor-events'); ?>">
                    <?php foreach ($active_filters as $active_filter): ?>
                        <span class="zap-filter-chip"><?php echo esc_html($active_filter); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'all'): ?>
                <div class="notice notice-success is-dismissible"><p>🗑️ Todos os logs de eventos foram excluídos.</p></div>
            <?php endif; ?>

            <!-- Filters card -->
            <div class="zap-events-card">
                <div class="zap-events-card__hdr">🔍 <?php esc_html_e( 'Filtros', 'zap-tutor-events' ); ?></div>
                <div class="zap-events-card__body">
                    <form method="get" class="zap-logs-filter-form">
                        <input type="hidden" name="page" value="zap-tutor-events-logs">
                        <?php wp_nonce_field('zap_export_logs', 'zap_export_nonce'); ?>
                        <div class="zap-logs-filter-row">
                            <select name="event_filter" class="zap-select">
                                <option value=""><?php esc_html_e( 'Todos os eventos', 'zap-tutor-events' ); ?></option>
                                <?php foreach ($all_events as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($event_filter, $key); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number"
                                   name="user_filter"
                                   placeholder="<?php esc_attr_e( 'User ID', 'zap-tutor-events' ); ?>"
                                   value="<?php echo $user_filter ?: ''; ?>"
                                   class="zap-input"
                                   style="width:100px;">
                            <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" class="zap-input">
                            <input type="date" name="date_to"   value="<?php echo esc_attr($date_to);   ?>" class="zap-input">
                            <select name="per_page" class="zap-select">
                                <option value="50"  <?php selected($per_page,  50); ?>>50 / pág.</option>
                                <option value="100" <?php selected($per_page, 100); ?>>100 / pág.</option>
                                <option value="200" <?php selected($per_page, 200); ?>>200 / pág.</option>
                            </select>
                            <div class="zap-logs-filter-btns">
                                <button type="submit" class="zap-btn zap-btn-primary">
                                    🔍 <?php esc_html_e( 'Filtrar', 'zap-tutor-events' ); ?>
                                </button>
                                <a href="<?php echo esc_url( admin_url('admin.php?page=zap-tutor-events-logs') ); ?>" class="zap-btn zap-btn-secondary">
                                    ✕ <?php esc_html_e( 'Limpar', 'zap-tutor-events' ); ?>
                                </a>
                                <button type="submit" name="export_csv" value="1" class="zap-btn zap-btn-primary" style="background:#1565c0;">
                                    📥 <?php esc_html_e( 'Exportar CSV', 'zap-tutor-events' ); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="margin-top:12px;">
                        <?php wp_nonce_field('zap_events_delete_all_logs', 'zap_delete_logs_nonce'); ?>
                        <input type="hidden" name="action" value="zap_events_delete_all_logs">
                        <button type="submit"
                                class="zap-btn zap-btn--danger"
                                onclick="return confirm('<?php echo esc_js(__('Tem certeza que deseja excluir TODOS os logs de eventos? Esta ação não pode ser desfeita.', 'zap-tutor-events')); ?>');">
                            🗑️ <?php esc_html_e( 'Excluir Todos os Logs', 'zap-tutor-events' ); ?>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Logs table card -->
            <?php if (empty($logs)): ?>
                <div class="zap-events-card">
                    <div class="zap-events-card__body">
                        <div class="zap-empty-state">
                            <span class="zap-empty-state__icon">📋</span>
                            <p><?php esc_html_e( 'Nenhum evento registrado com os filtros selecionados.', 'zap-tutor-events' ); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="zap-events-card">
                    <div class="zap-events-card__hdr">
                        📊 <?php esc_html_e( 'Registros', 'zap-tutor-events' ); ?>
                        <span class="zap-badge zap-badge--count"><?php echo number_format_i18n($total_logs); ?></span>
                    </div>
                    <div class="zap-events-card__body--flush">
                        <div style="overflow-x:auto;">
                            <table class="zap-logs-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?php esc_html_e( 'Evento', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Usuário', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Data', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Contexto', 'zap-tutor-events' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log):
                                        $user_data  = get_userdata($log->user_id);
                                        $user_name  = $user_data ? $user_data->display_name : 'N/A';
                                        $event_label = $all_events[$log->event_key] ?? $log->event_key;
                                    ?>
                                        <tr>
                                            <td class="zap-col-id"><?php echo esc_html($log->id); ?></td>
                                            <td>
                                                <strong><?php echo esc_html($event_label); ?></strong><br>
                                                <code class="zap-code-sm"><?php echo esc_html($log->event_key); ?></code>
                                            </td>
                                            <td>
                                                <span class="zap-user-name"><?php echo esc_html($user_name); ?></span><br>
                                                <code class="zap-code-sm">ID: <?php echo esc_html($log->user_id); ?></code>
                                            </td>
                                            <td class="zap-col-date"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($log->created_at))); ?></td>
                                            <td><pre class="zap-pre-ctx"><?php echo esc_html($log->context); ?></pre></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="zap-pagination">
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
                            'prev_text' => '« ' . esc_html__('Anterior', 'zap-tutor-events'),
                            'next_text' => esc_html__('Próxima', 'zap-tutor-events') . ' »',
                        ]);
                        ?>
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

    /**
     * Handle delete all event logs
     */
    public static function handle_delete_all_logs() {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('zap_events_delete_all_logs', 'zap_delete_logs_nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $wpdb->query("TRUNCATE TABLE {$table}");

        wp_redirect(add_query_arg([
            'page'    => 'zap-tutor-events-logs',
            'deleted' => 'all',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handle delete all webhook logs
     */
    public static function handle_delete_webhook_logs() {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('zap_events_delete_webhook_logs', 'zap_delete_webhook_nonce');

        global $wpdb;
        $table = $wpdb->prefix . 'zap_webhook_logs';
        $wpdb->query("TRUNCATE TABLE {$table}");

        wp_redirect(add_query_arg([
            'page'    => 'zap-tutor-events-webhook-logs',
            'deleted' => 'all',
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Webhook logs page
     */
    public static function webhook_logs_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_webhook_logs';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table}'") != $table) {
            ?>
            <div class="wrap zap-events-page">
                <div class="zap-events-header">
                    <div class="zap-events-header__info">
                        <span class="zap-events-header__icon">📡</span>
                        <div>
                            <h1 class="zap-events-header__title"><?php esc_html_e( 'Logs de Webhook', 'zap-tutor-events' ); ?></h1>
                            <p class="zap-events-header__sub"><?php esc_html_e( 'Acompanhe entregas e falhas de webhooks', 'zap-tutor-events' ); ?></p>
                        </div>
                    </div>
                </div>
                <?php Admin::render_tab_nav( 'zap-tutor-events-webhook-logs' ); ?>
                <div class="zap-events-card"><div class="zap-events-card__body"><div class="zap-empty-state"><span class="zap-empty-state__icon">🔗</span><p><?php esc_html_e('Nenhum log de webhook registrado ainda.', 'zap-tutor-events'); ?></p></div></div></div>
            </div>
            <?php
            return;
        }

        $paged    = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 50;
        $offset   = ($paged - 1) * $per_page;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $logs  = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        $total_pages = ceil($total / $per_page);

        if (isset($_GET['deleted']) && $_GET['deleted'] === 'all') {
            echo '<div class="notice notice-success is-dismissible"><p>🗑️ Todos os logs de webhook foram excluídos.</p></div>';
        }
        ?>
        <div class="wrap zap-events-page">

            <!-- Page header -->
            <div class="zap-events-header">
                <div class="zap-events-header__info">
                    <span class="zap-events-header__icon">🔗</span>
                    <div>
                        <h1 class="zap-events-header__title"><?php esc_html_e( 'Logs de Webhook', 'zap-tutor-events' ); ?></h1>
                        <p class="zap-events-header__sub"><?php printf( esc_html__( '%s registros', 'zap-tutor-events' ), number_format_i18n( $total ) ); ?></p>
                    </div>
                </div>
                <div class="zap-events-header__nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events' ) ); ?>" class="zap-nav-btn">← Dashboard</a>
                    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline;">
                        <?php wp_nonce_field('zap_events_delete_webhook_logs', 'zap_delete_webhook_nonce'); ?>
                        <input type="hidden" name="action" value="zap_events_delete_webhook_logs">
                        <button type="submit"
                                class="zap-nav-btn"
                                style="background:rgba(220,53,69,.25);border-color:rgba(220,53,69,.5);"
                                onclick="return confirm('<?php echo esc_js(__('Excluir TODOS os logs de webhook?', 'zap-tutor-events')); ?>');">
                            🗑️ <?php esc_html_e( 'Limpar Logs', 'zap-tutor-events' ); ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php Admin::render_tab_nav( 'zap-tutor-events-webhook-logs' ); ?>

            <?php if (empty($logs)): ?>
                <div class="zap-events-card">
                    <div class="zap-events-card__body">
                        <div class="zap-empty-state">
                            <span class="zap-empty-state__icon">🔗</span>
                            <p><?php esc_html_e( 'Nenhum log de webhook registrado.', 'zap-tutor-events' ); ?></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="zap-events-card">
                    <div class="zap-events-card__hdr">
                        📊 <?php esc_html_e( 'Registros de Webhook', 'zap-tutor-events' ); ?>
                        <span class="zap-badge zap-badge--count"><?php echo number_format_i18n($total); ?></span>
                    </div>
                    <div class="zap-events-card__body--flush">
                        <div style="overflow-x:auto;">
                            <table class="zap-logs-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th><?php esc_html_e( 'Evento', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Status', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Tentativa', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Mensagem', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'URL', 'zap-tutor-events' ); ?></th>
                                        <th><?php esc_html_e( 'Data', 'zap-tutor-events' ); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td class="zap-col-id"><?php echo esc_html($log->id); ?></td>
                                            <td><code class="zap-code-sm"><?php echo esc_html($log->event_key); ?></code></td>
                                            <td>
                                                <?php if ($log->success): ?>
                                                    <span class="zap-status zap-status--ok">✔ <?php esc_html_e( 'Sucesso', 'zap-tutor-events' ); ?></span>
                                                <?php else: ?>
                                                    <span class="zap-status zap-status--err">✘ <?php esc_html_e( 'Falha', 'zap-tutor-events' ); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo esc_html($log->attempt); ?></td>
                                            <td><?php echo esc_html($log->message); ?></td>
                                            <td><small class="zap-url-sm"><?php echo esc_html($log->webhook_url); ?></small></td>
                                            <td class="zap-col-date"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($log->created_at))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($total_pages > 1): ?>
                    <div class="zap-pagination">
                        <?php
                        echo paginate_links([
                            'base'      => add_query_arg('paged', '%#%', admin_url('admin.php?page=zap-tutor-events-webhook-logs')),
                            'format'    => '',
                            'current'   => $paged,
                            'total'     => $total_pages,
                            'prev_text' => '«',
                            'next_text' => '»',
                        ]);
                        ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
