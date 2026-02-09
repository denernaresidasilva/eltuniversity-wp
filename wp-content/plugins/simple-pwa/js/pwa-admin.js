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