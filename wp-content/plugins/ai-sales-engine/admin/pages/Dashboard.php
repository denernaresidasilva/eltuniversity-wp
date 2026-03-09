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
            <h1 class="aise-page-title">
                <span class="dashicons dashicons-chart-line"></span>
                <?php esc_html_e( 'Dashboard', 'ai-sales-engine' ); ?>
            </h1>

            <div class="aise-stats-grid" id="aise-stats-grid">
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'Total Leads', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-total-leads">–</span>
                </div>
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'New Today', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-new-today">–</span>
                </div>
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'Total Events', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-total-events">–</span>
                </div>
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'Avg. Lead Score', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-avg-score">–</span>
                </div>
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'Active Automations', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-automations">–</span>
                </div>
                <div class="aise-stat-card aise-loading">
                    <span class="aise-stat-label"><?php esc_html_e( 'Pending Jobs', 'ai-sales-engine' ); ?></span>
                    <span class="aise-stat-value" id="stat-pending-jobs">–</span>
                </div>
            </div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Events (last 7 days)', 'ai-sales-engine' ); ?></h2>
                <canvas id="aise-events-chart" height="80"></canvas>
            </div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Top Lead Sources', 'ai-sales-engine' ); ?></h2>
                <table class="widefat striped" id="aise-sources-table">
                    <thead><tr>
                        <th><?php esc_html_e( 'Source', 'ai-sales-engine' ); ?></th>
                        <th><?php esc_html_e( 'Leads', 'ai-sales-engine' ); ?></th>
                    </tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
