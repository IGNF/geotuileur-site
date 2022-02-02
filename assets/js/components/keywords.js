import Tagify from '@yaireo/tagify/dist/tagify';
require ('@yaireo/tagify/dist/tagify.css')

/**
 * 
 */
export default class KeywordsManager {
    constructor() {
        this._keywordsElement = $('#keywords')[0];
        this._url             = $('#keywords').data('url');
        this._tagify          = null;
    }

    async initialize() {
        let response = await fetch(this._url);
        if (! response.ok) {
            return { status: 'ERROR', error: response.statusText };
        }
        
        let keywords = await response.json();
            
        let words = [];
        for (let theme in keywords) {
            if (! (keywords[theme].length)) continue;
            words = words.concat(keywords[theme]);
        }
        words.sort();
        
        this._tagify = new Tagify(this._keywordsElement, {
            whitelist: words,
            autoComplete: {
                enabled: true
            },
            dropdown : {
                enabled       : 1,
                maxItems      : 10,
                position      : "text", 
                closeOnSelect : false,
                highlightFirst: true
            }
        });

        return { status: 'OK' };
    }

    get keywords() {
        let val = $('#keywords').val();
        if (! val) return '[]';

        val = JSON.parse(val)
        val = val.map(v => v['value'])
        return JSON.stringify(val);
    }
}
