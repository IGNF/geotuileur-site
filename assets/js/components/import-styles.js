import React from 'react';
import ReactDOM from 'react-dom';
import MapViewer from "../components/MapViewer";
import { StyleFileReader } from "../components/style-file-reader"
import StylesList from "../components/react/StylesList";


export class ImportStyles {
	constructor(pyramidDatas) {
        this._types = ['json', 'sld', 'qml'];

		this._datastoreId = pyramidDatas.datastoreid;
		this._pyramid     = pyramidDatas.pyramid;

        let params = {
            datastoreId: this._datastoreId, 
            pyramidId: this._pyramid._id
        };
		this._url = Routing.generate('plage_style_add_ajax_mapbox', params)
		
		this._metadatas         = null;
        this._layerNames        = [];
		this._styleFileReader   = null;
        this._fileType          = 'json';
        this._num               = 0;

        let importText = Translator.trans('pyramid.style.import');
        this._templateImport = '<div class="block-import col-md-5 text-center">'
            + '<label class="btn btn-sm btn--ghost btn--primary w-100" for="style-file-NUM">'
            + `<img src="/geotuiler/build/img/icons/Deposer.png">&nbsp;&nbsp;${importText}</label>`                  
            + '<input type="file" id="style-file-NUM" accept=".TYPE" data-layer="LAYER" data-num="NUM"></input>'
            + '</div>';
			
		// Url du style par defaut
		let styles = pyramidDatas.styles;
		if (Array.isArray(styles)) {
			styles = {};
		}

		let style;
		let ids = Object.keys(styles);
		if (ids.length) {   // Toujours le premier
			style = Object.assign({ id: ids[0] }, styles[ids[0]]);
		}

		let streamUrl = this._pyramid.tags.tms_url;
		this._viewer = new MapViewer('map-target', streamUrl, style?.url, { 
			layerSwitcherControl: true
		});
		
		// La couche TMS a ete ajoutee
		this._viewer.on('tmslayeradded', (event) => {
            // Mise a jour de l'entete de la page
            $('.o-page-title h1').text(event.metadatas.title);

			/**
			 * STYLES LIST
			 */
			let re = React.createElement(StylesList, {
				datastoreId: this._datastoreId,
				pyramidId: this._pyramid._id,
				styles: styles, 
				defaultStyle: style?.id,
				wait: wait,
				onChange: url => {
					this._viewer.setStyle(url);
				}
			});
			this._stylesList = ReactDOM.render(re, document.getElementById('styles-wrapper'));
			this._setMetadatas(event.metadatas);

            // Recuperation du nom des couches
            this._metadatas.vector_layers.forEach(layer => {
                this._layerNames.push(layer.id);
            });
		});

        $('#import-styles').on('click', () => {
            this.showDialog();
        });
	}
	
    /**
     * Construction du formulaire
     * @returns 
     */
    buildForm() {
        this._reset();

        const template = document.getElementById("template-dlg-multiple");
        const clone = template.content.cloneNode(true);
        ['sld', 'qml'].forEach(type => { this._build(clone, type)});

        return clone;
    }

    /**
     * Affichage de la boite de dialogue
     * @returns 
     */
    showDialog() {
        let self = this;

        let clone = this.buildForm();
        let dlg = bootbox.dialog({ 
            title: Translator.trans('pyramid.style.import'),
            message: clone,
            closeButton: false,
            buttons: {
                cancel: {
                    label: Translator.trans('cancel'),
                    className: 'btn btn-sm btn--ghost btn--primary',
                    callback: () => {}
                },
                add: {
                    label: Translator.trans('add'),
                    className: 'btn btn-sm btn--plain btn--primary',
                    callback: () => {
                        let $form = $('.form-multiple');

                        let name = $form.find('#style-name').val();
                        if (! name) {
                            self._showError(Translator.trans('pyramid.style.name_not_empty'));
                            return false;
                        }
                        if (self._stylesList.styleExists(name)) {
                            self._showError(Translator.trans('pyramid.style.name_exists'));
                            return false;
                        }

                        if ('json' === self._fileType) {
                            let files = $('.form-multiple #json-file').prop('files');
                            if (files.length) {
                                self._jsonFileCallback(name, files[0]);
                            }
                            return true;
                        } 
                        
                        let selector = `.form-multiple #${self._fileType}-style input[type="file"]`;
                       
                        let styles = {};
                        $(selector).each(function() {
                            let files = $(this).prop('files');
                            if (files.length) {
                                let layer = $(this).data('layer');
                                styles[layer] = files[0];
                            }
                        });
                        self._otherTypesFileCallback({ name: name, styles: styles });
                        return true;
                    }
                }
            }
        });

        dlg.init(() => {
            //let self = this;
            let $form = $('.form-multiple');
            
            // Changement de type de fichier
            $form.find('input[name="file-type"]').on('change', (e) => {
                this._toggleType($(e.currentTarget).val());
            });

            // Changement du fichier json
            $form.find('input[id=json-file]').on('change', (e) => { this._onJsonFileChange(e); });

            // Changement de fichier SLD ou QML
            $form.find('input[id^=style-file]').on('change', (e) => { this._onFileChange(e); });
           
            // Suppression du fichier d'import
            $form.find('.remove-style').on('click', (e) => {
                let $this   = $(e.currentTarget);
                
                let num     = $this.data('num'); 
                let layer   = $(`#layer-${num}`).data('layer');
                $(`#filename-${num}`).text("");
                $(`#style-file-${num}`).val("");
                $(`#layer-${num}`).text(layer);
            });
        });
    }

    /**
     * 
     */
    _reset() {
        this._num = 0;
        this._fileType = 'json';
    }
	
    /**
     * Suppression du message
     */
    _clearError() {
        $('.form-multiple').find('.style-error').html("");   
    }

    /**
     * Affichage du message d'erreur. Celui-ci disparait apres 5 secondes
     * @param string message 
     */
    _showError(message) {
        let $error = $('.form-multiple').find('.style-error');
        $error.html(`<span class="icons-alert message-icon"></span>${message}`);    
    }

    /**
     * L'extension du fichier est-elle correcte ?
     * @param Filefile 
     * @returns 
     */
    _hasRightExtension(file) {
        let extension = file.name.split('.').pop().toLowerCase();
        return (extension === this._fileType);
    }

    /**
     * Le fichier json a change
     * @param Event e 
     */
    async _onJsonFileChange(e) {
        this._clearError();

        let $this = $(e.currentTarget);
        let file = $this.prop('files')[0];
        try {
            let content = await this._checkFile(file).catch(err => { throw err; });
            let json = JSON.parse(content);

            const layers = json.layers.filter(layer => this._layerNames.includes(layer["source-layer"]));
            if (! layers.length) {
                throw new Error(Translator.trans('pyramid.style.file_not_match'));
            }
            $('#filename-json').text(file.name);
        } catch(err) {
            $this.val("");
            $('#filename-json').text("");

            this._showError(err.message);    
        }
    }

    /**
     * Le fichier SLD ou QML a change
     * @param Event e 
     */
     async _onFileChange(e) {
        this._clearError();

        let $this   = $(e.currentTarget);
        
        let file    = $this.prop('files')[0];
        let num     = $this.data('num');
        let layer   = $(`#layer-${num}`).data('layer');

        try {
            await this._checkFile(file);
            $(`#filename-${num}`).text(file.name);
            $(`#layer-${num}`).html(`${layer}&nbsp;<i class="icon-check"></i>`);
        } catch(err) {
            $(`#filename-${num}`).text("");
            $(`#layer-${num}`).html(`${layer}`);
            $this.val("");

            this._showError(err.message);    
        }
    }

    /**
     * On verifie que le fichier est correct et peut etre analyse (parse)
     * @param File file 
     * @returns 
     */
    async _checkFile(file) {
        if (! this._hasRightExtension(file)) {
            throw new Error(Translator.trans('pyramid.style.type_not_authorized'));  
        }

        let content = await this._styleFileReader.parseFile(file).catch(err => { throw err; });
        return content;
    }

    /**
     * Changement de type de fichier
     * @param string type 
     */
    _toggleType(type) {
        this._fileType = type;
        $(`#${type}-style`).show();

        let others = this._types.filter(t => t !== type);
        others.forEach(t => {
            // Suppression des fichiers et des noms de fichiers
            $(`#${t}-style input[type="file"]`).val("");
            $(`#${t}-style div[id^="filename-"]`).text("");
            $(`#${t}-style`).hide();
        });
    }

    /**
     * Initialisation
     * @param Object metadatas 
     */
	_setMetadatas(metadatas) {
		this._metadatas = metadatas;
		this._styleFileReader = new StyleFileReader(metadatas);
	}
	
    /**
     * Construction des sous formulaires SLD et QML
     * @param HTMLElement clone 
     * @param string type SLD, QML
     */
	_build(clone, type) {
        this._metadatas.vector_layers.forEach(layer => {
            let $main = $(clone).find(`div#${type}-style`);
            let $row = $('<div>', { class: 'row'}).appendTo($main);

            let $div = $('<div>', { class: 'col-md-6' }).appendTo($row);
            $('<label>', { class: "form-label mb-0", id: `layer-${this._num}`, text: layer.id, 'data-layer': layer.id }).appendTo($div);
            $('<div>', {class: 'ml-2 filename', id: `filename-${this._num}`}).appendTo($div);

            let divImport = this._templateImport.replaceAll('NUM', this._num);
            divImport = divImport.replaceAll('TYPE', type);
            divImport = divImport.replaceAll('LAYER', layer.id);
            $(divImport).appendTo($row);

            let $btnRemove = $('<button>', { class: 'btn btn-sm btn--ghost px-0 remove-style', 'data-num': this._num });
            $btnRemove.append($('<i>', { class: 'icons-trash'}));
            $row.append($btnRemove);
 
            this._num++;
        });    
    }

    /**
     * Appel ajax pour l'enregistrement du style
     * @param string name Nom du style
     * @param string style Style
     */
    _ajaxCall(name, style) {
        let self = this;
        
        let formData = new FormData();
        formData.append("name", name);
        formData.append("style", style);

        $.ajax({
            url: this._url,
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false
        }).done(style => {
            wait.hide();
            self._stylesList.add(style);
        }).fail(() => {
            wait.hide();
            let message = Translator.trans('pyramid.style.add_failed');
            flash.flashAdd(message, 'danger');
        })   
    }
    
    /**
     * Appel ajax pour l'enregistrement du style JSON
     * @param string name 
     * @param File file 
     */
    _jsonFileCallback(name, file) {
        wait.show(Translator.trans('pyramid.style.add_wait_msg'));

        this._styleFileReader.readJsonFile(name, file)
            .then(style => {
                // filtrage des layers
                style.layers.filter(layer => {
                    return this._layerNames.includes(layer["source-layer"]);
                }).forEach(layer => {
                    layer.source = this._metadatas.name;
                });
                this._ajaxCall(name, JSON.stringify(style));
            }).catch(err => {
                wait.hide();
                flash.flashAdd(err.message);
            })
    }

    /**
     * Appel ajax pour l'enregistrement des style SLD et QML
     * @param Object datas
     * @param File file 
     */
    _otherTypesFileCallback(datas) {
        if (! Object.keys(datas).length) {
            return;
        }

        wait.show(Translator.trans('pyramid.style.add_wait_msg'));

        this._styleFileReader.readFiles(datas.name, datas.styles)
            .then(style => {
                this._ajaxCall(datas.name, JSON.stringify(style));
            }).catch(err => {
                wait.hide();
                flash.flashAdd(err.message);
            });
    }
}