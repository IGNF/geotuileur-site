import { getWidth } from "ol/extent";
import TileLayer from "ol/layer/Tile";
import { get as getProjection } from "ol/proj";
import WMTS from "ol/source/WMTS";
import WMTSTileGrid from "ol/tilegrid/WMTS";
import DataVisu from "./DataVisu";

/**
 * @class DataVisuWMTS
 * @constructor
 * @param {Object} options
 * @returns {DataVisuWMTS}
 */
const DataVisuWMTS = function (options) {
    DataVisu.call(this, options);
};
DataVisuWMTS.prototype = Object.create(DataVisu.prototype);

/**
 * @function
 * @name fetchMapLayers
 * @memberof DataVisuWMTS
 * @returns {Array}
 */
DataVisuWMTS.prototype.fetchMapLayers = function () {
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

    return [
        new TileLayer({
            source: new WMTS({
                url: this.options.dataSourceUrl,
                layer: this.options.dataLayers,
                matrixSet: "PM",
                format: "image/jpeg",
                projection: "EPSG:3857",
                tileGrid: tileGrid,
                style: "normal",
            }),
        }),
    ];
};

/**
 * @function
 * @name getLayersForLayerSwitcher
 * @memberof DataVisuWMTS
 * @returns {Array}
 */
DataVisuWMTS.prototype.getLayersForLayerSwitcher = function () {
    return [
        {
            layer: this.layers[1],
            config: {
                title: this.options.storedDataName || "Ma donnée",
            },
        },
    ];
};

/**
 * @function
 * @name applyStyle
 * @memberof DataVisuWMTS
 * @returns {void}
 */
DataVisuWMTS.prototype.applyStyle = function () {};

export default DataVisuWMTS;
