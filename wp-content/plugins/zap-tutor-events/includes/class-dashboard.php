<?php
/**
 * Dashboard Class
 * 
 * Displays statistics and analytics for events
 * 
 * @package ZapTutorEvents
 * @since 1.1.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Dashboard {

    /**
     * Initialize dashboard
     */
    public static function init() {
        // Dashboard page is registered by Admin::menu() as the main landing page.
        // No additional submenu registration needed here.
    }

    /**
     * Render dashboard page
     */
    public static function render_dashboard() {
        
        if (!current_user_can('manage_options')) {
            return;
        }

        $days = isset($_GET['days']) ? absint($_GET['days']) : 30;
        $stats = self::get_event_stats($days);
        $webhook_stats = Webhook::get_stats($days);
        $top_users = self::get_top_users($days);
        $course_completion = self::get_course_completion_rate($days);
        $timeline = self::get_event_timeline($days);

        $all_events = Events::registry();
        $total_events = array_sum(array_column($stats, 'count'));
        $log_enabled = (bool) get_option('zap_events_log_enabled', true);
        $use_queue = (bool) get_option('zap_events_use_queue', false);
        $active_webhooks = count(array_filter(WebhooksPage::get_webhooks(), static function($wh) {
            return !empty($wh['active']);
        }));

        // Color palette for event types
        $event_colors = [
            'tutor_student_signup'           => ['bg' => '#e8f5e9', 'border' => '#43a047', 'badge' => '#43a047'],
            'tutor_student_login'            => ['bg' => '#e3f2fd', 'border' => '#1e88e5', 'badge' => '#1e88e5'],
            'tutor_course_enrolled'          => ['bg' => '#fff3e0', 'border' => '#fb8c00', 'badge' => '#fb8c00'],
            'tutor_enrol_status_changed'     => ['bg' => '#fce4ec', 'border' => '#e91e63', 'badge' => '#e91e63'],
            'tutor_lesson_completed'         => ['bg' => '#f3e5f5', 'border' => '#8e24aa', 'badge' => '#8e24aa'],
            'tutor_course_progress_50'       => ['bg' => '#e0f7fa', 'border' => '#00acc1', 'badge' => '#00acc1'],
            'tutor_course_completed'         => ['bg' => '#e8f5e9', 'border' => '#2e7d32', 'badge' => '#2e7d32'],
            'tutor_assignment_submitted'     => ['bg' => '#fff8e1', 'border' => '#f9a825', 'badge' => '#f9a825'],
            'tutor_quiz_started'             => ['bg' => '#e8eaf6', 'border' => '#3949ab', 'badge' => '#3949ab'],
            'tutor_quiz_finished'            => ['bg' => '#fbe9e7', 'border' => '#bf360c', 'badge' => '#bf360c'],
            'tutor_order_payment_status_changed' => ['bg' => '#f9fbe7', 'border' => '#9e9d24', 'badge' => '#9e9d24'],
        ];

        ?>
        <div class="wrap zap-dashboard">
            <style>
                .zap-dashboard { background: #f0f2f7; margin: 0 -20px 0 -12px; padding: 20px; min-height: 100vh; }
                .zap-db-header {
                    background: linear-gradient(135deg, #1a237e 0%, #283593 50%, #3949ab 100%);
                    border-radius: 16px;
                    padding: 30px 35px;
                    color: #fff;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 16px;
                    box-shadow: 0 8px 24px rgba(26,35,126,0.25);
                }
                .zap-db-header h1 { margin: 0; font-size: 1.8rem; color: #fff; font-weight: 700; }
                .zap-db-header p  { margin: 4px 0 0; opacity: 0.85; font-size: 0.95rem; }
                .zap-db-header .period-form select {
                    padding: 8px 14px;
                    border-radius: 8px;
                    border: 2px solid rgba(255,255,255,0.4);
                    background: rgba(255,255,255,0.15);
                    color: #fff;
                    font-size: 0.9rem;
                    cursor: pointer;
                }
                .zap-db-header .period-form select option { background: #283593; color: #fff; }

                /* KPI Cards row */
                .zap-kpi-row {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                    gap: 16px;
                    margin-bottom: 24px;
                }
                .zap-kpi {
                    background: #fff;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
                    border-left: 4px solid #3949ab;
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }
                .zap-kpi .kpi-icon { font-size: 1.8rem; }
                .zap-kpi .kpi-value { font-size: 2rem; font-weight: 700; color: #1a237e; line-height: 1; }
                .zap-kpi .kpi-label { font-size: 0.8rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
                .zap-kpi.green  { border-color: #43a047; }
                .zap-kpi.green  .kpi-value { color: #2e7d32; }
                .zap-kpi.red    { border-color: #e53935; }
                .zap-kpi.red    .kpi-value { color: #c62828; }
                .zap-kpi.orange { border-color: #fb8c00; }
                .zap-kpi.orange .kpi-value { color: #e65100; }
                .zap-kpi.teal   { border-color: #00acc1; }
                .zap-kpi.teal   .kpi-value { color: #006064; }

                /* Main grid */
                .zap-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
                    gap: 20px;
                    margin-bottom: 24px;
                }
                .zap-card {
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
                    overflow: hidden;
                }
                .zap-card-header {
                    padding: 16px 20px;
                    background: linear-gradient(90deg, #3949ab, #5c6bc0);
                    color: #fff;
                    font-size: 1rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .zap-card-header.green  { background: linear-gradient(90deg, #43a047, #66bb6a); }
                .zap-card-header.orange { background: linear-gradient(90deg, #fb8c00, #ffa726); }
                .zap-card-header.teal   { background: linear-gradient(90deg, #00acc1, #26c6da); }
                .zap-card-header.purple { background: linear-gradient(90deg, #8e24aa, #ab47bc); }
                .zap-card-body { padding: 20px; }

                /* Event list */
                .zap-event-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 10px 12px;
                    border-radius: 8px;
                    margin-bottom: 8px;
                    font-size: 0.88rem;
                }
                .zap-event-item .evt-label { font-weight: 600; }
                .zap-event-item .evt-key   { font-size: 0.75rem; color: #888; font-family: monospace; }
                .zap-event-item .evt-count {
                    font-size: 1.1rem;
                    font-weight: 700;
                    min-width: 50px;
                    text-align: right;
                }
                .zap-event-item .evt-bar-wrap {
                    flex: 1;
                    margin: 0 12px;
                    height: 6px;
                    background: rgba(0,0,0,0.07);
                    border-radius: 3px;
                }
                .zap-event-item .evt-bar { height: 100%; border-radius: 3px; }

                /* Users table */
                .zap-user-row { display: flex; align-items: center; gap: 12px; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
                .zap-user-row:last-child { border-bottom: none; }
                .zap-user-avatar {
                    width: 36px; height: 36px; border-radius: 50%;
                    background: linear-gradient(135deg, #3949ab, #7986cb);
                    color: #fff;
                    display: flex; align-items: center; justify-content: center;
                    font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
                }
                .zap-user-name { font-weight: 600; font-size: 0.9rem; }
                .zap-user-id   { font-size: 0.75rem; color: #999; }
                .zap-user-count {
                    margin-left: auto;
                    background: #e8eaf6; color: #3949ab;
                    padding: 2px 10px; border-radius: 20px;
                    font-weight: 700; font-size: 0.9rem;
                }

                /* Webhook status */
                .zap-status-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f0f0f0; }
                .zap-status-row:last-child { border-bottom: none; }
                .zap-status-label { font-size: 0.9rem; color: #555; }
                .zap-status-value { font-weight: 700; font-size: 1rem; }

                /* Timeline bar chart */
                .zap-timeline {
                    display: flex;
                    align-items: flex-end;
                    height: 160px;
                    gap: 3px;
                    padding-bottom: 24px;
                    position: relative;
                    border-bottom: 2px solid #e0e0e0;
                }
                .zap-tbar-wrap {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: flex-end;
                    height: 100%;
                    position: relative;
                }
                .zap-tbar {
                    width: 100%;
                    background: linear-gradient(180deg, #5c6bc0, #3949ab);
                    border-radius: 3px 3px 0 0;
                    min-height: 3px;
                    transition: opacity 0.2s;
                    cursor: pointer;
                    position: relative;
                }
                .zap-tbar:hover { opacity: 0.8; }
                .zap-tbar-tooltip {
                    display: none;
                    position: absolute;
                    bottom: calc(100% + 6px);
                    left: 50%;
                    transform: translateX(-50%);
                    background: #1a237e;
                    color: #fff;
                    padding: 4px 8px;
                    border-radius: 6px;
                    font-size: 0.75rem;
                    white-space: nowrap;
                    z-index: 10;
                }
                .zap-tbar:hover .zap-tbar-tooltip { display: block; }
                .zap-tbar-label {
                    position: absolute;
                    bottom: -22px;
                    font-size: 9px;
                    color: #888;
                    transform: rotate(-45deg);
                    white-space: nowrap;
                    transform-origin: top center;
                }

                /* Completion donut-like */
                .zap-completion-row { display: flex; gap: 16px; flex-wrap: wrap; }
                .zap-completion-stat {
                    flex: 1;
                    min-width: 100px;
                    text-align: center;
                    padding: 16px 12px;
                    border-radius: 10px;
                    background: #f7f9ff;
                    border: 1px solid #e8eaf6;
                }
                .zap-completion-stat .cs-value { font-size: 1.8rem; font-weight: 700; color: #1a237e; line-height: 1; }
                .zap-completion-stat .cs-label { font-size: 0.75rem; color: #666; margin-top: 4px; }
                .zap-completion-rate {
                    text-align: center;
                    margin-top: 16px;
                    padding: 12px;
                    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
                    border-radius: 10px;
                    border: 1px solid #a5d6a7;
                }
                .zap-completion-rate .rate-val { font-size: 2.2rem; font-weight: 700; color: #2e7d32; }
                .zap-completion-rate .rate-label { font-size: 0.8rem; color: #4caf50; font-weight: 600; }
                .zap-wide { grid-column: 1 / -1; }
            </style>

            <!-- Header -->
            <div class="zap-db-header">
                <div>
                    <h1>📊 ZAP Tutor Events — Dashboard</h1>
                    <p>Monitoramento de eventos e automações Tutor LMS</p>
                </div>
                <form class="period-form" method="get">
                    <input type="hidden" name="page" value="zap-tutor-events">
                    <select name="days" onchange="this.form.submit()">
                        <option value="7"  <?php selected($days, 7); ?>>Últimos 7 dias</option>
                        <option value="30" <?php selected($days, 30); ?>>Últimos 30 dias</option>
                        <option value="60" <?php selected($days, 60); ?>>Últimos 60 dias</option>
                        <option value="90" <?php selected($days, 90); ?>>Últimos 90 dias</option>
                    </select>
                </form>
            </div>

            <?php \ZapTutorEvents\Admin::render_tab_nav( 'zap-tutor-events' ); ?>

            <div class="zap-dashboard-actions">
                <a class="zap-btn zap-btn-primary" href="<?php echo esc_url(admin_url('admin.php?page=zap-tutor-events-webhooks')); ?>">➕ Novo Webhook</a>
                <a class="zap-btn zap-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=zap-tutor-events-logs')); ?>">📋 Ver Logs</a>
                <a class="zap-btn zap-btn-secondary" href="<?php echo esc_url(admin_url('admin.php?page=zap-tutor-events-settings')); ?>">⚙️ Configurações</a>
            </div>

            <div class="zap-health-grid">
                <div class="zap-health-item">
                    <span class="zap-health-item__label">Logs de eventos</span>
                    <strong class="zap-health-item__value <?php echo $log_enabled ? 'is-ok' : 'is-off'; ?>"><?php echo $log_enabled ? 'Ativos' : 'Desativados'; ?></strong>
                </div>
                <div class="zap-health-item">
                    <span class="zap-health-item__label">Fila assíncrona</span>
                    <strong class="zap-health-item__value <?php echo $use_queue ? 'is-ok' : 'is-off'; ?>"><?php echo $use_queue ? 'Ligada' : 'Desligada'; ?></strong>
                </div>
                <div class="zap-health-item">
                    <span class="zap-health-item__label">Webhooks ativos</span>
                    <strong class="zap-health-item__value is-ok"><?php echo esc_html($active_webhooks); ?></strong>
                </div>
            </div>

            <!-- KPI row -->
            <div class="zap-kpi-row">
                <div class="zap-kpi">
                    <span class="kpi-icon">⚡</span>
                    <span class="kpi-value"><?php echo number_format_i18n($total_events); ?></span>
                    <span class="kpi-label">Total de Eventos</span>
                </div>
                <div class="zap-kpi green">
                    <span class="kpi-icon">✅</span>
                    <span class="kpi-value"><?php echo number_format_i18n($webhook_stats['successful']); ?></span>
                    <span class="kpi-label">Webhooks OK</span>
                </div>
                <div class="zap-kpi red">
                    <span class="kpi-icon">❌</span>
                    <span class="kpi-value"><?php echo number_format_i18n($webhook_stats['failed']); ?></span>
                    <span class="kpi-label">Webhooks Falhos</span>
                </div>
                <div class="zap-kpi orange">
                    <span class="kpi-icon">📚</span>
                    <span class="kpi-value"><?php echo number_format_i18n($course_completion['enrolled']); ?></span>
                    <span class="kpi-label">Matrículas</span>
                </div>
                <div class="zap-kpi teal">
                    <span class="kpi-icon">🎓</span>
                    <span class="kpi-value"><?php echo number_format_i18n($course_completion['completed']); ?></span>
                    <span class="kpi-label">Cursos Concluídos</span>
                </div>
                <div class="zap-kpi green">
                    <span class="kpi-icon">📈</span>
                    <span class="kpi-value"><?php echo $webhook_stats['success_rate']; ?>%</span>
                    <span class="kpi-label">Taxa de Sucesso</span>
                </div>
            </div>

            <!-- Main grid -->
            <div class="zap-grid">

                <!-- Event stats card -->
                <div class="zap-card">
                    <div class="zap-card-header">📋 Eventos por Tipo <small style="opacity:.7;font-weight:400;">(<?php echo $days; ?> dias)</small></div>
                    <div class="zap-card-body">
                        <?php if (empty($stats)): ?>
                            <p style="color:#888;text-align:center;padding:20px;">Nenhum evento neste período.</p>
                        <?php else: ?>
                            <?php foreach ($stats as $stat):
                                $pct = $total_events > 0 ? round(($stat->count / $total_events) * 100, 1) : 0;
                                $label = $all_events[$stat->event_key] ?? $stat->event_key;
                                $colors = $event_colors[$stat->event_key] ?? ['bg' => '#f5f5f5', 'border' => '#9e9e9e', 'badge' => '#9e9e9e'];
                            ?>
                            <div class="zap-event-item" style="background:<?php echo $colors['bg']; ?>;border-left:4px solid <?php echo $colors['border']; ?>;">
                                <div>
                                    <div class="evt-label"><?php echo esc_html($label); ?></div>
                                    <div class="evt-key"><?php echo esc_html($stat->event_key); ?></div>
                                </div>
                                <div class="evt-bar-wrap">
                                    <div class="evt-bar" style="width:<?php echo $pct; ?>%;background:<?php echo $colors['badge']; ?>;"></div>
                                </div>
                                <div class="evt-count" style="color:<?php echo $colors['badge']; ?>;">
                                    <?php echo number_format_i18n($stat->count); ?>
                                    <div style="font-size:0.7rem;color:#aaa;font-weight:400;"><?php echo $pct; ?>%</div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Webhook status -->
                <div class="zap-card">
                    <div class="zap-card-header teal">🔗 Status de Webhooks</div>
                    <div class="zap-card-body">
                        <div class="zap-status-row">
                            <span class="zap-status-label">📤 Total Enviado</span>
                            <span class="zap-status-value"><?php echo number_format_i18n($webhook_stats['total']); ?></span>
                        </div>
                        <div class="zap-status-row">
                            <span class="zap-status-label" style="color:#2e7d32;">✅ Sucesso</span>
                            <span class="zap-status-value" style="color:#2e7d32;"><?php echo number_format_i18n($webhook_stats['successful']); ?></span>
                        </div>
                        <div class="zap-status-row">
                            <span class="zap-status-label" style="color:#c62828;">❌ Falhas</span>
                            <span class="zap-status-value" style="color:#c62828;"><?php echo number_format_i18n($webhook_stats['failed']); ?></span>
                        </div>
                        <div class="zap-status-row">
                            <span class="zap-status-label">📊 Taxa de Sucesso</span>
                            <span class="zap-status-value" style="color:<?php echo $webhook_stats['success_rate'] >= 80 ? '#2e7d32' : '#c62828'; ?>;"><?php echo $webhook_stats['success_rate']; ?>%</span>
                        </div>
                        <?php if ($webhook_stats['total'] > 0): ?>
                        <div style="margin-top:16px;">
                            <div style="background:#e0e0e0;border-radius:6px;height:10px;overflow:hidden;">
                                <div style="width:<?php echo $webhook_stats['success_rate']; ?>%;height:100%;background:linear-gradient(90deg,#43a047,#66bb6a);border-radius:6px;transition:width 0.5s;"></div>
                            </div>
                            <div style="font-size:0.75rem;color:#888;margin-top:4px;text-align:center;">Taxa de sucesso geral</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Course completion -->
                <div class="zap-card">
                    <div class="zap-card-header green">🎓 Conclusão de Cursos</div>
                    <div class="zap-card-body">
                        <div class="zap-completion-row">
                            <div class="zap-completion-stat">
                                <div class="cs-value" style="color:#fb8c00;"><?php echo number_format_i18n($course_completion['enrolled']); ?></div>
                                <div class="cs-label">📚 Matrículas</div>
                            </div>
                            <div class="zap-completion-stat">
                                <div class="cs-value" style="color:#00acc1;"><?php echo number_format_i18n($course_completion['half_completed']); ?></div>
                                <div class="cs-label">⏳ 50% Concluídos</div>
                            </div>
                            <div class="zap-completion-stat">
                                <div class="cs-value" style="color:#43a047;"><?php echo number_format_i18n($course_completion['completed']); ?></div>
                                <div class="cs-label">✅ 100% Concluídos</div>
                            </div>
                        </div>
                        <div class="zap-completion-rate">
                            <div class="rate-val"><?php echo $course_completion['completion_rate']; ?>%</div>
                            <div class="rate-label">Taxa de conclusão (<?php echo $days; ?> dias)</div>
                        </div>
                    </div>
                </div>

                <!-- Top users -->
                <div class="zap-card">
                    <div class="zap-card-header purple">👥 Usuários Mais Ativos</div>
                    <div class="zap-card-body">
                        <?php if (empty($top_users)): ?>
                            <p style="color:#888;text-align:center;padding:20px;">Nenhum usuário ativo neste período.</p>
                        <?php else: ?>
                            <?php foreach ($top_users as $i => $user):
                                $user_data = get_userdata($user->user_id);
                                $user_name = $user_data ? $user_data->display_name : "User #{$user->user_id}";
                                $initials = mb_strtoupper(mb_substr($user_name, 0, 1));
                            ?>
                                <div class="zap-user-row">
                                    <div class="zap-user-avatar"><?php echo esc_html($initials); ?></div>
                                    <div>
                                        <div class="zap-user-name"><?php echo esc_html($user_name); ?></div>
                                        <div class="zap-user-id">ID: <?php echo $user->user_id; ?></div>
                                    </div>
                                    <span class="zap-user-count"><?php echo number_format_i18n($user->event_count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Timeline - full width -->
                <div class="zap-card zap-wide">
                    <div class="zap-card-header">📅 Linha do Tempo de Eventos (<?php echo $days; ?> dias)</div>
                    <div class="zap-card-body">
                        <?php if (empty($timeline)): ?>
                            <p style="color:#888;text-align:center;padding:20px;">Nenhum evento neste período.</p>
                        <?php else: ?>
                            <div class="zap-timeline">
                                <?php
                                $max_count = max(1, max(array_column($timeline, 'count')));
                                foreach ($timeline as $day):
                                    $height = max(3, ($day->count / $max_count) * 100);
                                ?>
                                    <div class="zap-tbar-wrap">
                                        <div class="zap-tbar" style="height:<?php echo $height; ?>%;">
                                            <span class="zap-tbar-tooltip"><?php echo date('d/m', strtotime($day->date)); ?>: <?php echo number_format_i18n($day->count); ?></span>
                                        </div>
                                        <div class="zap-tbar-label"><?php echo date('d/m', strtotime($day->date)); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /.zap-grid -->
        </div><!-- /.zap-dashboard -->
        <?php
    }

    /**
     * Get event statistics
     * 
     * @param int $days Number of days
     * @return array Statistics
     */
    private static function get_event_stats($days) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT event_key, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY event_key 
             ORDER BY count DESC",
            $date_limit
        ));
    }

    /**
     * Get top active users
     * 
     * @param int $days Number of days
     * @return array Top users
     */
    private static function get_top_users($days) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT user_id, COUNT(*) as event_count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY user_id 
             ORDER BY event_count DESC 
             LIMIT 10",
            $date_limit
        ));
    }

    /**
     * Get course completion rate
     * 
     * @param int $days Number of days
     * @return array Completion statistics
     */
    private static function get_course_completion_rate($days) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $enrolled = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE event_key = 'tutor_course_enrolled' AND created_at >= %s",
            $date_limit
        ));

        $half_completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE event_key = 'tutor_course_progress_50' AND created_at >= %s",
            $date_limit
        ));

        $completed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} 
             WHERE event_key = 'tutor_course_completed' AND created_at >= %s",
            $date_limit
        ));

        $completion_rate = $enrolled > 0 ? round(($completed / $enrolled) * 100, 1) : 0;

        return [
            'enrolled'         => (int) $enrolled,
            'half_completed'   => (int) $half_completed,
            'completed'        => (int) $completed,
            'completion_rate'  => $completion_rate,
        ];
    }

    /**
     * Get event timeline
     * 
     * @param int $days Number of days
     * @return array Daily event counts
     */
    private static function get_event_timeline($days) {
        global $wpdb;
        $table = $wpdb->prefix . 'zap_event_logs';
        $date_limit = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$table} 
             WHERE created_at >= %s 
             GROUP BY DATE(created_at) 
             ORDER BY date ASC",
            $date_limit
        ));
    }
}
