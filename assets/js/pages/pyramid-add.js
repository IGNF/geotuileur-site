require('../components/jquery-ui-widgets/jquery-ui-samplewidget.js');
require('../components/jquery-ui-widgets/jquery-ui-zoomrange.js');

import React from 'react';
import ReactDOM from 'react-dom';
import { TippeCanoeList } from '../components/react/Tippecanoe';
const flash = require("../components/flash-messages");

let sampleInstance = null;
let tippeCanoeList = null;
let $bbox = $('#generate_pyramid_bbox');
let numTables = $('div#part-2').data('numtables');

let first = true;

/**
 * Retourne les informations suivantes pour une table :
 *      - Le nombre de champs selectionnes
 *      - Niveau de zooms
 * 
 * @param {integer} num 
 */
function getTableInfos(num) {
    // Nbre de champs selectionnes
    let nb = $(`#collapse-map${num} input:checkbox:checked`).length;

    // Niveaux de zoom
    let $zoomLevels = $(`#zoom-levels${num}`);
    
    let topLevel = $zoomLevels.zoomrange('getTopLevel');
    let bottomLevel = $zoomLevels.zoomrange('getBottomLevel');

    let numText;
    if (! nb) {
        numText = 'aucun attribut conservé';    
    } else {
        numText = (nb > 1) ? `${nb} attributs conservés` : `${nb} attribut conservé`
    }
    return `(${numText}, niveaux ${topLevel} à ${bottomLevel})`;
}


// Creation du json des zooms pour les donnees
function getLevels() {
    let levels = {};

    $(`[id*="zoom-levels"]`).each(function () {
        let id = $(this).attr('id');

        let key;
        if (/^main/.test(id)) {
            key = 'main';
        } else key = $(this).data('table');

        let topLevel = $(this).zoomrange('getTopLevel');
        let bottomLevel = $(this).zoomrange('getBottomLevel');
        levels[key] = { topLevel: topLevel, bottomLevel: bottomLevel };
    });

    return levels;
};

$(function () {
    /**
      * GESTION DES ZOOMS
      */
    let datas = $('#main-zoom-levels').data();
    let options = {
        min: datas.toplevelmin,
        max: datas.bottomlevelmax,
        topLevel: datas.toplevelmin,
        bottomLevel: datas.bottomlevelmax
    };

    // Outils pour la definition des zoom min et max
    // Quand les niveaux du zoom principal changent, les autres sont mis a jour
    $('#main-zoom-levels').zoomrange(options).on('zoomrangechanged', (event, data) => {
        // Mise a jour des niveaux de zoom et recalcul de la zone pour l'echantillon
        $('[id^=zoom-levels]').zoomrange('setValues', data.values);
        sampleInstance?.setBottomLevel(data.values[1]); 
    });

    // On differe la creation des cartes apres l'ouverture des accordeons
    $('[id^=zoom-levels]').zoomrange($.extend(options, { defer: true }));
    $('[id^=collapse-map]').on('shown.bs.collapse', function () {
        let num = $(this).data('num');
        $(`#zoom-levels${num}`).zoomrange('showMaps');
    });

    // Par defaut l'accordeon est plie. Lorsqu'on clique sur le lien
    // On deplie le premier une seule fois 
    $('a[href="#part-2"]').on('click', (e) => {
        if (first) {
            $('[id=collapse-map0]').collapse('show');
        } 
        first = ! first;   
    });

    // Bouton table suivante => Affichage de l'icone, on cache le courant et on ouvre le suivant
    $('.next-table').on('click', (e) => {
        e.preventDefault();
        
        let num = $(e.currentTarget).data('num');
        $(`#table-valid${num}`).show();
        $(`[id=collapse-map${num}]`).collapse('hide');
        if (num + 1 < numTables) {
            // Ce n'est pas la dernière table, on peut déplier la suivante
            $(`[id=collapse-map${num+1}]`).collapse('show');    
            // TODO scroll to element
        }
    });

    /*
     * Mise a jour des informations sur les tables :
     *      - Changement des niveaux de zoom
     *      - Click sur un attribut d'une table
     */
    $('[id^=zoom-levels]').on('zoomrangechanged', (e) => {
        let num = $(e.currentTarget).data('num');
        let infos = getTableInfos(num);
        $(`span#table-infos${num}`).text(infos);
    });

    $('.table-field').on('click', (e) => {        
        let num = $(e.currentTarget).closest('[id^=collapse-map]').data('num');
        let infos = getTableInfos(num);
        $(`span#table-infos${num}`).text(infos);
    });

    /**
     * TIPPECANOE
     */
    let tpcdatas = $('#tippecanoe-wrapper').data();

    let re = React.createElement(TippeCanoeList, tpcdatas.tippecanoes);
    tippeCanoeList = ReactDOM.render(re, document.getElementById('tippecanoe-wrapper'));

    /**
     * L'ECHANTILLON
     */
    // Changement d'etat de la case a cocher [Générer un échantillon]
    $('#generate_pyramid_sample').on('change', (e) => {
        let checked = e.currentTarget.checked;
        if (checked) {
            $('#modal-sample').modal('show'); 
            $('#define-bbox').show();   
        } else {
            $bbox.val("");
            $('#define-bbox').hide();
        }
    });

    $('#define-bbox').on('click', function (e) {
        e.preventDefault();
        $('#modal-sample').modal('show');
    });

    // Boite pour l'echantillon
    $('#modal-sample').on('shown.bs.modal', () => {
        let bottomLevel = $('#main-zoom-levels').zoomrange('getBottomLevel');
        if (! sampleInstance) {
            sampleInstance = $('#sample-map').samplewidget({ zoom: bottomLevel, height: '500px' }).data('customSamplewidget');
        } else sampleInstance.setBottomLevel(bottomLevel);
    });

    // Annulation
    $('#sample-cancel').on('click', () => {
        $bbox.val("");
        $('#modal-sample').modal('hide');
    });

    // Validation
    $('#sample-ok').on('click', () => {
        let extent = $('#sample-map').samplewidget('getExtent');
        $bbox.val(JSON.stringify(extent));
        $('#modal-sample').modal('hide');
    });

    /**
     * GESTION DES PANELS
     */
    $('.switch-panel').on('click', function (e) {
        e.preventDefault(); // prevent auto scroll to panel

        let target = $(this).attr("href");
        $(".generate-panel").addClass("hidden");
        $(target).removeClass("hidden");
        window.scrollTo({ top: 0, behavior: 'smooth' });
        console.log("finish scroll");
    });

    // Soumission du formulaire
    $('#form_pyramid_add').on('submit', function () {
        let levels = getLevels();
        $('#generate_pyramid_levels').val(JSON.stringify(levels));

        let sample = $('#generate_pyramid_sample').is(':checked');
        let $bbox = $('#generate_pyramid_bbox');
        if (sample && !$bbox.val()) {
            flash.flashAdd("La zone pour l'échantillon doit être définie", 'danger');
            return false;
        }

        $('#generate_pyramid_tippecanoe').val(tippeCanoeList.value);
        return true;
    });
});
