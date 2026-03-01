<?php
namespace ZapWA\Admin\Pages;

use ZapWA\Metrics as MetricsCore;

if (!defined('ABSPATH')) {
    exit;
}

class Metrics {

    public static function render() {
        
        $summary      = MetricsCore::summary();
        $events       = MetricsCore::events();
        $logs         = MetricsCore::last_logs();
        $current_user = wp_get_current_user();
        $display_name = ( $current_user->ID > 0 && ! empty( $current_user->display_name ) )
            ? $current_user->display_name
            : ( ! empty( $current_user->user_login ) ? $current_user->user_login : '' );

        $total   = (int) $summary['total'];
        $sent    = (int) $summary['sent'];
        $errors  = (int) $summary['error'];
        $success_rate = $total > 0 ? round(($sent / $total) * 100, 1) : 0;
        ?>

        <div class="wrap zapwa-metrics">
            <style>
                .zapwa-metrics { background:#f0f7f4; margin:0 -20px 0 -12px; padding:20px; min-height:100vh; }

                /* Header */
                .zapwa-header {
                    background: linear-gradient(135deg, #075e54 0%, #128c7e 60%, #25d366 100%);
                    border-radius: 16px;
                    padding: 28px 35px;
                    color: #fff;
                    margin-bottom: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 16px;
                    box-shadow: 0 8px 24px rgba(7,94,84,0.25);
                }
                .zapwa-header h1 { margin:0; font-size:1.8rem; color:#fff; font-weight:700; }
                .zapwa-header .sub { opacity:.88; font-size:.95rem; margin:4px 0 0; }
                .zapwa-header .greeting {
                    background: rgba(255,255,255,0.18);
                    border-radius: 8px;
                    padding: 6px 14px;
                    font-size: 0.9rem;
                    font-weight: 600;
                }

                /* KPI row */
                .zapwa-kpi-row {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                    gap: 16px;
                    margin-bottom: 24px;
                }
                .zapwa-kpi {
                    background: #fff;
                    border-radius: 12px;
                    padding: 20px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.07);
                    border-left: 4px solid #25d366;
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }
                .zapwa-kpi .icon  { font-size: 1.8rem; }
                .zapwa-kpi .val   { font-size: 2rem; font-weight: 700; color: #075e54; line-height: 1; }
                .zapwa-kpi .lbl   { font-size: 0.78rem; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
                .zapwa-kpi.green  { border-color: #25d366; } .zapwa-kpi.green .val  { color: #075e54; }
                .zapwa-kpi.red    { border-color: #e53935; } .zapwa-kpi.red   .val  { color: #c62828; }
                .zapwa-kpi.orange { border-color: #fb8c00; } .zapwa-kpi.orange .val { color: #e65100; }
                .zapwa-kpi.blue   { border-color: #1e88e5; } .zapwa-kpi.blue  .val  { color: #1565c0; }

                /* Grid */
                .zapwa-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
                    gap: 20px;
                }
                .zapwa-card {
                    background: #fff;
                    border-radius: 14px;
                    box-shadow: 0 2px 12px rgba(0,0,0,0.07);
                    overflow: hidden;
                }
                .zapwa-card-hdr {
                    padding: 14px 20px;
                    background: linear-gradient(90deg, #075e54, #128c7e);
                    color: #fff;
                    font-size: 1rem;
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .zapwa-card-hdr.green  { background: linear-gradient(90deg, #25d366, #128c7e); }
                .zapwa-card-hdr.red    { background: linear-gradient(90deg, #e53935, #ef5350); }
                .zapwa-card-hdr.blue   { background: linear-gradient(90deg, #1565c0, #1e88e5); }
                .zapwa-card-body { padding: 20px; }

                /* Event rows */
                .zapwa-event-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 8px 10px;
                    border-radius: 8px;
                    margin-bottom: 6px;
                    background: #f0faf5;
                    border-left: 3px solid #25d366;
                }
                .zapwa-event-name { flex: 1; font-size: 0.88rem; font-weight: 600; color: #075e54; }
                .zapwa-event-bar-wrap { flex: 2; height: 6px; background: #ddd; border-radius: 3px; }
                .zapwa-event-bar { height: 100%; border-radius: 3px; background: linear-gradient(90deg, #25d366, #128c7e); }
                .zapwa-event-count {
                    min-width: 40px; text-align: right;
                    font-weight: 700; font-size: 1rem; color: #075e54;
                }

                /* Log rows */
                .zapwa-log-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 10px 12px;
                    border-radius: 8px;
                    margin-bottom: 6px;
                    background: #f9f9f9;
                    font-size: 0.85rem;
                    border-left: 3px solid #ccc;
                }
                .zapwa-log-row.ok  { background: #e8f5e9; border-color: #43a047; }
                .zapwa-log-row.err { background: #ffebee; border-color: #e53935; }
                .zapwa-log-date  { color: #888; font-size: 0.78rem; min-width: 120px; }
                .zapwa-log-event { font-weight: 600; flex: 1; }
                .zapwa-log-phone { color: #555; }
                .zapwa-badge {
                    padding: 2px 8px;
                    border-radius: 20px;
                    font-size: 0.72rem;
                    font-weight: 700;
                    text-transform: uppercase;
                }
                .zapwa-badge.ok  { background: #c8e6c9; color: #2e7d32; }
                .zapwa-badge.err { background: #ffcdd2; color: #c62828; }
                .zapwa-badge.pnd { background: #fff9c4; color: #f57f17; }

                /* Progress bar */
                .zapwa-progress { margin-top: 10px; }
                .zapwa-progress-bar { background: #ddd; border-radius: 8px; height: 12px; overflow: hidden; }
                .zapwa-progress-fill { height: 100%; border-radius: 8px; background: linear-gradient(90deg, #25d366, #128c7e); transition: width .5s; }
                .zapwa-progress-label { font-size: 0.78rem; color: #888; margin-top: 4px; text-align: center; }

                .zapwa-wide { grid-column: 1 / -1; }
            </style>

            <!-- Header -->
            <div class="zapwa-header">
                <div>
                    <h1>💬 ZAP WhatsApp Automation</h1>
                    <p class="sub">Monitoramento de envios e automações WhatsApp</p>
                </div>
                <?php if ($display_name): ?>
                    <span class="greeting">👋 Olá, <?php echo esc_html($display_name); ?>!</span>
                <?php endif; ?>
            </div>

            <!-- KPI Row -->
            <div class="zapwa-kpi-row">
                <div class="zapwa-kpi green">
                    <span class="icon">✅</span>
                    <span class="val"><?php echo number_format_i18n($sent); ?></span>
                    <span class="lbl">Mensagens Enviadas</span>
                </div>
                <div class="zapwa-kpi red">
                    <span class="icon">❌</span>
                    <span class="val"><?php echo number_format_i18n($errors); ?></span>
                    <span class="lbl">Erros de Envio</span>
                </div>
                <div class="zapwa-kpi blue">
                    <span class="icon">📦</span>
                    <span class="val"><?php echo number_format_i18n($summary['total']); ?></span>
                    <span class="lbl">Total Processados</span>
                </div>
                <div class="zapwa-kpi <?php echo $success_rate >= 80 ? 'green' : ($success_rate >= 50 ? 'orange' : 'red'); ?>">
                    <span class="icon">📊</span>
                    <span class="val"><?php echo $success_rate; ?>%</span>
                    <span class="lbl">Taxa de Sucesso</span>
                </div>
            </div>

            <!-- Grid -->
            <div class="zapwa-grid">

                <!-- Events card -->
                <div class="zapwa-card">
                    <div class="zapwa-card-hdr">⚡ Eventos Mais Usados</div>
                    <div class="zapwa-card-body">
                        <?php if (empty($events)): ?>
                            <p style="color:#888;text-align:center;padding:20px;">Nenhum evento registrado ainda.</p>
                        <?php else:
                            $max_evt = max(1, max(array_column((array) $events, 'total')));
                            foreach ($events as $e):
                                $pct = round(($e->total / $max_evt) * 100);
                        ?>
                            <div class="zapwa-event-row">
                                <span class="zapwa-event-name">📌 <?php echo esc_html($e->event); ?></span>
                                <div class="zapwa-event-bar-wrap">
                                    <div class="zapwa-event-bar" style="width:<?php echo $pct; ?>%;"></div>
                                </div>
                                <span class="zapwa-event-count"><?php echo number_format_i18n($e->total); ?></span>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>

                <!-- Success rate card -->
                <div class="zapwa-card">
                    <div class="zapwa-card-hdr green">📈 Taxa de Entrega</div>
                    <div class="zapwa-card-body">
                        <div style="text-align:center;padding:10px 0;">
                            <div style="font-size:3rem;font-weight:700;color:<?php echo $success_rate >= 80 ? '#075e54' : ($success_rate >= 50 ? '#e65100' : '#c62828'); ?>;">
                                <?php echo $success_rate; ?>%
                            </div>
                            <div style="color:#888;font-size:0.9rem;margin-top:4px;">Taxa de sucesso geral</div>
                        </div>
                        <div class="zapwa-progress">
                            <div class="zapwa-progress-bar">
                                <div class="zapwa-progress-fill" style="width:<?php echo $success_rate; ?>%;background:<?php echo $success_rate >= 80 ? 'linear-gradient(90deg,#25d366,#128c7e)' : ($success_rate >= 50 ? 'linear-gradient(90deg,#fb8c00,#ffa726)' : 'linear-gradient(90deg,#e53935,#ef5350)'); ?>;"></div>
                            </div>
                            <div class="zapwa-progress-label"><?php echo number_format_i18n($sent); ?> de <?php echo number_format_i18n($summary['total']); ?> mensagens entregues</div>
                        </div>
                        <div style="display:flex;justify-content:space-around;margin-top:20px;">
                            <div style="text-align:center;">
                                <div style="font-size:1.4rem;font-weight:700;color:#2e7d32;">✅ <?php echo number_format_i18n($sent); ?></div>
                                <div style="font-size:0.75rem;color:#888;">Enviadas</div>
                            </div>
                            <div style="text-align:center;">
                                <div style="font-size:1.4rem;font-weight:700;color:#c62828;">❌ <?php echo number_format_i18n($errors); ?></div>
                                <div style="font-size:0.75rem;color:#888;">Erros</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent logs - full width -->
                <div class="zapwa-card zapwa-wide">
                    <div class="zapwa-card-hdr blue">🕐 Últimos Disparos</div>
                    <div class="zapwa-card-body">
                        <?php if (empty($logs)): ?>
                            <p style="color:#888;text-align:center;padding:20px;">Nenhum disparo registrado ainda.</p>
                        <?php else: ?>
                            <?php foreach ($logs as $log):
                                $is_ok = ($log->status === 'enviado');
                                $is_err = ($log->status === 'erro');
                                $row_cls = $is_ok ? 'ok' : ($is_err ? 'err' : '');
                                $badge_cls = $is_ok ? 'ok' : ($is_err ? 'err' : 'pnd');
                            ?>
                                <div class="zapwa-log-row <?php echo $row_cls; ?>">
                                    <span class="zapwa-log-date">📅 <?php echo esc_html($log->created_at); ?></span>
                                    <span class="zapwa-log-event">⚡ <?php echo esc_html($log->event); ?></span>
                                    <span class="zapwa-log-phone">📱 <?php echo esc_html($log->phone); ?></span>
                                    <span class="zapwa-badge <?php echo $badge_cls; ?>"><?php echo esc_html($log->status); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div><!-- /.zapwa-grid -->
        </div><!-- /.zapwa-metrics -->
        <?php
    }
}

