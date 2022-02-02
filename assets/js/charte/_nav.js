var menu_open = function() {
    $("body").addClass('nav-is-open').removeClass('nav-is-closed');
    $('[data-target="#menuPrincipalMobile"], [data-target="#menuLeftDesktop"]').attr('aria-expanded', true);
    //Cookies.set('verticalNav', 'open', { expires: 7 });

    // Position of diaporama controls has changed
    $('.o-paragraph-diaporama .tns-controls').hide();

    setTimeout(function() {
        //fn_paragraph_diaporama_controls();
        //$('.o-paragraph-diaporama .tns-controls').fadeIn();

        $("body").removeClass('js-menu-transition');
    }, 300);
};

var menu_close = function() {
    $("body").removeClass('nav-is-open').addClass('nav-is-closed');
    $('[data-target="#menuPrincipalMobile"], [data-target="#menuLeftDesktop"]').attr('aria-expanded', false);
    //Cookies.set('verticalNav', 'closed', { expires: 7 });

    // Position of diaporama controls has changed
    $('.o-paragraph-diaporama .tns-controls').hide();

    setTimeout(function() {
        //fn_paragraph_diaporama_controls();
        //$('.o-paragraph-diaporama .tns-controls').fadeIn();

        $("body").removeClass('js-menu-transition');
    }, 300);
};

var fn_nav = function () {

    $('[data-target="#menuPrincipalMobile"], [data-target="#menuLeftDesktop"]').on('click', function(){
        $("body").addClass('js-menu-transition');

        if ($(this).attr('aria-expanded') === "true") {
            menu_close();
        } else {
            menu_open();
        }
    });

    $('.navbar-toggler-close').on('click', function(){
        $('.navbar-toggler').focus();
    });

    // Navbar left
    // When there is a current page, all parents are opened
    $('.navbar-nav--left .is-active').parents('.nav-collapse').collapse('show');

    // Desktop only
    if ($(window).width() > 991) {
        // Check cookie for vertical Nav (open/closed)
        // Desktop - Open by default
        /*if (Cookies.get('verticalNav') === "closed") {
            menu_close();
        } else {
            menu_open();
        }*/

        // Megamenu desktop
        // Current portail is opened by default
        //$('.megamenu__link.is-active').siblings('.megamenu__collapse').collapse('show');

        // Megamenu desktop
        // Doesn't close the open item when clicking on itself
        /*$('.megamenu__link').on('click', function(e){
            if ($(this).attr('aria-expanded') === "true") {
                e.stopPropagation();
            }
        });*/
    } else {
        // Check cookie for vertical Nav (open/closed)
        // Mobile - Closed by default
        //Cookies.set('verticalNav', 'closed', { expires: 7 });
    }
};

export default {
    fn_nav
};