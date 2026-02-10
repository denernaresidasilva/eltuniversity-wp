(function($) {
    'use strict';

    let qrCodeTimer = null;
    let connectionCheckTimer = null;
    let expirationTime = null;
    let timerInterval = null;

    const ZapWAQRCode = {
        
        init: function() {
            this.bindEvents();
            
            // Auto-load QR code if container exists
            if ($('#zapwa-qrcode-display').length) {
                this.loadQRCode();
            }
        },

        bindEvents: function() {
            $(document).on('click', '#zapwa-refresh-qrcode', this.refreshQRCode.bind(this));
            $(document).on('click', '#zapwa-download-qrcode', this.downloadQRCode.bind(this));
        },

        loadQRCode: function() {
            this.showLoading();
            this.fetchQRCode();
        },

        refreshQRCode: function(e) {
            if (e) e.preventDefault();
            this.stopTimers();
            this.loadQRCode();
        },

        fetchQRCode: function() {
            const self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'zapwa_get_qrcode',
                    instance: zapwaConfig.instanceName,
                    nonce: zapwaConfig.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.qrcode_base64) {
                        self.displayQRCode(response.data.qrcode_base64);
                        self.startTimer(response.data.expires_in || 120);
                        self.startConnectionCheck();
                        self.showStatus('Escaneie o QR Code com seu WhatsApp', 'info');
                    } else {
                        self.showError(response.data && response.data.error ? response.data.error : 'Erro ao carregar QR Code');
                    }
                },
                error: function(xhr, status, error) {
                    self.showError('Erro de conexão: ' + error);
                }
            });
        },

        displayQRCode: function(base64) {
            const $container = $('#zapwa-qrcode-display');
            const $image = $container.find('.zapwa-qrcode-image');
            
            $image.html('<img src="' + base64 + '" alt="QR Code WhatsApp">');
            $container.removeClass('loading expired').addClass('active');
        },

        showLoading: function() {
            const $container = $('#zapwa-qrcode-display');
            const $image = $container.find('.zapwa-qrcode-image');
            
            $image.html('<div class="zapwa-loading-spinner"></div><p>Carregando QR Code...</p>');
            $container.addClass('loading').removeClass('expired connected');
        },

        showError: function(message) {
            const $container = $('#zapwa-qrcode-display');
            const $image = $container.find('.zapwa-qrcode-image');
            
            $image.html('<p style="color: #dc3232;">❌ ' + message + '</p>');
            $container.removeClass('loading active').addClass('expired');
            this.showStatus(message, 'error');
        },

        showStatus: function(message, type) {
            const $status = $('#zapwa-qrcode-status');
            $status.removeClass('info success error').addClass(type);
            $status.html(message).show();
        },

        startTimer: function(seconds) {
            const self = this;
            expirationTime = Date.now() + (seconds * 1000);
            
            const updateTimer = function() {
                const remaining = Math.max(0, Math.floor((expirationTime - Date.now()) / 1000));
                const minutes = Math.floor(remaining / 60);
                const secs = remaining % 60;
                
                const $timer = $('#zapwa-qrcode-timer');
                $timer.text('⏱️ Expira em: ' + minutes + ':' + (secs < 10 ? '0' : '') + secs);
                
                if (remaining < 30) {
                    $timer.removeClass('warning').addClass('expired');
                } else if (remaining < 60) {
                    $timer.addClass('warning');
                }
                
                if (remaining === 0) {
                    self.handleExpiration();
                }
            };
            
            updateTimer();
            timerInterval = setInterval(updateTimer, 1000);
        },

        handleExpiration: function() {
            this.stopTimers();
            const $container = $('#zapwa-qrcode-display');
            $container.removeClass('active').addClass('expired');
            this.showStatus('QR Code expirado. Clique em "Atualizar QR Code" para gerar um novo.', 'error');
        },

        startConnectionCheck: function() {
            const self = this;
            
            connectionCheckTimer = setInterval(function() {
                self.checkConnection();
            }, 5000); // Check every 5 seconds
        },

        checkConnection: function() {
            const self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'zapwa_check_connection',
                    instance: zapwaConfig.instanceName,
                    nonce: zapwaConfig.nonce
                },
                success: function(response) {
                    if (response.success && response.data && response.data.connected) {
                        self.handleConnected();
                    }
                }
            });
        },

        handleConnected: function() {
            this.stopTimers();
            
            const $container = $('#zapwa-qrcode-display');
            $container.removeClass('loading active expired').addClass('connected');
            
            const $image = $container.find('.zapwa-qrcode-image');
            $image.html('<div style="font-size: 72px; color: #46b450;">✅</div><h2 style="color: #46b450;">Conectado com sucesso!</h2>');
            
            this.showStatus('WhatsApp conectado! A página será recarregada em 3 segundos...', 'success');
            
            setTimeout(function() {
                location.reload();
            }, 3000);
        },

        downloadQRCode: function(e) {
            e.preventDefault();
            
            const $img = $('.zapwa-qrcode-image img');
            if ($img.length) {
                const link = document.createElement('a');
                link.href = $img.attr('src');
                link.download = 'whatsapp-qrcode-' + Date.now() + '.png';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },

        stopTimers: function() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            if (connectionCheckTimer) {
                clearInterval(connectionCheckTimer);
                connectionCheckTimer = null;
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        ZapWAQRCode.init();
    });

})(jQuery);
