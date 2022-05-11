require('geoportal-extensions-openlayers/src/OpenLayers/CSS/Controls/SearchEngine/GPsearchEngineOpenLayers.css');

import Map from 'ol/Map';
import View from 'ol/View';
import { MVT } from "ol/format";
import { fromLonLat, transformExtent } from 'ol/proj';
import WMTSCapabilities from 'ol/format/WMTSCapabilities';
import { optionsFromCapabilities } from 'ol/source/WMTS';
import TileLayer from 'ol/layer/Tile';
import VectorTileLayer from 'ol/layer/VectorTile';
import WMTS from 'ol/source/WMTS';
import VectorTileSource from 'ol/source/VectorTile';
import { defaults as defaultControls } from 'ol/control';
import { defaults as defaultInteractions } from 'ol/interaction';
import SearchEngine from 'geoportal-extensions-openlayers/src/OpenLayers/Controls/SearchEngine';
import LayerSwitcher from 'geoportal-extensions-openlayers/src/OpenLayers/Controls/LayerSwitcher';
import Attribution from 'geoportal-extensions-openlayers/src/OpenLayers/Controls/GeoportalAttribution';
import GetFeatureInfo from "geoportal-extensions-openlayers/src/OpenLayers/Controls/GetFeatureInfo";
import ScaleLine from 'ol/control/ScaleLine';
import { applyStyle as mapboxApplyStyle } from "ol-mapbox-style";

import MapboxStyleParser from 'geostyler-mapbox-parser';
import OlStyleParser from 'geostyler-openlayers-parser';

const flash = require("./flash-messages");

/**
 * Extension de Map d'openlayers
 */
export default class MapViewer extends Map {
    constructor(target, streamUrl, styleUrl, options) {
        let defaultOptions = {
            backgroundLayer: {
                key: 'cartes',
                layerName: 'GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2'
            },
            searchControl: true,
            layerSwitcherControl: false,
            scaleControl: true,
            center: [2.35, 48.85],  // Paris
            zoom: 15
        }
        options = Object.assign(defaultOptions, options);

        // Controles par defaut
        let controls = defaultControls({
            attribution: false,
            rotate: false,
            zoom: true
        });

        controls.push(new Attribution());

        // Controles supplementaires
        if (options.scaleControl) {
            controls.push(new ScaleLine());
        }

        if (options.searchControl) {
            controls.push(new SearchEngine({
                apiKey: 'essentiels',
                collapsed: false,
                displayAdvancedSearch: false,
                displayMarker: false,
                zoomTo: 'current zoom'
            }));
        }

        if (options.layerSwitcherControl) {
            controls.push(new LayerSwitcher({ name: 'LayerSwitcher' }));
        }

        // Appel du constructeur parent
        super({
            target: target,
            controls: controls,
            interactions: defaultInteractions(),
            view: new View({
                center: fromLonLat(options.center),
                zoom: options.zoom
            })
        });

        this._streamUrl = streamUrl;
        this._styleUrl  = styleUrl;
        this._options   = options;
        
        this._layerSwitcher = controls.item(controls.getLength() - 1);
        
        /* metadatas du flux et couche */
        this._metadatas = null;
        this._tmsLayer  = null;
        
        // Ajout de la couche de fond
        this.addBackgroundLayer();

        // Ajout du flux en superposition si on en souhaite un
        if (this._streamUrl !== null) {
            this.on('backgroundlayeradded', () => {
                this.addTileLayer(this._streamUrl);
            })
        }

        // Definition du style
        this.on('tmslayeradded', () => {
            if (this._styleUrl) this.setStyle(this._styleUrl);
        })
    }

    /**
     * Ajout de la couche de fond
     */
    addBackgroundLayer() {
        let key = this._options.backgroundLayer.key;
        let layerName = this._options.backgroundLayer.layerName;

        let url = `https://wxs.ign.fr/${key}/geoportail/wmts?SERVICE=WMTS&VERSION=1.0.0&REQUEST=GetCapabilities`;
        fetch(url).then((response) => {
            if (response.status != 200) throw response.statusText;
            return response.text();
        }).then((response) => {
            let format = new WMTSCapabilities();
            let capabilities = format.read(response);

            const layers = capabilities['Contents']['Layer'];
            const l = layers.find(layer => {
                return layer['Identifier'] == layerName;
            });
            if (! l) {
                throw Error();
            }
            let wmtsOptions = optionsFromCapabilities(capabilities, {
                layer: layerName
            });

            let layer = new TileLayer({
                opacity: 1,
                source: new WMTS(wmtsOptions)
            })
            this.addLayer(layer, { title: l.Title, description: l.Abstract });
            this.dispatchEvent({ type: 'backgroundlayeradded' });
        }).catch(error => {
            flash.flashAdd(`La couche ${layerName} n'a pas été trouvée.`, "warning");
        });
    }

    /**
     * Ajout du flux de tuiles vectorielles
     */
    addTileLayer() {
        if (this._tmsLayer) { return; }

        this.getMetadatas(this._streamUrl)
            .then(metadatas => {
                this._metadatas = metadatas;
                
                /* Modification de la view */
                let zoom = this.getView().getZoom();
                if (zoom < metadatas.minzoom || zoom > metadatas.maxzoom) {
                    zoom = metadatas.maxzoom;
                }
                let view = new View({
                    center: metadatas.center,
                    extent: metadatas.bounds,
                    zoom: zoom,
                    minZoom: metadatas.minzoom,
                    maxZoom: metadatas.maxzoom
                });
                this.setView(view);

                /* Ajout de la couche */
                let attributions = this.getAttribution();
                this._tmsLayer = new VectorTileLayer({
                    attributions: attributions,
                    minZoom: metadatas.minzoom,
                    maxZoom: metadatas.maxzoom,
                    declutter: true,
                    source: new VectorTileSource({
                        attributions: attributions,
                        url: metadatas.url,
                        format: new MVT(),
                        minZoom: metadatas.minzoom,
                        maxZoom: metadatas.maxzoom,
                        tileSize: metadatas.tileSize
                    }),
                });
                this.addLayer(this._tmsLayer, {
                    title: metadatas.title,
                    description: metadatas.description,
                    keywords: metadatas.keywords,
                    attribution: metadatas.attribution
                });
                this.dispatchEvent({ type: 'tmslayeradded', metadatas: metadatas });
                
                let control = new GetFeatureInfo({
                    options: {
                        auto: true,
                        active: true,
                        hidden: true
                    },
                    layers: [{ obj: this._tmsLayer }]
                });
                this.addControl(control);
            }).catch(error => {
                console.error(error);
                flash.flashAdd(`L'ajout du flux ${this._streamUrl} s'est mal passé.`, "danger");
            });
    }

    get tmsLayer() {
        return this._tmsLayer;
    }

    /**
     * Ajout d'une couche
     * @param {ol.layer} layer 
     * @param {Object} opt_options options de la couche
     *      - title {string}: Titre
     *      - description {string}: Description
     *      - keywords{Array}: Mots cles
     *      - attribution{Object|null} : Attribution
     * 
     * @param {string} description 
     */
    addLayer(layer, opt_options) {
        let options = opt_options || {};
        options = $.extend({ title: null, description: null, keywords: [], attribution: {} }, options)
        super.addLayer(layer);

        if (this._layerSwitcher) {
            let description = `<p>${options.description}</p>`;
            if (options.keywords.length) {
                description += '<p><span class="font-weight-bold">Mots clés : </span>' + options.keywords.join(', ') + '</p>';
            }
            this._layerSwitcher.addLayer(layer, {
                title: options.title,
                description: description
            });
        }
    }

    /**
     * Modification du style de la couche TMS
     * @param {string} styleUrl 
     */
    setStyle(styleUrl) {
        if (! styleUrl) {
            this._tmsLayer.setStyle(undefined);
            return;
        }

        fetch(styleUrl)
            .then(response => {
                return response.json();
            }).then(mapboxStyle => {
                let mbp = new MapboxStyleParser();
                mbp.readStyle(mapboxStyle).then(gStyle => {
                    let olp = new OlStyleParser();
                    olp.writeStyle(gStyle.output).then(olStyle =>{
                        this._tmsLayer.setStyle(olStyle.output);   
                    }).catch(() => {
                        flash.flashAdd("La création du style Openlayers a échoué ", "warning");
                    });   
                }).catch(() => {
                    flash.flashAdd(Translator.trans('pyramid.style.parse_failed'), "warning");   
                });
            }).catch(() => {
                flash.flashAdd(`Le style n'a pas été trouvé. Le style par défaut a été appliqué`, "warning");
            });
    }

    /**
     * Récupère les métadonnées du serveur de tuiles vectorielles
     * Interroge successivement l'URL du flux passé en paramètre
     * puis suivie de /metadata.json
     * @param {string} url 
     * @returns 
     */
    async getMetadatas(url) {
        let response;
        try {
            response = await fetch(url);
        } catch(error) {
            // Si bloqué par CORS policy
            flash.flashAdd(`Impossible d'accéder au flux ${url}. Existe-t-il ?`);
            throw Error(error);
        }

        if (!response.ok) {
            // Si réponse autre que 200
            flash.flashAdd(`Impossible d'accéder au flux ${url}. Existe-t-il ?`);
            throw Error(`${response.status}: ${response.statusText}`);
        }

        const capabilities = await response.text();
        let infos;
        try {
            infos = this.getInfosFromCapabilities(capabilities);
        } catch(error) {
            flash.flashAdd(`Impossible de lire ${url}. Ce n'est pas un fichier XML valide.`);
            throw Error(error);
        }

        try {
            response = await fetch(`${url}/metadata.json`);
        } catch(error) {
            // Si bloqué par CORS policy
            flash.flashAdd(`Impossible d'accéder aux métadonnées ${url}/metadata.json`);
            throw Error(error);
        }

        if (!response.ok) {
            // Si réponse autre que 200
            flash.flashAdd(`Impossible d'accéder aux métadonnées ${url}/metadata.json`);
            throw Error(`${response.status}: ${response.statusText}`);
        }

        let metadatas;

        try {
            metadatas = await response.json();
        } catch(error) {
            flash.flashAdd(`Impossible de lire ${url}/metadata.json. Ce n'est pas un fichier de métadonnées valide.`);
            throw Error(error);
        }

        $.extend(metadatas, infos, { url: `${url}/{z}/{x}/{y}.pbf`});

        // Transformation de l'extent et du centre
        metadatas.bounds = transformExtent(metadatas.bounds, 'EPSG:4326', 'EPSG:3857');
        metadatas.center = fromLonLat(metadatas.center);

        return metadatas;
    }

    /**
     * Recuperation des infos contenues dans les capabilities
     * @param {string} capabilities 
     * @returns 
     */
    getInfosFromCapabilities(capabilities) {
        const parser = new DOMParser();
        let document = parser.parseFromString(capabilities, 'application/xml');

        let result = {
            title: null,
            keywords: [],
            attribution: null,
            tileSize: null
        };

        // Titre
        let tileNode = document.firstElementChild;
        result.title = tileNode.getElementsByTagName("Title")[0].textContent;

        // Mots clés et attribution
        let keywordNodes = tileNode.getElementsByTagName('KeywordList');
        for (let k=0; k < keywordNodes.length; ++k) {
            result.keywords.push(keywordNodes[k].textContent);
        }

        let attributionNodes = tileNode.getElementsByTagName('Attribution');
        if (attributionNodes.length) {
            let attributionNode = attributionNodes[0];

            result.attribution = {
                title: attributionNode.getElementsByTagName("Title")[0].textContent
            };
            let urlNode = attributionNode.getElementsByTagName("Url");   // L'URL semble absente des réponses de ROK4
            if (urlNode.length) {
                result.attribution['url'] = urlNode[0].textContent;
            }
        }

        // Taille des tuiles
        let tileFormat = tileNode.getElementsByTagName("TileFormat")[0];
        let tileSize = tileFormat.getAttribute('width');
        result.tileSize = parseInt(tileSize, 10);

        return result;
    }
    
    synchronizeViewWith(otherMapViewer) {
        this.setView(otherMapViewer.getView());
    }

    getAttribution() {
        if (! this._metadatas) return null;
        if (! this._metadatas.attribution) return null;
        
        if ('url' in this._metadatas.attribution) {
            return `<a href=${metadatas.attribution.url}>${this._metadatas.attribution.title}</a>`;
        } else return this._metadatas.attribution.title;
    }

}
