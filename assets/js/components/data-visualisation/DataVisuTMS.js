import { applyBackground, applyStyle } from "ol-mapbox-style";
import { MVT } from "ol/format";
import VectorTileLayer from "ol/layer/VectorTile";
import VectorTileSource from "ol/source/VectorTile";
import DataVisu from "./DataVisu";

/**
 * @class DataVisuTMS
 * @constructor
 * @param {Object} options
 * @returns {DataVisuTMS}
 */
const DataVisuTMS = function (options) {
    DataVisu.call(this, options);
};
DataVisuTMS.prototype = Object.create(DataVisu.prototype);

/**
 * @function
 * @name fetchMapLayers
 * @memberof DataVisuTMS
 * @returns {Array}
 */
DataVisuTMS.prototype.fetchMapLayers = function () {
    return [
        new VectorTileLayer({
            source: new VectorTileSource({
                url: this.options.dataSourceUrl,
                format: new MVT(),
            }),
        }),
    ];
};

/**
 * @function
 * @name getLayersForLayerSwitcher
 * @memberof DataVisuTMS
 * @returns {Array}
 */
DataVisuTMS.prototype.getLayersForLayerSwitcher = function () {
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
 * @name getLayersForGetFeatureInfo
 * @memberof DataVisuTMS
 * @returns {Array}
 */
DataVisuTMS.prototype.getLayersForGetFeatureInfo = function () {
    return [
        {
            obj: this.layers[1],
        },
    ];
};

/**
 * @function
 * @name applyStyle
 * @memberof DataVisuTMS
 * @returns {void}
 */
DataVisuTMS.prototype.applyStyle = function (styleUrl = this.options.styleUrl) {
    fetch(styleUrl).then((response) => {
        response.json().then((mapboxStyle) => {
            applyBackground(this.map, mapboxStyle);
            applyStyle(
                this.layers[1],
                mapboxStyle,
                Object.keys(mapboxStyle.sources)[0]
            );
        });
    });
};

export default DataVisuTMS;
