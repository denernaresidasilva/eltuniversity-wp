<?php
/**
 * Agents admin page.
 *
 * @package AISalesEngine\Admin
 */

namespace AISalesEngine\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Agents {

    public static function render(): void {
        ?>
        <div class="aise-wrap">
            <div class="aise-page-header">
                <h1 class="aise-page-title">
                    <span class="dashicons dashicons-superhero"></span>
                    <?php esc_html_e( 'Agentes de IA', 'ai-sales-engine' ); ?>
                </h1>
                <button class="aise-btn aise-btn-primary" id="aise-add-agent-btn">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e( 'Novo Agente', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-agents-grid" id="aise-agents-grid">
                <p class="aise-loading-row"><?php esc_html_e( 'Carregando agentes…', 'ai-sales-engine' ); ?></p>
            </div>

            <!-- Modal: Novo/Editar Agente -->
            <div class="aise-modal" id="aise-agent-modal" style="display:none;">
                <div class="aise-modal-backdrop"></div>
                <div class="aise-modal-dialog aise-modal-lg">
                    <div class="aise-modal-header">
                        <h3><?php esc_html_e( 'Configuração do Agente', 'ai-sales-engine' ); ?></h3>
                        <button class="aise-modal-close" aria-label="<?php esc_attr_e( 'Fechar', 'ai-sales-engine' ); ?>">&#x2715;</button>
                    </div>
                    <div class="aise-modal-body">
                        <form id="aise-agent-form">
                            <div class="aise-form-row">
                                <label for="agent-name"><?php esc_html_e( 'Nome', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="agent-name" name="name" class="aise-input" required />
                            </div>
                            <div class="aise-form-row">
                                <label for="agent-role"><?php esc_html_e( 'Função', 'ai-sales-engine' ); ?></label>
                                <input type="text" id="agent-role" name="role" class="aise-input" />
                            </div>
                            <div class="aise-form-row">
                                <label for="agent-goal"><?php esc_html_e( 'Objetivo', 'ai-sales-engine' ); ?></label>
                                <textarea id="agent-goal" name="goal" rows="3" class="aise-input"></textarea>
                            </div>
                            <div class="aise-form-row">
                                <label for="agent-personality"><?php esc_html_e( 'Personalidade', 'ai-sales-engine' ); ?></label>
                                <textarea id="agent-personality" name="personality" rows="3" class="aise-input"></textarea>
                            </div>
                            <div class="aise-form-row">
                                <label for="agent-training"><?php esc_html_e( 'Prompt de Treinamento', 'ai-sales-engine' ); ?></label>
                                <textarea id="agent-training" name="training_prompt" rows="6" class="aise-input"></textarea>
                            </div>
                            <div class="aise-form-row">
                                <label><?php esc_html_e( 'Recursos', 'ai-sales-engine' ); ?></label>
                                <div class="aise-checkboxes">
                                    <label class="aise-checkbox-label">
                                        <input type="checkbox" name="voice_enabled" value="1" />
                                        <span><?php esc_html_e( 'Voz habilitada', 'ai-sales-engine' ); ?></span>
                                    </label>
                                    <label class="aise-checkbox-label">
                                        <input type="checkbox" name="image_enabled" value="1" />
                                        <span><?php esc_html_e( 'Geração de imagem habilitada', 'ai-sales-engine' ); ?></span>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="aise-modal-footer">
                        <button class="aise-btn aise-btn-ghost aise-modal-close"><?php esc_html_e( 'Cancelar', 'ai-sales-engine' ); ?></button>
                        <button class="aise-btn aise-btn-primary" id="aise-agent-save-btn"><?php esc_html_e( 'Salvar Agente', 'ai-sales-engine' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
