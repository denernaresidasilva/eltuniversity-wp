/**
 * Scripts para o painel administrativo do Custom Scrollbar Styler
 */

jQuery(document).ready(function($) {
    
    // Inicializa o color picker
    $('.color-picker').wpColorPicker({
        change: function(event, ui) {
            // Atualiza a visualização quando a cor mudar
            updateScrollbarPreview();
            
            // Atualiza a cor do preview ao lado do seletor
            var colorPreview = $(this).closest('td').find('.color-preview');
            colorPreview.css('background-color', ui.color.toString());
        }
    });
    
    // Atualiza a visualização quando os valores mudarem
    $('#scrollbar_width, #thumb_border_radius, #thumb_hover_direction').on('change', function() {
        updateScrollbarPreview();
    });
    
    // Função para atualizar a visualização da barra de rolagem
    function updateScrollbarPreview() {
        var scrollbarWidth = $('#scrollbar_width').val() + 'px';
        var trackColor = $('#track_color').val();
        var thumbGradientStart = $('#thumb_gradient_start').val();
        var thumbGradientEnd = $('#thumb_gradient_end').val();
        var thumbBorderRadius = $('#thumb_border_radius').val() + 'px';
        var thumbHoverDirection = $('#thumb_hover_direction').val() === 'reverse' ? '0deg' : '180deg';
        
        // Aplicar estilos à visualização
        var css = `
            .scrollbar-preview::-webkit-scrollbar {
                width: ${scrollbarWidth};
            }
            .scrollbar-preview::-webkit-scrollbar-track {
                background: ${trackColor};
            }
            .scrollbar-preview::-webkit-scrollbar-thumb {
                -webkit-border-radius: ${thumbBorderRadius};
                border-radius: ${thumbBorderRadius};
                background: linear-gradient(180deg, ${thumbGradientStart}, ${thumbGradientEnd});
            }
            .scrollbar-preview::-webkit-scrollbar-thumb:hover {
                -webkit-border-radius: ${thumbBorderRadius};
                border-radius: ${thumbBorderRadius};
                background: linear-gradient(${thumbHoverDirection}, ${thumbGradientEnd}, ${thumbGradientStart});
            }
        `;
        
        // Remover estilo anterior e adicionar o novo
        $('#custom-scrollbar-preview-css').remove();
        $('head').append(`<style id="custom-scrollbar-preview-css">${css}</style>`);
    }
    
    // Inicializar a visualização quando a página carregar
    updateScrollbarPreview();
    
    // Alerta ao salvar as configurações
    $('form').on('submit', function() {
        // Adiciona uma classe para mostrar que está salvando
        $('.scrollbar-preview-container').addClass('saving');
        
        // Adiciona um flash de confirmação após salvar
        setTimeout(function() {
            $('.scrollbar-preview-container').removeClass('saving');
        }, 1000);
    });
    
    // Adiciona botão de reset para valores padrão
    $('.wrap form').prepend('<button type="button" id="reset-scrollbar-defaults" class="button button-secondary">Restaurar Valores Padrão</button>');
    
    // Função para restaurar valores padrão
    $('#reset-scrollbar-defaults').on('click', function() {
        if (confirm('Tem certeza que deseja restaurar os valores padrão? Todas as configurações personalizadas serão perdidas.')) {
            $('#scrollbar_width').val('8');
            $('#track_color').wpColorPicker('color', '#171616');
            $('#thumb_gradient_start').wpColorPicker('color', '#634AAC');
            $('#thumb_gradient_end').wpColorPicker('color', '#A9D818');
            $('#thumb_border_radius').val('3');
            $('#thumb_hover_direction').val('reverse');
            
            // Atualiza os previews de cor
            $('.color-preview').each(function() {
                var input = $(this).closest('td').find('.color-picker');
                $(this).css('background-color', input.val());
            });
            
            // Atualiza a visualização
            updateScrollbarPreview();
        }
    });
    
    // Adiciona botão para exportar configurações
    $('.wrap form').prepend('<button type="button" id="export-scrollbar-settings" class="button button-secondary">Exportar Configurações</button>');
    
    // Função para exportar configurações
    $('#export-scrollbar-settings').on('click', function() {
        var settings = {
            scrollbar_width: $('#scrollbar_width').val(),
            track_color: $('#track_color').val(),
            thumb_gradient_start: $('#thumb_gradient_start').val(),
            thumb_gradient_end: $('#thumb_gradient_end').val(),
            thumb_border_radius: $('#thumb_border_radius').val(),
            thumb_hover_direction: $('#thumb_hover_direction').val()
        };
        
        // Converte para JSON e cria um elemento de download
        var dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(settings, null, 2));
        var downloadAnchorNode = document.createElement('a');
        downloadAnchorNode.setAttribute("href", dataStr);
        downloadAnchorNode.setAttribute("download", "custom-scrollbar-settings.json");
        document.body.appendChild(downloadAnchorNode);
        downloadAnchorNode.click();
        downloadAnchorNode.remove();
    });
});
