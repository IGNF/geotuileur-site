import React from 'react'

export default class StylesList extends React.Component {
    constructor(props) {
        super(props);

        // Nom des styles
        this.names = Object.values(props.styles).map(s => { return s.name });

        this.state = {
            styles: this.props.styles,
            defaultStyle: this.props.defaultStyle
        };

        this.handleRemove = this.handleRemove.bind(this);
        this.handleChange = this.handleChange.bind(this);
    }

    /**
     * Regarde si le nom du style existe deja
     * @param {string} styleName 
     */
    styleExists(styleName) {
        return this.names.includes(styleName);
    }

    /**
     * Ajout d'un style
     * @param {Object} style 
     */
    add(style) {
        this.names.push(style.name);

        let styleToAdd = {};
        styleToAdd[style.id] = { name: style.name, url: style.url };
        const styles = Object.assign(styleToAdd, this.state.styles);

        this.setState({
            styles: styles,
            defaultStyle: style.id
        });
        this.props.onChange(style.url);
    }

    /**
     * Selection d'un bouton radio
     * @param {event} e 
     */
    handleChange(e) {
        let self = this;

        let id = e.currentTarget.id;
        let styleUrl = e.currentTarget.value;

        let url = Routing.generate('plage_style_change_default_ajax', {
            datastoreId: this.props.datastoreId,
            pyramidId: this.props.pyramidId,
            annexeId: id
        });

        this.props.wait.show(Translator.trans('pyramid.style.change_default_wait_msg'));
        $.post(url, () => {
            self.props.wait.hide();

            self.setState({ defaultStyle: id });
            self.props.onChange(styleUrl);
        }).fail(function () {
            self.props.wait.hide();

            self.setState({ defaultStyle: null });
            self.props.onChange(null);
            flash.flashAdd('pyramid.style.change_default_failed', 'danger');
        });
    }

    /**
     * Suppression d'un style
     * @param {event} e 
     */
    handleRemove(e) {
        let self = this;

        let styleId = e.currentTarget.dataset.id;
        let styleName = this.state.styles[styleId].name;

        let url = Routing.generate('plage_style_remove_ajax', {
            datastoreId: this.props.datastoreId,
            pyramidId: this.props.pyramidId,
            annexeId: styleId
        });

        this.props.wait.show(Translator.trans('pyramid.style.remove_wait_msg'));
        $.post(url, (result) => {
            self.props.wait.hide();

            let styles = result.styles;
            if (Array.isArray(result.styles)) { // Pas de styles
                styles = {};
            }
            let ids = Object.keys(styles);

            let defaultStyle = null, newStyle = null;
            if (ids.length) {
                defaultStyle = ids[0];
                newStyle = this.state.styles[defaultStyle];
            }
            self.setState({ styles: styles, defaultStyle: defaultStyle });

            // Mise a jour des noms
            self.names = self.names.filter(name => { return name !== styleName; });
            self.props.onChange(newStyle?.url);
        }).fail(function () {
            self.props.wait.hide();
            flash.flashAdd('pyramid.style.remove_failed', 'danger');
        })
    }

    /**
     * Rendu du composant
     * @returns 
     */
    render() {
        const isEmpty = (Object.keys(this.state.styles).length == 0);
        const text = isEmpty ? Translator.trans('pyramid.share.no_style_defined') : "";
        let count = 0;

        return (
            <>
                {isEmpty ? (
                    <input type="text" className="form-control text-secondary mt-1" readOnly value={text} />
                ) : (
                    Object.keys(this.state.styles).map(styleid => {
                        let style = this.state.styles[styleid];
                        let active = (styleid == this.state.defaultStyle);

                        return (
                            <div key={++count} className={`style-item p-1 ${active ? "active" : ""}`}>
                                <div className="custom-control custom-radio input-wrapper">
                                    <input className="custom-control-input" type="radio"
                                        key={styleid}
                                        id={styleid} name="style"
                                        value={style.url} checked={active}
                                        onChange={this.handleChange}
                                    />
                                    <label className="custom-control-label" htmlFor={styleid}>{style.name}</label>
                                </div>
                                <button className="btn btn--ghost p-0" data-id={styleid} onClick={this.handleRemove}><i className="icons-trash"></i></button>
                                <a className="btn btn--ghost p-0" href={style.url}><i className="icon-download"></i></a>
                            </div>
                        )
                    })
                )}
            </>
        );
    }
}
