import SldParser from 'geostyler-sld-parser';
import QGISStyleParser from 'geostyler-qgis-parser';
import MapboxStyleParser from 'geostyler-mapbox-parser';

const parseError = Translator.trans('pyramid.style.parse_failed');

export class StyleFileReader {
    constructor(metadatas) {
        this._sourceName   = metadatas.name;

        this._sources = {};
        this._sources[this._sourceName] = {
            type: "vector",
            tiles: metadatas.tiles,
            minzoom: metadatas.minzoom,
            maxzoom: metadatas.maxzoom
        }

        // Les differents parser
        this._sldParser     = new SldParser();
        this._qgisParser    = new QGISStyleParser();
        this._mapboxParser  = new MapboxStyleParser();
    }

    /**
     * 
     * @param string content le xml du style SLD
     * @returns 
     */
    version(content) {
        const domParser = new DOMParser();
        const doc = domParser.parseFromString(content, "application/xml");

        const errorNode = doc.querySelector('parsererror');
        if (errorNode) {
            throw new Error(parseError);    
        }

        let sld = doc.firstChild;
        return sld.hasAttribute('version') ? sld.getAttribute('version') : '1.0.0';
    }

    /**
     * Lecture du fichier de style (SLD ou QML)
     * @param File file 
     * @param string layerId 
     * @returns 
     */
    async readFile(file, layerId) {
        return new Promise((resolve, reject) => {
            let extension = file.name.split('.').pop().toLowerCase();
            if (! ['sld', 'qml'].includes(extension)) {
                reject(new Error(Translator.trans('pyramid.style.type_not_authorized')));
            }

            // Le parser qui va bien
            let parser = ('sld' === extension) ? this._sldParser : this._qgisParser;

            let fileReader = new FileReader();
            fileReader.onload = async event => {
                try {
                    let content = fileReader.result;
                    
                    // Si SLD, recherche de la version
                    if ('sld' === extension) {
                        parser.sldVersion = this.version(content);
                    }
                    
                    let style = await parser.readStyle(content).catch(err => { throw new Error(parseError); });
                    if ('errors' in style) {
                        reject(new Error(style.errors[0]));
                    }

                    let mbStyle = await this._mapboxParser.writeStyle(style.output);
                    let jsStyle = JSON.parse(mbStyle.output);
                    
                    // Ajout de la source
                    let layers = jsStyle.layers.map(layer => {
                        return $.extend(layer, {
                            source: this._sourceName,
                            layout: { visibility: "visible" },
                            "source-layer": layerId  
                        });
                    });
                    jsStyle['sources'] = this._sources;
                    jsStyle.layers = layers;
                    resolve(jsStyle);
                } catch(err) {
                    reject(err);
                }
            };
            fileReader.readAsText(file);    
        });
    }

    /**
     * 
     * @param Object files
     */
     async readFiles(name, files) {
        return new Promise((resolve, reject) => {
            let promises = [];
            for (const [layerId, file] of Object.entries(files)) {
                let extension = file.name.split('.').pop();
                if (['json', 'sld', 'qml'].includes(extension)) {
                    promises.push(this.readFile(file, layerId));
                }
            };
            Promise.all(promises)
                .then(mbStyles => {
                    // Concatenation des styles pour n'en former qu'un
                    let jsStyle = mbStyles[0];
                    jsStyle['name']     = name;
                    jsStyle['sources']  = this._sources;
                    for (let s=1; s<mbStyles.length; ++s) {
                        jsStyle.layers = jsStyle.layers.concat(mbStyles[s].layers);  
                    }

                    resolve(JSON.stringify(jsStyle));
                }).catch(err => {
                    reject(err);
                });
        });  
    }
}