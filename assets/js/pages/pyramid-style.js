const flash = require("../components/flash-messages");
const jsZip = require("jszip");

import React from 'react';
import ReactDOM from 'react-dom';
import StylesList from "../components/react/StylesList";
import MapViewer from "../components/MapViewer";
import { Wait } from "../utils";
import { StyleFileReader } from "../components/style-file-reader";

let wait = new Wait({ id: 'styles' });

let viewer  = null;
let stylesList = null;
let accepted = null;
let styleFileReader = null;

/**
 * Ouverture de la boite pour le nom
 * @param string url 
 * @param Object datas 
 */
function openNameDialog(url, datas) {
    const template = document.getElementById("template-dlg-style");
    const clone = template.content.cloneNode(true);

    bootbox.dialog({ 
        title: Translator.trans('pyramid.style.import'),
        message: clone,
        buttons: {
            cancel: {
                label: Translator.trans('cancel'),
                className: 'btn btn-sm btn--ghost btn--primary',
                callback: () => {}
            },
            add: {
                label: Translator.trans('add'),
                className: 'btn btn-sm btn--plain btn--primary',
                callback: () => {
                    wait.show(Translator.trans('pyramid.style.add_wait_msg'));

                    let $error = $('.style-error');
                    $error.hide();

                    let name = $('#style-name').val();
                    if (! name) {
                        $error.text(Translator.trans('pyramid.style.name_not_empty'));
                        $error.show();
                        return false;
                    }
                    if (stylesList.styleExists(name)) {
                        $error.text(Translator.trans('pyramid.style.name_exists'));
                        $error.show();
                        return false;
                    }
                    
                    let formData = new FormData();
                    formData.append("name", name);
                    for (const [key, value] of Object.entries(datas)) {
                        formData.append(key, value);
                    }

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false
                    }).done(style => {
                        wait.hide();
                        stylesList.add(style);
                    }).fail(() => {
                        wait.hide();
                        let message = Translator.trans('pyramid.style.add_failed');
                        flash.flashAdd(message, 'danger');
                    });
                }
            }
        }
    }); 
}

$(function() {
    let pyramidDatas = $('#map-target').data();

    let datastoreId  = pyramidDatas.datastoreid;
    let pyramid      = pyramidDatas.pyramid;
    
    // Url du style par defaut
    let styles = pyramidDatas.styles;
    if (Array.isArray(styles)) {
        styles = {};
    }

    let style;
    let ids = Object.keys(styles);
    if (ids.length) {   // Toujours le premier
        style = Object.assign({ id: ids[0] }, styles[ids[0]]);
    }

    let streamUrl = pyramid.tags.tms_url;
    viewer = new MapViewer('map-target', streamUrl, style?.url, { 
        layerSwitcherControl: true
    });

    // La couche TMS a ete ajoutee
    viewer.on('tmslayeradded', (event) => {
        let metadatas = event.metadatas;
        styleFileReader = new StyleFileReader(metadatas);

        /* Les styles SLD et QGIS ne concernent qu'un seule "couche". Si le flux contient plusieurs
        "couches", seules les styles mapbox (.json) sont acceptés */ 
        let numLayers = metadatas.vector_layers.length;
        let accept = (numLayers === 1) ? '.sld, .qml, .json' : '.json';
        $('#style-file').attr('accept', accept);

        accepted = accept.split(',').map(a => { return a.slice(1) });
    });

    $('#style-file').on('change', (e) => {
        if (! styleFileReader) {
            return;
        }

        let file = e.currentTarget.files[0];
        
        let extension = file.name.split('.').pop().toLowerCase();
        if (! accepted.includes(extension)) {
            flash.flashAdd(Translator.trans('pyramid.style.type_not_authorized'), 'warning');
            return;
        }

        let url;
        switch(extension) {
            case 'json':
                // Ouverture de la boite pour le nom
                url = Routing.generate('plage_style_add_ajax', { datastoreId: datastoreId, pyramidId: pyramid._id });
                openNameDialog(url, { file: file });
                break;
            case 'sld':
            case 'qml':
                styleFileReader.readFile(file).then(style => {
                    // Ouverture de la boite pour le nom et la couche associee
                    let url = Routing.generate('plage_style_add_ajax_mapbox', { datastoreId: datastoreId, pyramidId: pyramid._id });
                    openNameDialog(url, { style: style });
                }).catch(err => {
                    flash.flashAdd(err.message);
                });
                break;
        }
    });

    /**
     * STYLES LIST
     */
    let re = React.createElement(StylesList, {
        datastoreId: datastoreId,
        pyramidId: pyramid._id,
        styles: styles, 
        defaultStyle: style?.id,
        wait: wait,
        onChange: url => {
            viewer.setStyle(url);
        }
    });
    stylesList = ReactDOM.render(re, document.getElementById('styles-wrapper'));
});