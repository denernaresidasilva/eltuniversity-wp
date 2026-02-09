<?php
/**
 * Plugin Name: Tutor LMS Gamification Simple
 * Description: Sistema simples de pré-requisitos e gamificação para Tutor LMS
 * Version: 1.1.0
 * Author: Raul Cruz
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Text Domain: tutor-lms-gamification
 */

// Segurança
if (!defined('ABSPATH')) {
    exit;
}

class TutorLMSGamificationSimple {

    private $version = '1.1.0';
    private $options;

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));

        // Carregar opções
        $this->options = get_option('tutor_gamification_options', array(
            'redirect_page' => 0,
        ));
    }

    public function init() {
        if (!function_exists('tutor')) {
            add_action('admin_notices', array($this, 'tutor_missing_notice'));
            return;
        }

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));

        add_shortcode('user_gaming_status', array($this, 'status_shortcode'));
        add_shortcode('blocked_lesson_info', array($this, 'blocked_lesson_info_shortcode'));

        add_action('tutor_lesson_completed_after', array($this, 'on_lesson_completed'), 10, 2);
        add_action('template_redirect', array($this, 'check_prerequisites'));

        add_action('wp_ajax_tlg_save_config', array($this, 'ajax_save_config'));
        add_action('wp_ajax_tlg_delete_config', array($this, 'ajax_delete_config'));
        add_action('wp_ajax_tlg_save_settings', array($this, 'ajax_save_settings'));
    }

    public function tutor_missing_notice() {
        echo '<div class="notice notice-error"><p><strong>'.__('Tutor LMS Gamification', 'tutor-lms-gamification').':</strong> '.__('Requer o plugin Tutor LMS ativo.','tutor-lms-gamification').'</p></div>';
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutor_gamification';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            lesson_id bigint(20) NOT NULL,
            required_lesson_id bigint(20) DEFAULT NULL,
            status_name varchar(255) DEFAULT NULL,
            status_data longtext DEFAULT NULL,
            PRIMARY KEY (id),
            KEY lesson_id (lesson_id),
            KEY type_lesson (type, lesson_id)
        ) {$wpdb->get_charset_collate()};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $user_table = $wpdb->prefix . 'tutor_user_statuses';

        $sql2 = "CREATE TABLE IF NOT EXISTS $user_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            status_name varchar(255) NOT NULL,
            date_earned datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_status (user_id, status_name)
        ) {$wpdb->get_charset_collate()};";

        dbDelta($sql2);

        if (!get_option('tutor_gamification_options')) {
            update_option('tutor_gamification_options', array(
                'redirect_page' => 0,
            ));
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Tutor Gamification',
            'Tutor Gamification',
            'manage_options',
            'tutor-gamification',
            array($this, 'admin_page'),
            'dashicons-awards'
        );
    }

    public function admin_init() {
        wp_enqueue_script('jquery');
    }

    public function admin_page() {
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'lessons';
        ?>
        <div class="wrap">
            <h1>Tutor LMS Gamification</h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=tutor-gamification&tab=lessons" class="nav-tab <?php echo $active_tab === 'lessons' ? 'nav-tab-active' : ''; ?>"><?php _e('Aulas','tutor-lms-gamification'); ?></a>
                <a href="?page=tutor-gamification&tab=status" class="nav-tab <?php echo $active_tab === 'status' ? 'nav-tab-active' : ''; ?>"><?php _e('Status','tutor-lms-gamification'); ?></a>
                <a href="?page=tutor-gamification&tab=prerequisites" class="nav-tab <?php echo $active_tab === 'prerequisites' ? 'nav-tab-active' : ''; ?>"><?php _e('Pré-requisitos','tutor-lms-gamification'); ?></a>
                <a href="?page=tutor-gamification&tab=settings" class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>"><?php _e('Configurações','tutor-lms-gamification'); ?></a>
                <a href="?page=tutor-gamification&tab=users" class="nav-tab <?php echo $active_tab === 'users' ? 'nav-tab-active' : ''; ?>"><?php _e('Usuários','tutor-lms-gamification'); ?></a>
                <a href="?page=tutor-gamification&tab=debug" class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>"><?php _e('Debug','tutor-lms-gamification'); ?></a>
            </h2>
            <?php
            switch ($active_tab) {
                case 'lessons':
                    $this->lessons_tab();
                    break;
                case 'status':
                    $this->status_tab();
                    break;
                case 'prerequisites':
                    $this->prerequisites_tab();
                    break;
                case 'settings':
                    $this->settings_tab();
                    break;
                case 'users':
                    $this->users_tab();
                    break;
                case 'debug':
                    $this->debug_tab();
                    break;
            }
            ?>
        </div>
        <script>
        jQuery(document).ready(function($) {

            $('#status-form').on('submit', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'tlg_save_config',
                    type: 'status',
                    nonce: '<?php echo wp_create_nonce('tlg_nonce'); ?>',
                    lesson_id: $('#status_lesson').val(),
                    status_name: $('#status_name').val(),
                    status_icon: $('#status_icon').val(),
                    status_color: $('#status_color').val(),
                    status_text_color: $('#status_text_color').val(),
                    status_description: $('#status_description').val()
                }, function(response) {
                    if (response.success) {
                        alert('Status salvo com sucesso!');
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                    }
                });
            });

            $('#prerequisite-form').on('submit', function(e) {
                e.preventDefault();
                var lesson = $('#prereq_lesson').val();
                var required = $('#prereq_required').val();

                if (lesson === required) {
                    alert('Uma aula não pode ser pré-requisito dela mesma!');
                    return;
                }

                $.post(ajaxurl, {
                    action: 'tlg_save_config',
                    type: 'prerequisite',
                    nonce: '<?php echo wp_create_nonce('tlg_nonce'); ?>',
                    lesson_id: lesson,
                    required_lesson_id: required
                }, function(response) {
                    if (response.success) {
                        alert('Pré-requisito salvo!');
                        location.reload();
                    } else {
                        alert('Erro: ' + response.data);
                    }
                });
            });

            $('#settings-form').on('submit', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'tlg_save_settings',
                    nonce: '<?php echo wp_create_nonce('tlg_nonce'); ?>',
                    redirect_page: $('#redirect_page').val()
                }, function(response) {
                    if (response.success) {
                        alert('Configurações salvas!');
                    } else {
                        alert('Erro: ' + response.data);
                    }
                });
            });

            $('.delete-item').click(function() {
                if (confirm('Deletar este item?')) {
                    var id = $(this).data('id');
                    $.post(ajaxurl, {
                        action: 'tlg_delete_config',
                        nonce: '<?php echo wp_create_nonce('tlg_nonce'); ?>',
                        id: id
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        }
                    });
                }
            });
        });
        </script>
        <style>
        .config-item {
            background: #f9f9f9;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-preview {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
            background: transparent;
            border: none;
        }
        .debug-item {
            background: #f1f1f1;
            padding: 10px;
            margin: 5px 0;
            border-radius: 3px;
            font-family: monospace;
        }
        </style>
        <?php
    }

    private function lessons_tab() {
        echo '<h2>'.__('Todas as Aulas do Tutor LMS','tutor-lms-gamification').'</h2>';
        $lessons = $this->get_tutor_lessons();

        if (empty($lessons)) {
            echo '<div class="notice notice-warning"><p>'.__('Nenhuma aula encontrada. Verifique se há cursos publicados com aulas no Tutor LMS.','tutor-lms-gamification').'</p></div>';
            return;
        }
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>ID</th><th>'.__('Título','tutor-lms-gamification').'</th><th>'.__('Curso','tutor-lms-gamification').'</th><th>'.__('Configurações','tutor-lms-gamification').'</th></tr></thead>';
        echo '<tbody>';
        foreach ($lessons as $lesson) {
            $course_title = get_the_title($lesson->post_parent);
            $configs = $this->get_lesson_configs($lesson->ID);
            echo '<tr>';
            echo '<td>' . $lesson->ID . '</td>';
            echo '<td><strong>' . esc_html($lesson->post_title) . '</strong></td>';
            echo '<td>' . esc_html($course_title) . '</td>';
            echo '<td>';
            foreach ($configs as $config) {
                if ($config->type === 'status') {
                    echo '<span style="color: green;">✓ '.__('Gera Status','tutor-lms-gamification').'</span><br/>';
                } elseif ($config->type === 'prerequisite') {
                    echo '<span style="color: orange;">⚠ '.__('Tem Pré-requisito','tutor-lms-gamification').'</span><br/>';
                }
            }
            echo '</td></tr>';
        }
        echo '</tbody></table>';
    }

    private function status_tab() {
        echo '<h2>'.__('Configurar Status de Gamificação','tutor-lms-gamification').'</h2>';
        ?>
        <form id="status-form">
            <table class="form-table">
                <tr>
                    <th><?php _e('Aula que Libera o Status','tutor-lms-gamification'); ?></th>
                    <td>
                        <select id="status_lesson" required>
                            <option value=""><?php _e('Selecione uma aula','tutor-lms-gamification'); ?></option>
                            <?php $this->lessons_options(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Nome do Status','tutor-lms-gamification'); ?></th>
                    <td><input type="text" id="status_name" placeholder="Ex: Iniciante, Expert" required /></td>
                </tr>
                <tr>
                    <th><?php _e('Ícone','tutor-lms-gamification'); ?></th>
                    <td><input type="text" id="status_icon" placeholder="✓ ★ ♥ ⚡" /></td>
                </tr>
                <tr>
                    <th><?php _e('Cor do Fundo','tutor-lms-gamification'); ?></th>
                    <td><input type="color" id="status_color" value="#3498db" /></td>
                </tr>
                <tr>
                    <th><?php _e('Cor do Texto','tutor-lms-gamification'); ?></th>
                    <td>
                        <select id="status_text_color">
                            <option value="white" selected><?php _e('Branco','tutor-lms-gamification'); ?></option>
                            <option value="black"><?php _e('Preto','tutor-lms-gamification'); ?></option>
                        </select>
                        <p class="description"><?php _e('Define se o texto na etiqueta será branco ou preto.','tutor-lms-gamification'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Descrição','tutor-lms-gamification'); ?></th>
                    <td><textarea id="status_description" placeholder="<?php _e('Descrição do status','tutor-lms-gamification'); ?>"></textarea></td>
                </tr>
            </table>
            <input type="submit" class="button-primary" value="<?php _e('Salvar Status','tutor-lms-gamification'); ?>" />
        </form>
        <h3><?php _e('Status Configurados','tutor-lms-gamification'); ?></h3>
        <?php $this->display_status_configs(); ?>
        <?php
    }

    private function prerequisites_tab() {
        echo '<h2>'.__('Configurar Pré-requisitos','tutor-lms-gamification').'</h2>';
        ?>
        <form id="prerequisite-form">
            <table class="form-table">
                <tr>
                    <th><?php _e('Aula que será Bloqueada','tutor-lms-gamification'); ?></th>
                    <td>
                        <select id="prereq_lesson" required>
                            <option value=""><?php _e('Selecione a aula','tutor-lms-gamification'); ?></option>
                            <?php $this->lessons_options(); ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Aula que deve ser Concluída Primeiro','tutor-lms-gamification'); ?></th>
                    <td>
                        <select id="prereq_required" required>
                            <option value=""><?php _e('Selecione o pré-requisito','tutor-lms-gamification'); ?></option>
                            <?php $this->lessons_options(); ?>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" class="button-primary" value="<?php _e('Salvar Pré-requisito','tutor-lms-gamification'); ?>" />
        </form>
        <h3><?php _e('Pré-requisitos Configurados','tutor-lms-gamification'); ?></h3>
        <?php $this->display_prerequisites(); ?>
        <?php
    }

    private function settings_tab() {
        echo '<h2>'.__('Configurações Gerais','tutor-lms-gamification').'</h2>';
        $pages = get_pages();
        ?>
        <form id="settings-form">
            <table class="form-table">
                <tr>
                    <th><?php _e('Página de Redirecionamento para Aulas Bloqueadas','tutor-lms-gamification'); ?></th>
                    <td>
                        <select id="redirect_page">
                            <option value="0"><?php _e('Usar tela padrão de bloqueio','tutor-lms-gamification'); ?></option>
                            <?php
                            foreach ($pages as $page) {
                                $selected = ($this->options['redirect_page'] == $page->ID) ? 'selected' : '';
                                echo '<option value="' . $page->ID . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                            }
                            ?>
                        </select>
                        <p class="description"><?php _e('Quando um usuário tentar acessar uma aula bloqueada, ele será redirecionado para esta página. As informações da aula bloqueada serão enviadas como parâmetros de URL. Você pode usar o shortcode [blocked_lesson_info] na página para exibir detalhes, mas isso é opcional.','tutor-lms-gamification'); ?></p>
                    </td>
                </tr>
            </table>
            <input type="submit" class="button-primary" value="<?php _e('Salvar Configurações','tutor-lms-gamification'); ?>" />
        </form>
        <h3><?php _e('Parâmetros de URL Disponíveis','tutor-lms-gamification'); ?></h3>
        <p><?php _e('Quando o usuário for redirecionado, os seguintes parâmetros estarão disponíveis na URL:','tutor-lms-gamification'); ?></p>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <li><code>tlg_blocked=1</code></li>
            <li><code>lesson_id</code></li>
            <li><code>lesson_title</code></li>
            <li><code>prereq_count</code></li>
        </ul>
        <?php
    }

    private function users_tab() {
        echo '<h2>'.__('Status dos Usuários','tutor-lms-gamification').'</h2>';
        global $wpdb;
        $user_table = $wpdb->prefix . 'tutor_user_statuses';

        $statuses = $wpdb->get_results("
            SELECT us.*, u.display_name, u.user_email
            FROM $user_table us
            LEFT JOIN {$wpdb->users} u ON us.user_id = u.ID
            ORDER BY us.date_earned DESC
            LIMIT 50
        ");
        if (empty($statuses)) {
            echo '<p>'.__('Nenhum usuário conquistou status ainda.','tutor-lms-gamification').'</p>';
            return;
        }
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>'.__('Usuário','tutor-lms-gamification').'</th><th>'.__('Status','tutor-lms-gamification').'</th><th>'.__('Data','tutor-lms-gamification').'</th></tr></thead>';
        echo '<tbody>';
        foreach ($statuses as $status) {
            echo '<tr>';
            echo '<td>' . esc_html($status->display_name) . '<br/><small>' . esc_html($status->user_email) . '</small></td>';
            echo '<td><strong>' . esc_html($status->status_name) . '</strong></td>';
            echo '<td>' . date('d/m/Y H:i', strtotime($status->date_earned)) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function debug_tab() {
        echo '<h2>Informações de Debug</h2>';
        global $wpdb;
        $table1 = $wpdb->prefix . 'tutor_gamification';
        $table2 = $wpdb->prefix . 'tutor_user_statuses';
        $table1_exists = $wpdb->get_var("SHOW TABLES LIKE '$table1'") === $table1;
        $table2_exists = $wpdb->get_var("SHOW TABLES LIKE '$table2'") === $table2;

        echo '<div class="debug-item"><strong>Tutor LMS:</strong> ' . (function_exists('tutor') ? '✓ Ativo' : '✗ Inativo');
        if (defined('TUTOR_VERSION')) {
            echo ' (Versão: ' . TUTOR_VERSION . ')';
        }
        echo '</div>';

        echo '<div class="debug-item"><strong>Tabela Configurações:</strong> ' . ($table1_exists ? '✓' : '✗') . '</div>';
        echo '<div class="debug-item"><strong>Tabela Usuários:</strong> ' . ($table2_exists ? '✓' : '✗') . '</div>';
        if ($table1_exists) {
            $config_count = $wpdb->get_var("SELECT COUNT(*) FROM $table1");
            echo '<div class="debug-item"><strong>Configurações Salvas:</strong> ' . $config_count . '</div>';
        }
        if ($table2_exists) {
            $status_count = $wpdb->get_var("SELECT COUNT(*) FROM $table2");
            echo '<div class="debug-item"><strong>Status de Usuários:</strong> ' . $status_count . '</div>';
        }
        echo '<div class="debug-item"><strong>Página de Redirecionamento:</strong> ' . $this->options['redirect_page'] . '</div>';
        if ($this->options['redirect_page'] > 0) {
            echo '<div class="debug-item"><strong>Título da Página:</strong> ' . get_the_title($this->options['redirect_page']) . '</div>';
            echo '<div class="debug-item"><strong>URL da Página:</strong> ' . get_permalink($this->options['redirect_page']) . '</div>';
        }
        $lessons = $this->get_tutor_lessons();
        echo '<div class="debug-item"><strong>Aulas Encontradas:</strong> ' . count($lessons) . '</div>';
        echo '<div class="debug-item"><strong>Hook lesson_completed:</strong> ' . (has_action('tutor_lesson_completed_after') ? '✓' : '✗') . '</div>';

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            echo '<div class="debug-item"><strong>Usuário Logado:</strong> ' . $user->display_name . ' (ID: ' . $user->ID . ')</div>';
            $user_statuses = $this->get_user_statuses($user->ID);
            echo '<div class="debug-item"><strong>Seus Status:</strong> ' . count($user_statuses) . '</div>';
            if (!empty($user_statuses)) {
                echo '<div class="debug-item"><strong>Último Status:</strong> ' . $user_statuses[0]->status_name . ' (' . date('d/m/Y', strtotime($user_statuses[0]->date_earned)) . ')</div>';
            }
        }
        echo '</div>';
    }

    private function get_tutor_lessons() {
        global $wpdb;
        return $wpdb->get_results("
            SELECT ID, post_title, post_parent 
            FROM {$wpdb->posts} 
            WHERE post_type = 'lesson' 
            AND post_status = 'publish' 
            ORDER BY post_title ASC
        ");
    }

    private function lessons_options() {
        $lessons = $this->get_tutor_lessons();
        foreach ($lessons as $lesson) {
            $course_title = get_the_title($lesson->post_parent);
            echo '<option value="' . $lesson->ID . '">' . esc_html($lesson->post_title) . ' (' . esc_html($course_title) . ')</option>';
        }
    }

    private function get_lesson_configs($lesson_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        return $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table WHERE lesson_id = %d
        ", $lesson_id));
    }

    private function display_status_configs() {
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';

        $configs = $wpdb->get_results("
            SELECT g.*, p.post_title as lesson_title
            FROM $table g
            LEFT JOIN {$wpdb->posts} p ON g.lesson_id = p.ID
            WHERE g.type = 'status'
            ORDER BY g.status_name ASC
        ");

        if (empty($configs)) {
            echo '<p>'.__('Nenhum status configurado.','tutor-lms-gamification').'</p>';
            return;
        }
        foreach ($configs as $config) {
            $data = maybe_unserialize($config->status_data);
            $icon = $data['icon'] ?? '';
            $color = $data['color'] ?? 'transparent';
            $text_color = $data['text_color'] ?? 'white';
            $description = $data['description'] ?? '';
            echo '<div class="config-item">';
            echo '<div>';
            echo '<strong>' . esc_html($config->status_name) . '</strong>';
            echo '<span class="status-preview" style="background: transparent; color:' . ($text_color == 'black' ? '#222' : '#fff') . ';">' . esc_html($icon.' '.$config->status_name) . '</span>';
            echo '<br/><small>'.__('Aula','tutor-lms-gamification').': ' . esc_html($config->lesson_title) . '</small>';
            if ($description) {
                echo '<br/><small>' . esc_html($description) . '</small>';
            }
            echo '</div>';
            echo '<button class="button delete-item" data-id="' . $config->id . '">'.__('Deletar','tutor-lms-gamification').'</button>';
            echo '</div>';
        }
    }

    private function display_prerequisites() {
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        $prereqs = $wpdb->get_results("
            SELECT g.*, 
                   p1.post_title as lesson_title,
                   p2.post_title as required_title
            FROM $table g
            LEFT JOIN {$wpdb->posts} p1 ON g.lesson_id = p1.ID
            LEFT JOIN {$wpdb->posts} p2 ON g.required_lesson_id = p2.ID
            WHERE g.type = 'prerequisite'
            ORDER BY p1.post_title ASC
        ");
        if (empty($prereqs)) {
            echo '<p>'.__('Nenhum pré-requisito configurado.','tutor-lms-gamification').'</p>';
            return;
        }
        foreach ($prereqs as $prereq) {
            echo '<div class="config-item">';
            echo '<div>';
            echo '<strong>' . esc_html($prereq->lesson_title) . '</strong>';
            echo '<br/><small>'.__('Requer','tutor-lms-gamification').': ' . esc_html($prereq->required_title) . '</small>';
            echo '</div>';
            echo '<button class="button delete-item" data-id="' . $prereq->id . '">'.__('Deletar','tutor-lms-gamification').'</button>';
            echo '</div>';
        }
    }

    public function on_lesson_completed($lesson_id, $user_id) {
        // Log para debug
        error_log("TLG: Aula $lesson_id concluída por usuário $user_id");

        // Verifica se deve atribuir status
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        $status_configs = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM $table 
            WHERE type = 'status' AND lesson_id = %d
        ", $lesson_id));
        foreach ($status_configs as $config) {
            $this->award_status($user_id, $config->status_name, $config->status_data);
        }
    }

    // Adaptação: atributos extraídos do status_data do registro
    private function award_status($user_id, $status_name, $status_data_serialized = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_user_statuses';

        // Remove qualquer status do usuário - status único por vez!
        $wpdb->delete($table, array('user_id' => $user_id));

        $result = $wpdb->insert($table, array(
            'user_id' => $user_id,
            'status_name' => $status_name
        ));
        if ($result) {
            error_log("TLG: Status '$status_name' atribuído ao usuário $user_id");
        }
    }

    public function check_prerequisites() {
        if (!is_singular('lesson') || !is_user_logged_in()) {
            return;
        }
        global $post;
        $lesson_id = $post->ID;
        $user_id = get_current_user_id();
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        $prereqs = $wpdb->get_results($wpdb->prepare("
            SELECT required_lesson_id FROM $table 
            WHERE type = 'prerequisite' AND lesson_id = %d
        ", $lesson_id));

        if (empty($prereqs)) return;

        $missing = array();
        foreach ($prereqs as $prereq) {
            if (!$this->is_lesson_completed($prereq->required_lesson_id, $user_id)) {
                $missing[] = array(
                    'id' => $prereq->required_lesson_id,
                    'title' => get_the_title($prereq->required_lesson_id),
                    'url' => get_permalink($prereq->required_lesson_id)
                );
            }
        }

        if (!empty($missing)) {
            if (!empty($this->options['redirect_page']) && $this->options['redirect_page'] > 0) {
                $redirect_url = get_permalink($this->options['redirect_page']);
                $params = array(
                    'tlg_blocked' => 1,
                    'lesson_id' => $lesson_id,
                    'lesson_title' => urlencode(get_the_title($lesson_id)),
                    'prereq_count' => count($missing)
                );
                foreach ($missing as $index => $prereq) {
                    $params['prereq_id_' . $index] = $prereq['id'];
                    $params['prereq_title_' . $index] = urlencode($prereq['title']);
                    $params['prereq_url_' . $index] = urlencode($prereq['url']);
                }
                $redirect_url = add_query_arg($params, $redirect_url);
                wp_redirect($redirect_url);
                exit;
            } else {
                $this->show_prerequisite_block($missing);
            }
        }
    }

    public function is_lesson_completed($lesson_id, $user_id) {
        if (function_exists('tutor_utils')) {
            if (tutor_utils()->is_completed_lesson($lesson_id, $user_id)) {
                return true;
            }
        }
        $completed = get_user_meta($user_id, '_tutor_lesson_completed_' . $lesson_id, true);
        if ($completed) {
            return true;
        }
        global $wpdb;
        $completed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) FROM {$wpdb->postmeta} 
            WHERE post_id = %d 
            AND meta_key = '_lesson_completed_by_%d'
        ", $lesson_id, $user_id));
        return $completed > 0;
    }

    private function show_prerequisite_block($missing) {
        $content = '<div style="max-width: 500px; margin: 50px auto; padding: 30px; background: white; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
        $content .= '<h2 style="color: #e74c3c;">⚠ '. __('Aula Bloqueada','tutor-lms-gamification').'</h2>';
        $content .= '<p>'. __('Complete primeiro estas aulas:', 'tutor-lms-gamification') .'</p><ul style="list-style: none; padding: 0;">';
        foreach ($missing as $item) {
            $content .= '<li style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
            <strong>' . esc_html($item['title']) . '</strong><br/>';
            $content .= '<a href="' . esc_url($item['url']) . '" style="color: #3498db;">⏩ '. __('Acessar Aula','tutor-lms-gamification').'</a></li>';
        }
        $content .= '</ul><a href="' . home_url() . '" style="display: inline-block; margin-top: 20px; padding: 10px 20px; background: #3498db; color: white; text-decoration: none; border-radius: 5px;">'.__("Voltar","tutor-lms-gamification").'</a></div>';
        wp_die($content, __('Pré-requisito Necessário','tutor-lms-gamification'));
    }

    // SHORTCODE: exibe status único do usuário, badge transparente, branco/preto.
    public function status_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>'.__('Faça login para ver seus status.','tutor-lms-gamification').'</p>';
        }
        $atts = shortcode_atts(array(
            'user_id' => get_current_user_id(),
            'style' => 'badges',
            'limit' => 1 // Apenas o status atual
        ), $atts);

        $statuses = $this->get_user_statuses($atts['user_id'], intval($atts['limit']));
        if (empty($statuses)) {
            return '<p>'.__('','tutor-lms-gamification').'</p>';
        }

        $output = '<div class="tutor-user-statuses">';
        foreach ($statuses as $status) {
            $status_data = maybe_unserialize($status->status_data ?? '');
            $icon = $status_data['icon'] ?? '';
            $text_color = $status_data['text_color'] ?? 'white';
            $output .= '<span style="background:transparent;color:'.($text_color == 'black' ? '#222' : '#fff').';padding:5px 12px;border-radius:15px;display:inline-block;font-size:14px;margin:2px;">' . esc_html($icon." ".$status->status_name) . '</span> ';
        }
        $output .= '</div>';
        return $output;
    }

    // Shortcode para informações de bloqueio (opcional, para quem quiser usar)
    public function blocked_lesson_info_shortcode($atts) {
        if (!isset($_GET['tlg_blocked']) || $_GET['tlg_blocked'] != 1) {
            return '<p>'.__('Este shortcode só funciona em páginas de redirecionamento de aulas bloqueadas.','tutor-lms-gamification').'</p>';
        }
        $lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
        $lesson_title = isset($_GET['lesson_title']) ? urldecode($_GET['lesson_title']) : __('Aula Desconhecida','tutor-lms-gamification');
        $prereq_count = isset($_GET['prereq_count']) ? intval($_GET['prereq_count']) : 0;

        $output = '<div class="blocked-lesson-info">';
        $output .= '<h2>'.__('Aula Bloqueada','tutor-lms-gamification').': ' . esc_html($lesson_title) . '</h2>';
        $output .= '<p>'.__('Para acessar esta aula, você precisa completar primeiro:','tutor-lms-gamification').'</p>';
        $output .= '<ul>';
        for ($i = 0; $i < $prereq_count; $i++) {
            if (isset($_GET['prereq_title_' . $i]) && isset($_GET['prereq_url_' . $i])) {
                $title = urldecode($_GET['prereq_title_' . $i]);
                $url = urldecode($_GET['prereq_url_' . $i]);
                $output .= '<li><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></li>';
            }
        }
        $output .= '</ul></div>';
        return $output;
    }

    private function get_user_statuses($user_id, $limit = 0) {
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_user_statuses';
        $sql = $wpdb->prepare(" SELECT * FROM $table WHERE user_id = %d ORDER BY date_earned DESC ", $user_id );
        if ($limit > 0) {
            $sql .= " LIMIT " . intval($limit);
        }
        return $wpdb->get_results($sql);
    }

    // AJAX Handlers
    public function ajax_save_config() {
        if (!wp_verify_nonce($_POST['nonce'], 'tlg_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }
        $type = sanitize_text_field($_POST['type']);
        if ($type === 'test') {
            wp_send_json_success('Teste OK!');
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        $data = array(
            'type' => $type,
            'lesson_id' => intval($_POST['lesson_id'])
        );
        if ($type === 'status') {
            $data['status_name'] = sanitize_text_field($_POST['status_name']);
            $data['status_data'] = serialize(array(
                'icon' => sanitize_text_field($_POST['status_icon']),
                'color' => sanitize_hex_color($_POST['status_color']),
                'text_color' => ($_POST['status_text_color'] === 'black') ? 'black' : 'white',
                'description' => sanitize_textarea_field($_POST['status_description'])
            ));
        } elseif ($type === 'prerequisite') {
            $data['required_lesson_id'] = intval($_POST['required_lesson_id']);
        }
        $result = $wpdb->insert($table, $data);
        if ($result) {
            wp_send_json_success('Salvo com sucesso!');
        } else {
            wp_send_json_error('Erro ao salvar: ' . $wpdb->last_error);
        }
    }

    public function ajax_delete_config() {
        if (!wp_verify_nonce($_POST['nonce'], 'tlg_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'tutor_gamification';
        $result = $wpdb->delete($table, array('id' => intval($_POST['id'])));
        wp_send_json_success();
    }

    public function ajax_save_settings() {
        if (!wp_verify_nonce($_POST['nonce'], 'tlg_nonce') || !current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }
        $options = array(
            'redirect_page' => intval($_POST['redirect_page'])
        );
        update_option('tutor_gamification_options', $options);
        $this->options = $options;
        wp_send_json_success('Configurações salvas!');
    }
}

// Inicializa
$tlg_instance = new TutorLMSGamificationSimple();

// API global para desenvolvedores
function tlg_get_user_statuses($user_id = null, $limit = 0) {
    if (!$user_id) $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'tutor_user_statuses';
    $sql = $wpdb->prepare("
        SELECT * FROM $table WHERE user_id = %d ORDER BY date_earned DESC
    ", $user_id);
    if ($limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    return $wpdb->get_results($sql);
}

function tlg_user_has_status($status_name, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    global $wpdb;
    $table = $wpdb->prefix . 'tutor_user_statuses';
    $exists = $wpdb->get_var($wpdb->prepare("
        SELECT id FROM $table WHERE user_id = %d AND status_name = %s
    ", $user_id, $status_name));
    return !empty($exists);
}

function tlg_get_pending_prerequisites($lesson_id, $user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    global $wpdb, $tlg_instance;
    $table = $wpdb->prefix . 'tutor_gamification';
    $prereqs = $wpdb->get_results($wpdb->prepare("
        SELECT g.required_lesson_id, p.post_title 
        FROM $table g
        LEFT JOIN {$wpdb->posts} p ON g.required_lesson_id = p.ID
        WHERE g.type = 'prerequisite' AND g.lesson_id = %d
    ", $lesson_id));
    if (empty($prereqs)) return array();
    $missing = array();
    foreach ($prereqs as $prereq) {
        if (!$tlg_instance->is_lesson_completed($prereq->required_lesson_id, $user_id)) {
            $missing[] = array(
                'id' => $prereq->required_lesson_id,
                'title' => $prereq->post_title,
                'url' => get_permalink($prereq->required_lesson_id)
            );
        }
    }
    return $missing;
}

function tlg_is_lesson_completed($lesson_id, $user_id = null) {
    global $tlg_instance;
    if (!$user_id) $user_id = get_current_user_id();
    return $tlg_instance->is_lesson_completed($lesson_id, $user_id);
}
