import axios from 'axios';
import React, { useEffect, useState, useRef } from 'react';
import ContentLoader from "react-content-loader";
import { StoredDataActionFactory } from "../stored-data-actions";

const flash = require("../flash-messages");

const StoredData = ({ datastoreId, storedDataId, autoRefresh }) => {
    const isMounted = useRef(true);
    const abortController = useRef(new AbortController());

    const [storedData, setStoredData] = useState({});
    const [isLoading, setIsLoading] = useState(true);

    let dataIcon = null;
    let dropdownMenu = null;
    let statusBadge = null;
    let actionPossibleMain = null;
    let isPyramidPublished = storedData?.tags?.published // est-ce que la pyramide est publiée
    let isPyramidInitial = storedData?.tags?.update_pyramid_id // est-ce que la pyramide est celle qu'on veut mettre à jour
    let isPyramidUpdate = storedData?.tags?.initial_pyramid_id // est-ce que la pyramide est une pyramide de mise à jour
    let isPyramidSample = storedData?.tags?.is_sample  // est-ce que la pyramide est une pyramide de mise à jour

    let actionFactory = new StoredDataActionFactory();

    /****** fonctions useEffect ******/

    useEffect(() => {
        getStoredData();

        let refreshInterval = null;
        if (autoRefresh) {
            refreshInterval = setInterval(() => {
                getStoredData();
            }, 10000);
        }

        /**
         * useEffect peut retourner une fonction de nettoyage (cleanup) (appelée à la destruction du composant)
         * - on retourne une fonction qui sauvegarde l'information que l'instance actuelle de composant a été supprimé du DOM
         * - on utilisera cette information pour éviter d'envoyer une requete et de mettre à jour le composant alors qu'il a été retiré du DOM
         * - on supprime aussi l'interval
         */
        return () => {
            if (refreshInterval) clearInterval(refreshInterval);
            isMounted.current = false;
            abortController.current.abort();
        }

        /**
         * Le tableau [] en dernier paramètre de useEffect prend les variables sur lesquelles dépend le composant react. 
         * - Si le tableau est vide (donc [], pas "null"), cet hook est uniquement appelé à la création du composant.
         * - Si le tableau n'est pas vide (ex. [var1, var2]), cet hook est appelé à la création du composant, mais aussi à chaque fois que var1 et var2 change
         * - Si le tableau est "null" ou si on ne passe rien à ce paramètre, la fonction va être appelée en boucle à l'infini 
         */
    }, []);

    /****** fonctions utilitaires ******/
    const getStoredData = () => {
        let url = Routing.generate("plage_stored_data_get", {
            datastoreId: datastoreId,
            storedDataId: storedDataId
        });

        axios.get(url, {
            signal: abortController.current.signal
        })
            .then(response => {
                if (isMounted.current) {
                    setStoredData(response?.data);
                    setIsLoading(false);
                }
            }).catch(error => {
                console.error(error.data);

                if (isMounted.current) {
                    setIsLoading(false)
                }
            })
    }

    // Action sur une storedData (suppression, depublication ...)
    function handleAction(type, e) {
        e.preventDefault();
        if (!storedData) { return; }

        let action = actionFactory.create(type, datastoreId, storedData);
        if (action) {
            action.confirm();
        }
    }

    // click sur la generation d'une pyramide (add)
    // Si l'extent n'existe pas dans storedData => Avertissement et on empeche d'aller sur la page
    function handleAdd(e) {
        if (!('extent' in storedData)) {
            flash.flashAdd("L'étendue géographique des données n'a pas pu être déterminée. Il n'est pas possible de générer une pyramide de tuiles vectorielles à partir de ces données.", 'danger');
            e.preventDefault();
        }
    }

    /****** rendu conditionnel ******/

    // stored_data icon based on its type and status
    if (storedData?.type == 'VECTOR-DB') {
        switch (storedData?.status) {
            case 'GENERATED':
                dataIcon = <i className="icons-data-generated"></i>
                break;
            case 'UNSTABLE':
                dataIcon = <i className="icons-data-unstable"></i>
                break;
            case 'GENERATING':
                // this icon shouldn't be necessary because data integration is short
                dataIcon = <i className="icons-generating"></i>
                break;
            default:
                // this icon shouldn't be necessary
                dataIcon = <i className="icons-data"></i>
                break;
        }
    }
    else if (storedData?.type == 'ROK4-PYRAMID-VECTOR') {
        switch (storedData?.status) {
            case 'GENERATED':
                dataIcon = <i className="icons-tiles-generated"></i>
                break;
            case 'UNSTABLE':
                dataIcon = <i className="icons-tiles-unstable"></i>
                break;
            case 'GENERATING':
                dataIcon = <i className="icons-generating"></i>
                break;
            default:
                // this icon shouldn't be necessary
                dataIcon = <i className="icons-tiles"></i>
                break;
        }
    }

    dropdownMenu =
        storedData?.status !== 'GENERATING' ? (
            <>
                <button type="button" className="btn btn-sm btn--plain btn--white icons-more-menu" data-toggle="dropdown" aria-expanded="false">
                </button>
                <div className="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                    {
                        isPyramidPublished ? (
                            <>
                                {
                                    isPyramidInitial || isPyramidUpdate || isPyramidSample ? ('') : (<a className="dropdown-item" href={Routing.generate("plage_pyramid_update", { datastoreId: datastoreId, pyramidId: storedData?._id })}>{Translator.trans('datastore.dashboard.menus.update')}</a>)
                                }

                                {isPyramidUpdate || isPyramidSample ? ('') : (<a className="dropdown-item" href={Routing.generate("plage_style_manage", { datastoreId: datastoreId, pyramidId: storedData?._id })}>{Translator.trans('datastore.dashboard.menus.personalize')}</a>)}

                                {
                                    isPyramidInitial || isPyramidUpdate || isPyramidSample ? ('') : (<a className="dropdown-item" href={Routing.generate("plage_pyramid_update_publish", { datastoreId: datastoreId, pyramidId: storedData?._id })}>{Translator.trans('datastore.dashboard.menus.update_published')}</a>)
                                }
                                
                                {
                                    isPyramidInitial || isPyramidUpdate || isPyramidSample ? ('') : (<a className="dropdown-item" href="#" onClick={(e) => handleAction('unpublish', e)}>{Translator.trans('datastore.dashboard.menus.unpublish')}</a>)
                                }

                                {isPyramidInitial || isPyramidUpdate || isPyramidSample ? ('') : (<div className="dropdown-divider"></div>)}
                            </>
                        ) : ('')
                    }
                    {isPyramidInitial ? ('') : (<a className="dropdown-item text-danger" href="#" onClick={(e) => handleAction('remove', e)}>{Translator.trans('datastore.dashboard.menus.remove')}</a>)}
                </div>
            </>
        ) : (
            ''
        )

    // status and actionPossibleMain based on stored_data status AND type
    if (storedData?.status == 'GENERATED') {
        if (isPyramidPublished) {
            statusBadge = <><i className="icon-check-circle text-primary"></i>&nbsp;Publié</>
        } else {
            statusBadge = <><span className="icons-status text-success"></span>&nbsp;Prêt</>
        }

        if (storedData?.type == 'VECTOR-DB') {
            let url = Routing.generate("plage_pyramid_add", {
                datastoreId: datastoreId,
                vectordbId: storedDataId,
            });
            actionPossibleMain = <a className="btn btn--plain btn--primary btn-sm w-100" href={url} onClick={handleAdd} >Générer</a>
        }
        else if (storedData?.type == 'ROK4-PYRAMID-VECTOR') {
            let url = '';

            // si c'est une pyramide sur un échantillon
            if (isPyramidSample) {
                url = Routing.generate("plage_pyramid_sample_check", {
                    datastoreId: datastoreId,
                    pyramidId: storedDataId,
                });

                statusBadge = <><span className="icons-status text-success"></span>&nbsp;Prêt</>
                actionPossibleMain = <a href={url} className="btn btn--plain btn--primary btn-sm w-100">Visualiser</a>
            }
            else if (isPyramidPublished) {
                if (storedData?.tags?.initial_pyramid_id) {
                    url = Routing.generate("plage_pyramid_update_compare", {
                        datastoreId: datastoreId,
                        pyramidId: storedDataId,
                    });

                    statusBadge = <><span className="icons-status text-success"></span>&nbsp;Prêt</>
                    actionPossibleMain = <a href={url} className="btn btn--plain btn--primary btn-sm w-100">Visualiser</a>
                } else {
                    url = Routing.generate("plage_pyramid_share", {
                        datastoreId: datastoreId,
                        pyramidId: storedDataId,
                    });

                    actionPossibleMain = <a href={url} className="btn btn--primary btn-sm w-100">Voir</a>
                }

            } else {
                // si c'est une mise à jour d'une pyramide
                if (storedData?.tags?.initial_pyramid_id) {
                    url = Routing.generate("plage_pyramid_update_compare", {
                        datastoreId: datastoreId,
                        pyramidId: storedDataId,
                    });

                    actionPossibleMain = <a href={url} className="btn btn--plain btn--primary btn-sm w-100">Visualiser</a>
                } else {
                    url = Routing.generate("plage_pyramid_publish", {
                        datastoreId: datastoreId,
                        pyramidId: storedDataId,
                    });

                    actionPossibleMain = <a href={url} className="btn btn--plain btn--primary btn-sm w-100">Publier</a>
                }
            }
        }

    } else if (storedData?.status == 'GENERATING') {
        statusBadge = <><span className="icons-status text-warning"></span>&nbsp;En cours</>

        if (storedData?.type == 'VECTOR-DB') {
            let url = Routing.generate("plage_upload_integration", {
                datastoreId: datastoreId,
                uploadId: storedData?.input_upload_id,
            });

            actionPossibleMain = <a href={url} className="btn btn--gray btn-sm w-100">Voir l'avancement</a>
        }
        else if (storedData?.type == 'ROK4-PYRAMID-VECTOR') {
            let url = Routing.generate("plage_stored_data_report", {
                datastoreId: datastoreId,
                storedDataId: storedDataId
            });
            actionPossibleMain = <a href={url} className="btn btn--gray btn-sm w-100">Voir l'avancement</a>
        }

    } else if (storedData?.status == 'UNSTABLE') {
        statusBadge = <><span className="icons-status text-danger"></span>&nbsp;Echec</>

        let url = Routing.generate("plage_stored_data_report", {
            datastoreId: datastoreId,
            storedDataId: storedDataId
        });

        actionPossibleMain = <a href={url} className="btn btn--gray btn-sm w-100">Voir le rapport</a>
    }

    return (
        <div className="row border border-darken-1 mb-1 p-1">
            {
                isLoading ? (
                    <ContentLoader
                        speed={1}
                        width={1270}
                        height={50}
                        backgroundColor="#f3f3f3"
                        foregroundColor="#ecebeb"
                    >
                        <circle cx="20" cy="20" r="20" />
                        <rect x="50" y="16" rx="3" ry="3" width="300" height="10" />
                        <rect x="860" y="16" rx="3" ry="3" width="52" height="10" />
                        <rect x="960" y="10" rx="3" ry="3" width="170" height="25" />
                    </ContentLoader>
                ) : (
                    <>
                        <div className="col-md-6 my-auto d-flex align-items-center">
                            {dataIcon}<span>{storedData?.name}</span>{storedData?.tags?.is_sample ? (
                                <span className="ml-1 badge-echantillon" title="La pyramide de tuiles vectorielles n'a pas été générée sur l'intégralité des données">Echantillon</span>
                            ) : ('')}
                        </div>

                        <div className="col-md-2 my-auto text-gray-600">
                            {storedData?.last_event?.date_text}
                        </div>

                        <div className="col-md-1 my-auto text-gray-600 text-nowrap">
                            {statusBadge}
                        </div>

                        <div className="col-md-2 my-auto">
                            {actionPossibleMain}
                        </div>

                        <div className="col-md-1 my-auto align-self-end align-items-end btn-group">
                            {dropdownMenu}
                        </div>
                    </>
                )
            }
        </div >
    )
}

export default StoredData;
