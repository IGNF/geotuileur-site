require('../components/jquery-ui-widgets/jquery-ui-samplewidget.js');
require('../components/jquery-ui-widgets/jquery-ui-zoomrange.js');

import React from 'react';
import ReactDOM from 'react-dom';
import { TippeCanoeList } from '../components/react/Tippecanoe';
import { PyramidComposition } from '../components/pyramid-add-page/pyramid-composition';
import axios from 'axios';
import flash from "../components/flash-messages";

let datas = $('#part-2').data();

let sampleInstance = null;
let tippeCanoeList = null;
let pyramidComposition = null;

let $bbox = $('#generate_pyramid_bbox');
let numTables = datas.typeinfos.relations.length;
let sampleParameters = datas.pyramidsample ? datas.pyramidsample.parameters : null;

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
    if (!nb) {
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
     * AUTHENTIFICATION EXPIRATION
     */
    let onGoingRequest = false;
    let loginExpiredMsgShown = false;

    setInterval(async () => {
        if (onGoingRequest) return;

        onGoingRequest = true;
        let response = null;

        try {
            response = await axios.get(Routing.generate("plage_security_check_auth"))

        } catch (error) {
            console.error(error);
        } finally {
            onGoingRequest = false;
        }

        if (!response?.data?.is_authenticated) {
            if (!loginExpiredMsgShown) {
                const url = Routing.generate("plage_security_login", { 'side_login': true });
                let flashEl = flash.flashAdd(`Votre connexion a expiré, veuillez vous <a href="#" class="btn-login">reconnecter</a>`, 'error', true)

                flashEl.find(".btn-login").on('click', function () {
                    window.open(url, '_blank');
                    flashEl.remove();
                    loginExpiredMsgShown = false;
                });
                loginExpiredMsgShown = true
            }
        }

    }, 10000);


    /**
     * TABLES ET ATTRIBUTS
     */
    pyramidComposition = new PyramidComposition();
    pyramidComposition.buildForm();

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
    if (sampleParameters) {
        let num = 0;
        sampleParameters.composition.forEach(composition => {
            $(`#zoom-levels${num}`).zoomrange({
                min: composition['top_level'],
                max: composition['bottom_level'],
                topLevel: composition['top_level'],
                bottomLevel: composition['bottom_level'],
                defer: true
            });
            num++
        });
    } else $('[id^=zoom-levels]').zoomrange(options);

    $('[id^=collapse-map]').on('shown.bs.collapse', function () {
        let num = $(this).data('num');

        // Initialisation des cartes pour la selection des niveaux de zoom
        $(`#zoom-levels${num}`).zoomrange('showMaps');
    });

    // Par defaut l'accordeon est plie. Lorsqu'on clique sur le lien
    // On deplie le premier une seule fois 
    $('a[href="#part-2"]').on('click', (e) => {
        if (first) {
            $('[id=collapse-map0]').collapse('show');
        }
        first = !first;
    });

    // Comportement etrange, quand on passe sur une partie ou il y a des cartes
    // celles-ci doivent etre raffraichies ????
    ['part-1', 'part-2'].forEach(part => {
        let id = `#${part}`;
        $(`a[href="${id}"]`).on('click', (e) => {
            $(`${id} [id*="zoom-levels"]`).zoomrange('refresh');
        });
    });

    // Bouton table suivante => Affichage de l'icone, on cache le courant et on ouvre le suivant
    $('.next-table').on('click', (e) => {
        e.preventDefault();

        let num = $(e.currentTarget).data('num');

        let infos = getTableInfos(num);
        $(`#table-valid${num}`).show();
        $(`span#table-infos${num}`).text(infos);

        $(`[id=collapse-map${num}]`).collapse('hide');
        if (num + 1 < numTables) {
            // Ce n'est pas la dernière table, on peut déplier la suivante
            $(`[id=collapse-map${num + 1}]`).collapse('show');
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
        if (!sampleInstance) {
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
    });

    // Soumission du formulaire
    $('#form_pyramid_add').on('submit', function () {
        let levels = getLevels();
        $('#generate_pyramid_levels').val(JSON.stringify(levels));

        let composition = pyramidComposition.asJsonString();
        $('#generate_pyramid_composition').val(composition);

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
