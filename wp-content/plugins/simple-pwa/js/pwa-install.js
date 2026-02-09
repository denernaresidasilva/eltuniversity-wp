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