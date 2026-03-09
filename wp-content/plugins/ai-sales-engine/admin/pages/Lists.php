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
                    <?php esc_html_e( 'Listas de Contatos', 'ai-sales-engine' ); ?>
                </h1>
                <button class="aise-btn aise-btn-primary" id="aise-add-list-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Nova Lista', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card">
                <div class="aise-table-responsive">
                    <table class="aise-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Descrição', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'URL do Webhook', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Criado em', 'ai-sales-engine' ); ?></th>
                                <th><?php esc_html_e( 'Ações', 'ai-sales-engine' ); ?></th>
                            </tr>
                        </thead>
                        <tbody id="aise-lists-tbody">
                            <tr><td colspan="5" class="aise-loading-row"><?php esc_html_e( 'Carregando…', 'ai-sales-engine' ); ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: Nova/Editar Lista -->
            <div class="aise-modal" id="aise-list-modal" style="display:none;">
                <div class="aise-modal-backdrop"></div>
                <div class="aise-modal-dialog">
                    <div class="aise-modal-header">
                        <h3><?php esc_html_e( 'Nova Lista', 'ai-sales-engine' ); ?></h3>
                        <button class="aise-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'ai-sales-engine' ); ?>">&#x2715;</button>
                    </div>
                    <div class="aise-modal-body">
                        <form id="aise-list-form">
                            <div class="aise-form-row">
                                <label for="list-name"><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="list-name" name="name" class="aise-input" required />
                            </div>
                            <div class="aise-form-row">
                                <label for="list-description"><?php esc_html_e( 'Descrição', 'ai-sales-engine' ); ?></label>
                                <textarea id="list-description" name="description" class="aise-input" rows="3"></textarea>
                            </div>
                            <div class="aise-form-row">
                                <label for="list-webhook"><?php esc_html_e( 'URL do Webhook', 'ai-sales-engine' ); ?></label>
                                <input type="url" id="list-webhook" name="webhook_url" class="aise-input" />
                            </div>
                        </form>
                    </div>
                    <div class="aise-modal-footer">
                        <button class="aise-btn aise-btn-ghost aise-modal-close"><?php esc_html_e( 'Cancelar', 'ai-sales-engine' ); ?></button>
                        <button class="aise-btn aise-btn-primary" id="aise-list-save-btn"><?php esc_html_e( 'Salvar Lista', 'ai-sales-engine' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
