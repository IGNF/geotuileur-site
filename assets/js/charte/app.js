import nav from "./_nav";
import target_blank from "./_target-blank";
import float_label from "./_float-label.js";
import overlay_scrollbars from "./_overlayScrollbars.js";
import sticky_sidebar from "./_stickySidebar.js";
import followers from "./_followers.js";
import modal_access from "./_modal-access.js";

// Gère tous les JS utiles au bon fonctionnement du Design System 2020

$(function () {
    nav.fn_nav(); // gère l'ouverture du menu de navigation principal à gauche
    // si utile fn_cover()
    // si utile fn_table()
    // si utile fn_iframe()
    target_blank.fn_target_blank(); // ajoute des icônes et modifie les title des liens target="_blank"
    // si utile fn_banner_video();
    float_label.fn_float_label(); // Gère les inputs de formulaire dont le label est flottant à l'intérieur
    overlay_scrollbars.fn_overlayScrollbars();
    // si utile fn_adjustAnchor();
    // si utile fn_adjustAnchorOnClick();
    sticky_sidebar.fn_sticky_sidebar();
    // si utile fn_paragraph_diaporama();
    // si utile fn_paragraph_diaporama_controls();
    // si utile fn_modaal();
    // si utile fn_masonry();
    modal_access.fn_modal_access();
    followers.fn_update_followers();
});
