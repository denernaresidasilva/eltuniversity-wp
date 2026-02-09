// Script para as opções da página de admin
jQuery(document).ready(function($) {
    // Inicializa o color picker
    $('.color-picker').wpColorPicker();
    
    // Upload de ícone
    $('#upload_icon_button').click(function(e) {
        e.preventDefault();
        
        var image_frame;
        
        if(image_frame) {
            image_frame.open();
            return;
        }
        
        image_frame = wp.media({
            title: 'Selecione um ícone para o PWA',
            multiple: false,
            library: {
                type: 'image',
            }
        });
        
        image_frame.on('select', function() {
            var attachment = image_frame.state().get('selection').first().toJSON();
            $('#icon_path').val(attachment.url);
            $('#icon-preview').attr('src', attachment.url).show();
            $('#remove_icon_button').show();
        });
        
        image_frame.open();
    });
    
    // Remover ícone
    $('#remove_icon_button').click(function(e) {
        e.preventDefault();
        $('#icon_path').val('');
        $('#icon-preview').attr('src', '').hide();
        $(this).hide();
    });
});