// Post Type and Taxonomy Order
(function ($) {
    $('.wp-list-table').wrap('<div class="adminify-table-responsive-wrapper"></div>');
    var titleWidth = $('.wp-list-table td.title').first().width();
    $('.wp-list-table td.title, .wp-list-table th#title').css('width', titleWidth + 'px');

    // Media
    $("#adminify-pto-media #sortable").sortable({
        tolerance: "pointer",
        cursor: "pointer",
        items: "li",
        axis: "y",
        revert: true,
        placeholder: "placeholder",
        nested: "ul",
        update: function (e, ui) {
            $.post(
                ajaxurl,
                {
                    action: "update_post_types_order",
                    order: $("#adminify-pto-media #sortable").sortable("serialize"),
                    adminify_media_sort_nonce: $("#adminify_media_sort_nonce").val(),
                },
                function () {
                    $("#adminify-ajax-response").html(
                        '<div class="message updated"><p>Media Order Updated</p></div>'
                    );
                    $("#adminify-ajax-response div").delay(2000).hide("slow");
                }
            );
        },
    });

    $("#sortable").disableSelection();

    // posts
    // $("table.posts #the-list, table.pages #the-list, table.media #the-list").sortable({
    //     items: "tr",
    //     axis: "y",
    //     containment: "parent",
    //     forceHelperSize: true,
    //     forcePlaceholderSize: true,
    //     revert: true,
    //     tolerance: "pointer",
    //     distance: 1,
    //     helper: adminifyPTOHelper,
    //     update: function (e, ui) {
    //         $.post(ajaxurl, {
    //             action: "update_post_types_order",
    //             order: $("#the-list").sortable("serialize"),
    //         });
    //     },
    // });
    

    $("table.posts #the-list, table.pages #the-list, table.media #the-list").sortable({
        items: "tr",
        axis: "y",
        containment: "parent",
        forceHelperSize: true,
        forcePlaceholderSize: true,
        revert: true,
        tolerance: "pointer",
        distance: 1,
        helper: adminifyPTOHelper,
        placeholder: "ui-sortable-placeholder",
        update: function (e, ui) {
            $.post(ajaxurl, {
                action: "update_post_types_order",
                order: $("#the-list").sortable("serialize"),
            });
        },
    });
    
    
    $("#the-list").disableSelection();

    // Taxonomy Order
    $("table.tags #the-list").sortable({
        items: "tr",
        axis: "y",
        revert: true,
        containment: "parent",
        forceHelperSize: true,
        forcePlaceholderSize: true,
        revert: true,
        tolerance: "pointer",
        distance: 1,
        helper: adminifyPTOHelper,
        placeholder: "ui-sortable-placeholder",
        update: function (e, ui) {
            $.post(ajaxurl, {
                action: "update_post_types_taxonomy_order",
                order: $("#the-list").sortable("serialize"),
            });
        },
    });
    // $("#the-list").disableSelection();

    // pro code start
    // sites
    var site_table_tr = $("table.sites #the-list tr");
    site_table_tr.each(function () {
        var ret = null;
        var url = $(this).find("td.blogname a").attr("href");
        parameters = url.split("?");
        if (parameters.length > 1) {
            var params = parameters[1].split("&");
            var paramsArray = [];
            for (var i = 0; i < params.length; i++) {
                var neet = params[i].split("=");
                paramsArray.push(neet[0]);
                paramsArray[neet[0]] = neet[1];
            }
            ret = paramsArray["id"];
        }
        $(this).attr("id", "site-" + ret);
    });
    // pro code end

    $("table.sites #the-list").sortable({
        items: "tr",
        axis: "y",
        containment: "parent",
        forceHelperSize: true,
        forcePlaceholderSize: true,
        revert: true,
        tolerance: "pointer",
        distance: 1,
        helper: adminifyPTOHelper,
        placeholder: "ui-sortable-placeholder",
        update: function (e, ui) {
            $.post(ajaxurl, {
                action: "update_post_types_order_sites",
                order: $("#the-list").sortable("serialize"),
            });
        },
    });

    var adminifyPTOHelper = function (e, ui) {
        ui.children()
            .children()
            .each(function () {
                $(this).width($(this).width());
            });
        return ui;
    };
})(jQuery);
