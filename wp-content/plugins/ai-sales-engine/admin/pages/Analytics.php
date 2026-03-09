<?php
/**
 * Analytics admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Analytics {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e( 'Relatórios', 'ai-sales-engine' ); ?>
                </h1>
            </div>

            <div class="aise-stats-grid" id="aise-analytics-stats"></div>

            <div class="aise-two-col">
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Captação de Leads (últimos 7 dias)', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-leads-chart" height="100"></canvas>
                </div>
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Atividade de Eventos (últimos 7 dias)', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-analytics-events-chart" height="100"></canvas>
                </div>
            </div>

            <div class="aise-two-col">
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Principais Fontes', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-sources-chart" height="120"></canvas>
                </div>
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Distribuição de Score', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-score-chart" height="120"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
}
