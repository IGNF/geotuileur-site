import MapViewer from "../components/MapViewer";
import VectorLayer from 'ol/layer/Vector';
import VectorSource from "ol/source/Vector";
import GeoJSON from 'ol/format/GeoJSON';

$(function () {

    var viewer = new MapViewer('extent-map', null, null, {
        layerSwitcherControl: true,
        searchControl: false
    });

    var extent = $('#extent-map').data('extent');

    if (!extent) {
        return;
    }

    var features = new GeoJSON({
        dataProjection: 'EPSG:4326',
        featureProjection: 'EPSG:3857'
    }).readFeatures(extent);

    var vectorSource = new VectorSource({
        features: features
    });

    var vectorLayer = new VectorLayer({ source: vectorSource });
    viewer.on('backgroundlayeradded', () => {
        viewer.addLayer(vectorLayer, { title: 'Emprise des données', description: 'Emprise des données' });
        viewer.getView().fit(vectorSource.getExtent());
    })

})
