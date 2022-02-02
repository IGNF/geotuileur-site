import MapViewer from "../components/MapViewer";

$(function () {
    let viewer = null;

    let pyramid = $('#map-target').data('pyramid');
    let streamUrl = pyramid.tags.tms_url;
    viewer = new MapViewer('map-target', streamUrl, null, {
        layerSwitcherControl: true
    });
    viewer.on('tmslayeradded', (event) => {

    });
});
