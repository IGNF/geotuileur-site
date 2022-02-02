import * as olms from "ol-mapbox-style";
import GeoJSON from "ol/format/GeoJSON";
import BaseLayer from "ol/layer/Base";
import VectorLayer from "ol/layer/Vector";
import { bbox } from "ol/loadingstrategy";
import VectorSource from "ol/source/Vector";
import DataVisu from "./DataVisu";

/**
 * @class DataVisuWFS
 * @constructor
 * @param {Object} options
 * @returns {DataVisuWFS}
 */
const DataVisuWFS = function (options) {
    DataVisu.call(this, options);
};
DataVisuWFS.prototype = Object.create(DataVisu.prototype);

/**
 * @function
 * @name fetchMapLayers
 * @memberof DataVisuWFS
 * @returns {Array}
 */
DataVisuWFS.prototype.fetchMapLayers = function () {
    this.options.dataLayers = this.options.dataLayers.split(",");

    return this.options.dataLayers.map((layerName) => {
        return new VectorLayer({
            layerName: layerName,
            source: new VectorSource({
                format: new GeoJSON(),
                url: (extent) => {
                    let ext = extent.join(",");
                    return `${this.options.dataSourceUrl}?service=WFS&version=1.1.0&request=GetFeature&typename=${layerName}&outputFormat=application/json&srsname=EPSG:4326&bbox=${ext},EPSG:3857`;
                },
                strategy: bbox,
            }),
        });
    });
};

/**
 * @function
 * @name getLayersForLayerSwitcher
 * @memberof DataVisuWFS
 * @returns {Array}
 */
DataVisuWFS.prototype.getLayersForLayerSwitcher = function () {
    let layers = this.layers.map((layer) => {
        return {
            layer: layer,
            config: {
                title: layer.get("layerName"),
            },
        };
    });
    layers.shift();
    return layers;
};

/**
 * @function
 * @name getLayersForGetFeatureInfo
 * @memberof DataVisuWFS
 * @returns {Array}
 */
DataVisuWFS.prototype.getLayersForGetFeatureInfo = function () {
    let layers = this.layers.map((layer) => {
        return {
            obj: layer,
        };
    });
    layers.shift();
    return layers;
};

/**
 * @function
 * @name applyStyle
 * @memberof DataVisuWFS
 * @returns {void}
 */
DataVisuWFS.prototype.applyStyle = function (styleUrl = this.options.styleUrl) {
    fetch(styleUrl).then((response) => {
        response.json().then((mapboxStyle) => {
            olms.applyBackground(this.map, mapboxStyle);

            this.options.dataLayers.map((layerName) => {
                olms.applyStyle(
                    this.getLayer(layerName),
                    mapboxStyle,
                    layerName
                );
            });
        });
    });
};

export default DataVisuWFS;
