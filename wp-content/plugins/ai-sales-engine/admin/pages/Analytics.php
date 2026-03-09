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
            <h1 class="aise-page-title">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e( 'Analytics', 'ai-sales-engine' ); ?>
            </h1>

            <div class="aise-stats-grid" id="aise-analytics-stats"></div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Lead Acquisition (last 7 days)', 'ai-sales-engine' ); ?></h2>
                <canvas id="aise-leads-chart" height="80"></canvas>
            </div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Event Activity (last 7 days)', 'ai-sales-engine' ); ?></h2>
                <canvas id="aise-analytics-events-chart" height="80"></canvas>
            </div>

            <div class="aise-two-col">
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Top Sources', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-sources-chart" height="120"></canvas>
                </div>
                <div class="aise-card">
                    <h2><?php esc_html_e( 'Lead Score Distribution', 'ai-sales-engine' ); ?></h2>
                    <canvas id="aise-score-chart" height="120"></canvas>
                </div>
            </div>
        </div>
        <?php
    }
}
