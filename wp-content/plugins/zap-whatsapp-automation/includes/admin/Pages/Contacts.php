<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Contacts {

    public static function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sem permissão', 'zap-whatsapp-automation'));
        }

        // Handle tag actions
        $action_notice = '';
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['zapwa_contact_action'])
            && check_admin_referer('zapwa_contact_tag_action')
        ) {
            $contact_id = absint($_POST['contact_id'] ?? 0);
            $tag_slug   = sanitize_title(wp_unslash($_POST['tag_slug'] ?? ''));
            $action     = sanitize_key(wp_unslash($_POST['zapwa_contact_action'] ?? ''));

            if ($contact_id && $tag_slug) {
                if ($action === 'add_tag' && class_exists('\ZapWA\Tag_Manager')) {
                    \ZapWA\Tag_Manager::add_tag_to_contact($contact_id, $tag_slug);
                    $action_notice = esc_html__('Tag adicionada.', 'zap-whatsapp-automation');
                } elseif ($action === 'remove_tag' && class_exists('\ZapWA\Tag_Manager')) {
                    \ZapWA\Tag_Manager::remove_tag_from_contact($contact_id, $tag_slug);
                    $action_notice = esc_html__('Tag removida.', 'zap-whatsapp-automation');
                }
            }
        }

        // Get contacts with tags
        global $wpdb;
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $page_num = max(1, absint($_GET['paged'] ?? 1));
        $per_page = 20;
        $offset   = ($page_num - 1) * $per_page;

        $args = [
            'number'  => $per_page,
            'offset'  => $offset,
            'orderby' => 'registered',
            'order'   => 'DESC',
        ];
        if ($search) {
            $args['search'] = '*' . $search . '*';
        }

        $users       = get_users($args);
        $total_users = count(get_users(array_merge($args, ['number' => -1, 'offset' => 0, 'count_total' => true])));

        // Get all available tags
        $all_tags = class_exists('\ZapWA\Tag_Manager') ? \ZapWA\Tag_Manager::get_all_tags() : [];
        ?>
        <div class="wrap zapwa-page">
            <div class="zapwa-admin-header">
                <div>
                    <h1>👥 <?php esc_html_e('Contatos & Tags', 'zap-whatsapp-automation'); ?></h1>
                    <p class="sub"><?php esc_html_e('Gerencie contatos e suas tags de automação', 'zap-whatsapp-automation'); ?></p>
                </div>
            </div>

            <?php if ($action_notice): ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html($action_notice); ?></p></div>
            <?php endif; ?>

            <!-- Search -->
            <form method="get">
                <input type="hidden" name="page" value="zap-wa-contacts" />
                <div style="display:flex;gap:8px;margin-bottom:16px;">
                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Buscar contato...', 'zap-whatsapp-automation'); ?>" class="regular-text" />
                    <button type="submit" class="zapwa-btn zapwa-btn-secondary">🔍 <?php esc_html_e('Buscar', 'zap-whatsapp-automation'); ?></button>
                </div>
            </form>

            <!-- Contacts table -->
            <div class="zapwa-card">
                <div class="zapwa-card-body" style="padding:0;">
                    <table class="wp-list-table widefat fixed striped" style="border:none;">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Contato', 'zap-whatsapp-automation'); ?></th>
                                <th><?php esc_html_e('Email', 'zap-whatsapp-automation'); ?></th>
                                <th><?php esc_html_e('Tags', 'zap-whatsapp-automation'); ?></th>
                                <th><?php esc_html_e('Ações', 'zap-whatsapp-automation'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr><td colspan="4" style="text-align:center;padding:20px;"><?php esc_html_e('Nenhum contato encontrado.', 'zap-whatsapp-automation'); ?></td></tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <?php
                                    $tags = class_exists('\ZapWA\Tag_Manager')
                                        ? \ZapWA\Tag_Manager::get_contact_tags($user->ID)
                                        : [];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($user->display_name); ?></strong>
                                            <div style="font-size:.8em;color:#888;">#<?php echo esc_html((string)$user->ID); ?></div>
                                        </td>
                                        <td><?php echo esc_html($user->user_email); ?></td>
                                        <td>
                                            <?php if (!empty($tags)): ?>
                                                <?php foreach ($tags as $tag): ?>
                                                    <span style="display:inline-block;background:#e0f2fe;color:#0369a1;padding:2px 8px;border-radius:12px;font-size:.8em;margin:2px;">
                                                        <?php echo esc_html($tag); ?>
                                                        <form method="post" style="display:inline;">
                                                            <?php wp_nonce_field('zapwa_contact_tag_action'); ?>
                                                            <input type="hidden" name="contact_id" value="<?php echo esc_attr((string)$user->ID); ?>" />
                                                            <input type="hidden" name="tag_slug" value="<?php echo esc_attr($tag); ?>" />
                                                            <input type="hidden" name="zapwa_contact_action" value="remove_tag" />
                                                            <button type="submit" style="background:none;border:none;cursor:pointer;color:#dc2626;font-size:.9em;padding:0 2px;" title="<?php esc_attr_e('Remover tag', 'zap-whatsapp-automation'); ?>">×</button>
                                                        </form>
                                                    </span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span style="color:#888;font-size:.85em;"><?php esc_html_e('Sem tags', 'zap-whatsapp-automation'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="post" style="display:flex;gap:6px;align-items:center;">
                                                <?php wp_nonce_field('zapwa_contact_tag_action'); ?>
                                                <input type="hidden" name="contact_id" value="<?php echo esc_attr((string)$user->ID); ?>" />
                                                <input type="hidden" name="zapwa_contact_action" value="add_tag" />
                                                <select name="tag_slug" style="font-size:.85em;">
                                                    <option value=""><?php esc_html_e('— Tag —', 'zap-whatsapp-automation'); ?></option>
                                                    <?php foreach ($all_tags as $t): ?>
                                                        <option value="<?php echo esc_attr($t->slug); ?>"><?php echo esc_html($t->name); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="zapwa-btn zapwa-btn-primary" style="padding:4px 10px;font-size:.8em;">+ Tag</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php
    }
}
