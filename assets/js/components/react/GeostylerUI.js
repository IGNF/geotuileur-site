import React, { useEffect, useState } from 'react'
import PropTypes from 'prop-types'
import axios from 'axios';

import ConfigProvider from 'antd/lib/config-provider';
import {
    CardStyle,
    Style,
    locale as GsLocale,
} from 'geostyler'
import LegendRenderer from 'geostyler-legend/dist/LegendRenderer/LegendRenderer';

import MapboxStyleParser from 'geostyler-mapbox-parser';

import merge from 'lodash.merge'

/**
 * @type {GeoStylerLocale}
 */
let appLocale = null;

// appLocale = {
//     Rules: {
//         rulesTitle: "Règles",
//         addRule: "Ajouter une règle",
//         edit: "Modifier une règle",
//     },
//     Rule: {
//         nameFieldLabel: "Nom",
//         nameFieldPlaceholder: "Saisir un nom",
//     },
//     Symbolizers: {
//         symbolizersTitle: "Symboles",
//         addSymbolizer: "Ajouter",
//     },
//     CardStyle: {
//         classificationTitle: "arnest"
//     }
// }

// appLocale = merge(GsLocale.fr_FR, appLocale)
appLocale = GsLocale.fr_FR

const iconLibraryConfig = [{
    name: 'Traffic',
    icons: [{
        src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5f/Parking_icon.svg/128px-Parking_icon.svg.png',
        caption: 'Parking'
    }, {
        src: 'https://upload.wikimedia.org/wikipedia/commons/a/ac/RWB-RWBA_Autobahn.svg',
        caption: 'Highway'
    }]
}, {
    name: 'GeoStyler',
    icons: [{
        src: 'https://raw.githubusercontent.com/geostyler/geostyler/master/public/logo.svg',
        caption: 'GeoStyler Logo'
    }]
}];

const mapboxStyleParser = new MapboxStyleParser({ ignoreConversionErrors: false })

const GeostylerUI = ({ styleAnnexe, applyStyle, saveNewStyle, replaceCurrentStyle }) => {
    let demoStyle = {
        name: 'Demo Style',
        rules: [{
            name: 'Rule 1',
            symbolizers: [{
                kind: 'Mark',
                wellKnownName: 'circle',
                color: '#93c0ed',
                strokeColor: '#1345c3',
                strokeWidth: 5,
                radius: 20
            }]
        }]
    }

    const [isLoading, setIsLoading] = useState(true)
    const [style, setStyle] = useState(demoStyle)
    const [showCardStyle, setShowCardStyle] = useState(true)
    const [showCompactStyle, setShowCompactStyle] = useState(true)

    useEffect(() => {
        getStyle()
    }, [])

    useEffect(() => {
        applyStyle(style)
    }, [style])

    useEffect(() => {
        if (!isLoading) {
            renderLegend()
        }
    }, [isLoading])

    const getStyle = () => {
        if (!styleAnnexe) {
            setIsLoading(false)
            return
        }

        axios
            .get(styleAnnexe.url)
            .then(async response => {
                console.debug("mapbox style", response.data);

                const temp = await mapboxStyleParser.readStyle(response.data)
                console.debug("ol style", JSON.stringify(temp));
                setStyle(temp.output)
                setIsLoading(false)
            })
            .catch(error => console.error(error.response))
    }

    const renderLegend = () => {
        const legendRenderer = new LegendRenderer({
            maxColumnWidth: 300,
            maxColumnHeight: 300,
            overflow: 'auto',
            styles: [style],
            size: [600, 300],
            hideRect: true
        });
        const legendEl = document.getElementById("legend");
        if (legendEl) {
            legendRenderer.render(legendEl);
        }
    }

    const handleClickSaveNewStyle = () => {
        saveNewStyle(style)
    }

    const handleClickReplaceCurrrentStyle = () => {
        replaceCurrentStyle(style)
    }

    renderLegend()

    return (
        <ConfigProvider
            theme={{
                token: {
                    borderRadius: 0,
                    colorPrimary: "#3993f3",
                    colorError: "#f45648",
                    colorSuccess: "#90c149",
                    colorWarning: "#f0ad4e",
                },
                components: {
                    Breadcrumb: {
                        colorLink: "#000",
                    },
                    Card: {
                        margin: 0
                    },
                }
            }}
            locale={appLocale}
        >
            <div className="container-content mt-5">
                <h2>Edition de style</h2>
                {
                    isLoading ?
                        <h1 className="text-center text-dark mt-5"><i className="icon-reinitialiser icons-spin"></i></h1>
                        :
                        <>
                            {/* TODO : revoir cette partie quand le contenu sera définitif */}
                            <fieldset>
                                <legend>CardStyle ?</legend>
                                <div>
                                    <input type="radio" name="card-style" value="yes" defaultChecked={showCardStyle} onClick={() => setShowCardStyle(true)} />&nbsp;
                                    <label htmlFor="yes">Oui</label>&nbsp;
                                    <input type="radio" name="card-style" value="no" defaultChecked={!showCardStyle} onClick={() => setShowCardStyle(false)} />&nbsp;
                                    <label htmlFor="no">Non</label>
                                </div>
                            </fieldset>
                            {
                                showCardStyle ?
                                    <CardStyle
                                        style={style}
                                        onStyleChange={(style) => setStyle(style)}
                                        rendererType={"OpenLayers"}
                                        iconLibraries={iconLibraryConfig}
                                    /> :
                                    <>
                                        <fieldset>
                                            <legend>Compact ?</legend>
                                            <div>
                                                <input
                                                    type="radio"
                                                    name="compact-style"
                                                    value="yes"
                                                    defaultChecked={showCompactStyle}
                                                    onClick={() => setShowCompactStyle(true)}
                                                />&nbsp;
                                                <label htmlFor="yes">Oui</label>&nbsp;
                                                <input
                                                    type="radio"
                                                    name="compact-style"
                                                    value="no"
                                                    defaultChecked={!showCompactStyle}
                                                    onClick={() => setShowCompactStyle(false)}
                                                />&nbsp;
                                                <label htmlFor="no">Non</label>
                                            </div>
                                        </fieldset>
                                        <Style
                                            style={style}
                                            compact={showCompactStyle}
                                            onStyleChange={(style) => setStyle(style)}
                                            ruleRendererType={"OpenLayers"}
                                            iconLibraries={iconLibraryConfig}
                                        />
                                    </>
                            }
                            <button
                                className="btn btn--plain btn--primary btn-active-effect btn-sm"
                                onClick={handleClickSaveNewStyle}>Enregistrer un nouveau style
                            </button>
                            <button
                                className={`btn btn--plain btn--primary btn-active-effect btn-sm ${styleAnnexe?.name != style?.name ? 'disabled' : ''}`}
                                onClick={handleClickReplaceCurrrentStyle}>Enregistrer et remplacer le style sélectionné
                            </button>
                            <button
                                className="btn btn--plain btn--primary btn-active-effect btn-sm"
                                onClick={() => getStyle()}>Effacer les changements
                            </button>

                            <h2 className="mt-2">Légende</h2>
                            <div id="legend" />
                        </>
                }
            </div>
        </ConfigProvider>
    )
}

GeostylerUI.propTypes = {
    styleAnnexe: PropTypes.object,
    applyStyle: PropTypes.func,
    saveNewStyle: PropTypes.func,
    replaceCurrentStyle: PropTypes.func
}

export default GeostylerUI
