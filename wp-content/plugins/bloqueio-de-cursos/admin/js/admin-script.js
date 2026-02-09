// JavaScript para adicionar feedback visual e confirmação de salvamento (Agencycoders)
jQuery(document).ready(function($) {
    // Toggle redirecionamento ativado/desativado
    $('.switch-ajax').on('change', function() {
        var switchElement = $(this);
        var courseId = switchElement.data('course-id');
        var enabled = switchElement.is(':checked');
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'toggle_redirect',
                course_id: courseId,
                enabled: enabled,
                security: $('#tutor_lms_redirect_nonce_field').val()
            },
            beforeSend: function() {
                // Adiciona um indicador de carregamento
                switchElement.closest('tr').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    switchElement.closest('tr').toggleClass('highlight', enabled);
                    alert('Redirecionamento ' + (enabled ? 'ativado' : 'desativado') + ' com sucesso.');
                } else {
                    alert('Erro ao salvar alterações');
                }
            },
            complete: function() {
                // Remove o indicador de carregamento
                switchElement.closest('tr').css('opacity', '1');
            },
            error: function() {
                alert('Erro ao salvar alterações');
            }
        });
    });

    // Atualiza página de redirecionamento
    $('.redirect-page-select').on('change', function() {
        var selectElement = $(this);
        var courseId = selectElement.data('course-id');
        var redirectPageId = selectElement.val();

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'update_redirect_page',
                course_id: courseId,
                redirect_page_id: redirectPageId,
                security: $('#tutor_lms_redirect_nonce_field').val()
            },
            beforeSend: function() {
                // Adiciona um indicador de carregamento
                selectElement.closest('tr').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    alert('Página de redirecionamento atualizada com sucesso.');
                } else {
                    alert('Erro ao salvar alterações');
                }
            },
            complete: function() {
                // Remove o indicador de carregamento
                selectElement.closest('tr').css('opacity', '1');
            },
            error: function() {
                alert('Erro ao salvar alterações');
            }
        });
    });

    // Atualiza URL externa de redirecionamento
    $('.redirect-url-input').on('change', function() {
        var inputElement = $(this);
        var courseId = inputElement.data('course-id');
        var redirectUrl = inputElement.val();

        // Verifica se a URL está vazia ou não é válida
        if (!redirectUrl || !isValidURL(redirectUrl)) {
            alert('Por favor, insira uma URL válida incluindo http:// ou https://');
            return;
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'update_redirect_url',
                course_id: courseId,
                redirect_url: redirectUrl,
                security: $('#tutor_lms_redirect_nonce_field').val()
            },
            beforeSend: function() {
                // Adiciona um indicador de carregamento
                inputElement.closest('tr').css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    alert('URL de redirecionamento atualizada com sucesso.');
                } else {
                    alert('Erro ao salvar alterações');
                }
            },
            complete: function() {
                // Remove o indicador de carregamento
                inputElement.closest('tr').css('opacity', '1');
            },
            error: function() {
                alert('Erro ao salvar alterações');
            }
        });
    });

    // Alternar entre os tipos de redirecionamento (página ou URL)
    $('.redirect-type-select').on('change', function() {
        var selectElement = $(this);
        var courseId = selectElement.data('course-id');
        var redirectType = selectElement.val();
        var row = selectElement.closest('tr');
        
        // Exibe o campo apropriado baseado no tipo selecionado
        if (redirectType === 'page') {
            row.find('.page-select-container').show();
            row.find('.url-input-container').hide();
        } else { // url
            row.find('.page-select-container').hide();
            row.find('.url-input-container').show();
        }
        
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'update_redirect_type',
                course_id: courseId,
                redirect_type: redirectType,
                security: $('#tutor_lms_redirect_nonce_field').val()
            },
            beforeSend: function() {
                // Adiciona um indicador de carregamento
                row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    console.log('Tipo de redirecionamento atualizado com sucesso.');
                } else {
                    alert('Erro ao salvar alterações');
                }
            },
            complete: function() {
                // Remove o indicador de carregamento
                row.css('opacity', '1');
            },
            error: function() {
                alert('Erro ao salvar alterações');
            }
        });
    });

    // Função para validar URL
    function isValidURL(url) {
        // Verifica se a URL começa com http:// ou https://
        return /^(https?:\/\/)/.test(url);
    }
});