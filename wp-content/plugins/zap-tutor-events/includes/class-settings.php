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
        
        // Webhook settings
        register_setting('zap_events_settings', 'zap_events_webhook_url');
        register_setting('zap_events_settings', 'zap_events_webhook_events');
        register_setting('zap_events_settings', 'zap_events_webhook_timeout');
        register_setting('zap_events_settings', 'zap_events_webhook_logging');
        
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

        $webhook_url = get_option('zap_events_webhook_url', '');
        $webhook_events = get_option('zap_events_webhook_events', []);
        $webhook_timeout = get_option('zap_events_webhook_timeout', 10);
        $webhook_logging = get_option('zap_events_webhook_logging', true);
        $log_enabled = get_option('zap_events_log_enabled', true);
        $log_retention = get_option('zap_events_log_retention_days', 30);
        $use_queue = get_option('zap_events_use_queue', false);
        $api_key = API::get_api_key();

        $all_events = Events::registry();

        ?>
        <div class="wrap">
            <h1>Configurações - ZAP Tutor Events</h1>

            <form method="post">
                <?php wp_nonce_field('zap_events_settings'); ?>

                <h2>Configurações de Webhook</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="webhook_url">URL do Webhook</label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="webhook_url" 
                                   name="webhook_url" 
                                   value="<?php echo esc_attr($webhook_url); ?>" 
                                   class="regular-text"
                                   placeholder="https://hooks.zapier.com/hooks/catch/...">
                            <p class="description">
                                URL para enviar eventos via HTTP POST (Zapier, n8n, Make, etc). Deixe vazio para desabilitar.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label>Eventos para Webhook</label>
                        </th>
                        <td>
                            <fieldset>
                                <legend class="screen-reader-text">Selecione os eventos</legend>
                                <?php foreach ($all_events as $key => $label): ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="webhook_events[]" 
                                               value="<?php echo esc_attr($key); ?>"
                                               <?php checked(in_array($key, $webhook_events)); ?>>
                                        <?php echo esc_html($label); ?> (<code><?php echo esc_html($key); ?></code>)
                                    </label><br>
                                <?php endforeach; ?>
                                <p class="description">
                                    Selecione quais eventos devem ser enviados para o webhook. Se nenhum estiver selecionado, todos serão enviados.
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="webhook_timeout">Timeout (segundos)</label>
                        </th>
                        <td>
                            <input type="number" 
                                   id="webhook_timeout" 
                                   name="webhook_timeout" 
                                   value="<?php echo esc_attr($webhook_timeout); ?>" 
                                   min="5" 
                                   max="60" 
                                   class="small-text">
                            <p class="description">Tempo máximo de espera para resposta do webhook.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="webhook_logging">Log de Webhooks</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="webhook_logging" 
                                       name="webhook_logging" 
                                       value="1"
                                       <?php checked($webhook_logging); ?>>
                                Registrar tentativas de webhook no banco de dados
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Configurações de Logs</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="log_enabled">Ativar Logs</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="log_enabled" 
                                       name="log_enabled" 
                                       value="1"
                                       <?php checked($log_enabled); ?>>
                                Salvar logs de eventos no banco de dados
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="log_retention">Retenção de Logs</label>
                        </th>
                        <td>
                            <select id="log_retention" name="log_retention">
                                <option value="7" <?php selected($log_retention, 7); ?>>7 dias</option>
                                <option value="30" <?php selected($log_retention, 30); ?>>30 dias</option>
                                <option value="60" <?php selected($log_retention, 60); ?>>60 dias</option>
                                <option value="90" <?php selected($log_retention, 90); ?>>90 dias</option>
                                <option value="0" <?php selected($log_retention, 0); ?>>Infinito (não limpar)</option>
                            </select>
                            <p class="description">Logs mais antigos que este período serão automaticamente removidos.</p>
                        </td>
                    </tr>
                </table>

                <h2>Configurações Avançadas</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="use_queue">Fila de Processamento</label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="use_queue" 
                                       name="use_queue" 
                                       value="1"
                                       <?php checked($use_queue); ?>>
                                Processar webhooks em background via WP Cron (recomendado para alto volume)
                            </label>
                            <p class="description">
                                Quando ativado, webhooks são enfileirados e processados a cada minuto, evitando lentidão durante eventos.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>API REST</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label>Chave de API</label>
                        </th>
                        <td>
                            <input type="text" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text" 
                                   readonly 
                                   onclick="this.select()">
                            <p class="description">
                                Use esta chave no header <code>X-API-Key</code> para autenticar requisições à API REST.<br>
                                Endpoints disponíveis:
                            </p>
                            <ul style="list-style: disc; margin-left: 20px;">
                                <li><code>GET <?php echo rest_url('zap-events/v1/logs'); ?></code> - Listar eventos</li>
                                <li><code>GET <?php echo rest_url('zap-events/v1/stats'); ?></code> - Estatísticas</li>
                                <li><code>GET <?php echo rest_url('zap-events/v1/events'); ?></code> - Tipos de eventos</li>
                                <li><code>POST <?php echo rest_url('zap-events/v1/test'); ?></code> - Disparar evento de teste</li>
                            </ul>
                        </td>
                    </tr>
                </table>

                <p class="submit">"
                    <input type="submit" 
                           name="zap_events_save_settings" 
                           class="button button-primary" 
                           value="Salvar Configurações">
                </p>
            </form>

            <hr>

            <h2>Limpeza Manual de Logs</h2>
            <p>Remova manualmente logs antigos baseado na configuração de retenção atual.</p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('zap_events_cleanup'); ?>
                <input type="hidden" name="action" value="zap_events_cleanup_logs">
                <input type="submit" 
                       class="button button-secondary" 
                       value="Limpar Logs Antigos Agora"
                       onclick="return confirm('Tem certeza que deseja limpar os logs antigos? Esta ação não pode ser desfeita.');">
            </form>
        </div>
        <?php
    }

    /**
     * Save settings
     */
    private static function save_settings() {
        
        update_option('zap_events_webhook_url', sanitize_url($_POST['webhook_url'] ?? ''));
        update_option('zap_events_webhook_events', array_map('sanitize_text_field', $_POST['webhook_events'] ?? []));
        update_option('zap_events_webhook_timeout', absint($_POST['webhook_timeout'] ?? 10));
        update_option('zap_events_webhook_logging', isset($_POST['webhook_logging']) ? 1 : 0);
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
}
