jQuery(document).ready(function($) {
    // Toggle de status
    $(".status-toggle").on("change", function() {
        var userId = $(this).data("user-id");
        var statusText = $(this).closest("tr").find(".status-text");
        var toggleElement = $(this);
        
        // Verificar se é o usuário atual
        if (userId == subscriberManager.current_user_id) {
            alert("Você não pode desativar sua própria conta!");
            toggleElement.prop("checked", !toggleElement.prop("checked"));
            return;
        }
        
        $.ajax({
            url: subscriberManager.ajax_url,
            type: "POST",
            data: {
                action: "toggle_user_status",
                user_id: userId,
                nonce: subscriberManager.nonce
            },
            success: function(response) {
                if (response.success) {
                    statusText.text(response.data.status_text);
                } else {
                    alert("Erro: " + response.data);
                    // Reverter o toggle
                    toggleElement.prop("checked", !toggleElement.prop("checked"));
                }
            },
            error: function() {
                alert("Erro na requisição");
                // Reverter o toggle
                toggleElement.prop("checked", !toggleElement.prop("checked"));
            }
        });
    });
    
    // Modal de edição
    $(".edit-user").on("click", function() {
        var userId = $(this).data("user-id");
        var row = $(this).closest("tr");
        
        $("#edit-user-id").val(userId);
        
        // Extrair nome de usuário (removendo o badge "Você" se existir)
        var usernameText = row.find("td:eq(1)").clone();
        usernameText.find(".current-user-badge").remove();
        $("#edit-username").val(usernameText.text().trim());
        
        $("#edit-email").val(row.find("td:eq(2)").text());
        $("#edit-password").val("");
        
        // Buscar dados do usuário via AJAX
        $.ajax({
            url: subscriberManager.ajax_url,
            type: "POST",
            data: {
                action: "get_user_data",
                user_id: userId,
                nonce: subscriberManager.nonce
            },
            success: function(response) {
                if (response.success) {
                    $("#edit-first-name").val(response.data.first_name);
                    $("#edit-last-name").val(response.data.last_name);
                    $("#edit-email").val(response.data.email);
                } else {
                    alert("Erro ao carregar dados do usuário: " + response.data);
                }
            },
            error: function() {
                alert("Erro ao carregar dados do usuário");
            }
        });
        
        $("#edit-user-modal").show();
    });
    
    // Fechar modal
    $(".close, .cancel-edit").on("click", function() {
        $("#edit-user-modal").hide();
    });
    
    // Fechar modal clicando fora dele
    $(window).on("click", function(event) {
        if (event.target.id === "edit-user-modal") {
            $("#edit-user-modal").hide();
        }
    });
    
    // Fechar modal com ESC
    $(document).on("keydown", function(event) {
        if (event.key === "Escape") {
            $("#edit-user-modal").hide();
        }
    });
    
    // Excluir usuário
    $(".delete-user").on("click", function() {
        var userId = $(this).data("user-id");
        var username = $(this).closest("tr").find("td:eq(1)").text().trim();
        
        // Verificar se é o usuário atual
        if (userId == subscriberManager.current_user_id) {
            alert("Você não pode excluir sua própria conta!");
            return;
        }
        
        if (confirm("Tem certeza que deseja excluir o assinante '" + username + "'?\n\nEsta ação não pode ser desfeita!")) {
            var deleteButton = $(this);
            deleteButton.prop("disabled", true).text("Excluindo...");
            
            $.ajax({
                url: subscriberManager.ajax_url,
                type: "POST",
                data: {
                    action: "delete_subscriber",
                    user_id: userId,
                    nonce: subscriberManager.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remover a linha da tabela com animação
                        deleteButton.closest("tr").fadeOut(500, function() {
                            $(this).remove();
                        });
                    } else {
                        alert("Erro ao excluir assinante: " + response.data);
                        deleteButton.prop("disabled", false).text("Excluir");
                    }
                },
                error: function() {
                    alert("Erro na requisição");
                    deleteButton.prop("disabled", false).text("Excluir");
                }
            });
        }
    });
    
    // Gerar senha
    $("#generate-password").on("click", function() {
        var password = generatePassword();
        $("#password").val(password);
        
        // Mostrar senha gerada temporariamente
        var originalType = $("#password").attr("type");
        $("#password").attr("type", "text");
        
        setTimeout(function() {
            $("#password").attr("type", originalType);
        }, 2000);
    });
    
    // Validação do formulário de edição
    $("#edit-user-form").on("submit", function(e) {
        var email = $("#edit-email").val();
        var firstName = $("#edit-first-name").val();
        var lastName = $("#edit-last-name").val();
        
        if (!email) {
            alert("O campo email é obrigatório!");
            e.preventDefault();
            return false;
        }
        
        // Validação simples de email
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Por favor, insira um email válido!");
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Validação do formulário de adicionar
    $(".subscriber-form").on("submit", function(e) {
        var username = $("#username").val();
        var email = $("#email").val();
        var password = $("#password").val();
        
        if (!username || !email || !password) {
            alert("Por favor, preencha todos os campos obrigatórios!");
            e.preventDefault();
            return false;
        }
        
        // Validação simples de email
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Por favor, insira um email válido!");
            e.preventDefault();
            return false;
        }
        
        // Validação de senha
        if (password.length < 6) {
            alert("A senha deve ter pelo menos 6 caracteres!");
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // Função para gerar senha aleatória
    function generatePassword() {
        var length = 12;
        var charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
        var password = "";
        
        // Garantir pelo menos um de cada tipo
        var lowercase = "abcdefghijklmnopqrstuvwxyz";
        var uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        var numbers = "0123456789";
        var special = "!@#$%^&*()_+";
        
        password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
        password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += special.charAt(Math.floor(Math.random() * special.length));
        
        // Preencher o resto
        for (var i = 4; i < length; i++) {
            password += charset.charAt(Math.floor(Math.random() * charset.length));
        }
        
        // Embaralhar a senha
        return password.split('').sort(function() { return 0.5 - Math.random(); }).join('');
    }
    
    // Melhorar experiência do usuário
    $("input[type='password']").on("focus", function() {
        $(this).attr("placeholder", "Digite uma senha segura");
    });
    
    $("input[type='password']").on("blur", function() {
        $(this).attr("placeholder", "");
    });
    
    // Auto-focus no primeiro campo dos modais
    $("#edit-user-modal").on("show", function() {
        setTimeout(function() {
            $("#edit-email").focus();
        }, 100);
    });
});