import MapViewer from "../components/MapViewer";

$(function () {
    let viewerInitial = null;
    let viewerUpdate = null;

    let pyramidInitial = $('#map-target-initial').data('pyramid');
    let streamUrlInitial = pyramidInitial.tags.tms_url;
    viewerInitial = new MapViewer('map-target-initial', streamUrlInitial, null, {
        layerSwitcherControl: true
    });
    viewerInitial.on('tmslayeradded', (event) => {

    });

    let pyramidUpdate = $('#map-target-update').data('pyramid');
    let streamUrlUpdate = pyramidUpdate.tags.tms_url;
    viewerUpdate = new MapViewer('map-target-update', streamUrlUpdate, null, {
        layerSwitcherControl: true
    });
    viewerUpdate.on('tmslayeradded', (event) => {
        viewerUpdate.synchronizeViewWith(viewerInitial);
    });
})
