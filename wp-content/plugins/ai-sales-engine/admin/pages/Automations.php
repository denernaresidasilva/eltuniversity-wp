<?php
/**
 * Automations admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Automations {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-randomize"></span>
                    <?php esc_html_e( 'Automations', 'ai-sales-engine' ); ?>
                </h1>
                <button class="button button-primary" id="aise-add-automation-btn">
                    + <?php esc_html_e( 'New Automation', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Trigger', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Created', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'ai-sales-engine' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="aise-automations-tbody">
                        <tr><td colspan="5"><?php esc_html_e( 'Loading…', 'ai-sales-engine' ); ?></td></tr>
                    </tbody>
                </table>
            </div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Flow Builder', 'ai-sales-engine' ); ?></h2>
                <div id="aise-flow-builder-root">
                    <p class="description">
                        <?php esc_html_e( 'Select an automation above to edit its flow.', 'ai-sales-engine' ); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }
}
