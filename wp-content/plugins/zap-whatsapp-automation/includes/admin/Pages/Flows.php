<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page: list all automation flows.
 */
class Flows {

    public static function render() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Sem permissão', 'zap-whatsapp-automation'));
        }

        $flows = get_posts([
            'post_type'      => 'automation_flow',
            'post_status'    => ['publish', 'draft'],
            'posts_per_page' => 100,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        $new_url     = esc_url(admin_url('admin.php?page=zap-wa-flow-builder'));
        $metrics_url = esc_url(admin_url('admin.php?page=zap-wa-metrics'));
        ?>
        <div class="wrap zapwa-page zapwa-flows-page">

            <div class="zapwa-messages-header">
                <div class="zapwa-messages-header__info">
                    <span class="zapwa-messages-header__icon">🔀</span>
                    <div>
                        <h2 class="zapwa-messages-header__title"><?php esc_html_e('Fluxos de Automação', 'zap-whatsapp-automation'); ?></h2>
                        <p class="zapwa-messages-header__sub"><?php esc_html_e('Crie e gerencie seus fluxos visuais de automação', 'zap-whatsapp-automation'); ?></p>
                    </div>
                </div>
                <div class="zapwa-messages-header__actions">
                    <a href="<?php echo $metrics_url; ?>" class="zapwa-btn zapwa-btn-ghost">
                        ← <?php esc_html_e('Dashboard', 'zap-whatsapp-automation'); ?>
                    </a>
                    <a href="<?php echo $new_url; ?>" class="zapwa-btn zapwa-btn-new">
                        <span class="zapwa-btn-new__icon">＋</span>
                        <?php esc_html_e('Novo Fluxo', 'zap-whatsapp-automation'); ?>
                    </a>
                </div>
            </div>

            <?php if (empty($flows)): ?>
                <div class="zapwa-flows-empty">
                    <div class="zapwa-flows-empty__icon">🔀</div>
                    <h3><?php esc_html_e('Nenhum fluxo criado ainda', 'zap-whatsapp-automation'); ?></h3>
                    <p><?php esc_html_e('Crie seu primeiro fluxo de automação com o builder visual.', 'zap-whatsapp-automation'); ?></p>
                    <a href="<?php echo $new_url; ?>" class="button button-primary button-large">
                        <?php esc_html_e('Criar Primeiro Fluxo', 'zap-whatsapp-automation'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="zapwa-flows-grid">
                    <?php foreach ($flows as $flow):
                        $flow_status = get_post_meta($flow->ID, '_flow_status', true) ?: 'inactive';
                        $trigger     = get_post_meta($flow->ID, '_flow_trigger', true) ?: '—';
                        $structure   = get_post_meta($flow->ID, '_flow_structure', true);
                        $data        = $structure ? json_decode($structure, true) : [];
                        $node_count  = count($data['nodes'] ?? []);
                        $edit_url    = esc_url(admin_url('admin.php?page=zap-wa-flow-builder&flow_id=' . $flow->ID));
                        $delete_url  = esc_url(wp_nonce_url(
                            admin_url('admin-post.php?action=zapwa_delete_flow&flow_id=' . $flow->ID),
                            'zapwa_delete_flow_' . $flow->ID
                        ));
                        $toggle_url  = esc_url(wp_nonce_url(
                            admin_url('admin-post.php?action=zapwa_toggle_flow&flow_id=' . $flow->ID),
                            'zapwa_toggle_flow_' . $flow->ID
                        ));
                        $is_active   = ($flow_status === 'active');
                    ?>
                        <div class="zapwa-flow-card <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>">
                            <div class="zapwa-flow-card__header">
                                <span class="zapwa-flow-card__status-dot <?php echo $is_active ? 'active' : 'inactive'; ?>"></span>
                                <span class="zapwa-flow-card__title"><?php echo esc_html($flow->post_title); ?></span>
                            </div>
                            <div class="zapwa-flow-card__meta">
                                <span>⚡ <?php echo esc_html($trigger); ?></span>
                                <span>📦 <?php echo number_format_i18n($node_count); ?> <?php esc_html_e('nós', 'zap-whatsapp-automation'); ?></span>
                                <span class="zapwa-flow-status-badge <?php echo $is_active ? 'active' : 'inactive'; ?>">
                                    <?php echo $is_active ? esc_html__('Ativo', 'zap-whatsapp-automation') : esc_html__('Inativo', 'zap-whatsapp-automation'); ?>
                                </span>
                            </div>
                            <div class="zapwa-flow-card__actions">
                                <a href="<?php echo $edit_url; ?>" class="button button-small">
                                    ✏️ <?php esc_html_e('Editar', 'zap-whatsapp-automation'); ?>
                                </a>
                                <a href="<?php echo $toggle_url; ?>" class="button button-small <?php echo $is_active ? 'button-secondary' : 'button-primary'; ?>">
                                    <?php echo $is_active ? '⏸ ' . esc_html__('Desativar', 'zap-whatsapp-automation') : '▶ ' . esc_html__('Ativar', 'zap-whatsapp-automation'); ?>
                                </a>
                                <a href="<?php echo $delete_url; ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('<?php esc_attr_e('Excluir este fluxo permanentemente?', 'zap-whatsapp-automation'); ?>')">
                                    🗑 <?php esc_html_e('Excluir', 'zap-whatsapp-automation'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
        <?php
    }
}
