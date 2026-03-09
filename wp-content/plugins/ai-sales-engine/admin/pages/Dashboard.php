<?php
/**
 * Dashboard admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e( 'Painel', 'ai-sales-engine' ); ?>
                </h1>
            </div>

            <div class="aise-stats-grid" id="aise-stats-grid">
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-groups"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Total de Leads', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-total-leads">–</span>
                    </div>
                </div>
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-plus-alt"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Novos Hoje', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-new-today">–</span>
                    </div>
                </div>
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-bell"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Total de Eventos', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-total-events">–</span>
                    </div>
                </div>
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-star-filled"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Score Médio', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-avg-score">–</span>
                    </div>
                </div>
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-randomize"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Automações Ativas', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-automations">–</span>
                    </div>
                </div>
                <div class="aise-stat-card aise-loading">
                    <div class="aise-stat-icon"><span class="dashicons dashicons-clock"></span></div>
                    <div class="aise-stat-info">
                        <span class="aise-stat-label"><?php esc_html_e( 'Tarefas Pendentes', 'ai-sales-engine' ); ?></span>
                        <span class="aise-stat-value" id="stat-pending-jobs">–</span>
                    </div>
                </div>
            </div>

            <div class="aise-two-col">
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Eventos (últimos 7 dias)', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-events-chart" height="100"></canvas>
                </div>
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Principais Fontes de Leads', 'ai-sales-engine' ); ?></h2>
                    <table class="aise-table" id="aise-sources-table">
                        <thead><tr>
                            <th><?php esc_html_e( 'Fonte', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Leads', 'ai-sales-engine' ); ?></th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
