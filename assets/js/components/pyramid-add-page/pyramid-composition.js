import { guid } from "../../utils";
export class PyramidComposition {
    /**
     * Constructeur
     */
    constructor() {
        this._$container = $('#part-2 #composition');
        
        let datas = $('#part-2').data();
        this._docPath   = datas.docpath;
        this._typeInfos = datas.typeinfos;
        this._numTables = datas.typeinfos.relations.length;
        this._sampleParameters = datas.pyramidsample ? datas.pyramidsample.parameters : null;
    }

    /**
     * Trie des attributs 
     * @param Object attributes 
     * @param Array primaryKeys 
     * @returns 
     */
    _convertToArray(attributes, primaryKeys) {
        // Trie alphabetique
        const sortedObject = Object.keys(attributes).sort().reduce((r, k) => (r[k] = attributes[k], r), {});

        let array = [];
        for (const [key, value] of Object.entries(sortedObject)) {
            if (/^geometry/.test(value) || primaryKeys.includes(key)) continue;
            array.push({ name: key, type: value });
        }
        return array;
    }

    /**
     * Recupere les attributs pour une table de l'echantillon de la pyramid
     * @param string tableName 
     * @returns 
     */
    _getSampleAttributes(tableName) {
        if (! this._sampleParameters) return [];
        const filtered = this._sampleParameters.composition.filter(composition => {
            return composition.table === tableName;   
        });

        if (filtered.length === 1) {
            return filtered[0].attributes.split(',');
        }
    }

    /**
     * Construction des attributs
     * 
     * @param JQuery Element $cardBody 
     * @param Object attributes
     * @param Array primaryKeys
     * @param Array sampleAttributes
     * 
     */
    _buildAttributes($cardBody, attributes, primaryKeys, sampleAttributes) {
        let columns = this._convertToArray(attributes, primaryKeys);
        
        let numAttributes = columns.length;
        
        let mid = Math.floor(numAttributes / 2);
        if (numAttributes % 2) {   // odd
            mid += 1;
        }

        const templateColumn = document.getElementById("template-table-column");

        let $row = $('<div>', { class: "row"}).appendTo($cardBody);
        let ranges = [{ start: 0, end: mid}, {start: mid, end: numAttributes}];
        ranges.forEach(range => {
            let $col = $('<div>', { class: "col-md-6"}).appendTo($row);
            columns.slice(range.start, range.end).forEach(columnProps => {
                let uid = guid();

                const $column = $(templateColumn.content.cloneNode(true));
                let $checkbox = $column.find(':checkbox');

                $checkbox.prop({ id: uid, name: columnProps.name });
                if (sampleAttributes.includes(columnProps.name)) {
                    $checkbox.prop('checked', true);   
                }
                $column.find('label').prop('for', uid).text(columnProps.name);
                $col.append($column);
            });    
        });
    }

    /**
     * Recupere les informations sur une table
     * @param string table 
     * @returns 
     */
    _getInfos(table) {
        if (! this._sampleParameters) return null;

        const filtered = this._sampleParameters.composition.filter(composition => {
            return composition.table === table;   
        });
        if (filtered.length === 1) {
            let attributes = (null === filtered[0].attributes) ? "" : filtered[0].attributes;
            
            let num = attributes.split(',').length;
            let topLevel    = filtered[0]['top_level'];
            let bottomLevel = filtered[0]['bottom_level']

            let numText = num ? ((num > 1) ? `${num} attributs conservés` : `${nb} attribut conservé`) : 'aucun attribut conservé';
            return `(${numText}, niveaux ${topLevel} à ${bottomLevel})`;
        }

        return null;
    }

    /**
     * Construction d'un accordeon'
     * 
     * @param Object relation 
     * @param integer num 
     * @returns 
     */
    _buildAccordeon(relation, num) {
        let id = `accordion-map${num}`;
        const template = document.getElementById("template-table-accordeon");
        
        const $accordeon = $(template.content.cloneNode(true));
        
        $accordeon.find('.table-composition').prop('id', id);
        $accordeon.find('.o-accordion__panel')
            .prop('id', `collapse-map${num}`)
            .attr('data-num', num);

        let $a = $accordeon.find("a");
        $a.prop('href',`#collapse-map${num}`).attr('data-parent', `#${id}`);

        let html = relation.name + '&nbsp;';
        if (this._numTables > 1) {
            let infos = this._getInfos(relation.name);
            let $spanInfos = $('<span>', {class: "font-weight-light small", id: `table-infos${num}`});
            if (infos) {
                $spanInfos.text(infos);
            }

            let $span = $('<span>', { class: "table-valid", id: `table-valid${num}` })
                .css('display', infos ? 'inline' : 'none')
                .append($('<i>', {class: "icon-check-circle text-success"}))
                .append($spanInfos);
            html += $span.prop('outerHTML');
            $a.append(html);   
        }

        // Les attributs
        let $cardBody = $accordeon.find('.card-body');
        $cardBody.attr('data-table', relation.name);
        
        if (this._numTables > 1) {
            $cardBody.append($('<label>', { text: Translator.trans('pyramid.form_add.min_and_max_zoom') }));
            $cardBody.append($('<div>', { class: "form-group zoom-levels", id: `zoom-levels${num}`, 'data-table': relation.name, 'data-num': num }));
        }
        let columnNames = Object.keys(relation.attributes);
        if (! columnNames.length) {
            return;
        }

        $cardBody.append($('<p>', { class: "font-weight-bold mb-0", text: Translator.trans('pyramid.form_add.attributes') }));
 
        html = $('<i>', { class: "icon-question" }).prop('outerHTML');
        html += '&nbsp' + Translator.trans('pyramid.form_add.attributes_help', { url: `${this._docPath}#/generate?id=composition` });
        $('<p>', { class: "description wysiwyg" }).html(html).appendTo($cardBody);  

        // Ajout des attributs
        let sampleAttributes = this._getSampleAttributes(relation.name);
        this._buildAttributes($cardBody, relation.attributes, relation['primary_key'], sampleAttributes);

        // Eventuellement le bouton suivant
        if (this._numTables > 1 && num < (this._numTables - 1)) {
            let $p = $('<p>', { class: "text-center mb-0 mt-2" }) .appendTo($cardBody);
            $('<button>', { class: "btn btn-width--lg btn--plain btn--primary next-table", 'data-num': num, text: Translator.trans('pyramid.form_add.next_table')})
                .appendTo($p);
        }

        // Ajout de l'accordeon
        this._$container.append($accordeon);
    }

    /**
     * Retourne la liste des tables et des attributs
     * @returns 
     */
    asJsonString() {
        let composition = {};
        $('.card-body').each(function() {
            let tableName = $(this).data('table');
            composition[tableName] = [];
            
            $(this).find(':checkbox').each(function() {
                if ($(this).is(':checked')) {
                    composition[tableName].push($(this).prop('name'));   
                }  
            });
        });
        
        return JSON.stringify(composition);
    }

    /**
     * Construction du formulaire
     */
    buildForm() {
        let num = 0;
        this._typeInfos.relations.forEach(relation => {
            this._buildAccordeon(relation, num);
            num++;
        });      
    }
}