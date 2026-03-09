<?php
/**
 * Leads admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Leads {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-groups"></span>
                    <?php esc_html_e( 'Leads', 'ai-sales-engine' ); ?>
                </h1>
                <button class="button button-primary" id="aise-add-lead-btn">
                    + <?php esc_html_e( 'Add Lead', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <div class="aise-table-toolbar">
                    <input type="search" id="aise-lead-search"
                           placeholder="<?php esc_attr_e( 'Search by name, email or phone…', 'ai-sales-engine' ); ?>"
                           class="aise-search-input" />
                    <select id="aise-lead-status-filter" class="aise-select">
                        <option value=""><?php esc_html_e( 'All statuses', 'ai-sales-engine' ); ?></option>
                        <option value="active"><?php esc_html_e( 'Active', 'ai-sales-engine' ); ?></option>
                        <option value="inactive"><?php esc_html_e( 'Inactive', 'ai-sales-engine' ); ?></option>
                    </select>
                </div>

                <table class="widefat striped aise-leads-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Name', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Email', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Phone', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Score', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Created', 'ai-sales-engine' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'ai-sales-engine' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="aise-leads-tbody">
                        <tr><td colspan="7"><?php esc_html_e( 'Loading…', 'ai-sales-engine' ); ?></td></tr>
                    </tbody>
                </table>

                <div class="aise-pagination" id="aise-leads-pagination"></div>
            </div>
        </div>
        <?php
    }
}
