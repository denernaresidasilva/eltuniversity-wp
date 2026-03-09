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
                    <?php esc_html_e( 'AI Agents', 'ai-sales-engine' ); ?>
                </h1>
                <button class="button button-primary" id="aise-add-agent-btn">
                    + <?php esc_html_e( 'New Agent', 'ai-sales-engine' ); ?>
                </button>
            </div>

            <div class="aise-agents-grid" id="aise-agents-grid">
                <p><?php esc_html_e( 'Loading agents…', 'ai-sales-engine' ); ?></p>
            </div>

            <div class="aise-card" id="aise-agent-form-card" style="display:none;">
                <h2><?php esc_html_e( 'Agent Configuration', 'ai-sales-engine' ); ?></h2>
                <form id="aise-agent-form">
                    <table class="form-table">
                        <tr>
                            <th><label for="agent-name"><?php esc_html_e( 'Name', 'ai-sales-engine' ); ?></label></th>
                            <td><input type="text" id="agent-name" name="name" class="regular-text" required /></td>
                        </tr>
                        <tr>
                            <th><label for="agent-role"><?php esc_html_e( 'Role', 'ai-sales-engine' ); ?></label></th>
                            <td><input type="text" id="agent-role" name="role" class="regular-text" /></td>
                        </tr>
                        <tr>
                            <th><label for="agent-goal"><?php esc_html_e( 'Goal', 'ai-sales-engine' ); ?></label></th>
                            <td><textarea id="agent-goal" name="goal" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="agent-personality"><?php esc_html_e( 'Personality', 'ai-sales-engine' ); ?></label></th>
                            <td><textarea id="agent-personality" name="personality" rows="3" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="agent-training"><?php esc_html_e( 'Training Prompt', 'ai-sales-engine' ); ?></label></th>
                            <td><textarea id="agent-training" name="training_prompt" rows="6" class="large-text"></textarea></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'Features', 'ai-sales-engine' ); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="voice_enabled" value="1" />
                                    <?php esc_html_e( 'Voice enabled', 'ai-sales-engine' ); ?>
                                </label>
                                &nbsp;&nbsp;
                                <label>
                                    <input type="checkbox" name="image_enabled" value="1" />
                                    <?php esc_html_e( 'Image generation enabled', 'ai-sales-engine' ); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Save Agent', 'ai-sales-engine' ); ?>
                        </button>
                        <button type="button" class="button" id="aise-agent-cancel-btn">
                            <?php esc_html_e( 'Cancel', 'ai-sales-engine' ); ?>
                        </button>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}
