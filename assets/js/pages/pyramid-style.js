const flash = require("../components/flash-messages");

import React from 'react';
import ReactDOM from 'react-dom';
import StylesList from "../components/react/StylesList";
import MapViewer from "../components/MapViewer";
import { Wait } from "../utils";


let viewer  = null;
let stylesList = null;
let wait = new Wait({ id: 'styles' });


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

    $('#style-file').on('change', (e) => {
        let file = e.currentTarget.files[0];
        if (file.type !== 'application/json') {
            flash.flashAdd(Translator.trans('pyramid.style.type_not_authorized'), 'warning');
            return;
        }

        // Ouverture de la boite pour le nom
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
                        
                        let route = Routing.generate('plage_style_add_ajax', { datastoreId: datastoreId, pyramidId: pyramid._id });

                        let formData = new FormData();
                        formData.append("name", name);
                        formData.append("file", file);
                        $.ajax({
                            url: route,
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
                        })
                    }
                }
            }
        });  
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