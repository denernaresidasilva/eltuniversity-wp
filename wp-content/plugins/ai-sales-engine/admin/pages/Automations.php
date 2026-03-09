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
                    <?php esc_html_e( 'Automações', 'ai-sales-engine' ); ?>
                </h1>
                <button class="aise-btn aise-btn-primary" id="aise-add-automation-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Nova Automação', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <div class="aise-table-responsive">
                    <table class="aise-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Gatilho', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Status', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Criado em', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Ações', 'ai-sales-engine' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aise-automations-tbody">
                            <tr><td colspan="5" class="aise-loading-row"><?php esc_html_e( 'Carregando…', 'ai-sales-engine' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="aise-card">
                <h2><?php esc_html_e( 'Construtor de Fluxo', 'ai-sales-engine' ); ?></h2>
                <div id="aise-flow-builder-root">
                    <p class="aise-empty-hint">
                        <span class="dashicons dashicons-info-outline"></span>
                        <?php esc_html_e( 'Selecione uma automação acima para editar seu fluxo.', 'ai-sales-engine' ); ?>
                    </p>
                </div>
            </div>

            <!-- Modal: Nova Automação -->
            <div class="aise-modal" id="aise-automation-modal" style="display:none;">
                <div class="aise-modal-backdrop"></div>
                <div class="aise-modal-dialog">
                    <div class="aise-modal-header">
                        <h3><?php esc_html_e( 'Nova Automação', 'ai-sales-engine' ); ?></h3>
                        <button class="aise-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'ai-sales-engine' ); ?>">&#x2715;</button>
                    </div>
                    <div class="aise-modal-body">
                        <form id="aise-automation-form">
                            <div class="aise-form-row">
                                <label for="automation-name"><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="automation-name" name="name" class="aise-input" required />
                            </div>
                            <div class="aise-form-row">
                                <label for="automation-trigger"><?php esc_html_e( 'Gatilho', 'ai-sales-engine' ); ?></label>
                                <select id="automation-trigger" name="trigger_type" class="aise-select">
                                    <option value="page_visit"><?php esc_html_e( 'Visita à Página', 'ai-sales-engine' ); ?></option>
                                    <option value="message_reply"><?php esc_html_e( 'Resposta de Mensagem', 'ai-sales-engine' ); ?></option>
                                    <option value="webinar_completed"><?php esc_html_e( 'Webinar Concluído', 'ai-sales-engine' ); ?></option>
                                    <option value="purchase_completed"><?php esc_html_e( 'Compra Concluída', 'ai-sales-engine' ); ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="aise-modal-footer">
                        <button class="aise-btn aise-btn-ghost aise-modal-close"><?php esc_html_e( 'Cancelar', 'ai-sales-engine' ); ?></button>
                        <button class="aise-btn aise-btn-primary" id="aise-automation-save-btn"><?php esc_html_e( 'Criar Automação', 'ai-sales-engine' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
