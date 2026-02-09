jQuery(document).ready(function($) {
    // Navegação por abas
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Ativar aba
        $('.nav-tab-wrapper a').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Mostrar conteúdo
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
    
    // Alternar campos com base no tipo de widget
    $('#tipo').on('change', function() {
        var tipo = $(this).val();
        $('.tipo-conteudo').hide();
        $('.tipo-' + tipo).show();
    });
    
    // Upload de imagem
    $('.upload-imagem').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var imagePreviewContainer = button.closest('.tipo-imagem').find('.imagem-preview-container');
        var inputField = button.closest('.tipo-imagem').find('#conteudo-imagem');
        
        var mediaUploader = wp.media({
            title: 'Selecionar Imagem',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            inputField.val(attachment.url);
            
            // Atualizar preview
            imagePreviewContainer.html('<img src="' + attachment.url + '" alt="" class="imagem-preview">');
            button.siblings('.remover-imagem').show();
        });
        
        mediaUploader.open();
    });
    
    // Remover imagem
    $('.remover-imagem').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var imagePreviewContainer = button.closest('.tipo-imagem').find('.imagem-preview-container');
        var inputField = button.closest('.tipo-imagem').find('#conteudo-imagem');
        
        inputField.val('');
        imagePreviewContainer.html('<div class="sem-imagem">Nenhuma imagem selecionada</div>');
        button.hide();
    });
    
    // Adicionar link
    $('.adicionar-link').on('click', function(e) {
        e.preventDefault();
        
        var linksContainer = $('#links-container');
        var index = linksContainer.children().length;
        
        var newLinkItem = $('<div class="link-item"></div>');
        newLinkItem.append('<input type="text" name="conteudo[links][' + index + '][url]" placeholder="URL">');
        newLinkItem.append('<input type="text" name="conteudo[links][' + index + '][texto]" placeholder="Texto do link">');
        newLinkItem.append('<button type="button" class="button remover-link">Remover</button>');
        
        linksContainer.append(newLinkItem);
    });
    
    // Remover link (delegação de eventos)
    $(document).on('click', '.remover-link', function() {
        $(this).closest('.link-item').remove();
        
        // Reindexar campos
        $('#links-container .link-item').each(function(index) {
            $(this).find('input').each(function() {
                var name = $(this).attr('name');
                var newName = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', newName);
            });
        });
    });
    
    // Enviar formulário via AJAX
    $('#form-widget').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var editorContent = tinymce.get('conteudo-editor');
        
        // Atualizar conteúdo do editor para o campo oculto (se estiver visível)
        if (editorContent && $('.tipo-editor').is(':visible')) {
            $('#conteudo-editor').val(editorContent.getContent());
        }
        
        $.ajax({
            url: painelWidgetsAjax.ajax_url,
            type: 'POST',
            data: form.serialize() + '&action=salvar_widget&nonce=' + painelWidgetsAjax.nonce,
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true).text('Salvando...');
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data);
                    form.find('button[type="submit"]').prop('disabled', false).text('Adicionar Widget');
                }
            },
            error: function() {
                alert('Erro ao processar a solicitação.');
                form.find('button[type="submit"]').prop('disabled', false).text('Adicionar Widget');
            }
        });
    });
    
    // Editar widget
    $(document).on('click', '.editar-widget', function(e) {
        e.preventDefault();
        
        var widgetId = $(this).data('id');
        
        // Mostrar a aba de adicionar
        $('.nav-tab-wrapper a[href="#adicionar-widget"]').trigger('click');
        
        // Carregar dados do widget para edição
        // Nota: Aqui você precisaria implementar um AJAX para buscar os detalhes do widget
        // Para simplificar, recarregaremos a página com um parâmetro para edição
        window.location.href = window.location.href + '&edit=' + widgetId;
    });
    
    // Excluir widget
    $(document).on('click', '.excluir-widget', function(e) {
        e.preventDefault();
        
        if (confirm('Tem certeza que deseja excluir este widget?')) {
            var widgetId = $(this).data('id');
            
            $.ajax({
                url: painelWidgetsAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'excluir_widget',
                    widget_id: widgetId,
                    nonce: painelWidgetsAjax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        window.location.reload();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('Erro ao processar a solicitação.');
                }
            });
        }
    });
    
    // Cancelar edição
    $(document).on('click', '.cancelar-edicao', function(e) {
        e.preventDefault();
        window.location.href = window.location.href.split('&edit=')[0];
    });
});