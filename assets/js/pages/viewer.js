import { fromLonLat, toLonLat } from 'ol/proj';
import MapViewer from '../components/MapViewer';
import { parse as parseOSMHashParam, update as updateOSMHashParam } from '../components/osm-hashparams';


/**
 * 
 * @returns 
 */

let viewer  = null;

$(function() {
    let osmhash = parseOSMHashParam(window.location.hash);

    const queryString = window.location.search;
    const urlParams = new URLSearchParams(queryString);
    
    let streamUrl = urlParams.get('tiles_url');
    let styleUrl  = urlParams.get('style_url');

    viewer = new MapViewer('map', streamUrl, styleUrl, { 
        layerSwitcherControl: true
    });
    viewer.on('tmslayeradded', (event) => {
        // Mise a jour de l'entete et du title de la page
        $('#tiles-layer-title').text(event.metadatas.title);
        document.title = document.title.replace('__LAYER__', event.metadatas.title);

        // Centrage et zoom
        if (osmhash) {
            viewer.getView().setCenter(fromLonLat([osmhash.lon, osmhash.lat]));
            viewer.getView().setZoom(osmhash.z);
        }
    });

    // Centrage et zoom derrière un hash avec actualisation en direct pour repartage facile
    viewer.on('moveend', (evt) => {
        const map = evt.map;
        let center = toLonLat(map.getView().getCenter());
        let zoom = map.getView().getZoom();
        updateOSMHashParam(center[0], center[1], zoom);
    });
});