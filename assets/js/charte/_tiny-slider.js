var fn_paragraph_diaporama = function () {

    if ( $('.js-paragraph-diaporama').length > 0 ) {
        var paragraph_diaporama = tns({
            container: '.js-paragraph-diaporama',
            items: 1,
            nav: false,
            loop: false,
            controlsText: ["Slide précédente", "Slide suivante"],
            responsive: {
                992: {
                    items: 1.4,
                    gutter: 20,
                }
            }
        });
    }

};

var fn_paragraph_diaporama_controls = function () {
    if ($(window).width() > 991) {
        if ($('.o-paragraph-diaporama__item').length > 0) {
            $('.o-paragraph-diaporama .tns-controls').css("left", $('.o-paragraph-diaporama__item').width() - $('.tns-controls').width());
        }
    }
};