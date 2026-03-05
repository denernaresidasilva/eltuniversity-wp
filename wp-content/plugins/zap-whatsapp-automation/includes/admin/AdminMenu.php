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
        // Messages list: inject header + "Nova Mensagem" button
        add_action('admin_notices', ['ZapWA\\Admin\\Pages\\Messages', 'render_list_header']);
        // Message editor: inject WhatsApp toolbar between title and content editor
        add_action('edit_form_after_title', [self::class, 'render_wa_editor_toolbar']);
        // Flows: delete and toggle actions
        add_action('admin_post_zapwa_delete_flow', [self::class, 'handle_delete_flow']);
        add_action('admin_post_zapwa_toggle_flow', [self::class, 'handle_toggle_flow']);
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

    /**
     * Returns true when the current page is the flow builder.
     */
    private static function is_flow_builder_page() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
        return $page === 'zap-wa-flow-builder';
    }

    public static function enqueue_assets($hook) {

        if (!self::is_zapwa_page()) {
            return;
        }

        $url     = ZAP_WA_URL . 'assets/admin/';
        $version = '1.3.0';

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

        // Load flow builder assets only on builder and flows list pages.
        if (self::is_flow_builder_page() || sanitize_key(wp_unslash($_GET['page'] ?? '')) === 'zap-wa-flows') {
            wp_enqueue_style(
                'zapwa-flow-builder',
                $url . 'flow-builder.css',
                ['zapwa-admin'],
                $version
            );
        }

        if (self::is_flow_builder_page()) {
            wp_enqueue_script(
                'zapwa-flow-builder',
                $url . 'flow-builder.js',
                [],
                $version,
                true
            );
        }
    }

    /**
     * Outputs the floating quick-action buttons, the email-preview modal,
     * and the WhatsApp preview modal in the admin footer — only on ZapWA plugin pages.
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

        <!-- Email preview modal -->
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

        <!-- WhatsApp preview modal (rendered in footer to avoid CSS transform stacking issues) -->
        <div id="zapwa-wa-preview-modal"
             class="zapwa-modal-overlay"
             style="display:none;"
             role="dialog"
             aria-modal="true"
             aria-label="<?php esc_attr_e('Preview WhatsApp', 'zap-whatsapp-automation'); ?>">
            <div class="zapwa-modal zapwa-modal--wa">
                <div class="zapwa-modal-header zapwa-modal-header--green">
                    <span>&#x1F4AC; <?php esc_html_e('Preview WhatsApp', 'zap-whatsapp-automation'); ?></span>
                    <button type="button"
                            class="zapwa-modal-close"
                            id="zapwa-wa-modal-close"
                            aria-label="<?php esc_attr_e('Fechar', 'zap-whatsapp-automation'); ?>">&#10005;</button>
                </div>
                <div class="zapwa-modal-body zapwa-modal-body--wa">
                    <div class="zapwa-phone-header">
                        <div class="zapwa-phone-avatar">&#x1F916;</div>
                        <div>
                            <div class="zapwa-phone-name"><?php echo esc_html(get_bloginfo('name') ?: __('Meu Site', 'zap-whatsapp-automation')); ?></div>
                            <div class="zapwa-phone-status">online</div>
                        </div>
                    </div>
                    <div class="zapwa-phone-body">
                        <div class="zapwa-bubble">
                            <div id="zapwa-wa-modal-text">
                                <span class="zapwa-bubble-empty"><?php esc_html_e('Escreva a mensagem para ver o preview...', 'zap-whatsapp-automation'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Inject a WhatsApp-style toolbar between the post title and the content editor.
     * Only fires on the zapwa_message post-edit screen.
     */
    public static function render_wa_editor_toolbar( $post ) {

        if ( ! ( $post instanceof \WP_Post ) || get_post_type( $post ) !== 'zapwa_message' ) {
            return;
        }

        $vars = [
            '{user_name}', '{user_email}', '{user_phone}',
            '{course_name}', '{course_progress}', '{course_url}',
            '{site_name}', '{current_date}', '{days_inactive}',
        ];
        ?>
        <div class="zapwa-editor-wrap" id="zapwa-editor-wrap">

            <!-- Toolbar strip -->
            <div class="zapwa-editor-toolbar" role="toolbar" aria-label="<?php esc_attr_e( 'Ferramentas de mensagem', 'zap-whatsapp-automation' ); ?>">
                <span class="zapwa-editor-toolbar__label">💬 <?php esc_html_e( 'Mensagem WhatsApp', 'zap-whatsapp-automation' ); ?></span>

                <div class="zapwa-editor-toolbar__tools">
                    <!-- Emoji picker trigger -->
                    <button type="button" class="zapwa-tool-btn" id="zapwa-emoji-btn" title="<?php esc_attr_e( 'Inserir emoji', 'zap-whatsapp-automation' ); ?>" aria-haspopup="true" aria-expanded="false">
                        😊
                    </button>

                    <!-- Media / Image -->
                    <button type="button" class="zapwa-tool-btn" id="zapwa-media-btn" title="<?php esc_attr_e( 'Inserir mídia (URL)', 'zap-whatsapp-automation' ); ?>">
                        🖼️
                    </button>

                    <!-- File upload -->
                    <button type="button" class="zapwa-tool-btn" id="zapwa-file-btn" title="<?php esc_attr_e( 'Carregar arquivo', 'zap-whatsapp-automation' ); ?>">
                        📎
                    </button>

                    <div class="zapwa-tool-divider"></div>

                    <!-- WhatsApp preview -->
                    <button type="button" class="zapwa-tool-btn zapwa-tool-btn--preview" id="zapwa-wa-preview-btn" title="<?php esc_attr_e( 'Preview WhatsApp', 'zap-whatsapp-automation' ); ?>">
                        👁 <?php esc_html_e( 'Preview', 'zap-whatsapp-automation' ); ?>
                    </button>
                </div>
            </div>

            <!-- Emoji picker panel (hidden by default) -->
            <div class="zapwa-emoji-panel" id="zapwa-emoji-panel" style="display:none;" role="dialog" aria-label="<?php esc_attr_e( 'Selecionar emoji', 'zap-whatsapp-automation' ); ?>">
                <?php
                $emojis = ['😊','😃','😄','😁','🎉','👍','👋','🙏','❤️','🔥','✅','⭐','📱','💬','📚','🎓','🏆','💡','📝','🚀','⏰','📅','💪','🤝','😎','🙌','👏','💰','📢','✨'];
                foreach ( $emojis as $e ) {
                    echo '<button type="button" class="zapwa-emoji-item" data-emoji="' . esc_attr( $e ) . '">' . esc_html( $e ) . '</button>';
                }
                ?>
            </div>

            <!-- Variables quick-bar -->
            <div class="zapwa-editor-vars" aria-label="<?php esc_attr_e( 'Variáveis disponíveis', 'zap-whatsapp-automation' ); ?>">
                <span class="zapwa-editor-vars__label"><?php esc_html_e( 'Variáveis:', 'zap-whatsapp-automation' ); ?></span>
                <div class="zapwa-editor-vars__chips">
                    <?php foreach ( $vars as $v ) : ?>
                        <span class="zapwa-var" data-var="<?php echo esc_attr( $v ); ?>"><?php echo esc_html( $v ); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /.zapwa-editor-wrap -->
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

        // SUBMENU FLUXOS
        add_submenu_page(
            'zap-wa-metrics',
            __('Fluxos', 'zap-whatsapp-automation'),
            '🔀 ' . __('Fluxos', 'zap-whatsapp-automation'),
            'manage_options',
            'zap-wa-flows',
            ['ZapWA\\Admin\\Pages\\Flows', 'render']
        );

        // SUBMENU FLOW BUILDER (hidden — opened programmatically)
        add_submenu_page(
            null,
            __('Builder de Fluxo', 'zap-whatsapp-automation'),
            __('Builder de Fluxo', 'zap-whatsapp-automation'),
            'manage_options',
            'zap-wa-flow-builder',
            ['ZapWA\\Admin\\Pages\\FlowBuilder', 'render']
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

    // -------------------------------------------------------------------------
    // Flow admin actions (delete / toggle)
    // -------------------------------------------------------------------------

    public static function handle_delete_flow() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Sem permissão', 'zap-whatsapp-automation'));
        }

        $flow_id = isset($_GET['flow_id']) ? absint($_GET['flow_id']) : 0;

        if (!$flow_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'zapwa_delete_flow_' . $flow_id)) {
            wp_die(__('Requisição inválida', 'zap-whatsapp-automation'));
        }

        $post = get_post($flow_id);
        if ($post && $post->post_type === 'automation_flow') {
            wp_delete_post($flow_id, true);
        }

        wp_redirect(admin_url('admin.php?page=zap-wa-flows&deleted=1'));
        exit;
    }

    public static function handle_toggle_flow() {

        if (!current_user_can('manage_options')) {
            wp_die(__('Sem permissão', 'zap-whatsapp-automation'));
        }

        $flow_id = isset($_GET['flow_id']) ? absint($_GET['flow_id']) : 0;

        if (!$flow_id || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'zapwa_toggle_flow_' . $flow_id)) {
            wp_die(__('Requisição inválida', 'zap-whatsapp-automation'));
        }

        $post = get_post($flow_id);
        if ($post && $post->post_type === 'automation_flow') {
            $current = get_post_meta($flow_id, '_flow_status', true);
            $new     = ($current === 'active') ? 'inactive' : 'active';
            update_post_meta($flow_id, '_flow_status', $new);
            wp_update_post([
                'ID'          => $flow_id,
                'post_status' => ($new === 'active') ? 'publish' : 'draft',
            ]);
        }

        wp_redirect(admin_url('admin.php?page=zap-wa-flows&toggled=1'));
        exit;
    }
}
