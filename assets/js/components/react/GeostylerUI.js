import React, { useEffect, useState } from 'react'
import PropTypes from 'prop-types'

import ConfigProvider from 'antd/lib/config-provider';
import { CardStyle, Style, locale as GsLocale } from 'geostyler'
import axios from 'axios';
import MapboxStyleParser from 'geostyler-mapbox-parser';

const andtThemeToken = {
    borderRadius: 0,
    colorPrimary: "#3993f3",
    colorError: "#f45648",
    colorSuccess: "#90c149",
    colorWarning: "#f0ad4e",
}

const mapboxStyleParser = new MapboxStyleParser({ ignoreConversionErrors: false })

const GeostylerUI = ({ styleAnnexe, onStyleChange }) => {
    let demoStyle = {
        name: 'Demo Style',
        rules: [{
            name: 'Rule 1',
            symbolizers: [{
                kind: 'Mark',
                wellKnownName: 'square',
                color: '#93c0ed',
                strokeColor: '#1345c3',
                strokeWidth: 5,
                radius: 20
            }]
        }]
    }

    const [isLoading, setIsLoading] = useState(true)
    const [style, setStyle] = useState(demoStyle)
    const [showCardStyle, setShowCardStyle] = useState(false)

    useEffect(() => {
        axios
            .get(styleAnnexe.url)
            .then(async response => {
                console.log(response.data);
                setIsLoading(false)

                const temp = await mapboxStyleParser.readStyle(response.data)
                console.log(JSON.stringify(temp));
                setStyle(temp.output)
            })
            .catch(error => console.error(error.response))
    }, [])

    useEffect(() => {
        onStyleChange(style)
    }, [style])

    return (
        <ConfigProvider theme={{
            token: andtThemeToken,
        }} locale={GsLocale.fr_FR} >
            <div className="container-content mt-5">
                {
                    isLoading ?
                        <h1 className="text-center text-dark mt-5"><i className="icon-reinitialiser icons-spin"></i></h1>
                        :
                        <>
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
                                    <CardStyle style={style} onStyleChange={(style) => setStyle(style)} rendererType={"OpenLayers"} /> :
                                    <Style style={style} onStyleChange={(style) => setStyle(style)} ruleRendererType={"OpenLayers"} />
                            }
                            <button className="btn btn--plain btn--primary btn-sm">Enregistrer le style</button>
                        </>
                }
            </div>
        </ConfigProvider>
    )
}

GeostylerUI.propTypes = {
    styleAnnexe: PropTypes.object,
    onStyleChange: PropTypes.func
}

export default GeostylerUI
