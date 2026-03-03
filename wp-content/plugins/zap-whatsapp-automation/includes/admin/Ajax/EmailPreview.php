<?php
namespace ZapWA\Admin\Ajax;

if (!defined('ABSPATH')) exit;

class EmailPreview {

    public static function init() {
        add_action('wp_ajax_zapwa_email_preview', [self::class, 'handle']);
    }

    public static function handle() {

        check_ajax_referer('zapwa_email_preview', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permissão negada.', 'zap-whatsapp-automation'), '', ['response' => 403]);
        }

        $subject  = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
        $body_raw = wp_unslash($_POST['body'] ?? '');
        $is_html  = !empty($_POST['is_html']);

        if ($is_html) {
            $body = wp_kses_post($body_raw);
        } else {
            $body = nl2br(esc_html($body_raw));
        }

        $site_name = get_bloginfo('name');
        $site_url  = home_url('/');
        $logo_url  = get_site_icon_url(64);

        // $body is already sanitized: wp_kses_post (HTML) or esc_html + nl2br (plain text).
        // The heredoc below forms a complete HTML document returned directly as preview output.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo self::build_template($subject, $body, $site_name, $site_url, $logo_url);
        wp_die();
    }

    private static function build_template( $subject, $body, $site_name, $site_url, $logo_url ) {

        $site_name_esc = esc_html($site_name);
        $site_url_esc  = esc_url($site_url);
        $subject_esc   = esc_html($subject ?: __('(sem assunto)', 'zap-whatsapp-automation'));

        if ($logo_url) {
            $logo_html = '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($site_name) . '" width="44" height="44" style="border-radius:8px;flex-shrink:0;">';
        } else {
            $logo_html = '<div class="eh-logo">&#x1F4E7;</div>';
        }

        $footer_note = esc_html__('Este e-mail foi enviado automaticamente. Por favor, não responda.', 'zap-whatsapp-automation');

        return <<<HTML
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>{$subject_esc}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Arial,sans-serif;color:#333;padding:20px 0}
.ew{max-width:620px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.10)}
.eh{background:linear-gradient(135deg,#075e54 0%,#128c7e 60%,#25d366 100%);padding:22px 32px;display:flex;align-items:center;gap:14px}
.eh-logo{width:44px;height:44px;border-radius:8px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.eh-title{color:#fff;font-size:1.1rem;font-weight:700;line-height:1.2}
.eh-sub{color:rgba(255,255,255,.75);font-size:.8rem;margin-top:3px}
.es{background:#f8fffe;padding:14px 32px;border-bottom:1px solid #e0e0e0}
.es-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#999;margin-bottom:3px}
.es-value{font-size:.98rem;font-weight:700;color:#075e54}
.eb{padding:28px 32px;font-size:.95rem;line-height:1.75;color:#333}
.ef{background:#f8fffe;border-top:1px solid #e0e0e0;padding:16px 32px;text-align:center}
.ef a{color:#075e54;text-decoration:none;font-size:.82rem}
.ef p{font-size:.75rem;color:#bbb;margin-top:6px}
@media(max-width:640px){.ew{border-radius:0}.eh,.es,.eb,.ef{padding-left:18px;padding-right:18px}}
</style>
</head>
<body>
<div class="ew">
  <div class="eh">
    {$logo_html}
    <div>
      <div class="eh-title">{$site_name_esc}</div>
      <div class="eh-sub">{$site_url_esc}</div>
    </div>
  </div>
  <div class="es">
    <div class="es-label">Assunto</div>
    <div class="es-value">{$subject_esc}</div>
  </div>
  <div class="eb">{$body}</div>
  <div class="ef">
    <a href="{$site_url_esc}">{$site_name_esc}</a>
    <p>{$footer_note}</p>
  </div>
</div>
</body>
</html>
HTML;
    }
}
