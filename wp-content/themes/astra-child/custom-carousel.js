jQuery(document).ready(function ($) {
    console.log('custom-carousel.js script loaded');

    // Função para gravar o valor no localStorage ao clicar no banner
    $('.course-link').on('click', function (e) {
        var enableComments = $(this).data('enable-comments');
        console.log('Enable Comments:', enableComments);

        // Verifica se o valor é definido corretamente
        if (enableComments !== undefined) {
            localStorage.setItem('enable_comments', enableComments);
            console.log('Stored in LocalStorage:', localStorage.getItem('enable_comments'));
        } else {
            localStorage.setItem('enable_comments', 'no');
            console.log('Stored in LocalStorage as no:', localStorage.getItem('enable_comments'));
        }
    });

    // Função para esconder a aba de comentários na página do curso
    $(window).on('load', function () {
        console.log('Window on load event triggered');

        setTimeout(function() {
            var storedEnableComments = localStorage.getItem('enable_comments');
            console.log('Stored Enable Comments on Load:', storedEnableComments);

            if (storedEnableComments === 'no') {
                var commentsTab = $('a[data-tutor-query-value="comments"]'); // Ajuste o seletor conforme necessário
                console.log('Comments Tab Found:', commentsTab.length > 0);
                if (commentsTab.length > 0) {
                    commentsTab.addClass('hide-comments');
                    console.log('Comments Tab Class hide-comments Added');
                } else {
                    console.log('Comments Tab not found');
                }
            } else {
                console.log('Comments are enabled.');
            }
        }, 10); // Ajuste o tempo de atraso conforme necessário
    });

    // Funções para o carrossel
    $('.custom-carousel').on('scroll', function () {
        const $this = $(this);
        $this.parent().toggleClass('show-left', $this.scrollLeft() > 0);
        $this.parent().toggleClass('show-right', $this.scrollLeft() + $this.innerWidth() < $this[0].scrollWidth);
    }).trigger('scroll');

    $('.arrow.left').on('click', function () {
        $(this).siblings('.custom-carousel').scrollLeft($(this).siblings('.custom-carousel').scrollLeft() - 300);
    });

    $('.arrow.right').on('click', function () {
        $(this).siblings('.custom-carousel').scrollLeft($(this).siblings('.custom-carousel').scrollLeft() + 300);
    });
});
