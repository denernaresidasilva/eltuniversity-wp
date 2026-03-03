<?php
namespace ZapWA\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AdminMenu {

    public static function init() {

        self::load_pages();
        add_action('admin_menu', [self::class, 'register_menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue_assets']);
        add_action('admin_footer', [self::class, 'render_floating_nav']);
    }

    /**
     * Returns true when the current admin screen belongs to the ZapWA plugin.
     */
    private static function is_zapwa_page() {

        // $post_type and $page are used only for comparison; sanitize_key is sufficient.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $post_type   = isset($_GET['post_type']) ? sanitize_key(wp_unslash($_GET['post_type'])) : '';
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $page        = isset($_GET['page'])      ? sanitize_key(wp_unslash($_GET['page']))      : '';
        $is_zapwa_pt = ($post_type === 'zapwa_message');
        $is_zapwa_pg = ($page !== '' && strpos($page, 'zap-wa') === 0);

        if (!$is_zapwa_pt && !$is_zapwa_pg) {
            $post_id = isset($_GET['post']) ? (int) $_GET['post'] : 0;
            if ($post_id > 0 && get_post_type($post_id) === 'zapwa_message') {
                $is_zapwa_pt = true;
            }
        }

        return $is_zapwa_pt || $is_zapwa_pg;
    }

    public static function enqueue_assets($hook) {

        if (!self::is_zapwa_page()) {
            return;
        }

        $url     = ZAP_WA_URL . 'assets/admin/';
        $version = '1.2.0';

        wp_enqueue_style(
            'zapwa-admin',
            $url . 'zapwa-admin.css',
            [],
            $version
        );

        wp_enqueue_script(
            'zapwa-admin',
            $url . 'zapwa-admin.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('zapwa-admin', 'zapwaAdmin', [
            'previewPlaceholder' => __('Escreva a mensagem acima para ver o preview...', 'zap-whatsapp-automation'),
            'ajaxUrl'            => admin_url('admin-ajax.php'),
            'emailPreviewNonce'  => wp_create_nonce('zapwa_email_preview'),
            'messagesUrl'        => admin_url('edit.php?post_type=zapwa_message'),
            'i18n'               => [
                'emailPreviewTitle'   => __('Preview do E-mail', 'zap-whatsapp-automation'),
                'emailPreviewLoading' => __('Carregando preview...', 'zap-whatsapp-automation'),
                'emailPreviewError'   => __('Erro ao carregar o preview. Tente novamente.', 'zap-whatsapp-automation'),
                'close'               => __('Fechar', 'zap-whatsapp-automation'),
            ],
        ]);
    }

    /**
     * Outputs the floating quick-action buttons and the email-preview modal
     * in the admin footer — only on ZapWA plugin pages.
     */
    public static function render_floating_nav() {

        if (!self::is_zapwa_page()) {
            return;
        }

        $messages_url = esc_url(admin_url('edit.php?post_type=zapwa_message'));
        ?>
        <div id="zapwa-fab-group" class="zapwa-fab-group" aria-label="<?php esc_attr_e('Ações rápidas', 'zap-whatsapp-automation'); ?>">
            <button type="button"
                    class="zapwa-fab zapwa-fab-back"
                    title="<?php esc_attr_e('Voltar', 'zap-whatsapp-automation'); ?>"
                    onclick="history.back()">&#8592;</button>
            <a href="<?php echo $messages_url; ?>"
               class="zapwa-fab zapwa-fab-list"
               title="<?php esc_attr_e('Lista de mensagens', 'zap-whatsapp-automation'); ?>">&#9776;</a>
            <button type="button"
                    class="zapwa-fab zapwa-fab-top"
                    id="zapwa-fab-top"
                    title="<?php esc_attr_e('Ir ao topo', 'zap-whatsapp-automation'); ?>"
                    onclick="window.scrollTo({top:0,behavior:'smooth'})">&#8593;</button>
        </div>

        <div id="zapwa-email-preview-modal"
             class="zapwa-modal-overlay"
             style="display:none;"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e('Preview do E-mail', 'zap-whatsapp-automation'); ?>">
            <div class="zapwa-modal">
                <div class="zapwa-modal-header">
                    <span>&#x2709; <?php esc_html_e('Preview do E-mail', 'zap-whatsapp-automation'); ?></span>
                    <button type="button"
                            class="zapwa-modal-close"
                            id="zapwa-modal-close"
                            aria-label="<?php esc_attr_e('Fechar', 'zap-whatsapp-automation'); ?>">&#10005;</button>
                </div>
                <div class="zapwa-modal-body">
                    <div id="zapwa-preview-loading" class="zapwa-preview-loading">
                        <?php esc_html_e('Carregando preview...', 'zap-whatsapp-automation'); ?>
                    </div>
                    <iframe id="zapwa-email-preview-frame"
                            title="<?php esc_attr_e('Preview do E-mail', 'zap-whatsapp-automation'); ?>"
                            style="width:100%;height:100%;border:none;display:none;"></iframe>
                </div>
            </div>
        </div>
        <?php
    }

    private static function load_pages() {

        $base = plugin_dir_path(__FILE__) . 'Pages/';

        $files = [
            'Connection.php',
            'Messages.php',
            'Logs.php',
            'QueuePage.php', // ✅ FILA
            'Metrics.php', // ✅ MÉTRICAS
            'Settings.php', // ✅ CONFIGURAÇÕES
        ];

        foreach ($files as $file) {
            $path = $base . $file;
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }

    public static function register_menu() {

        if (!class_exists('ZapWA\\Admin\\Pages\\Connection')) {
            return;
        }

        // MENU PRINCIPAL - Deve abrir MÉTRICAS (dashboard)
        add_menu_page(
            'Zap WhatsApp Automation',
            'Zap WhatsApp',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render'],
            'dashicons-whatsapp',
            57
        );

        // SUBMENU MENSAGENS - Link direto para lista de mensagens
        add_submenu_page(
            'zap-wa-metrics',
            'Mensagens',
            'Mensagens',
            'manage_options',
            'edit.php?post_type=zapwa_message'
        );

        // SUBMENU CONEXÃO
        add_submenu_page(
            'zap-wa-metrics',
            'Conexão',
            'Conexão',
            'manage_options',
            'zap-wa-connection',
            ['ZapWA\\Admin\\Pages\\Connection', 'render']
        );

        if (get_option('zapwa_logging_enabled', true)) {
            add_submenu_page(
                'zap-wa-metrics',
                'Logs',
                'Logs',
                'manage_options',
                'zap-wa-logs',
                ['ZapWA\\Admin\\Pages\\Logs', 'render']
            );
        }

        add_submenu_page(
            'zap-wa-metrics',
            'Fila',
            'Fila',
            'manage_options',
            'zap-wa-queue',
            ['ZapWA\\Admin\\Pages\\QueuePage', 'render']
        );

        add_submenu_page(
            'zap-wa-metrics',
            'Métricas',
            'Métricas',
            'manage_options',
            'zap-wa-metrics',
            ['ZapWA\\Admin\\Pages\\Metrics', 'render']
        );

        add_submenu_page(
            'zap-wa-metrics',
            'Configurações',
            'Configurações',
            'manage_options',
            'zap-wa-settings',
            ['ZapWA\\Admin\\Pages\\Settings', 'render']
        );
    }
}
