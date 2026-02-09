jQuery(document).ready(function($) {
    $('.tutor-video-player').each(function() {
        var videoId = $(this).data('video-id'); // Supondo que você adicione um data attribute com o ID do vídeo
        var proxyUrl = '/wp-content/themes/hello-child/proxy.php?video_id=' + videoId;
        var iframe = $('<iframe>', {
            src: proxyUrl,
            width: '560',
            height: '315',
            frameborder: '0',
            allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
            allowfullscreen: true
        });
        $(this).html(iframe);
    });
});
