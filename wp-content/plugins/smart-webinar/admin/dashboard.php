<?php
namespace SmartWebinar\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

class Dashboard {

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
    }

    public static function register_menu(): void {
        add_menu_page(
            __( 'Smart Webinar', 'smart-webinar' ),
            __( 'Smart Webinar', 'smart-webinar' ),
            'manage_options',
            'smart-webinar',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-video-alt2',
            30
        );
        add_submenu_page(
            'smart-webinar',
            __( 'Dashboard', 'smart-webinar' ),
            __( 'Dashboard', 'smart-webinar' ),
            'manage_options',
            'smart-webinar',
            [ __CLASS__, 'render_dashboard' ]
        );
        add_submenu_page(
            'smart-webinar',
            __( 'Webinars', 'smart-webinar' ),
            __( 'Webinars', 'smart-webinar' ),
            'manage_options',
            'smart-webinar-list',
            [ __CLASS__, 'render_list' ]
        );
        // Hidden page — accessible via URL but not shown in sidebar menu
        add_submenu_page(
            null,
            __( 'Editor de Webinar', 'smart-webinar' ),
            __( 'Editor de Webinar', 'smart-webinar' ),
            'manage_options',
            'smart-webinar-new',
            [ 'SmartWebinar\\Admin\\WebinarEditor', 'render' ]
        );
        add_submenu_page(
            'smart-webinar',
            __( 'Configurações', 'smart-webinar' ),
            __( 'Configurações', 'smart-webinar' ),
            'manage_options',
            'smart-webinar-settings',
            [ __CLASS__, 'render_settings' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'smart-webinar' ) === false ) return;
        wp_enqueue_style( 'sw-admin', SMART_WEBINAR_URL . 'assets/css/admin.css', [], SMART_WEBINAR_VERSION );
        wp_enqueue_script( 'sw-admin', SMART_WEBINAR_URL . 'assets/js/admin.js', [ 'jquery', 'wp-color-picker' ], SMART_WEBINAR_VERSION, true );
        wp_enqueue_style( 'wp-color-picker' );
        // Media uploader for room appearance step
        wp_enqueue_media();
        wp_localize_script( 'sw-admin', 'swAdmin', [
            'nonce'   => wp_create_nonce( 'sw_admin_nonce' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'i18n'    => [
                'confirm_delete'   => __( 'Tem certeza que deseja excluir este webinar? Esta ação não pode ser desfeita.', 'smart-webinar' ),
                'saving'           => __( 'Salvando…', 'smart-webinar' ),
                'saved'            => __( 'Salvo!', 'smart-webinar' ),
                'error_save'       => __( 'Erro ao salvar', 'smart-webinar' ),
                'error_connection' => __( 'Erro de conexão.', 'smart-webinar' ),
                'select_image'     => __( 'Selecionar Imagem', 'smart-webinar' ),
                'select_video'     => __( 'Selecionar Vídeo', 'smart-webinar' ),
                'use_file'         => __( 'Usar este arquivo', 'smart-webinar' ),
            ],
        ] );
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $stats = self::get_stats();
        ?>
        <div class="wrap sw-dashboard">
            <h1><?php esc_html_e( 'Smart Webinar – Dashboard', 'smart-webinar' ); ?></h1>

            <div class="sw-stats-grid">
                <?php
                $cards = [
                    [ 'label' => __( 'Total de Visitantes',      'smart-webinar' ), 'value' => $stats['visitors'],      'icon' => 'dashicons-groups' ],
                    [ 'label' => __( 'Participantes',             'smart-webinar' ), 'value' => $stats['participants'],  'icon' => 'dashicons-admin-users' ],
                    [ 'label' => __( 'Tempo Médio (min)',          'smart-webinar' ), 'value' => $stats['avg_watch'],     'icon' => 'dashicons-clock' ],
                    [ 'label' => __( 'Viram a Oferta',            'smart-webinar' ), 'value' => $stats['offer_shown'],   'icon' => 'dashicons-tag' ],
                    [ 'label' => __( 'Cliques na Oferta',         'smart-webinar' ), 'value' => $stats['offer_clicked'], 'icon' => 'dashicons-external' ],
                    [ 'label' => __( 'Vendas',                    'smart-webinar' ), 'value' => $stats['conversions'],   'icon' => 'dashicons-cart' ],
                    [ 'label' => __( 'Taxa de Conversão',         'smart-webinar' ), 'value' => $stats['conv_rate'] . '%', 'icon' => 'dashicons-chart-line' ],
                ];
                foreach ( $cards as $card ) :
                ?>
                <div class="sw-stat-card">
                    <span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>"></span>
                    <div class="sw-stat-value"><?php echo esc_html( $card['value'] ); ?></div>
                    <div class="sw-stat-label"><?php echo esc_html( $card['label'] ); ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="sw-recent-webinars">
                <h2><?php esc_html_e( 'Webinars Recentes', 'smart-webinar' ); ?></h2>
                <?php self::render_webinars_table(); ?>
            </div>
        </div>
        <?php
    }

    public static function render_list(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        ?>
        <div class="wrap sw-dashboard">
            <h1 class="wp-heading-inline"><?php esc_html_e( 'Webinars', 'smart-webinar' ); ?></h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-webinar-new' ) ); ?>" class="page-title-action">
                <?php esc_html_e( '+ Novo Webinar', 'smart-webinar' ); ?>
            </a>
            <hr class="wp-header-end">
            <?php self::render_webinars_table( 100 ); ?>
        </div>
        <?php
    }

    private static function render_webinars_table( int $limit = 10 ): void {
        $webinars = \SmartWebinar\Core\WebinarEngine::get_all( [ 'limit' => $limit ] );
        if ( empty( $webinars ) ) {
            echo '<p>' . esc_html__( 'Nenhum webinar criado ainda.', 'smart-webinar' ) . '</p>';
            return;
        }
        $mode_labels = [
            'live'     => __( 'Ao Vivo', 'smart-webinar' ),
            'evergreen' => __( 'Evergreen', 'smart-webinar' ),
            'ondemand' => __( 'On Demand', 'smart-webinar' ),
        ];
        echo '<table class="wp-list-table widefat fixed striped sw-table">';
        echo '<thead><tr>';
        foreach ( [ 'ID', __( 'Título', 'smart-webinar' ), __( 'Modo', 'smart-webinar' ), __( 'Status', 'smart-webinar' ), __( 'Shortcode', 'smart-webinar' ), __( 'Ações', 'smart-webinar' ) ] as $col ) {
            echo '<th>' . esc_html( $col ) . '</th>';
        }
        echo '</tr></thead><tbody>';
        foreach ( $webinars as $w ) {
            $edit_url  = admin_url( 'admin.php?page=smart-webinar-new&webinar_id=' . absint( $w->id ) );
            $mode_label = $mode_labels[ $w->mode ] ?? esc_html( $w->mode );
            echo '<tr>';
            echo '<td>' . absint( $w->id ) . '</td>';
            echo '<td><a href="' . esc_url( $edit_url ) . '">' . esc_html( $w->title ) . '</a></td>';
            echo '<td>' . esc_html( $mode_label ) . '</td>';
            echo '<td><span class="sw-badge sw-badge--' . esc_attr( $w->status ) . '">' . esc_html( ucfirst( $w->status ) ) . '</span></td>';
            echo '<td><code>[smart_webinar id="' . absint( $w->id ) . '"]</code></td>';
            echo '<td class="sw-table-actions">';
            echo '<a href="' . esc_url( $edit_url ) . '" class="button button-small">' . esc_html__( 'Editar', 'smart-webinar' ) . '</a> ';
            echo '<button type="button" class="button button-small sw-delete-webinar" data-id="' . absint( $w->id ) . '" style="color:#a00;border-color:#a00">' . esc_html__( 'Excluir', 'smart-webinar' ) . '</button>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public static function render_settings(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( isset( $_POST['sw_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sw_settings_nonce'] ) ), 'sw_save_settings' ) ) {
            $settings = [
                'youtube_api_key' => sanitize_text_field( wp_unslash( $_POST['youtube_api_key'] ?? '' ) ),
                'default_mode'    => sanitize_key( wp_unslash( $_POST['default_mode'] ?? 'simulated' ) ),
            ];
            update_option( 'smart_webinar_settings', $settings );
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Configurações salvas.', 'smart-webinar' ) . '</p></div>';
        }
        $settings = get_option( 'smart_webinar_settings', [] );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Configurações – Smart Webinar', 'smart-webinar' ); ?></h1>
            <form method="post">
                <?php wp_nonce_field( 'sw_save_settings', 'sw_settings_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e( 'YouTube API Key', 'smart-webinar' ); ?></th>
                        <td><input type="text" name="youtube_api_key" class="regular-text"
                            value="<?php echo esc_attr( $settings['youtube_api_key'] ?? '' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Modo Padrão', 'smart-webinar' ); ?></th>
                        <td>
                            <select name="default_mode">
                                <?php foreach ( [ 'live' => __( 'Ao Vivo', 'smart-webinar' ), 'evergreen' => 'Evergreen', 'ondemand' => 'On Demand' ] as $v => $l ) : ?>
                                <option value="<?php echo esc_attr( $v ); ?>"
                                    <?php selected( $settings['default_mode'] ?? 'evergreen', $v ); ?>>
                                    <?php echo esc_html( $l ); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Salvar Configurações', 'smart-webinar' ) ); ?>
            </form>
        </div>
        <?php
    }

    private static function get_stats(): array {
        global $wpdb;
        $visitors     = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}webinar_sessions" ); // phpcs:ignore
        $participants = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}webinar_sessions WHERE watch_time > 60" ); // phpcs:ignore
        $avg_watch    = (int) $wpdb->get_var( "SELECT ROUND(AVG(watch_time)/60,1) FROM {$wpdb->prefix}webinar_sessions WHERE watch_time > 0" ); // phpcs:ignore
        $offer_shown  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}webinar_tracking WHERE event_type = 'offer_show'" ); // phpcs:ignore
        $offer_clicked= (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}webinar_tracking WHERE event_type = 'offer_click'" ); // phpcs:ignore
        $conversions  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}webinar_conversions" ); // phpcs:ignore
        $conv_rate    = $offer_shown > 0 ? round( ( $conversions / $offer_shown ) * 100, 1 ) : 0;
        return compact( 'visitors', 'participants', 'avg_watch', 'offer_shown', 'offer_clicked', 'conversions', 'conv_rate' );
    }
}
