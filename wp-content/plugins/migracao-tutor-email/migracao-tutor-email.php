<?php
/**
 * Plugin Name: Importador CSV Alunos Tutor LMS
 * Description: Importa usuários via CSV e inscreve automaticamente em cursos Tutor LMS, com senha e e-mail personalizados. Possui configurações globais persistentes.
 * Version: 1.1
 * Author: Raul & InnerAI
 */

if (!defined('ABSPATH')) exit;

class Tutor_LMS_CSV_Importer {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_forms'));
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
    }

    // CSS/Admin assets
    public function admin_assets($hook) {
        if ($hook == 'toplevel_page_importar-csv-lms') {
            wp_enqueue_style('tutor_csv_importer_style', plugin_dir_url(__FILE__) . 'tutor-csv-importer.css');
            wp_enqueue_editor();
            wp_enqueue_script('jquery');
        }
    }

    public function add_admin_menu() {
        add_menu_page(
            'Importar Usuários CSV - Tutor LMS',
            'Importar CSV LMS',
            'manage_options',
            'importar-csv-lms',
            array($this, 'admin_page'),
            'dashicons-upload',
            25
        );
    }

    // Página principal
    public function admin_page() {
        // Recarrega configs globais do banco
        $senha_padrao = get_option('tutor_csv_importer_pwd', 'Senha123*');
        $email_html   = get_option('tutor_csv_importer_html', $this->default_email_template());

        // Mensagens
        if (isset($_SESSION['tutor_csv_importer_notice'])) {
            echo $_SESSION['tutor_csv_importer_notice'];
            unset($_SESSION['tutor_csv_importer_notice']);
        }

        ?>
        <div class="wrap tutor-csv-importer-admin">
                        <!-- CONFIGURAÇÕES GLOBAIS -->
            <form method="post" class="card config-card">
                <?php wp_nonce_field('tutor_csv_importer_global'); ?>
                <h2><span class="icon">⚙️</span> Configurações Globais</h2>
                <div class="form-row">
                    <label class="label-bold">Senha padrão para novos usuários:</label>
                    <input type="text" name="cfg_senha_padrao" value="<?php echo esc_attr($senha_padrao); ?>" maxlength="100" style="max-width:250px;">
                    <div class="import-desc">Só será usada ao criar novos usuários.</div>
                </div>
                <div class="form-row">
                    <label class="label-bold">Modelo HTML do E-mail de boas-vindas:</label>
                    <?php
                    wp_editor(
                        $email_html,
                        'cfg_email_html',
                        array(
                            'textarea_name' => 'cfg_email_html',
                            'textarea_rows' => 10,
                            'teeny' => false,
                            'media_buttons' => false,
                            'tinymce' => array('resize' => false, 'wp_autoresize_on' => true)
                        )
                    );
                    ?>
                    <div class="import-desc">Variáveis: <code>(nome)</code> <code>(email)</code> <code>(senha)</code></div>
                </div>
                <div class="submit-row">
                    <input type="submit" name="salvar_cfg" class="button button-secondary" value="Salvar Configurações Globais">
                </div>
            </form>

            <!-- FORM DE IMPORTAÇÃO -->
            <form method="post" enctype="multipart/form-data" class="card main-card">
                <?php wp_nonce_field('tutor_csv_importer_import'); ?>
                <h2><span class="icon">📤</span> Importação de CSV & Matrículas</h2>
                <div class="form-row">
                    <label class="label-bold">Arquivo CSV de alunos:</label>
                    <input type="file" name="csv_file" accept=".csv" required style="max-width:350px;">
                    <div class="import-desc">A primeira coluna deve ser o e-mail. Outras colunas opcionais: nome, sobrenome…</div>
                </div>
                <div class="form-row">
                    <label class="label-bold">Cursos Tutor LMS a inscrever:</label>
                    <select name="cursos_ids[]" multiple required style="min-width:300px; max-width:700px;">
                        <?php
                        $cursos = get_posts(['post_type'=>'courses','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC']);
                        foreach ($cursos as $curso)
                            echo '<option value="'.$curso->ID.'">'.esc_html($curso->post_title).' (#'.$curso->ID.')</option>';
                        ?>
                    </select>
                    <div class="import-desc">Segure Ctrl/Cmd para múltipla seleção.</div>
                </div>
                <div class="form-row submit-row">
                    <input type="submit" class="button button-primary button-large" name="submit_csv" value="Importar e Inscrever">
                </div>
            </form>
            <?php
            // Exibe relatório se existir
            if (isset($_SESSION['tutor_csv_importer_report'])) {
                echo '<div class="card report-card">'.$_SESSION['tutor_csv_importer_report'].'</div>';
                unset($_SESSION['tutor_csv_importer_report']);
            }
            ?>
        </div>
        <style>
        .tutor-csv-importer-admin { background:#f8fafc; padding: 40px 20px; min-height: 95vh;}
        .header {background:#667eea;color:#fff; border-radius:20px; padding:28px 32px 22px;margin-bottom:40px;text-align:center;}
        .header h1{font-size:2.2rem;font-weight:bold;}.subtitle{font-size:1rem;opacity:0.95;margin-top:10px;}
        .card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(102,126,234,0.07);margin-bottom:40px;max-width:850px;margin-left:auto;margin-right:auto;padding:33px 38px 28px 38px;border-left:5px solid #764ba2;}
        .main-card{border-left-color:#667eea !important;}
        .config-card{border-left-color:#48bb78;}
        .report-card{background:#f0fff4; border-left:5px solid #38b2ac;}
        .form-row{margin-bottom:32px;}
        .label-bold{font-weight:600;display:block;margin-bottom:8px;}
        .import-desc{color:#718096;font-size:0.88em;margin-top:3px;}
        .submit-row{text-align:right;}
        code{background:#ecf0fa;color:#764ba2;padding:2px 6px;border-radius:4px;}
        .icon { font-size:1.2rem;margin-right:6px; }
        @media (max-width:900px){.tutor-csv-importer-admin .header, .card{padding:20px 4vw;}}
        @media (max-width:600px){.tutor-csv-importer-admin .header, .card{font-size:90%;}}
        </style>
        <?php
    }

    // Lida com as ações dos dois formulários
    public function handle_forms() {
        session_start();

        // Salvar Configurações Globais
        if (isset($_POST['salvar_cfg'])) {
            if (
                !current_user_can('manage_options') || !check_admin_referer('tutor_csv_importer_global')
            ) {
                $_SESSION['tutor_csv_importer_notice'] = '<div class="notice notice-error"><b>Erro de permissão ou nonce.</b></div>';
                return;
            }
            $senha_padrao = isset($_POST['cfg_senha_padrao']) ? sanitize_text_field($_POST['cfg_senha_padrao']) : 'Senha123*';
            $email_html = isset($_POST['cfg_email_html']) ? wp_kses_post(stripslashes($_POST['cfg_email_html'])) : $this->default_email_template();
            update_option('tutor_csv_importer_pwd', $senha_padrao);
            update_option('tutor_csv_importer_html', $email_html);

            $_SESSION['tutor_csv_importer_notice'] = '<div class="notice notice-success" style="margin:20px 0 0 0;"><b>Configurações Salvas!</b></div>';
            wp_redirect(admin_url('admin.php?page=importar-csv-lms'));
            exit;
        }

        // Submit do Importador CSV
        if (isset($_POST['submit_csv'])) {
            if (
                !current_user_can('manage_options') || !check_admin_referer('tutor_csv_importer_import')
            ) {
                $_SESSION['tutor_csv_importer_notice'] = '<div class="notice notice-error"><b>Erro de permissão ou nonce.</b></div>';
                return;
            }

            $senha_padrao = get_option('tutor_csv_importer_pwd', 'Senha123*');
            $email_html   = get_option('tutor_csv_importer_html', $this->default_email_template());
            $cursos_ids = isset($_POST['cursos_ids']) ? array_map('intval', $_POST['cursos_ids']) : [];

            // Arquivo CSV
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                $_SESSION['tutor_csv_importer_report'] = '<div class="notice notice-error">Erro no upload do CSV.</div>'; return;
            }
            $fname = $_FILES['csv_file']['tmp_name'];
            if (($handle = fopen($fname, 'r')) === false) {
                $_SESSION['tutor_csv_importer_report'] = '<div class="notice notice-error">Falha ao ler o CSV.</div>'; return;
            }
            $report = ''; $total = $criados = $existentes = $inscricao_novos = $inscricao_existentes = $falhas = 0; $erros = [];

            $first = true;
            while (($data = fgetcsv($handle, 2048, ",")) !== FALSE) {
                if ($first) { $first = false; continue; } // Cabeçalho
                if (!isset($data[0])) continue;
                $email = trim(strtolower($data[0]));
                if (!$email || !is_email($email)) { $erros[] = htmlspecialchars($email); continue; }
                $total++;

                // Nome (tentando variações)
                $nome = '';
                if (isset($data[1]) && trim($data[1])) $nome .= trim($data[1]);
                if (isset($data[2]) && trim($data[2])) $nome .= ' ' . trim($data[2]);
                if (!$nome) $nome = strstr($email, '@', true);

                $user_id = email_exists($email);
                $is_novo = false;
                if (!$user_id) {
                    $args = [
                        'user_login'    => sanitize_user(strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', strstr($email, '@', true))), true),
                        'user_pass'     => $senha_padrao,
                        'user_email'    => $email,
                        'display_name'  => $nome,
                        'role'          => 'subscriber'
                    ];
                    $user_id = wp_insert_user($args);
                    if (is_wp_error($user_id)) { $falhas++; continue; }
                    $criados++; $is_novo = true;
                    update_user_meta($user_id, 'first_name', $nome);
                } else {
                    $existentes++;
                    $user_obj = get_user_by('id', $user_id);
                    $nome = $user_obj->display_name ? $user_obj->display_name : $nome;
                }

                // Inscrição em cursos TutorLMS
                foreach ($cursos_ids as $curso_id) {
                    if (function_exists('tutor_utils_enroll_student'))
                        tutor_utils_enroll_student($curso_id, $user_id);
                    elseif (class_exists('\TUTOR\Enroll') && method_exists('\TUTOR\Enroll', 'enroll_by_course'))
                        \TUTOR\Enroll::enroll_by_course($curso_id, $user_id);
                    elseif (function_exists('tutils'))
                        tutils()->do_enroll($curso_id, 0, $user_id);
                }

                // Envia e-mail só para novos usuários
                if ($is_novo && $email_html) {
                    $texto_email = $email_html;
                    $texto_email = str_replace(
                        array('(nome)', '(email)', '(senha)'),
                        array($nome, $email, $senha_padrao),
                        $texto_email
                    );
                    $headers = [
                        'Content-Type: text/html; charset=UTF-8',
                        'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
                    ];
                    wp_mail($email, 'Seus dados de acesso - '.get_bloginfo('name'), $texto_email, $headers);
                    $inscricao_novos++;
                } else {
                    $inscricao_existentes++;
                }
            }
            fclose($handle);

            $report  = '<div class="notice-success" style="background:#e6fffa;color:#22543d; border-radius:8px; padding:28px 40px;">';
            $report .= sprintf('<b>Matrícula finalizada:</b><br>Total lidas: <b>%d</b><br>Usuários criados: <b>%d</b><br>Já existiam: <b>%d</b><br>E-mails enviados: <b>%d</b><br>Inscrições de usuários existentes: <b>%d</b><br>',
                $total, $criados, $existentes, $inscricao_novos, $inscricao_existentes);
            if ($falhas) $report .= '<span style="color:#e53e3e;">Falhas/criação: <b>'.$falhas.'</b></span><br>';
            if ($erros) $report .= 'Ignorados (e-mail inválido): <code>' . implode(', ', $erros) . '</code><br>';
            $report .= '</div>';
            $_SESSION['tutor_csv_importer_report'] = $report;
            wp_redirect(admin_url('admin.php?page=importar-csv-lms'));
            exit;
        }
    }

    // Email HTML padrão, usado como fallback e base inicial
    private function default_email_template() {
        return '<p>Olá <strong>(nome)</strong>,</p>
<p>Sua matrícula foi realizada!</p>
<p><strong>Usuário:</strong> (email)<br><strong>Senha:</strong> (senha)</p>
<p>Acesse: <a href="https://SEUSITE.com.br/login">Entrar na plataforma</a></p>
<p>Seja bem-vindo(a)!</p>';
    }
}

new Tutor_LMS_CSV_Importer();
