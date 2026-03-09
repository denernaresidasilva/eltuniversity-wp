<?php
/**
 * Lists admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Lists {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e( 'Contact Lists', 'ai-sales-engine' ); ?>
                </h1>
                <button class="button button-primary" id="aise-add-list-btn">
                    + <?php esc_html_e( 'New List', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Webhook URL', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Created', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'ai-sales-engine' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="aise-lists-tbody">
                        <tr><td colspan="5"><?php esc_html_e( 'Loading…', 'ai-sales-engine' ); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
