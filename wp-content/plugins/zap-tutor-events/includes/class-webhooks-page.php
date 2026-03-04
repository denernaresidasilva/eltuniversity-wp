<?php
/**
 * Webhooks Management Page
 *
 * Dedicated page to create, edit and delete webhooks.
 * Webhooks are stored as a JSON array in the option `zap_events_webhooks_list`.
 *
 * @package ZapTutorEvents
 * @since 1.2.0
 */

namespace ZapTutorEvents;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WebhooksPage {

    const OPTION_KEY = 'zap_events_webhooks_list';

    /** Register actions */
    public static function init() {
        add_action( 'admin_post_zap_events_save_webhook',   [ self::class, 'handle_save'   ] );
        add_action( 'admin_post_zap_events_delete_webhook', [ self::class, 'handle_delete' ] );
    }

    // -------------------------------------------------------------------------
    // Page render
    // -------------------------------------------------------------------------

    public static function render() {

        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $webhooks   = self::get_webhooks();
        $all_events = Events::registry();

        // Determine if we are editing an existing webhook
        $edit_id  = isset( $_GET['edit_id'] ) ? sanitize_text_field( wp_unslash( $_GET['edit_id'] ) ) : '';
        $edit_wh  = null;
        if ( $edit_id ) {
            foreach ( $webhooks as $wh ) {
                if ( $wh['id'] === $edit_id ) {
                    $edit_wh = $wh;
                    break;
                }
            }
        }
        ?>
        <div class="wrap zap-events-page">

            <!-- Page header -->
            <div class="zap-events-header">
                <div class="zap-events-header__info">
                    <span class="zap-events-header__icon">🔗</span>
                    <div>
                        <h1 class="zap-events-header__title"><?php esc_html_e( 'Webhooks', 'zap-tutor-events' ); ?></h1>
                        <p class="zap-events-header__sub"><?php esc_html_e( 'Envie eventos do Tutor LMS para qualquer URL externa', 'zap-tutor-events' ); ?></p>
                    </div>
                </div>
                <div class="zap-events-header__nav">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events' ) ); ?>" class="zap-nav-btn">← Dashboard</a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events-settings' ) ); ?>" class="zap-nav-btn">⚙️ Configurações</a>
                </div>
            </div>

            <?php if ( isset( $_GET['saved'] ) ): ?>
                <div class="notice notice-success is-dismissible"><p>✅ Webhook salvo com sucesso!</p></div>
            <?php endif; ?>
            <?php if ( isset( $_GET['deleted'] ) ): ?>
                <div class="notice notice-info is-dismissible"><p>🗑️ Webhook removido.</p></div>
            <?php endif; ?>

            <div class="zap-webhooks-layout">

                <!-- ── ADD / EDIT FORM ────────────────────────────────── -->
                <div class="zap-webhooks-form-col">
                    <div class="zap-events-card">
                        <div class="zap-events-card__hdr">
                            <?php echo $edit_wh ? '✏️ ' . esc_html__( 'Editar Webhook', 'zap-tutor-events' ) : '➕ ' . esc_html__( 'Novo Webhook', 'zap-tutor-events' ); ?>
                        </div>
                        <div class="zap-events-card__body">
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                <?php wp_nonce_field( 'zap_events_save_webhook' ); ?>
                                <input type="hidden" name="action" value="zap_events_save_webhook">
                                <?php if ( $edit_wh ): ?>
                                    <input type="hidden" name="webhook_id" value="<?php echo esc_attr( $edit_wh['id'] ); ?>">
                                <?php endif; ?>

                                <div class="zap-field-row">
                                    <label class="zap-field-label" for="wh_name"><?php esc_html_e( 'Nome', 'zap-tutor-events' ); ?></label>
                                    <input type="text"
                                           id="wh_name"
                                           name="wh_name"
                                           class="zap-input zap-input--wide"
                                           placeholder="Ex: Zapier – Matricula"
                                           value="<?php echo esc_attr( $edit_wh['name'] ?? '' ); ?>"
                                           required>
                                </div>

                                <div class="zap-field-row">
                                    <label class="zap-field-label" for="wh_url"><?php esc_html_e( 'URL do Webhook', 'zap-tutor-events' ); ?></label>
                                    <input type="url"
                                           id="wh_url"
                                           name="wh_url"
                                           class="zap-input zap-input--wide"
                                           placeholder="https://hooks.zapier.com/hooks/catch/..."
                                           value="<?php echo esc_attr( $edit_wh['url'] ?? '' ); ?>"
                                           required>
                                    <span class="zap-field-desc">Destino para POST: Zapier, n8n, Make, etc.</span>
                                </div>

                                <div class="zap-field-row">
                                    <label class="zap-field-label"><?php esc_html_e( 'Eventos', 'zap-tutor-events' ); ?></label>
                                    <span class="zap-field-desc"><?php esc_html_e( 'Deixe todos desmarcados para enviar todos os eventos.', 'zap-tutor-events' ); ?></span>
                                    <div class="zap-events-checklist">
                                        <?php
                                        $sel_events = $edit_wh['events'] ?? [];
                                        foreach ( $all_events as $key => $label ) :
                                        ?>
                                            <label class="zap-check-item">
                                                <input type="checkbox"
                                                       name="wh_events[]"
                                                       value="<?php echo esc_attr( $key ); ?>"
                                                       <?php checked( in_array( $key, $sel_events, true ) ); ?>>
                                                <span class="zap-check-label"><?php echo esc_html( $label ); ?></span>
                                                <code class="zap-check-key"><?php echo esc_html( $key ); ?></code>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="zap-field-row zap-field-row--inline">
                                    <label class="zap-field-label" for="wh_timeout"><?php esc_html_e( 'Timeout (s)', 'zap-tutor-events' ); ?></label>
                                    <input type="number"
                                           id="wh_timeout"
                                           name="wh_timeout"
                                           class="zap-input zap-input--sm"
                                           min="5"
                                           max="60"
                                           value="<?php echo esc_attr( $edit_wh['timeout'] ?? 10 ); ?>">
                                </div>

                                <div class="zap-field-row">
                                    <label class="zap-field-label" for="wh_active"><?php esc_html_e( 'Ativo', 'zap-tutor-events' ); ?></label>
                                    <label class="zap-switch">
                                        <input type="checkbox"
                                               id="wh_active"
                                               name="wh_active"
                                               value="1"
                                               <?php checked( $edit_wh['active'] ?? true ); ?>>
                                        <span class="slider"></span>
                                    </label>
                                </div>

                                <div class="zap-submit-row">
                                    <button type="submit" class="zap-btn zap-btn-primary zap-btn--lg">
                                        <?php echo $edit_wh ? '💾 ' . esc_html__( 'Atualizar Webhook', 'zap-tutor-events' ) : '➕ ' . esc_html__( 'Criar Webhook', 'zap-tutor-events' ); ?>
                                    </button>
                                    <?php if ( $edit_wh ): ?>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=zap-tutor-events-webhooks' ) ); ?>" class="zap-btn zap-btn-secondary">
                                            ✕ <?php esc_html_e( 'Cancelar', 'zap-tutor-events' ); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- ── WEBHOOKS LIST ──────────────────────────────────── -->
                <div class="zap-webhooks-list-col">
                    <div class="zap-events-card">
                        <div class="zap-events-card__hdr">
                            📋 <?php esc_html_e( 'Webhooks Cadastrados', 'zap-tutor-events' ); ?>
                            <span class="zap-badge zap-badge--count"><?php echo count( $webhooks ); ?></span>
                        </div>
                        <div class="zap-events-card__body zap-events-card__body--flush">
                            <?php if ( empty( $webhooks ) ): ?>
                                <div class="zap-empty-state">
                                    <span class="zap-empty-state__icon">🔗</span>
                                    <p><?php esc_html_e( 'Nenhum webhook cadastrado ainda.', 'zap-tutor-events' ); ?></p>
                                    <p class="zap-empty-state__sub"><?php esc_html_e( 'Use o formulário ao lado para criar o primeiro.', 'zap-tutor-events' ); ?></p>
                                </div>
                            <?php else: ?>
                                <ul class="zap-wh-list">
                                    <?php foreach ( $webhooks as $wh ):
                                        $is_editing = ( $edit_id === $wh['id'] );
                                        $event_count = count( $wh['events'] );
                                    ?>
                                    <li class="zap-wh-item<?php echo $is_editing ? ' zap-wh-item--editing' : ''; ?>">
                                        <div class="zap-wh-item__header">
                                            <div class="zap-wh-item__name">
                                                <?php echo esc_html( $wh['name'] ); ?>
                                                <?php if ( ! empty( $wh['active'] ) ): ?>
                                                    <span class="zap-badge zap-badge--ok">● Ativo</span>
                                                <?php else: ?>
                                                    <span class="zap-badge zap-badge--off">○ Pausado</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="zap-wh-item__actions">
                                                <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'zap-tutor-events-webhooks', 'edit_id' => $wh['id'] ], admin_url( 'admin.php' ) ) ); ?>"
                                                   class="zap-icon-btn zap-icon-btn--edit"
                                                   title="<?php esc_attr_e( 'Editar', 'zap-tutor-events' ); ?>">✏️</a>
                                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
                                                    <?php wp_nonce_field( 'zap_events_delete_webhook' ); ?>
                                                    <input type="hidden" name="action" value="zap_events_delete_webhook">
                                                    <input type="hidden" name="webhook_id" value="<?php echo esc_attr( $wh['id'] ); ?>">
                                                    <button type="submit"
                                                            class="zap-icon-btn zap-icon-btn--del"
                                                            title="<?php esc_attr_e( 'Excluir', 'zap-tutor-events' ); ?>"
                                                            onclick="return confirm('<?php echo esc_js( __( 'Excluir este webhook?', 'zap-tutor-events' ) ); ?>');">🗑️</button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="zap-wh-item__url"><?php echo esc_html( $wh['url'] ); ?></div>
                                        <div class="zap-wh-item__meta">
                                            <span>⚡ <?php echo $event_count > 0 ? sprintf( _n( '%d evento', '%d eventos', $event_count, 'zap-tutor-events' ), $event_count ) : esc_html__( 'Todos os eventos', 'zap-tutor-events' ); ?></span>
                                            <span>⏱ <?php echo esc_html( ( $wh['timeout'] ?? 10 ) . 's' ); ?></span>
                                            <span>🗓 <?php
                                            $ts = isset( $wh['created_at'] ) ? strtotime( $wh['created_at'] ) : false;
                                            echo $ts ? esc_html( date_i18n( 'd/m/Y', $ts ) ) : '—';
                                            ?></span>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div><!-- /.zap-webhooks-layout -->
        </div><!-- /.wrap -->
        <?php
    }

    // -------------------------------------------------------------------------
    // CRUD handlers
    // -------------------------------------------------------------------------

    public static function handle_save() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'zap_events_save_webhook' );

        $webhooks = self::get_webhooks();

        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $id      = sanitize_text_field( wp_unslash( $_POST['webhook_id'] ?? '' ) );
        $name    = sanitize_text_field( wp_unslash( $_POST['wh_name']    ?? '' ) );
        $url     = sanitize_url( wp_unslash( $_POST['wh_url']            ?? '' ) );
        $events  = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) ( $_POST['wh_events'] ?? [] ) ) );
        $timeout = absint( $_POST['wh_timeout'] ?? 10 );
        $active  = isset( $_POST['wh_active'] ) ? true : false;
        // phpcs:enable

        if ( empty( $name ) || empty( $url ) ) {
            wp_die( 'Nome e URL são obrigatórios.' );
        }

        if ( $id ) {
            // Update existing
            foreach ( $webhooks as &$wh ) {
                if ( $wh['id'] === $id ) {
                    $wh['name']    = $name;
                    $wh['url']     = $url;
                    $wh['events']  = $events;
                    $wh['timeout'] = $timeout;
                    $wh['active']  = $active;
                    break;
                }
            }
            unset( $wh );
        } else {
            // New webhook
            $webhooks[] = [
                'id'         => wp_generate_uuid4(),
                'name'       => $name,
                'url'        => $url,
                'events'     => $events,
                'timeout'    => $timeout,
                'active'     => $active,
                'created_at' => current_time( 'mysql' ),
            ];
        }

        update_option( self::OPTION_KEY, $webhooks );

        wp_redirect( add_query_arg( [ 'page' => 'zap-tutor-events-webhooks', 'saved' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function handle_delete() {

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        check_admin_referer( 'zap_events_delete_webhook' );

        $id = sanitize_text_field( $_POST['webhook_id'] ?? '' );
        if ( $id ) {
            $webhooks = array_values( array_filter( self::get_webhooks(), function ( $wh ) use ( $id ) {
                return $wh['id'] !== $id;
            } ) );
            update_option( self::OPTION_KEY, $webhooks );
        }

        wp_redirect( add_query_arg( [ 'page' => 'zap-tutor-events-webhooks', 'deleted' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }

    // -------------------------------------------------------------------------
    // Data helpers
    // -------------------------------------------------------------------------

    /** @return array List of webhook configs */
    public static function get_webhooks() {
        return (array) get_option( self::OPTION_KEY, [] );
    }
}
