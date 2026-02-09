<?php
/*
Plugin Name: Simple PWA
Plugin URI: https://pluginstech.com
Description: Este plugin habilita a opção de transformar seu site em um applicativo PWA, com isso os usuários poderá acessar o ícone na tela do celular e abrir o site como se fosse um aplicativo nativo
Version: 1.0
Author: Plugins Tech
Author URI: https://pluginstech.com
License: 
License URI: 
*/
// Evita acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

class PWA_Pro_Icones {
    
    private $options;
    
    public function __construct() {
        // Inicializa as opções
        $this->options = get_option('pwa_pro_icones_options', array(
            'app_name' => get_bloginfo('name'),
            'short_name' => get_bloginfo('name'),
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'display' => 'standalone',
            'show_ios_guide' => 'yes',
            'icon_192' => '',
            'icon_512' => '',
            'button_text' => 'Instalar App',
            'button_color' => '#007bff',
            'button_text_color' => '#ffffff',
            'button_position' => 'bottom',
            'button_horizontal_position' => 'right',
            'button_width' => '200px',
            'button_height' => '50px',
            'show_pages' => 'all',
            'show_ios_instructions' => 'yes',
            'pwa_redirect_page' => '0', // Página para redirecionamento do PWA
            'enable_install_button' => 'yes' // Nova opção para ativar/desativar o botão
        ));
        
        // Adiciona as ações necessárias
        add_action('wp_head', array($this, 'add_meta_tags'), 1); // Prioridade 1 para executar primeiro
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
        add_action('init', array($this, 'register_manifest_route'));
        add_action('wp_footer', array($this, 'add_ios_install_guide'));
        
        // Adiciona menu de administração
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Registra a ativação
        register_activation_hook(__FILE__, array($this, 'activation_hook'));
        
        // Adiciona script para redirecionamento PWA
        add_action('wp_head', array($this, 'add_pwa_redirect_script'), 0); // Prioridade 0 para executar primeiramente
    }
    
    // Adiciona script de redirecionamento PWA no início do <head>
    public function add_pwa_redirect_script() {
        // Só adiciona o script se um redirecionamento estiver configurado
        if (empty($this->options['pwa_redirect_page']) || $this->options['pwa_redirect_page'] == '0') {
            return;
        }
        
        // Só adiciona o script na página inicial
        if (!is_front_page() && !is_home()) {
            return;
        }
        
        // URL para onde redirecionar no modo PWA
        $redirect_url = get_permalink($this->options['pwa_redirect_page']);
        if (!$redirect_url) {
            return;
        }
        
        // Script que verifica se estamos no modo standalone/PWA e redireciona
        ?>
<script>
(function() {
    // Função para detectar se está sendo executado como PWA
    function isInStandaloneMode() {
        return (window.matchMedia('(display-mode: standalone)').matches) || 
               (window.matchMedia('(display-mode: fullscreen)').matches) ||
               (window.navigator.standalone === true);
    }
    
    // Se estiver em modo standalone/PWA e na página inicial, redireciona
    if (isInStandaloneMode()) {
        window.location.href = "<?php echo esc_url($redirect_url); ?>";
    }
})();
</script>
        <?php
    }
    
    // Função de ativação
    public function activation_hook() {
        // Cria os diretórios necessários
        $plugin_dir = plugin_dir_path(__FILE__);
        $icons_dir = $plugin_dir . 'icons';
        $js_dir = $plugin_dir . 'js';
        $css_dir = $plugin_dir . 'css';
        
        // Cria os diretórios se não existirem
        foreach (array($icons_dir, $js_dir, $css_dir) as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
        }
        
        // Cria os ícones padrão
        $this->create_default_icon($icons_dir . '/icon-192x192.png', 192, 192);
        $this->create_default_icon($icons_dir . '/icon-512x512.png', 512, 512);
        
        // Cria o arquivo JS para admin
        $admin_js_content = <<<EOT
jQuery(document).ready(function($) {
    // Inicializa o color picker
    $('.color-picker').wpColorPicker();
    
    // Upload de ícone 192x192
    $('#upload_icon_192_button').click(function(e) {
        e.preventDefault();
        
        var image_frame;
        
        if(image_frame) {
            image_frame.open();
            return;
        }
        
        image_frame = wp.media({
            title: 'Selecione um ícone 192x192 para o PWA',
            multiple: false,
            library: {
                type: 'image',
            }
        });
        
        image_frame.on('select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#icon_192').val(attachment.url);
            $('#icon_192_preview').attr('src', attachment.url).show();
            $('#remove_icon_192_button').show();
        });
        
        image_frame.open();
    });
    
    // Remover ícone 192x192
    $('#remove_icon_192_button').click(function(e) {
        e.preventDefault();
        $('#icon_192').val('');
        $('#icon_192_preview').attr('src', '').hide();
        $(this).hide();
    });
    
    // Upload de ícone 512x512
    $('#upload_icon_512_button').click(function(e) {
        e.preventDefault();
        
        var image_frame;
        
        if(image_frame) {
            image_frame.open();
            return;
        }
        
        image_frame = wp.media({
            title: 'Selecione um ícone 512x512 para o PWA',
            multiple: false,
            library: {
                type: 'image',
            }
        });
        
        image_frame.on('select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#icon_512').val(attachment.url);
            $('#icon_512_preview').attr('src', attachment.url).show();
            $('#remove_icon_512_button').show();
        });
        
        image_frame.open();
    });
    
    // Remover ícone 512x512
    $('#remove_icon_512_button').click(function(e) {
        e.preventDefault();
        $('#icon_512').val('');
        $('#icon_512_preview').attr('src', '').hide();
        $(this).hide();
    });
});
EOT;
        
        file_put_contents($js_dir . '/pwa-admin.js', $admin_js_content);
        
        // Cria o arquivo JS do PWA 
        $js_content = <<<EOT
// PWA Install Script
(function($) {
    let deferredPrompt;
    
    // Detecta iOS
    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }
    
    // Detecta Safari no iOS
    function isIOSSafari() {
        return isIOS() && /Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent);
    }
    
    // Detecta dispositivos móveis
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Detecta se está em modo standalone (PWA)
    function isInStandaloneMode() {
        return (window.matchMedia('(display-mode: standalone)').matches) || 
               (window.matchMedia('(display-mode: fullscreen)').matches) ||
               (window.navigator.standalone === true);
    }
    
    // Se não for dispositivo móvel, não prossegue
    if (!isMobile()) {
        return;
    }
    
    // Para dispositivos Android
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        if (!pwaData.showButton) {
            return;
        }
        
        // Cria o botão de instalação para Android
        const installBtn = $('<button id="pwa-install-btn"></button>');
        installBtn.text(pwaData.installText);
        
        // Aplica estilos conforme as configurações
        const buttonStyles = {
            'position': 'fixed',
            'z-index': '9999',
            'padding': '10px 15px',
            'background-color': pwaData.buttonColor,
            'color': pwaData.buttonTextColor,
            'border': 'none',
            'border-radius': '5px',
            'font-weight': 'bold',
            'cursor': 'pointer',
            'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
            'width': pwaData.buttonWidth,
            'height': pwaData.buttonHeight,
            'text-align': 'center',
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center'
        };
        
        // Posição vertical do botão
        if (pwaData.buttonPosition === 'top') {
            buttonStyles.top = '20px';
        } else {
            buttonStyles.bottom = '20px';
        }
        
        // Posição horizontal do botão
        if (pwaData.buttonHorizontalPosition === 'left') {
            buttonStyles.left = '20px';
        } else if (pwaData.buttonHorizontalPosition === 'center') {
            buttonStyles.left = '50%';
            buttonStyles.transform = 'translateX(-50%)';
        } else { // direita (padrão)
            buttonStyles.right = '20px';
        }
        
        installBtn.css(buttonStyles);
        
        // Adiciona evento de clique
        installBtn.on('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
                if (outcome === 'accepted') {
                    installBtn.remove();
                }
            }
        });
        
        // Adiciona ao body
        $('body').append(installBtn);
    });
    
    window.addEventListener('appinstalled', (evt) => {
        console.log('App instalado com sucesso!');
        $('#pwa-install-btn').remove();
    });
    
    // Para iOS Safari - mostra guia de instalação
    $(document).ready(function() {
        // Verifica se é iOS Safari e se deve mostrar instruções
        if (isIOSSafari() && !window.navigator.standalone && pwaData.showIOSInstructions === 'yes' && pwaData.showButton) {
            // Cria botão para iOS
            const installBtn = $('<button id="pwa-install-btn-ios"></button>');
            installBtn.text(pwaData.installText);
            
            // Aplica estilos conforme as configurações
            const buttonStyles = {
                'position': 'fixed',
                'z-index': '9999',
                'padding': '10px 15px',
                'background-color': pwaData.buttonColor,
                'color': pwaData.buttonTextColor,
                'border': 'none',
                'border-radius': '5px',
                'font-weight': 'bold',
                'cursor': 'pointer',
                'box-shadow': '0 2px 5px rgba(0,0,0,0.2)',
                'width': pwaData.buttonWidth,
                'height': pwaData.buttonHeight,
                'text-align': 'center',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center'
            };
            
            // Posição vertical do botão
            if (pwaData.buttonPosition === 'top') {
                buttonStyles.top = '20px';
            } else {
                buttonStyles.bottom = '20px';
            }
            
            // Posição horizontal do botão
            if (pwaData.buttonHorizontalPosition === 'left') {
                buttonStyles.left = '20px';
            } else if (pwaData.buttonHorizontalPosition === 'center') {
                buttonStyles.left = '50%';
                buttonStyles.transform = 'translateX(-50%)';
            } else { // direita (padrão)
                buttonStyles.right = '20px';
            }
            
            installBtn.css(buttonStyles);
            
            // Adiciona evento de clique para mostrar o guia
            installBtn.on('click', function() {
                $('#ios-pwa-install-guide').show();
            });
            
            // Adiciona ao body
            $('body').append(installBtn);
            
            // Dá um tempo para o usuário ver a página primeiro antes de mostrar o guia
            if (pwaData.showIOSGuide === 'yes') {
                setTimeout(function() {
                    $('#ios-pwa-install-guide').fadeIn();
                }, 3000);
            }
            
            // Fecha o guia ao clicar no X
            $('#close-ios-guide').on('click', function() {
                $('#ios-pwa-install-guide').fadeOut();
                
                // Salva cookie para não mostrar novamente na mesma sessão
                document.cookie = "ios_pwa_guide_shown=1; path=/; max-age=86400";
            });
            
            // Verifica se já mostrou o guia recentemente
            function getCookie(name) {
                const value = "; " + document.cookie;
                const parts = value.split("; " + name + "=");
                if (parts.length === 2) return parts.pop().split(";").shift();
            }
            
            if (getCookie('ios_pwa_guide_shown')) {
                $('#ios-pwa-install-guide').hide();
            }
        }
    });
})(jQuery);
EOT;
        
        file_put_contents($js_dir . '/pwa-install.js', $js_content);
        
        // Cria o arquivo CSS
        $css_content = <<<EOT
/* Estilos para o guia de instalação no iOS */
#ios-pwa-install-guide {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0,0,0,0.8);
    z-index: 99999;
    padding: 15px;
    display: none;
}

.ios-guide-container {
    background-color: #fff;
    border-radius: 10px;
    max-width: 500px;
    margin: 0 auto;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

.ios-guide-header {
    background-color: #f7f7f7;
    padding: 10px 15px;
    border-bottom: 1px solid #e1e1e1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.ios-guide-header h3 {
    margin: 0;
    font-size: 16px;
    color: #333;
}

#close-ios-guide {
    background: none;
    border: none;
    font-size: 24px;
    color: #666;
    cursor: pointer;
}

.ios-guide-content {
    padding: 15px;
    position: relative;
}

.ios-guide-content p {
    margin-top: 0;
    font-size: 14px;
}

.ios-guide-content ol {
    margin-bottom: 10px;
    padding-left: 20px;
}

.ios-guide-content li {
    margin-bottom: 8px;
    font-size: 14px;
}

.ios-share-icon {
    display: inline-block;
    background-color: #007aff;
    color: white;
    width: 20px;
    height: 20px;
    text-align: center;
    line-height: 20px;
    border-radius: 4px;
    font-size: 12px;
}

.ios-guide-arrow {
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 10px solid transparent;
    border-right: 10px solid transparent;
    border-top: 10px solid #fff;
}

@media (max-width: 480px) {
    .ios-guide-container {
        max-width: 100%;
    }
}

/* Botão personalizado de instalação */
#pwa-install-btn, #pwa-install-btn-ios {
    transition: transform 0.2s ease;
}

#pwa-install-btn:hover, #pwa-install-btn-ios:hover {
    transform: scale(1.05);
}

#pwa-install-btn:active, #pwa-install-btn-ios:active {
    transform: scale(0.98);
}

/* Ajuste para botão centralizado */
#pwa-install-btn.centered, #pwa-install-btn-ios.centered {
    transform: translateX(-50%);
}

#pwa-install-btn.centered:hover, #pwa-install-btn-ios.centered:hover {
    transform: translateX(-50%) scale(1.05);
}

#pwa-install-btn.centered:active, #pwa-install-btn-ios.centered:active {
    transform: translateX(-50%) scale(0.98);
}

/* Admin panel */
.icon-preview-wrapper {
    margin-bottom: 10px;
}

.icon-preview-wrapper img {
    border: 1px solid #ddd;
    padding: 5px;
    background: #f7f7f7;
}

.icon-size-label {
    display: block;
    font-weight: bold;
    margin-bottom: 5px;
    font-size: 13px;
}
EOT;
        
        file_put_contents($css_dir . '/pwa-styles.css', $css_content);
        
        // Limpa as regras de rewrite
        flush_rewrite_rules();
    }
    
    // Adiciona scripts de admin
    public function admin_scripts($hook) {
        if ($hook != 'toplevel_page_pwa-pro-icones') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_script('pwa-admin', plugin_dir_url(__FILE__) . 'js/pwa-admin.js', array('jquery', 'wp-color-picker'), '1.0', true);
    }
    
    // Registra scripts e estilos
    public function register_scripts() {
        // Registrando um script para PWA
        wp_register_script('pwa-install', plugin_dir_url(__FILE__) . 'js/pwa-install.js', array('jquery'), '1.0', true);
        wp_enqueue_script('pwa-install');
        
        // Registrando estilos para PWA
        wp_register_style('pwa-styles', plugin_dir_url(__FILE__) . 'css/pwa-styles.css', array(), '1.0');
        wp_enqueue_style('pwa-styles');
        
        // Verificar se o botão de instalação está habilitado
        $button_enabled = ($this->options['enable_install_button'] === 'yes');
        
        // Verificar se deve mostrar o botão na página atual
        $show_button = $button_enabled; // Começa com o status geral
        
        // Lógica para determinar se o botão deve ser exibido na página atual
        if ($button_enabled && $this->options['show_pages'] !== 'all') {
            // Se não estiver configurado para todas as páginas, verifica se é uma página específica
            if ($this->options['show_pages'] === 'home') {
                // Caso especial para a página inicial
                if (!is_front_page()) {
                    $show_button = false;
                }
            } else {
                // Para qualquer outra página específica
                $current_id = get_the_ID();
                if ($current_id != $this->options['show_pages']) {
                    $show_button = false;
                }
            }
        }
        
        // Passar variáveis para o script
        wp_localize_script('pwa-install', 'pwaData', array(
            'themeColor' => $this->options['theme_color'],
            'installText' => $this->options['button_text'],
            'buttonColor' => $this->options['button_color'],
            'buttonTextColor' => $this->options['button_text_color'],
            'buttonPosition' => $this->options['button_position'],
            'buttonHorizontalPosition' => $this->options['button_horizontal_position'],
            'buttonWidth' => $this->options['button_width'],
            'buttonHeight' => $this->options['button_height'],
            'showIOSGuide' => $this->options['show_ios_guide'],
            'showIOSInstructions' => $this->options['show_ios_instructions'],
            'showButton' => $show_button,
            'buttonEnabled' => $button_enabled
        ));
    }
    
    // Adiciona meta tags necessárias
    public function add_meta_tags() {
        $icon_192 = !empty($this->options['icon_192']) ? $this->options['icon_192'] : plugin_dir_url(__FILE__) . 'icons/icon-192x192.png';
        $icon_512 = !empty($this->options['icon_512']) ? $this->options['icon_512'] : plugin_dir_url(__FILE__) . 'icons/icon-512x512.png';
        ?>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="<?php echo esc_attr($this->options['theme_color']); ?>">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black">
        <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr($this->options['app_name']); ?>">
        
        <!-- Ícones específicos para iOS -->
        <link rel="apple-touch-icon" href="<?php echo esc_url($icon_192); ?>">
        <link rel="apple-touch-icon-precomposed" href="<?php echo esc_url($icon_192); ?>">
        <link rel="apple-touch-icon" sizes="192x192" href="<?php echo esc_url($icon_192); ?>">
        <link rel="apple-touch-icon" sizes="512x512" href="<?php echo esc_url($icon_512); ?>">
        
        <!-- Ícones específicos para dispositivos -->
        <link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url($icon_192); ?>">
        <link rel="icon" type="image/png" sizes="512x512" href="<?php echo esc_url($icon_512); ?>">
        
        <link rel="manifest" href="<?php echo esc_url(home_url('/?pwa_manifest=1')); ?>">
        <?php
    }
    
    // Adiciona guia de instalação para iOS
    public function add_ios_install_guide() {
        // Verifica se o botão está habilitado
        if ($this->options['enable_install_button'] !== 'yes') {
            return;
        }
        
        if ($this->options['show_ios_instructions'] !== 'yes') {
            return;
        }
        
        // Verifica se é um dispositivo móvel
        $is_mobile = preg_match('/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $_SERVER['HTTP_USER_AGENT']);
        
        if (!$is_mobile) {
            return; // Não mostra o guia em desktop
        }
        
        ?>
        <div id="ios-pwa-install-guide" style="display: none;">
            <div class="ios-guide-container">
                <div class="ios-guide-header">
                    <h3>Instale o <?php echo esc_html($this->options['app_name']); ?> na sua tela inicial</h3>
                    <button id="close-ios-guide">&times;</button>
                </div>
                <div class="ios-guide-content">
                    <p>Para instalar este webapp no seu iPhone/iPad:</p>
                    <ol>
                        <li>Toque no ícone <strong>Compartilhar</strong> <span class="ios-share-icon">&#x25B2;</span> abaixo</li>
                        <li>Deslize e selecione <strong>"Adicionar à Tela de Início"</strong></li>
                        <li>Confirme tocando em <strong>"Adicionar"</strong></li>
                    </ol>
                    <div class="ios-guide-arrow"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Registra a rota para o manifest.json
    public function register_manifest_route() {
        add_action('parse_request', function() {
            if (isset($_GET['pwa_manifest'])) {
                header('Content-Type: application/json');
                
                $icon_192 = !empty($this->options['icon_192']) ? $this->options['icon_192'] : plugin_dir_url(__FILE__) . 'icons/icon-192x192.png';
                $icon_512 = !empty($this->options['icon_512']) ? $this->options['icon_512'] : plugin_dir_url(__FILE__) . 'icons/icon-512x512.png';
                
                // Define a URL inicial com parâmetro source para tracking
                $start_url = home_url('/?source=pwa');
                
                echo json_encode(array(
                    'name' => $this->options['app_name'],
                    'short_name' => $this->options['short_name'],
                    'start_url' => $start_url,
                    'display' => $this->options['display'],
                    'background_color' => $this->options['background_color'],
                    'theme_color' => $this->options['theme_color'],
                    'icons' => array(
                        // Ícone 192x192 para propósito "any"
                        array(
                            'src' => $icon_192,
                            'sizes' => '192x192',
                            'type' => 'image/png',
                            'purpose' => 'any'
                        ),
                        // Ícone 192x192 para propósito "maskable"
                        array(
                            'src' => $icon_192,
                            'sizes' => '192x192',
                            'type' => 'image/png',
                            'purpose' => 'maskable'
                        ),
                        // Ícone 512x512 para propósito "any"
                        array(
                            'src' => $icon_512,
                            'sizes' => '512x512',
                            'type' => 'image/png',
                            'purpose' => 'any'
                        ),
                        // Ícone 512x512 para propósito "maskable"
                        array(
                            'src' => $icon_512,
                            'sizes' => '512x512',
                            'type' => 'image/png',
                            'purpose' => 'maskable'
                        )
                    )
                ));
                exit;
            }
        });
    }
    
    // Adiciona menu de administração
    public function add_admin_menu() {
        add_menu_page(
            'Simple PWA',
            'Simple PWA',
            'manage_options',
            'pwa-pro-icones',
            array($this, 'settings_page'),
            'dashicons-smartphone',
            100
        );
    }
    
    // Registra as configurações
    public function register_settings() {
        register_setting('pwa_pro_icones_group', 'pwa_pro_icones_options', array($this, 'validate_options'));
        
        // Seção de configurações gerais
        add_settings_section(
            'pwa_general_section',
            'Configurações Gerais do PWA',
            function() { echo '<p>Configure seu Progressive Web App aqui.</p>'; },
            'pwa-pro-icones'
        );
        
        // Configurações dos ícones
        add_settings_section(
            'pwa_icons_section',
            'Configurações dos Ícones',
            function() { echo '<p>Configure os ícones do seu Progressive Web App.</p>'; },
            'pwa-pro-icones'
        );
        
        // Seção de configurações do botão
        add_settings_section(
            'pwa_button_section',
            'Configurações do Botão de Instalação',
            function() { echo '<p>Personalize o botão de instalação do seu PWA.</p>'; },
            'pwa-pro-icones'
        );
        
        // Nova seção para redirecionamento PWA
        add_settings_section(
            'pwa_redirect_section',
            'Redirecionamento do PWA',
            function() { echo '<p>Configure para onde o visitante será redirecionado quando acessar a página inicial pelo PWA instalado.</p>'; },
            'pwa-pro-icones'
        );
        
        // Configurações gerais
        add_settings_field(
            'app_name',
            'Nome do App',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[app_name]" value="' . esc_attr($this->options['app_name']) . '" class="regular-text">';
            },
            'pwa-pro-icones',
            'pwa_general_section'
        );
        
        add_settings_field(
            'short_name',
            'Nome Curto',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[short_name]" value="' . esc_attr($this->options['short_name']) . '" class="regular-text">';
                echo '<p class="description">Nome que aparecerá abaixo do ícone (máximo 12 caracteres)</p>';
            },
            'pwa-pro-icones',
            'pwa_general_section'
        );
        
        add_settings_field(
            'background_color',
            'Cor de Fundo',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[background_color]" value="' . esc_attr($this->options['background_color']) . '" class="color-picker">';
            },
            'pwa-pro-icones',
            'pwa_general_section'
        );
        
        add_settings_field(
            'theme_color',
            'Cor do Tema',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[theme_color]" value="' . esc_attr($this->options['theme_color']) . '" class="color-picker">';
            },
            'pwa-pro-icones',
            'pwa_general_section'
        );
        
        add_settings_field(
            'display',
            'Modo de Exibição',
            function() {
                $options = array(
                    'standalone' => 'Standalone (sem navegador)',
                    'fullscreen' => 'Tela cheia',
                    'minimal-ui' => 'Interface mínima',
                    'browser' => 'Navegador normal'
                );
                
                echo '<select name="pwa_pro_icones_options[display]">';
                foreach ($options as $value => $label) {
                    $selected = ($this->options['display'] == $value) ? 'selected' : '';
                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
            },
            'pwa-pro-icones',
            'pwa_general_section'
        );
        
        // Ícone 192x192
        add_settings_field(
            'icon_192',
            'Ícone 192x192',
            function() {
                $icon_url = !empty($this->options['icon_192']) ? $this->options['icon_192'] : '';
                ?>
                <div class="icon-preview-wrapper">
                    <span class="icon-size-label">Ícone 192x192 pixels</span>
                    <img id="icon_192_preview" src="<?php echo esc_url($icon_url); ?>" style="max-width:100px;max-height:100px;<?php echo empty($icon_url) ? 'display:none;' : ''; ?>">
                </div>
                <input type="hidden" name="pwa_pro_icones_options[icon_192]" id="icon_192" value="<?php echo esc_attr($icon_url); ?>">
                <button type="button" class="button" id="upload_icon_192_button">Escolher Ícone</button>
                <button type="button" class="button" id="remove_icon_192_button" <?php echo empty($icon_url) ? 'style="display:none;"' : ''; ?>>Remover</button>
                <p class="description">Ícone que aparecerá na tela inicial (192x192 pixels)</p>
                <?php
            },
            'pwa-pro-icones',
            'pwa_icons_section'
        );
        
        // Ícone 512x512
        add_settings_field(
            'icon_512',
            'Ícone 512x512',
            function() {
                $icon_url = !empty($this->options['icon_512']) ? $this->options['icon_512'] : '';
                ?>
                <div class="icon-preview-wrapper">
                    <span class="icon-size-label">Ícone 512x512 pixels</span>
                    <img id="icon_512_preview" src="<?php echo esc_url($icon_url); ?>" style="max-width:100px;max-height:100px;<?php echo empty($icon_url) ? 'display:none;' : ''; ?>">
                </div>
                <input type="hidden" name="pwa_pro_icones_options[icon_512]" id="icon_512" value="<?php echo esc_attr($icon_url); ?>">
                <button type="button" class="button" id="upload_icon_512_button">Escolher Ícone</button>
                <button type="button" class="button" id="remove_icon_512_button" <?php echo empty($icon_url) ? 'style="display:none;"' : ''; ?>>Remover</button>
                <p class="description">Ícone de maior resolução (512x512 pixels)</p>
                <?php
            },
            'pwa-pro-icones',
            'pwa_icons_section'
        );
        
        // NOVA OPÇÃO: Habilitar/Desabilitar botão de instalação
        add_settings_field(
            'enable_install_button',
            'Habilitar Botão de Instalação',
            function() {
                $checked = ($this->options['enable_install_button'] == 'yes') ? 'checked="checked"' : '';
                echo '<input type="checkbox" name="pwa_pro_icones_options[enable_install_button]" value="yes" ' . $checked . '>';
                echo '<p class="description">Marque esta opção para exibir o botão de instalação do PWA no site</p>';
                echo '<p class="description"><strong>Nota:</strong> Quando desabilitado, o botão não aparecerá em nenhuma página, independente das outras configurações.</p>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        // Configurações do botão
        add_settings_field(
            'button_text',
            'Texto do Botão',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[button_text]" value="' . esc_attr($this->options['button_text']) . '" class="regular-text">';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        add_settings_field(
            'button_color',
            'Cor do Botão',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[button_color]" value="' . esc_attr($this->options['button_color']) . '" class="color-picker">';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        add_settings_field(
            'button_text_color',
            'Cor do Texto do Botão',
            function() {
                echo '<input type="text" name="pwa_pro_icones_options[button_text_color]" value="' . esc_attr($this->options['button_text_color']) . '" class="color-picker">';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        add_settings_field(
            'button_position',
            'Posição Vertical do Botão',
            function() {
                $options = array(
                    'bottom' => 'Parte inferior da tela',
                    'top' => 'Parte superior da tela'
                );
                
                echo '<select name="pwa_pro_icones_options[button_position]">';
                foreach ($options as $value => $label) {
                    $selected = ($this->options['button_position'] == $value) ? 'selected' : '';
                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        add_settings_field(
            'button_horizontal_position',
            'Posição Horizontal do Botão',
            function() {
                $options = array(
                    'left' => 'Lado esquerdo',
                    'center' => 'Centralizado',
                    'right' => 'Lado direito'
                );
                
                echo '<select name="pwa_pro_icones_options[button_horizontal_position]">';
                foreach ($options as $value => $label) {
                    $selected = ($this->options['button_horizontal_position'] == $value) ? 'selected' : '';
                    echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        add_settings_field(
            'show_pages',
            'Mostrar Botão em',
            function() {
                // Obter todas as páginas
                $pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'ASC'));
                
                echo '<select name="pwa_pro_icones_options[show_pages]">';
                
                // Opção para todas as páginas
                $selected = ($this->options['show_pages'] == 'all') ? 'selected' : '';
                echo '<option value="all" ' . $selected . '>Todas as páginas</option>';
                
                // Opção só para página inicial
                $selected = ($this->options['show_pages'] == 'home') ? 'selected' : '';
                echo '<option value="home" ' . $selected . '>Apenas página inicial</option>';
                
                // Opções para páginas específicas
                if (!empty($pages)) {
                    echo '<optgroup label="Páginas específicas">';
                    foreach ($pages as $page) {
                        $selected = ($this->options['show_pages'] == $page->ID) ? 'selected' : '';
                        echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                    }
                    echo '</optgroup>';
                }
                
                echo '</select>';
                echo '<p class="description">Escolha em qual página o botão de instalação será exibido (apenas se estiver habilitado acima)</p>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        // Mostra Instruções para iOS
        add_settings_field(
            'show_ios_instructions',
            'Mostrar Instruções para iOS',
            function() {
                $checked = ($this->options['show_ios_instructions'] == 'yes') ? 'checked="checked"' : '';
                echo '<input type="checkbox" name="pwa_pro_icones_options[show_ios_instructions]" value="yes" ' . $checked . '>';
                echo '<p class="description">Mostrar instruções de instalação para usuários do iOS (iPhone/iPad)</p>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        // Mostrar guia iOS automaticamente
        add_settings_field(
            'show_ios_guide',
            'Mostrar Guia iOS Automaticamente',
            function() {
                $checked = ($this->options['show_ios_guide'] == 'yes') ? 'checked="checked"' : '';
                echo '<input type="checkbox" name="pwa_pro_icones_options[show_ios_guide]" value="yes" ' . $checked . '>';
                echo '<p class="description">Mostrar guia de instalação automaticamente (após 3 segundos) para usuários do iOS</p>';
            },
            'pwa-pro-icones',
            'pwa_button_section'
        );
        
        // Página de redirecionamento para PWA
        add_settings_field(
            'pwa_redirect_page',
            'Página para redirecionamento no PWA',
            function() {
                // Obter todas as páginas
                $pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'ASC'));
                
                echo '<select name="pwa_pro_icones_options[pwa_redirect_page]">';
                
                // Opção para desativar redirecionamento
                $selected = ($this->options['pwa_redirect_page'] == '0') ? 'selected' : '';
                echo '<option value="0" ' . $selected . '>Não redirecionar</option>';
                
                // Opções para páginas específicas
                if (!empty($pages)) {
                    foreach ($pages as $page) {
                        $selected = ($this->options['pwa_redirect_page'] == $page->ID) ? 'selected' : '';
                        echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
                    }
                }
                
                echo '</select>';
                echo '<p class="description">Quando um usuário acessar a página inicial pelo PWA instalado, ele será redirecionado para esta página.</p>';
                echo '<p class="description"><strong>Dica:</strong> Crie uma página específica para usuários do PWA com um layout otimizado para aplicativos.</p>';
            },
            'pwa-pro-icones',
            'pwa_redirect_section'
        );
    }
    
    // Validar e sanitizar as opções
    public function validate_options($input) {
        $output = array();
        
        // Sanitiza os campos
        $output['app_name'] = sanitize_text_field($input['app_name']);
        $output['short_name'] = sanitize_text_field($input['short_name']);
        $output['background_color'] = sanitize_text_field($input['background_color']);
        $output['theme_color'] = sanitize_text_field($input['theme_color']);
        $output['display'] = sanitize_text_field($input['display']);
        $output['icon_192'] = esc_url_raw($input['icon_192']);
        $output['icon_512'] = esc_url_raw($input['icon_512']);
        $output['button_text'] = sanitize_text_field($input['button_text']);
        $output['button_color'] = sanitize_text_field($input['button_color']);
        $output['button_text_color'] = sanitize_text_field($input['button_text_color']);
        $output['button_position'] = sanitize_text_field($input['button_position']);
        $output['button_horizontal_position'] = sanitize_text_field($input['button_horizontal_position']);
        $output['button_width'] = isset($input['button_width']) ? sanitize_text_field($input['button_width']) : '200px';
        $output['button_height'] = isset($input['button_height']) ? sanitize_text_field($input['button_height']) : '50px';
        $output['show_pages'] = sanitize_text_field($input['show_pages']);
        $output['pwa_redirect_page'] = absint($input['pwa_redirect_page']);
        
        // Tratamento para checkboxes
        $output['show_ios_guide'] = isset($input['show_ios_guide']) ? 'yes' : 'no';
        $output['show_ios_instructions'] = isset($input['show_ios_instructions']) ? 'yes' : 'no';
        $output['enable_install_button'] = isset($input['enable_install_button']) ? 'yes' : 'no'; // Nova opção
        
        return $output;
    }
    
    // Renderiza a página de configurações
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('pwa_pro_icones_group');
                do_settings_sections('pwa-pro-icones');
                submit_button('Salvar Configurações');
                ?>
            </form>
            
            <div class="card" style="max-width: 800px; margin-top: 20px; padding: 10px 20px; background: #fff;">
                <h2>Como funciona o PWA em diferentes dispositivos</h2>
                
                <h3>Android (Chrome, Samsung Internet, etc)</h3>
                <p>Os dispositivos Android mostrarão automaticamente um banner "Instalar App" ou um botão no menu do navegador quando os requisitos do PWA forem atendidos.</p>
                
                <h3>iOS (Safari)</h3>
                <p>No Safari do iOS não existe instalação automática. Os usuários precisam:</p>
                <ol>
                    <li>Tocar no botão de compartilhamento</li>
                    <li>Selecionar "Adicionar à Tela de Início"</li>
                </ol>
                <p>Este plugin mostra um guia para ajudar os usuários de iOS a instalarem seu site como um aplicativo.</p>
                
                <h3>Redirecionamento da página inicial no PWA</h3>
                <p>A funcionalidade de redirecionamento permite criar uma experiência específica para usuários do PWA. Quando alguém acessa a página inicial através do PWA instalado, ele é redirecionado para a página selecionada.</p>
                <p><strong>Como funciona:</strong></p>
                <ul>
                    <li>O plugin detecta automaticamente se o site está sendo acessado como PWA (modo standalone ou fullscreen)</li>
                    <li>Quando a página inicial é carregada em modo PWA, ocorre um redirecionamento automático</li>
                    <li>A detecção é feita via JavaScript, sem cookies ou armazenamento persistente</li>
                    <li>Usuários que acessam o site pelo navegador normal continuam vendo a página inicial padrão</li>
                </ul>
                <p><strong>Dicas de uso:</strong></p>
                <ul>
                    <li>Crie uma página específica para o PWA com navegação simplificada</li>
                    <li>Adicione elementos de interface que pareçam mais com um aplicativo nativo</li>
                    <li>Oculte elementos desnecessários como cabeçalho ou rodapé complexos</li>
                    <li>Destaque funcionalidades principais para acesso rápido</li>
                </ul>
                
                <h3>Otimizações de ícones</h3>
                <p>Este plugin implementa várias melhorias na compatibilidade dos ícones:</p>
                <ul>
                    <li>Ícones separados para 192x192 e 512x512 pixels</li>
                    <li>Suporte a ícones "maskable" para Android</li>
                    <li>Declaração adequada de ícones no manifest.json</li>
                    <li>Compatibilidade com iOS através de meta tags apple-touch-icon</li>
                </ul>
                <p>Essas melhorias garantem que seu PWA tenha ícones funcionando corretamente em todos os dispositivos.</p>
                
                <h3>Configuração do Botão de Instalação</h3>
                <p>O plugin oferece controle total sobre a exibição do botão de instalação:</p>
                <ul>
                    <li><strong>Habilitar/Desabilitar:</strong> Use a opção "Habilitar Botão de Instalação" para ativar ou desativar completamente o botão</li>
                    <li><strong>Controle de Páginas:</strong> Escolha em quais páginas o botão deve aparecer (todas, só a inicial, ou páginas específicas)</li>
                    <li><strong>Personalização Visual:</strong> Configure cores, posição, tamanho e texto do botão</li>
                    <li><strong>Suporte Multiplataforma:</strong> Funciona tanto no Android quanto no iOS com interfaces adaptadas</li>
                </ul>
                <p><strong>Nota:</strong> Quando o botão está desabilitado, nenhuma funcionalidade de instalação é exibida, incluindo guias do iOS.</p>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px; padding: 10px 20px; background: #fff;">
                <h3>Criado Por: <a href="https://pluginstech.com" target="_blank">Plugins Tech</a></h3>
            </div>
        </div>
        <?php
    }
    
    // Função para criar ícones padrão
    private function create_default_icon($path, $width, $height) {
        if (file_exists($path)) {
            return;
        }
        
        // Verifica se as funções GD estão disponíveis
        if (!function_exists('imagecreatetruecolor')) {
            // Fallback básico
            copy(includes_url('images/w-logo-blue.png'), $path);
            return;
        }
        
        $image = imagecreatetruecolor($width, $height);
        
        // Define cores
        $bg = imagecolorallocate($image, 255, 255, 255);
        $blue = imagecolorallocate($image, 0, 115, 170);
        
        // Desenha um fundo branco
        imagefill($image, 0, 0, $bg);
        
        // Desenha um círculo azul
        $centerX = $width / 2;
        $centerY = $height / 2;
        $radius = min($width, $height) * 0.4;
        
        imagefilledellipse($image, $centerX, $centerY, $radius * 2, $radius * 2, $blue);
        
        // Salva a imagem como PNG
        imagepng($image, $path);
        imagedestroy($image);
    }
}

// Inicializa o plugin
$pwa_pro_icones = new PWA_Pro_Icones();

/**
 * Criado Por: Plugins Tech
 * @link https://pluginstech.com
 */