//
// Modaal init
// https://github.com/humaan/Modaal
//
var fn_modaal = function () {

    $('.o-paragraph-diaporama__fullscreen').modaal({
        type: 'image',
        close_text: 'Fermer le diaporama plein écran',
        close_aria_label: 'Fermer le diaporama plein écran',
        accessible_title: 'Diaporama plein écran',
        overlay_opacity: 1,
        background: "#fff",
        outer_controls: true
    });

};