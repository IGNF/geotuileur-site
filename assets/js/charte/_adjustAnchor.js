var fn_adjustAnchor = function () {
    var offset = $(':target').offset();
    var headerHeight = $('.header-principal').outerHeight();

    if (offset) {
        var scrollto = offset.top - headerHeight;
        $('html, body').animate({scrollTop: scrollto}, 0);
    }


    // Check hash
    var hash = window.location.hash;

    if (hash) {
        // Get accordion header element
        var requestedPanel = $(hash);
        if (requestedPanel.length) {
            var requestedPanelHeader = requestedPanel.find('.js-accordion__header');

            if (requestedPanelHeader.length) {
                var requestedPanelPanel = requestedPanelHeader.attr('aria-controls');
                requestedPanelHeader.attr("aria-expanded","true");
                console.log($(requestedPanelPanel))
                $('#' + requestedPanelPanel).removeAttr('aria-hidden');
            }
        }
    }
};

var fn_adjustAnchorOnClick = function () {
    // Smooth scroll for in page links
    $('a[href*="#"]:not([href="#"]):not([role="tab"]):not([data-toggle="collapse"]):not(.sr-only-focusable)').click(function () {
        if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
            var target = $(this.hash);
            var headerHeight = $('.header-principal').outerHeight() + 20;

            target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');

            if (target.length) {
                $('html,body').animate({
                    scrollTop: target.offset().top - headerHeight
                }, 1000);
                return false;
            }
        }
    });
};