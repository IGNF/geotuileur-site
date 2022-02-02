require('jquery-ui/ui/widget.js');
require('jquery-ui/ui/widgets/slider.js');
require('jquery-ui/themes/base/all.css');

import { guid } from '../../utils.js';
import Map from 'ol/Map';
import View from 'ol/View';
import { fromLonLat } from 'ol/proj';
import WMTSCapabilities from 'ol/format/WMTSCapabilities';
import { optionsFromCapabilities } from 'ol/source/WMTS';
import TileLayer from 'ol/layer/Tile';
import WMTS from 'ol/source/WMTS';
import { boundingExtent, containsCoordinate } from 'ol/extent';
import ScaleLine from 'ol/control/ScaleLine';


/**
 * @author Philippe Prevautel <philippe.prevautel@ign.fr>
 * 
 * Petit composant pour la selection des zoom mini et maxi d'un feature type
 * ou d'un geoservice
 */
$.widget("custom.zoomrange", {
    options: {
        defer: false,
        classes: {
            "ui-slider-handle": "ui-corner-all ui-custom-handle"
        },
        min: 0,
        max: 20,
        topLevel: 0,
        bottomLevel: 20,
        center: [2.35, 48.85],  // Paris
        distinct: false
    },

    /**
     * Verification des options
     */
    _checkOptions: function () {
        let maxExtent = boundingExtent([[-180,-90],[180,90]]);

        this.options.values = [
            parseInt(this.options.topLevel) || 0,
            parseInt(this.options.bottomLevel) || 20 
        ];

        if (this.options.values[0] < this.options.min) {
            this.options.values[0] = this.options.min;   
        }
        if (this.options.values[1] > this.options.max) {
            this.options.values[1] = this.options.max;   
        }

        let center = this.options.center;
        if (! Array.isArray(center) || center.length !== 2) {
            this.options.center = [2.35, 48.85];  // Paris
        } else if (! containsCoordinate(maxExtent, center)) {
            this.options.center = [2.35, 48.85];  // Paris    
        }
    },

    /**
     * Creation du widget (DOM)
     * @returns 
     */
    _create: function () {
        if (this.element[0].nodeName !== "DIV") {
            console.warn('element must be div type');
            return;
        }

        let self = this;
        let id = guid();
        this._mapId1 = `map-${id}`;

        id = guid();
        this._mapId2 = `map-${id}`;

        // Verification des options
        this._checkOptions();

        // Construction du widget
        this._wrapper = $('<div>', {class: 'ui-zoom-range' }).appendTo(this.element);
        
        let $row = $('<div>', { class: 'ui-map-zoom-levels' }).appendTo(this._wrapper);
        $('<div>', { class: 'ui-top-zoom-level', id: this._mapId1 }).appendTo($row);
        $('<div>', { class: 'ui-bottom-zoom-level', id: this._mapId2 }).appendTo($row);
        
        if (! this.options.defer) {
            this._buildMaps();
        }

        id = guid();
        this._slider = $('<div>', { class: 'ui-zoom-slider', id:`slider-${id}` }).appendTo(this._wrapper);
        this._slider.slider({
            classes: this.options.classes,
            range: true,
            min: this.options.min,
            max: this.options.max,
            values: this.options.values,
            slide: function (event, ui) {
                if (self.options.distinct && (ui.values[0] === ui.values[1]))
                    return false;

                if (ui.handleIndex === 0) {
                    self._map1.getView().setZoom(ui.value);
                } else {
                    self._map2.getView().setZoom(ui.value);
                }
            },
            change: function(event, ui) {
                self._trigger('changed', null, {values: ui.values});
            }
        });

        this._showMarks();
    },

    /**
     * Affichage des marqueurs
     */
    _showMarks: function() {
        let vals = this.options.max - this.options.min;
        for (let i = 0; i <= vals; i++) {
            let mark = this.options.min + i;
            let el = $('<label>', { class: 'ui-zoom-label', text: mark }).css('left', i / vals * 100 + '%');
            this._slider.append(el);
        }
    },

    /**
     * Re defini les marqueurs
     */
    _resetMarks: function() {
        this._slider.find('label.ui-zoom-label').remove();
        this._showMarks();  
    },

    /**
     * Construction des deux cartes
     * @returns 
     */
    _buildMaps: function() {
        let self = this;

        let values = this.options.values;
        if (this._map1 && this._map2) {
            return;
        }

        this._map1 = new Map({
            target: this._mapId1,
            controls: [new ScaleLine()],
            interactions: [],
            view: new View({
                center: fromLonLat(this.options.center),
                zoom: values[0]
            })
        });

        this._map2 = new Map({
            target: this._mapId2,
            controls: [new ScaleLine()],
            interactions: [],
            view: new View({
                center: fromLonLat(this.options.center),
                zoom: values[1]
            })
        });

        fetch("https://wxs.ign.fr/cartes/geoportail/wmts?SERVICE=WMTS&VERSION=1.0.0&REQUEST=GetCapabilities").then((response) => {
            if (! response.ok) {
                throw response.statusText;
            }
            return response.text();
        }).then((response) => {
            let format = new WMTSCapabilities();
            let capabilities = format.read(response);

            let wmtsOptions = optionsFromCapabilities(capabilities, {
                layer: 'GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2',
                matrixSet: 'EPSG:3857'
            });

            let layer = new TileLayer({
                opacity: 1,
                source: new WMTS(wmtsOptions)
            })
            self._map1.addLayer(layer);
            self._map2.addLayer(layer);
        }).catch(error => { });
    },

    _destroy: function () {
        this._wrapper.remove();
    },

    setDisabled: function (b) {
        this._wrapper.prop('disabled', b);
    },

    getTopLevel: function () {
        return this._slider.slider("values", 0);
    },

    getBottomLevel: function () {
        return this._slider.slider("values", 1);
    },

    /**
     * Mise a jour des valeurs
     * @param {array} values 
     * @returns 
     */
    setValues: function(values) {
        if (! Array.isArray(values)) return;
         
        // les anciennes valeurs
        let previousValues = this._slider.slider('values');

        // min, max
        let min = values[0], max = values[1];
        $.extend(this.options, { min: min, max: max });

        // values
        values[0] = (previousValues[0] < min) ? min : previousValues[0];
        values[1] = (previousValues[1] > max) ? max : previousValues[1];
        $.extend(this.options, { values: values });

        // Modification eventuelle des zooms
        if (this._map1) {   // Si l'onglet n'a pas ete ouvert (card bootstrap), _map1 n'a pas ete defini
            this._map1.getView().setZoom(values[0]);    
        }
        if (this._map2) {   
            this._map2.getView().setZoom(values[1]);
        }

        // Mise a jour du slider
        this._slider.slider('option', 'min', min);
        this._slider.slider('option', 'max', max);
        this._slider.slider('values', values);

        // Mise a jour des marqueurs
        this._resetMarks();
    },

    /**
     * Affichage des cartes
     */
    showMaps: function() {
        if (this.options.defer) {
            this._buildMaps();
        }  
    }
});