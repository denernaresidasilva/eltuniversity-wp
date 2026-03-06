<?php
/**
 * Settings Page Class
 * 
 * Manages plugin configuration and settings page
 * 
 * @package ZapTutorEvents
 * @since 1.1.0
 */

namespace ZapTutorEvents;

if (!defined('ABSPATH')) {
    exit;
}

class Settings {

    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_menu', [self::class, 'add_settings_page'], 20);
        add_action('admin_init', [self::class, 'register_settings']);
        add_action('admin_post_zap_events_cleanup_logs', [self::class, 'handle_manual_cleanup']);
        add_action('admin_post_zap_events_toggle_logs', [self::class, 'handle_toggle_logs']);
    }

    /**
     * Add settings page to admin menu
     */
    public static function add_settings_page() {
        add_submenu_page(
            'zap-tutor-events',
            'Configurações',
            'Configurações',
            'manage_options',
            'zap-tutor-events-settings',
            [self::class, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public static function register_settings() {
        
        // Log settings
        register_setting('zap_events_settings', 'zap_events_log_enabled');
        register_setting('zap_events_settings', 'zap_events_log_retention_days');
        
        // Queue settings
        register_setting('zap_events_settings', 'zap_events_use_queue');
    }

    /**
     * Render settings page
     */
    public static function render_settings_page() {
        
        if (!current_user_can('manage_options')) {
            return;
        }

        // Save settings
        if (isset($_POST['zap_events_save_settings'])) {
            check_admin_referer('zap_events_settings');
            self::save_settings();
            echo '<div class="notice notice-success"><p>Configurações salvas com sucesso!</p></div>';
        }

        $log_enabled = get_option('zap_events_log_enabled', true);
        $log_retention = get_option('zap_events_log_retention_days', 30);
        $use_queue = get_option('zap_events_use_queue', false);
        $api_key = API::get_api_key();

        ?>
        <div class="wrap zap-events-page">
            <div class="zap-events-header">
                <div class="zap-events-header__info">
                    <span class="zap-events-header__icon">⚙️</span>
                    <div>
                        <h1 class="zap-events-header__title"><?php esc_html_e( 'Configurações', 'zap-tutor-events' ); ?></h1>
                        <p class="zap-events-header__sub"><?php esc_html_e( 'Logs, fila e API REST', 'zap-tutor-events' ); ?></p>
                    </div>
                </div>
                <div class="zap-events-header__nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events' ) ); ?>" class="zap-nav-btn">← Dashboard</a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events-webhooks' ) ); ?>" class="zap-nav-btn zap-nav-btn--accent">🔗 Webhooks</a>
                </div>
            </div>

            <?php Admin::render_tab_nav( 'zap-tutor-events-settings' ); ?>

            <div class="zap-section-nav" aria-label="<?php esc_attr_e('Navegação das seções', 'zap-tutor-events'); ?>">
                <a href="#zap-settings-logs" class="zap-section-nav__item">📋 Logs</a>
                <a href="#zap-settings-advanced" class="zap-section-nav__item">⚡ Avançado</a>
                <a href="#zap-settings-api" class="zap-section-nav__item">🔑 API</a>
                <a href="#zap-settings-cleanup" class="zap-section-nav__item">🧹 Limpeza</a>
            </div>

            <?php if (isset($_GET['logs_toggled'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Logs de eventos <?php echo get_option('zap_events_log_enabled', true) ? '<strong>ativados</strong>' : '<strong>desativados</strong>'; ?> com sucesso.</p>
                </div>
            <?php endif; ?>

            <?php
            $cleanup_status = isset( $_GET['cleanup'] ) ? sanitize_text_field( wp_unslash( $_GET['cleanup'] ) ) : '';
            $deleted_count  = isset( $_GET['deleted'] ) ? absint( $_GET['deleted'] ) : 0;
            if ( $cleanup_status === 'success' ):
            ?>
                <div class="notice notice-success is-dismissible">
                    <p>Limpeza concluída: <strong><?php echo esc_html( $deleted_count ); ?></strong> log(s) removido(s).</p>
                </div>
            <?php endif; ?>

            <div class="zap-events-card">
                <div class="zap-events-card__hdr">📋 Controle de Logs</div>
                <div class="zap-events-card__body">
                    <p>
                        Status atual:
                        <?php if ($log_enabled): ?>
                            <strong class="zap-status zap-status--ok">✔ Logs ATIVADOS</strong>
                        <?php else: ?>
                            <strong class="zap-status zap-status--err">✘ Logs DESATIVADOS</strong>
                        <?php endif; ?>
                    </p>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                        <?php wp_nonce_field('zap_events_toggle_logs'); ?>
                        <input type="hidden" name="action" value="zap_events_toggle_logs">
                        <?php if ($log_enabled): ?>
                            <button type="submit" class="zap-btn zap-btn-secondary zap-btn--danger"
                                onclick="return confirm('Desativar os logs de eventos? Os registros existentes serão mantidos.');">
                                🔕 Desativar Logs
                            </button>
                        <?php else: ?>
                            <button type="submit" class="zap-btn zap-btn-primary">
                                🔔 Ativar Logs
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <form method="post">
                <?php wp_nonce_field('zap_events_settings'); ?>

                <div class="zap-events-card" id="zap-settings-logs">
                    <div class="zap-events-card__hdr">📁 Configurações de Logs</div>
                    <div class="zap-events-card__body">
                        <div class="zap-field-row">
                            <label class="zap-field-label" for="log_enabled">Ativar Logs</label>
                            <label class="zap-switch">
                                <input type="checkbox" id="log_enabled" name="log_enabled" value="1" <?php checked($log_enabled); ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="zap-field-desc">Salvar logs de eventos no banco de dados</span>
                        </div>
                        <div class="zap-field-row">
                            <label class="zap-field-label" for="log_retention">Retenção de Logs</label>
                            <select id="log_retention" name="log_retention" class="zap-select">
                                <option value="7" <?php selected($log_retention, 7); ?>>7 dias</option>
                                <option value="30" <?php selected($log_retention, 30); ?>>30 dias</option>
                                <option value="60" <?php selected($log_retention, 60); ?>>60 dias</option>
                                <option value="90" <?php selected($log_retention, 90); ?>>90 dias</option>
                                <option value="0" <?php selected($log_retention, 0); ?>>Infinito (não limpar)</option>
                            </select>
                            <span class="zap-field-desc">Logs mais antigos serão removidos automaticamente.</span>
                        </div>
                    </div>
                </div>

                <div class="zap-events-card" id="zap-settings-advanced">
                    <div class="zap-events-card__hdr">⚡ Configurações Avançadas</div>
                    <div class="zap-events-card__body">
                        <div class="zap-field-row">
                            <label class="zap-field-label" for="use_queue">Fila de Processamento</label>
                            <label class="zap-switch">
                                <input type="checkbox" id="use_queue" name="use_queue" value="1" <?php checked($use_queue); ?>>
                                <span class="slider"></span>
                            </label>
                            <span class="zap-field-desc">Processar webhooks em background via WP Cron (recomendado para alto volume).</span>
                        </div>
                    </div>
                </div>

                <div class="zap-events-card" id="zap-settings-api">
                    <div class="zap-events-card__hdr">🔑 API REST</div>
                    <div class="zap-events-card__body">
                        <div class="zap-field-row">
                            <label class="zap-field-label">Chave de API</label>
                            <input type="text" value="<?php echo esc_attr($api_key); ?>" class="zap-input zap-input--wide" readonly onclick="this.select()">
                            <span class="zap-field-desc">Use no header <code>X-API-Key</code> para autenticar requisições à API REST.</span>
                        </div>
                        <div class="zap-api-endpoints">
                            <p class="zap-field-desc"><strong>Endpoints:</strong></p>
                            <ul class="zap-api-list">
                                <li><code>GET <?php echo rest_url('zap-events/v1/logs'); ?></code> — Listar eventos</li>
                                <li><code>GET <?php echo rest_url('zap-events/v1/stats'); ?></code> — Estatísticas</li>
                                <li><code>GET <?php echo rest_url('zap-events/v1/events'); ?></code> — Tipos de eventos</li>
                                <li><code>POST <?php echo rest_url('zap-events/v1/test'); ?></code> — Disparar evento de teste</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="zap-submit-row">
                    <input type="submit" name="zap_events_save_settings" class="zap-btn zap-btn-primary zap-btn--lg" value="💾 Salvar Configurações">
                </div>
            </form>

            <div class="zap-events-card" id="zap-settings-cleanup">
                <div class="zap-events-card__hdr zap-events-card__hdr--warn">🧹 Limpeza Manual de Logs</div>
                <div class="zap-events-card__body">
                    <p>Remova manualmente logs antigos baseado na configuração de retenção atual.</p>
                    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('zap_events_cleanup'); ?>
                        <input type="hidden" name="action" value="zap_events_cleanup_logs">
                        <input type="submit"
                               class="zap-btn zap-btn-secondary zap-btn--danger"
                               value="🗑️ Limpar Logs Antigos Agora"
                               onclick="return confirm('Tem certeza? Esta ação não pode ser desfeita.');">
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private static function save_settings() {
        
        update_option('zap_events_log_enabled', isset($_POST['log_enabled']) ? 1 : 0);
        update_option('zap_events_log_retention_days', absint($_POST['log_retention'] ?? 30));
        update_option('zap_events_use_queue', isset($_POST['use_queue']) ? 1 : 0);
    }

    /**
     * Handle manual log cleanup
     */
    public static function handle_manual_cleanup() {
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('zap_events_cleanup');

        $deleted = Logger::cleanup_old_logs();

        wp_redirect(add_query_arg([
            'page'    => 'zap-tutor-events-settings',
            'cleanup' => 'success',
            'deleted' => $deleted,
        ], admin_url('admin.php')));
        
        exit;
    }

    /**
     * Handle enable/disable logs toggle
     */
    public static function handle_toggle_logs() {

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('zap_events_toggle_logs');

        $current = get_option('zap_events_log_enabled', true);
        update_option('zap_events_log_enabled', !$current);

        wp_redirect(add_query_arg([
            'page'         => 'zap-tutor-events-settings',
            'logs_toggled' => '1',
        ], admin_url('admin.php')));

        exit;
    }
}
