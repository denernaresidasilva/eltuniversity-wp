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
        add_action('admin_menu', [self::class, 'add_dashboard_page'], 15);
    }

    /**
     * Add dashboard page to menu
     */
    public static function add_dashboard_page() {
        add_submenu_page(
            'zap-tutor-events',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'zap-tutor-events-dashboard',
            [self::class, 'render_dashboard']
        );
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

        ?>
        <div class="wrap">
            <h1>Dashboard - ZAP Tutor Events</h1>

            <div class="zap-dashboard-filters">
                <form method="get">
                    <input type="hidden" name="page" value="zap-tutor-events-dashboard">
                    <label>Período: </label>
                    <select name="days" onchange="this.form.submit()">
                        <option value="7" <?php selected($days, 7); ?>>Últimos 7 dias</option>
                        <option value="30" <?php selected($days, 30); ?>>Últimos 30 dias</option>
                        <option value="60" <?php selected($days, 60); ?>>Últimos 60 dias</option>
                        <option value="90" <?php selected($days, 90); ?>>Últimos 90 dias</option>
                    </select>
                </form>
            </div>

            <div class="zap-dashboard-stats">
                
                <!-- Event Statistics -->
                <div class="zap-stats-card">
                    <h2>Eventos por Tipo (<?php echo $days; ?> dias)</h2>
                    <?php if (empty($stats)): ?>
                        <p>Nenhum evento registrado neste período.</p>
                    <?php else: ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Evento</th>
                                    <th>Total</th>
                                    <th>%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_events = array_sum(array_column($stats, 'count'));
                                $all_events = Events::registry();
                                foreach ($stats as $stat): 
                                    $percentage = $total_events > 0 ? round(($stat->count / $total_events) * 100, 1) : 0;
                                    $label = $all_events[$stat->event_key] ?? $stat->event_key;
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($label); ?></strong><br>
                                            <code><?php echo esc_html($stat->event_key); ?></code>
                                        </td>
                                        <td><?php echo number_format_i18n($stat->count); ?></td>
                                        <td><?php echo $percentage; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td><strong><?php echo number_format_i18n($total_events); ?></strong></td>
                                    <td><strong>100%</strong></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Webhook Status -->
                <div class="zap-stats-card">
                    <h2>Status de Webhooks</h2>
                    <table class="widefat">
                        <tr>
                            <th>Total Enviado</th>
                            <td><?php echo number_format_i18n($webhook_stats['total']); ?></td>
                        </tr>
                        <tr>
                            <th>Sucesso</th>
                            <td style="color: green;"><?php echo number_format_i18n($webhook_stats['successful']); ?></td>
                        </tr>
                        <tr>
                            <th>Falhas</th>
                            <td style="color: red;"><?php echo number_format_i18n($webhook_stats['failed']); ?></td>
                        </tr>
                        <tr>
                            <th>Taxa de Sucesso</th>
                            <td><strong><?php echo $webhook_stats['success_rate']; ?>%</strong></td>
                        </tr>
                    </table>
                </div>

                <!-- Top Users -->
                <div class="zap-stats-card">
                    <h2>Usuários Mais Ativos</h2>
                    <?php if (empty($top_users)): ?>
                        <p>Nenhum usuário ativo neste período.</p>
                    <?php else: ?>
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th>Usuário</th>
                                    <th>Eventos</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_users as $user): 
                                    $user_data = get_userdata($user->user_id);
                                    $user_name = $user_data ? $user_data->display_name : "User #{$user->user_id}";
                                ?>
                                    <tr>
                                        <td>
                                            <?php echo esc_html($user_name); ?>
                                            <br><small>ID: <?php echo $user->user_id; ?></small>
                                        </td>
                                        <td><?php echo number_format_i18n($user->event_count); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <!-- Course Completion Rate -->
                <div class="zap-stats-card">
                    <h2>Taxa de Conclusão de Cursos</h2>
                    <table class="widefat">
                        <tr>
                            <th>Matrículas</th>
                            <td><?php echo number_format_i18n($course_completion['enrolled']); ?></td>
                        </tr>
                        <tr>
                            <th>50% Concluídos</th>
                            <td><?php echo number_format_i18n($course_completion['half_completed']); ?></td>
                        </tr>
                        <tr>
                            <th>100% Concluídos</th>
                            <td><?php echo number_format_i18n($course_completion['completed']); ?></td>
                        </tr>
                        <tr>
                            <th>Taxa de Conclusão</th>
                            <td>
                                <strong><?php echo $course_completion['completion_rate']; ?>%</strong>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Event Timeline -->
                <div class="zap-stats-card" style="grid-column: 1 / -1;">
                    <h2>Linha do Tempo de Eventos</h2>
                    <?php if (empty($timeline)): ?>
                        <p>Nenhum evento neste período.</p>
                    <?php else: ?>
                        <div class="zap-timeline-chart">
                            <?php 
                            $max_count = max(array_column($timeline, 'count'));
                            foreach ($timeline as $day): 
                                $height = $max_count > 0 ? ($day->count / $max_count) * 100 : 0;
                            ?>
                                <div class="zap-timeline-bar" title="<?php echo esc_attr($day->date . ': ' . $day->count . ' eventos'); ?>">
                                    <div class="zap-timeline-bar-fill" style="height: <?php echo $height; ?>%;"></div>
                                    <div class="zap-timeline-label"><?php echo date('d/m', strtotime($day->date)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <style>
                .zap-dashboard-filters {
                    margin: 20px 0;
                    padding: 15px;
                    background: white;
                    border: 1px solid #ccc;
                }
                .zap-dashboard-stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                .zap-stats-card {
                    background: white;
                    padding: 20px;
                    border: 1px solid #ccc;
                    border-radius: 4px;
                }
                .zap-stats-card h2 {
                    margin-top: 0;
                    font-size: 16px;
                    border-bottom: 2px solid #2271b1;
                    padding-bottom: 10px;
                }
                .zap-timeline-chart {
                    display: flex;
                    align-items: flex-end;
                    height: 200px;
                    gap: 4px;
                    padding: 20px 0;
                    border-bottom: 2px solid #333;
                }
                .zap-timeline-bar {
                    flex: 1;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    justify-content: flex-end;
                }
                .zap-timeline-bar-fill {
                    width: 100%;
                    background: #2271b1;
                    min-height: 2px;
                    transition: all 0.3s;
                }
                .zap-timeline-bar:hover .zap-timeline-bar-fill {
                    background: #135e96;
                }
                .zap-timeline-label {
                    font-size: 10px;
                    margin-top: 5px;
                    transform: rotate(-45deg);
                    white-space: nowrap;
                }
            </style>
        </div>
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
