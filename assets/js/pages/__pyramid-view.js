import LayerSwitcher from "geoportal-extensions-openlayers/src/OpenLayers/Controls/LayerSwitcher";
import { View } from "ol";
import { getWidth as getExtentWidth } from "ol/extent";
import { MVT } from "ol/format";
import { defaults as defaultInteractions } from "ol/interaction";
import TileLayer from "ol/layer/Tile";
import VectorTileLayer from "ol/layer/VectorTile";
import Map from "ol/Map";
import { get as getProjection } from "ol/proj";
import VectorTileSource from "ol/source/VectorTile";
import WMTS from "ol/source/WMTS";
import WMTSTileGrid from "ol/tilegrid/WMTS";

let mapDiv = null
let map = null;
let backgroundLayer = null;
let pyramidLayer = null

let layerSwitcher = null
let getFeatureInfo = null

$(function () {
    mapDiv = $('#map-target')

    backgroundLayer = loadBackgroundLayer()
    pyramidLayer = loadPyramidLayer()

    map = new Map({
        target: 'map-target',
        layers: [backgroundLayer, pyramidLayer],
        view: new View({
            zoom: 10,
            center: [-6793723.073986, 1646759.337376],
            projection: "EPSG:3857",
            interactions: defaultInteractions(),
        })
    })

    loadDefaultControls()
})

const loadBackgroundLayer = function () {
    let resolutions = [];
    let matrixIds = [];
    let proj3857 = getProjection("EPSG:3857");
    let maxResolution = getExtentWidth(proj3857.getExtent()) / 256;

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

const loadPyramidLayer = function () {
    return new VectorTileLayer({
        source: new VectorTileSource({
            url: mapDiv.data('tms-url'),
            format: new MVT(),
            // extent: 
        }),
    });
}

const loadDefaultControls = function () {

    let layers = [
        {
            layer: backgroundLayer,
            config: {
                title: "Plan IGN v2",
            },
        },
        // {
        //     TODO pyramid layer here
        // }
    ]

    layerSwitcher = new LayerSwitcher({
        layers: layers,
    });
    map.addControl(layerSwitcher);


    // getFeatureInfo = new GetFeatureInfo({
    //     layers: [
    //         {
    //             obj: pyramidLayer,
    //         },
    //     ],
    // });
    // map.addControl(getFeatureInfo);

};
