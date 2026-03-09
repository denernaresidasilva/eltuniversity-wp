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
                <button class="aise-btn aise-btn-primary" id="aise-add-pipeline-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Novo Pipeline', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-card aise-pipeline-selector-card">
                <div class="aise-pipeline-selector-row">
                    <label for="aise-pipeline-select">
                        <?php esc_html_e( 'Selecionar pipeline:', 'ai-sales-engine' ); ?>
                    </label>
                    <select id="aise-pipeline-select" class="aise-select">
                        <option value=""><?php esc_html_e( '— Escolher —', 'ai-sales-engine' ); ?></option>
                    </select>
                </div>
            </div>

            <div class="aise-kanban-board" id="aise-kanban-board">
                <p class="aise-empty-hint">
                    <span class="dashicons dashicons-info-outline"></span>
                    <?php esc_html_e( 'Selecione um pipeline para visualizar o quadro.', 'ai-sales-engine' ); ?>
                </p>
            </div>

            <!-- Modal: Novo Pipeline -->
            <div class="aise-modal" id="aise-pipeline-modal" style="display:none;">
                <div class="aise-modal-backdrop"></div>
                <div class="aise-modal-dialog">
                    <div class="aise-modal-header">
                        <h3><?php esc_html_e( 'Novo Pipeline', 'ai-sales-engine' ); ?></h3>
                        <button class="aise-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'ai-sales-engine' ); ?>">&#x2715;</button>
                    </div>
                    <div class="aise-modal-body">
                        <form id="aise-pipeline-form">
                            <div class="aise-form-row">
                                <label for="pipeline-name"><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="pipeline-name" name="name" class="aise-input" required />
                            </div>
                            <div class="aise-form-row">
                                <label for="pipeline-description"><?php esc_html_e( 'Descrição', 'ai-sales-engine' ); ?></label>
                                <textarea id="pipeline-description" name="description" class="aise-input" rows="2"></textarea>
                            </div>
                            <div class="aise-form-row">
                                <label><?php esc_html_e( 'Etapas (uma por linha)', 'ai-sales-engine' ); ?></label>
                                <textarea id="pipeline-stages" name="stages" class="aise-input" rows="4"
                                    placeholder="<?php esc_attr_e( "Novo\nQualificado\nProposta\nFechado", 'ai-sales-engine' ); ?>"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="aise-modal-footer">
                        <button class="aise-btn aise-btn-ghost aise-modal-close"><?php esc_html_e( 'Cancelar', 'ai-sales-engine' ); ?></button>
                        <button class="aise-btn aise-btn-primary" id="aise-pipeline-save-btn"><?php esc_html_e( 'Criar Pipeline', 'ai-sales-engine' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
