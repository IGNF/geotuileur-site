var fn_sticky_sidebar = function () {

    //
    // Scrollspy & Affix
    //
    // Affix n'existe plus dans Bootstrap 4 et le package de bassjobsen n'est pas sur yarnpkg
    // Affix a donc été copié des sources du Design System (affix-v336-modbs4.js)
    // Affix - see: https://github.com/bassjobsen/affix
    var stickyHeight = $('.header-principal').outerHeight();

   if ($('.container-content--sidebar-inner').length > 0) {
        $('.container-content--sidebar-inner').affix({
            offset: {
                top: function () {
                    var offset = $('.js-scrollspy-target').offset();
                    return offset ? offset.top - stickyHeight - 20 : 0;
                },
                bottom: function () {
                    var eltStickyStop = $('.js-scrollspy-target');
                    var offset;

                    if (eltStickyStop.length > 0) {
                        offset = eltStickyStop.offset();
                        return $('html').outerHeight() - (eltStickyStop.outerHeight() + offset.top);
                    }
                }
            }
        });
    }

    // Scrollspy
    if ($('#navSummary').length > 0) {
        $('body').scrollspy({
            target: '#navSummary',
            offset: stickyHeight + 50
        });
    }

};

export default {
    fn_sticky_sidebar
}