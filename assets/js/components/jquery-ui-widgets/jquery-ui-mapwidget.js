require('jquery-ui/ui/widget.js');
require('geoportal-extensions-openlayers/src/OpenLayers/CSS/Controls/SearchEngine/GPsearchEngineOpenLayers.css');

import { guid } from '../../utils.js';

import Collection from 'ol/Collection';
import Map from 'ol/Map';
import View from 'ol/View';
import { fromLonLat } from 'ol/proj';
import WMTSCapabilities from 'ol/format/WMTSCapabilities';
import { optionsFromCapabilities } from 'ol/source/WMTS';
import TileLayer from 'ol/layer/Tile';
import WMTS from 'ol/source/WMTS';
import { defaults as defaultControls }  from 'ol/control/util';
import { defaults as defaultInteractions } from 'ol/interaction';
import SearchEngine from "geoportal-extensions-openlayers/src/OpenLayers/Controls/SearchEngine";
import LayerSwitcher from "geoportal-extensions-openlayers/src/OpenLayers/Controls/LayerSwitcher";
import ScaleLine from 'ol/control/ScaleLine';


/**
 * 
 */
 $.widget( "custom.mapwidget", {
    options: { 
        search: true,
        layerSwitcher: false,
        scale: true,
        center: [2.35, 48.85],  // Paris
        zoom: 15,
        width: null,
        height: null
    },
    
    /**
     * Creation du DOM et de la carte
     * @returns 
     */
    _create: function() {
        let self = this;

        if (this.element[0].nodeName !== "DIV") {
            console.warn('element must be div type');
            return;
        }

        this._setOption('maxZoom', this.options.zoom);
        
        this._layerSwitcher = null;

        let id = guid();
        let $div = $('<div>', { class: 'mx-auto', id: `map-${id}` }).appendTo(this.element);
        if (this.options.width) {
            $div.css('width', this.options.width);    
        }
        if (this.options.height) {
            $div.css('height', this.options.height);    
        }

        // Controles par defaut
        let controls = defaultControls({
            attribution: false,
            rotate: false,
            zoom: true
        });

        // Controles supplementaires
        if (this.options.scale) {
            controls.push(new ScaleLine());    
        }

        if (this.options.layerSwitcher) {
            this._layerSwitcher = new LayerSwitcher();
            controls.push(this._layerSwitcher);
        }

        if (this.options.search) {
            controls.push(new SearchEngine({
                apiKey: 'essentiels',
                collapsed: false,
                displayAdvancedSearch: false,
                displayMarker: false,
                zoomTo: 'current zoom'
            }));
        }

        this._map = new Map({
            target: `map-${id}`,
            controls: controls,
            interactions: defaultInteractions(),
            view: new View({
                center: fromLonLat(this.options.center),
                zoom: this.options.zoom
            })
        });
      
        // Ajout de la couche PLANIGNV2
        fetch("https://wxs.ign.fr/cartes/geoportail/wmts?SERVICE=WMTS&VERSION=1.0.0&REQUEST=GetCapabilities").then((response) => {
            if (response.status != 200) throw response.statusText;
            return response.text();
        }).then((response) => {
            let format = new WMTSCapabilities();
            let capabilities = format.read(response);
            
            const layers = capabilities['Contents']['Layer'];
            const l = layers.find(layer => {
                return layer['Identifier'] == 'GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2';    
            });

            let wmtsOptions = optionsFromCapabilities(capabilities, {
                layer: 'GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2'
            });
            
           let layer = new TileLayer({
                opacity: 1,
                source: new WMTS(wmtsOptions)
            })
            self._map.addLayer(layer);
            if (self._layerSwitcher) {
                self._layerSwitcher.addLayer(layer, { title: l.Title, description: l.Abstract });
            }
            self._trigger('initialized')
        }).catch(error => { console.error(error)});
    },

    /**
     * Suppression du widget
     */
    _destroy: function () {
        this.element.empty();
    },

    getMap: function() {
        return this._map;
    },

    /**
     * On defini un nouveau zoom pour la vue de la carte
     * @param {integer} zoom 
     */
    setZoom: function(zoom) {
        this.options.zoom = zoom;
        this._map.getView().setZoom(this.options.zoom);
    },

    /**
     * Ajout d'une couche
     * @param {ol.layer} layer 
     * @param {Object} opt_options 
     */
    addLayer: function (layer, opt_options) {
        let options = opt_options || { title: 'Sans titre' };
        this._map.addLayer(layer);
        
        if (this.options.layerSwitcher) {
            this._layerSwitcher.addLayer(layer, options);
        }
    }
});
