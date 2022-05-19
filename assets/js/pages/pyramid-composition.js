import { guid } from "../utils";
export class PyramidComposition {
    /**
     * Constructeur
     */
    constructor() {
        this._$part2 = $('#part-2');
        
        let datas = this._$part2.data();
        this._docPath   = datas.docpath;
        this._typeInfos = datas.typeinfos;
        this._numTables = datas.typeinfos.relations.length;
        this._procCreatPyramidSample = datas.pyramidsample;
    }

    /**
     * Construction des attributs
     * 
     * @param JQuery Element $cardBody 
     * @param Array columnNames Nom des colonnes
     */
    _buildAttributes($cardBody, columnNames) {
        let mid = Math.floor(columnNames.length / 2);
        if (columnNames.length % 2) {   // odd
            mid += 1;
        }

        // TODO VOIR AVEC LES CHAMPS DE _procCreatPyramidSample

        const templateColumn = document.getElementById("template-table-column");

        let $row = $('<div>', { class: "row"}).appendTo($cardBody);
        let ranges = [{ start: 0, end: mid}, {start: mid, end: columnNames.length}];
        ranges.forEach(range => {
            let $col = $('<div>', { class: "col-md-6"}).appendTo($row);
            columnNames.slice(range.start, range.end).forEach(columnName => {
                let uid = guid();

                const $column = $(templateColumn.content.cloneNode(true));
                $column.find(':checkbox').prop({ id: uid, name: columnName});
                $column.find('label').prop('for', uid).text(columnName);
                $col.append($column);
            });    
        });
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
        $accordeon.prop('id', id);

        $accordeon.find('.o-accordion__panel')
            .prop('id', `collapse-map${num}`)
            .attr('data-num', num);

        let $a = $accordeon.find("a");
        $a.prop('href',`#collapse-map${num}`).data('parent', id);

        let html = relation.name;
        if (this._numTables > 1) {
            let $span = $('<span>', { class: "table-valid", id: `table-valid${num}` })
                .append($('<i>', {class: "icon-check-circle text-success"}))
                .append($('<span>', {class: "font-weight-light small", id: `table-infos${num}`}));
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
        this._buildAttributes($cardBody, columnNames);

        // Eventuellement le bouton suivant
        if (this._numTables > 1 && num < (this._numTables - 1)) {
            let $p = $('<p>', { class: "text-center mb-0 mt-2" }) .appendTo($cardBody);
            $('<button>', { class: "btn btn-width--lg btn--plain btn--primary next-table", 'data-num': num, text: Translator.trans('pyramid.form_add.next_table')})
                .appendTo($p);
        }

        // Ajout de l'accordeon
        this._$part2.append($accordeon);
    }

    /**
     * Retourne la liste des tables et des attributs
     * @returns 
     */
    asJsonString() {
        let composition = [];
        $('.card-body').each(function() {
            let tableName = $(this).data('table');
            composition[tableName] = [];
            
            $(this).find(':checkbox').each(function() {
                if ($(this).is(':checked')) {
                    composition[tableName].push($(this).prop('name'));   
                }  
            });
        });
        return composition;
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