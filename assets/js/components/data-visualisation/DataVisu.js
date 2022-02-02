import GetFeatureInfo from "geoportal-extensions-openlayers/src/OpenLayers/Controls/GetFeatureInfo";
import LayerSwitcher from "geoportal-extensions-openlayers/src/OpenLayers/Controls/LayerSwitcher";
import { Map, View } from "ol";
import { getWidth } from "ol/extent";
import { defaults as defaultInteractions } from "ol/interaction";
import Layer from "ol/layer/Layer";
import TileLayer from "ol/layer/Tile";
import { get as getProjection } from "ol/proj";
import WMTS from "ol/source/WMTS";
import WMTSTileGrid from "ol/tilegrid/WMTS";

/**
 * @class DataVisu
 * @constructor
 * @param {Object} options
 * @returns {DataVisu}
 */
const DataVisu = function (options = {}) {
    this.map = null;
    this.options = options;
    console.log(this.options);

    // loading background and other data layers
    this.layers = this.fetchMapLayers();
    this.layers.unshift(this.loadDefaultBackgroundLayer());

    this.layerSwitcher = null;
    this.getFeatureInfo = null;

    // loading map and default controls
    this.loadMap();
    this.loadDefaultControls();

    // loading style if exists
    if (typeof this.options.styleUrl !== undefined) {
        this.applyStyle();
    }
};

/**
 * @function
 * @name fetchMapLayers
 * @memberof DataVisu
 * @returns {Array}
 */
DataVisu.prototype.fetchMapLayers = function () {};

/**
 * @function
 * @param {string} layerName
 * @memberof DataVisu
 * @returns {BaseLayer}
 */
DataVisu.prototype.getLayer = function (layerName) {
    for (let i = 0; i < this.layers.length; i++) {
        const layer = this.layers[i];
        if (layer.get("layerName") === layerName) return layer;
    }
    return null;
};

/**
 * @function
 * @name loadMap
 * @memberof DataVisu
 * @returns {void}
 */
DataVisu.prototype.loadMap = function () {
    this.map = new Map({
        target: this.options.mapTarget,
        layers: this.layers,
        view: new View({
            // zoom: 10,
            zoom: this.options.zoom,
            // center: [-6793723.073986, 1646759.337376],
            center: this.options.center,
            projection: "EPSG:3857",
        }),
        interactions: defaultInteractions(),
    });
};

/**
 * @function
 * @name loadDefaultBackgroundLayer
 * @memberof DataVisu
 * @returns {Layer}
 */
DataVisu.prototype.loadDefaultBackgroundLayer = function () {
    let resolutions = [];
    let matrixIds = [];
    let proj3857 = getProjection("EPSG:3857");
    let maxResolution = getWidth(proj3857.getExtent()) / 256;

    for (let i = 0; i < 18; i++) {
        matrixIds[i] = i.toString();
        resolutions[i] = maxResolution / Math.pow(2, i);
    }

    let tileGrid = new WMTSTileGrid({
        origin: [-20037508, 20037508],
        resolutions: resolutions,
        matrixIds: matrixIds,
    });

    let wmtsSource = new WMTS({
        url: "https://wxs.ign.fr/cartes/geoportail/wmts",
        layer: "GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2",
        matrixSet: "PM",
        format: "image/png",
        projection: "EPSG:3857",
        tileGrid: tileGrid,
        style: "normal",
        attributions:
            '<a href="http://www.ign.fr" target="_blank">' +
            '<img src="https://wxs.ign.fr/static/logos/IGN/IGN.gif" title="Institut national de l\'information géographique et forestière" alt="IGN"></a>',
    });

    return new TileLayer({
        source: wmtsSource,
    });
};

/**
 * @function
 * @name loadDefaultControls
 * @memberof DataVisu
 * @returns {void}
 */
DataVisu.prototype.loadDefaultControls = function () {
    let defaultBackgroundLayer = {
        layer: this.layers[0],
        config: {
            title: "Plan IGN v2",
        },
    };

    let layers = this.getLayersForLayerSwitcher();
    layers.unshift(defaultBackgroundLayer);

    this.layerSwitcher = new LayerSwitcher({
        layers: layers,
    });
    this.map.addControl(this.layerSwitcher);

    if (this.options.getFeatureInfo) {
        this.getFeatureInfo = new GetFeatureInfo({
            layers: this.getLayersForGetFeatureInfo(),
        });
        this.map.addControl(this.getFeatureInfo);
    }
};

/**
 * @function
 * @name getLayersForLayerSwitcher
 * @memberof DataVisu
 * @returns {Array}
 */
DataVisu.prototype.getLayersForLayerSwitcher = function () {
    return [];
};

/**
 * @function
 * @name getLayersForGetFeatureInfo
 * @memberof DataVisu
 * @returns {Array}
 */
DataVisu.prototype.getLayersForGetFeatureInfo = function () {
    return [];
};

/**
 * @function
 * @name applyStyle
 * @memberof DataVisu
 * @returns {void}
 */
DataVisu.prototype.applyStyle = function (styleUrl) {};

export default DataVisu;
