<?php
namespace WebinarPlataforma\Public_;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FormInscricao {

    public static function init(): void {
        add_shortcode( 'webinar_inscricao', [ self::class, 'render_form' ] );
    }

    public static function render_form( array $atts ): string {
        $atts = shortcode_atts( [ 'id' => 0 ], $atts );
        $id   = (int) $atts['id'];

        if ( ! $id ) {
            return '<p>' . esc_html__( 'ID do webinar inválido.', 'wp-webinar-plataforma' ) . '</p>';
        }

        global $wpdb;
        $webinar = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}webinars WHERE id = %d AND status = 'publicado'",
            $id
        ) );

        if ( ! $webinar ) {
            return '<p>' . esc_html__( 'Webinar não encontrado ou não está disponível.', 'wp-webinar-plataforma' ) . '</p>';
        }

        wp_enqueue_style( 'wp-webinar-public', WP_WEBINAR_URL . 'assets/css/webinar.css', [], WP_WEBINAR_VERSION );

        ob_start();
        ?>
        <div class="ww-inscricao-wrapper" id="ww-form-<?php echo esc_attr( $id ); ?>">
            <div class="ww-inscricao-box">
                <h2 class="ww-inscricao-titulo"><?php echo esc_html( $webinar->nome ); ?></h2>
                <?php if ( $webinar->descricao ) : ?>
                    <p class="ww-inscricao-desc"><?php echo esc_html( $webinar->descricao ); ?></p>
                <?php endif; ?>

                <div class="ww-alert ww-alert-success" id="ww-success-<?php echo esc_attr( $id ); ?>" style="display:none;"></div>
                <div class="ww-alert ww-alert-error" id="ww-error-<?php echo esc_attr( $id ); ?>" style="display:none;"></div>

                <form class="ww-form" id="ww-inscricao-form-<?php echo esc_attr( $id ); ?>" novalidate>
                    <div class="ww-form-group">
                        <label for="ww-nome-<?php echo esc_attr( $id ); ?>" class="ww-label">Seu nome *</label>
                        <input type="text" id="ww-nome-<?php echo esc_attr( $id ); ?>"
                               class="ww-input" name="nome" placeholder="Digite seu nome completo" required />
                    </div>

                    <div class="ww-form-group">
                        <label for="ww-email-<?php echo esc_attr( $id ); ?>" class="ww-label">Seu e-mail *</label>
                        <input type="email" id="ww-email-<?php echo esc_attr( $id ); ?>"
                               class="ww-input" name="email" placeholder="seu@email.com" required />
                    </div>

                    <div class="ww-form-group">
                        <label for="ww-telefone-<?php echo esc_attr( $id ); ?>" class="ww-label">Telefone (opcional)</label>
                        <input type="tel" id="ww-telefone-<?php echo esc_attr( $id ); ?>"
                               class="ww-input" name="telefone" placeholder="(11) 99999-9999" />
                    </div>

                    <button type="submit" class="ww-btn ww-btn-primary ww-btn-block" id="ww-submit-<?php echo esc_attr( $id ); ?>">
                        Quero me inscrever agora
                    </button>
                </form>
            </div>
        </div>

        <script>
        (function() {
            var formId = <?php echo (int) $id; ?>;
            var form   = document.getElementById('ww-inscricao-form-' + formId);
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var btn     = document.getElementById('ww-submit-' + formId);
                var success = document.getElementById('ww-success-' + formId);
                var error   = document.getElementById('ww-error-' + formId);

                btn.disabled    = true;
                btn.textContent = 'Aguarde...';
                success.style.display = 'none';
                error.style.display   = 'none';

                var nome     = form.querySelector('[name="nome"]').value.trim();
                var email    = form.querySelector('[name="email"]').value.trim();
                var telefone = form.querySelector('[name="telefone"]').value.trim();

                fetch('<?php echo esc_url_raw( rest_url( 'webinar/v1/inscrever' ) ); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ webinar_id: formId, nome: nome, email: email, telefone: telefone })
                })
                .then(function(r) { return r.json().then(function(d) { return { ok: r.ok, data: d }; }); })
                .then(function(res) {
                    if (res.ok || res.data.participante_id) {
                        success.textContent  = res.data.message || 'Inscrição realizada com sucesso!';
                        success.style.display = 'block';
                        form.style.display    = 'none';
                        if (res.data.redirect_url) {
                            setTimeout(function() { window.location.href = res.data.redirect_url; }, 1500);
                        }
                    } else {
                        throw new Error(res.data.message || 'Erro ao realizar inscrição.');
                    }
                })
                .catch(function(err) {
                    error.textContent  = err.message || 'Erro ao realizar inscrição.';
                    error.style.display = 'block';
                    btn.disabled    = false;
                    btn.textContent = 'Quero me inscrever agora';
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }
}
