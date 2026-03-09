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
                <button class="aise-btn aise-btn-primary" id="aise-add-lead-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Novo Lead', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <div class="aise-table-toolbar">
                    <div class="aise-search-wrap">
                        <span class="dashicons dashicons-search"></span>
                        <input type="search" id="aise-lead-search"
                               placeholder="<?php esc_attr_e( 'Buscar por nome, email ou telefone…', 'ai-sales-engine' ); ?>"
                               class="aise-search-input" />
                    </div>
                    <select id="aise-lead-status-filter" class="aise-select">
                        <option value=""><?php esc_html_e( 'Todos os status', 'ai-sales-engine' ); ?></option>
                        <option value="active"><?php esc_html_e( 'Ativo', 'ai-sales-engine' ); ?></option>
                        <option value="inactive"><?php esc_html_e( 'Inativo', 'ai-sales-engine' ); ?></option>
                    </select>
                </div>

                <div class="aise-table-responsive">
                    <table class="aise-table aise-leads-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Email', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Telefone', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Score', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Criado em', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Ações', 'ai-sales-engine' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aise-leads-tbody">
                            <tr><td colspan="7" class="aise-loading-row"><?php esc_html_e( 'Carregando…', 'ai-sales-engine' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="aise-pagination" id="aise-leads-pagination"></div>
            </div>

            <!-- Modal: Novo/Editar Lead -->
            <div class="aise-modal" id="aise-lead-modal" style="display:none;">
                <div class="aise-modal-backdrop"></div>
                <div class="aise-modal-dialog">
                    <div class="aise-modal-header">
                        <h3 id="aise-lead-modal-title"><?php esc_html_e( 'Novo Lead', 'ai-sales-engine' ); ?></h3>
                        <button class="aise-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'ai-sales-engine' ); ?>">&#x2715;</button>
                    </div>
                    <div class="aise-modal-body">
                        <form id="aise-lead-form">
                            <input type="hidden" id="lead-id" name="id" value="" />
                            <div class="aise-form-row">
                                <label for="lead-name"><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="lead-name" name="name" class="aise-input" required />
                            </div>
                            <div class="aise-form-row">
                                <label for="lead-email"><?php esc_html_e( 'Email', 'ai-sales-engine' ); ?></label>
                                <input type="email" id="lead-email" name="email" class="aise-input" />
                            </div>
                            <div class="aise-form-row">
                                <label for="lead-phone"><?php esc_html_e( 'Telefone', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="lead-phone" name="phone" class="aise-input" />
                            </div>
                            <div class="aise-form-row">
                                <label for="lead-whatsapp"><?php esc_html_e( 'WhatsApp', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="lead-whatsapp" name="whatsapp" class="aise-input" />
                            </div>
                            <div class="aise-form-row">
                                <label for="lead-source"><?php esc_html_e( 'Fonte', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="lead-source" name="source" class="aise-input" />
                            </div>
                            <div class="aise-form-row">
                                <label for="lead-status"><?php esc_html_e( 'Status', 'ai-sales-engine' ); ?></label>
                                <select id="lead-status" name="status" class="aise-select">
                                    <option value="active"><?php esc_html_e( 'Ativo', 'ai-sales-engine' ); ?></option>
                                    <option value="inactive"><?php esc_html_e( 'Inativo', 'ai-sales-engine' ); ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="aise-modal-footer">
                        <button class="aise-btn aise-btn-ghost aise-modal-close"><?php esc_html_e( 'Cancelar', 'ai-sales-engine' ); ?></button>
                        <button class="aise-btn aise-btn-primary" id="aise-lead-save-btn"><?php esc_html_e( 'Salvar Lead', 'ai-sales-engine' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
