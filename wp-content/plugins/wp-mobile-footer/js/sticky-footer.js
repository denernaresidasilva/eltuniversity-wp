(function($) {
    // Atualizar o campo de ícone com o valor clicado na pré-visualização
    $(document).on('click', '.icon-item', function() {
        var icon = $(this).data('icon');
        $(this).closest('.menu-item').find('.edit-menu-item-icon').val(icon); // Inserir o nome do ícone no campo
    });
})(jQuery);
