const flash = require("../components/flash-messages");
import MapViewer from "../components/MapViewer";

let iframe = `<iframe width="600" height="400" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" sandbox="allow-forms allow-scripts allow-same-origin" src="__VIEWER_ROUTE__"></iframe>`;
let viewer  = null;

// Generation de l'URL
function generateViewerRoute(streamUrl, styleUrl) {
    let params = { tiles_url: streamUrl };
    if (styleUrl) {
        params['style_url'] = styleUrl;
    }
    return Routing.generate('plage_viewer', params, true);
}


// Affichage de la boite de la publication du flux
function showDialog() {
    let $div = $('<div>', { class: 'text-center' });
    $div.append(Translator.trans('pyramid.share.flow_is_published'));
    $('<p>').text(Translator.trans('pyramid.share.flow_is_published_explain')).appendTo($div);
    
    bootbox.alert({
        className: 'text-center',
        title: " ",
        message: $div[0].outerHTML,
        buttons: {
            ok: {
                className: 'btn btn--plain btn--primary mx-auto',
                label: Translator.trans('pyramid.share.understood'),
            }
        },
        callback: () => {
            $('.warning-personalize').show();
        }
    });
}

$(function() {
    // Show warning on first visit after publish
    let referrer = document.referrer;
    if (/\/publish$/.test(referrer)) {
        showDialog();
    }

    let pyramidDatas = $('#map-target').data();
    let pyramid      = pyramidDatas.pyramid;
    
    // Url du style par defaut
    let styles = pyramidDatas.styles;
    if (Array.isArray(styles)) {
        styles = {};
    }

    let style;
    let ids = Object.keys(styles);
    if (ids.length) {   // Toujours le premier
        style = styles[ids[0]];
    }

    let streamUrl = pyramid.tags.tms_url;
    viewer = new MapViewer('map-target', streamUrl, style?.url, { 
        layerSwitcherControl: true
    });

    viewer.on('tmslayeradded', (event) => {
        // Mise a jour de l'entete de la page
        $('.o-page-title h1').text(event.metadatas.title);
    });
    
    // Mise a jour des url
    let urlView = generateViewerRoute(streamUrl, style?.url);
    $('#viewer-url').val(urlView)
    $('#iframe-code').html(iframe.replace('__VIEWER_ROUTE__', urlView));

    // Copie des urls dans le presse-papier
    $('.copy-link').on('click', (e) => {
        let src = $(e.currentTarget).data('src');
        let srcElt = $(`#${src}`);
        
        // 3 possibilités : input (val), textarea (html), select (val)
        let content = srcElt.is('textarea') ?  srcElt.html() : srcElt.val();
        if (srcElt.is('textarea')) {
            content = $('<textarea/>').html(content).text();
        }
        if (navigator.clipboard) {
            navigator.clipboard.writeText(content);
            flash.flashAdd(Translator.trans('pyramid.share.url_copied'), 'notice');
        }
    });

    // Gestion des styles (changement de style => application sur la couche + mise a jour du lien)
    $('#styles').on('change', (e) => {
        let url = $(e.currentTarget).val();
        viewer.setStyle(url);

        let urlView = generateViewerRoute(streamUrl, url);
        $('#viewer-url').val(urlView);
    });

    // Fermeture de la boite de warning
    $('.close-warning').on('click', () => {
        $('.warning-personalize').hide();
    });
});