<?php
namespace ZapWA\Admin\Pages;

if (!defined('ABSPATH')) exit;

class Settings {

    public static function render() {

        if (!current_user_can('manage_options')) {
            return;
        }

        // Processar toggle de logs
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['zapwa_toggle_logging']) && check_admin_referer('zapwa_toggle_logging')) {
            $current = get_option('zapwa_logging_enabled', true);
            update_option('zapwa_logging_enabled', !$current);
            $enabled = !$current;
            echo '<div class="notice notice-success is-dismissible"><p>' .
                ($enabled ? 'Logs <strong>ativados</strong> com sucesso.' : 'Logs <strong>desativados</strong> com sucesso.') .
                '</p></div>';
        }

        $logging_enabled = (bool) get_option('zapwa_logging_enabled', true);
        ?>
        <div class="wrap zapwa-page">
            <h1>Configurações – Zap WhatsApp Automation</h1>

            <h2>Controle de Logs</h2>
            <p>
                Os logs registram todas as etapas do processamento de mensagens (recebimento de eventos, envios, erros).
                Desativar os logs economiza espaço em disco e no banco de dados.
            </p>
            <p>
                Status atual: <?php if ($logging_enabled): ?>
                    <strong style="color:green;">✔ Logs ATIVADOS</strong>
                <?php else: ?>
                    <strong style="color:#a00;">✘ Logs DESATIVADOS</strong>
                <?php endif; ?>
            </p>
            <form method="post">
                <?php wp_nonce_field('zapwa_toggle_logging'); ?>
                <input type="hidden" name="zapwa_toggle_logging" value="1">
                <?php if ($logging_enabled): ?>
                    <button type="submit" class="button button-secondary" style="color:#a00;border-color:#a00;"
                        onclick="return confirm('Desativar logs? Os logs existentes serão mantidos, mas nenhum novo registro será feito.');">
                        🔕 Desativar Logs
                    </button>
                <?php else: ?>
                    <button type="submit" class="button button-primary">
                        🔔 Ativar Logs
                    </button>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }
}
