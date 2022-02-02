/**
 * Décode et met à jour un hash décrivant la position d'une carte
 * sous la forme : #map={z}/{lat}/{lon}
 */
export function parse() {     
    let hash = window.location.hash;
    if (! hash) return null; 

    if (hash.indexOf('#') === 0) {
        hash = hash.substring(1);
    }

    const match = hash.match(/map=(\d+)\/([-\d\.]+)\/([-\d\.]+)/);
    if (! match) return null;

    let result = { z: null, lon: null, lat: null };
    let z = parseInt(match[1], 10);
    if (z >= 0 && z <= 20) {
        result.z = z;    
    }
    let lat = parseFloat(match[2]);
    let lon = parseFloat(match[3]);
    if (lat >= -90. && lat <= 90.) {
        result.lat = lat;   
    }
    if (lon >= -180. && lon <= 180.) {
        result.lon = lon;   
    } 
    
    if (result.z && result.lon && result.lat)   return result;
    return null;
}

/**
 * Mise a jour des parametres hash de l'url
 * @param {float} lon 
 * @param {float} lat 
 * @param {integer} z 
 */
export function update(lon, lat, z) {
    function round(num) {
        let r = (Math.round(num * 10000) / 10000).toFixed(4);
        return parseFloat(r);
    }
    lon = round(lon);
    lat = round(lat);
    window.location.hash = 'map=' + [z, lat, lon].join('/');
}
