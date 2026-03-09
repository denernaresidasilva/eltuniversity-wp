<?php
/**
 * Pipelines admin page (Kanban board).
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Pipelines {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-columns"></span>
                    <?php esc_html_e( 'Pipelines', 'ai-sales-engine' ); ?>
                </h1>
                <button class="button button-primary" id="aise-add-pipeline-btn">
                    + <?php esc_html_e( 'New Pipeline', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-pipeline-selector aise-card">
                <label for="aise-pipeline-select">
                    <?php esc_html_e( 'Select pipeline:', 'ai-sales-engine' ); ?>
                </label>
                <select id="aise-pipeline-select" class="aise-select">
                    <option value=""><?php esc_html_e( '— Choose —', 'ai-sales-engine' ); ?></option>
                </select>
            </div>

            <div class="aise-kanban-board" id="aise-kanban-board">
                <p class="description">
                    <?php esc_html_e( 'Select a pipeline to view the board.', 'ai-sales-engine' ); ?>
                </p>
            </div>
        </div>
        <?php
    }
}
