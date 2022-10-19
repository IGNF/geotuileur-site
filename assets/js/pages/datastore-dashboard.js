import axios from "axios"
import React, { useEffect, useRef, useState } from "react"
import ReactDOM from "react-dom"
import { flashAdd } from "../components/flash-messages"
import ActionsRequiredSection from "../components/react/ActionsRequiredSection"
import InProgressSection from "../components/react/InProgressSection"
import PublishedPyramidsSection from "../components/react/PublishedPyramidsSection"

const DatastoreDashboard = ({ datastoreId }) => {
    const browserTabActive = useRef(true);
    const onGoingRequest = useRef(false);

    const [actionsRequired, setActionsRequired] = useState([])
    const [inProgress, setInProgress] = useState([])
    const [publishedPyramids, setPublishedPyramids] = useState([])
    const [isLoading, setIsLoading] = useState(true)

    let refreshInterval = null;

    const getDashboardData = async () => {
        if (!browserTabActive.current) { // passer parce que l'onglet du navigateur ou la fenêtre n'est pas active
            return;
        }

        if (onGoingRequest.current) { // passer parce qu'une requête est déjà en cours
            return;
        }

        try {
            onGoingRequest.current = true;
            const response = await axios.get(Routing.generate("plage_security_check_auth"))

            if (!response?.data?.is_authenticated) {
                flashAdd("Votre authentification a expirée, veuillez rafraîchir la page et vous reconnecter")

                var confirmResult = confirm("Votre connexion a expirée. Voulez-vous vous reconnecter ?");
                if (confirmResult == true) {
                    const url = Routing.generate("plage_security_login", { 'side_login': true });
                    window.open(url, '_blank');
                }
                return;
            }
        } catch (error) {
            console.error(error);
        } finally {
            onGoingRequest.current = false;
        }

        let url = Routing.generate("plage_datastore_get_dashboard_data", {
            datastoreId: datastoreId,
        });

        onGoingRequest.current = true;
        axios.
            get(url)
            .then(response => {
                setActionsRequired(response?.data?.actions_required)
                setInProgress(response?.data?.in_progress)
                setPublishedPyramids(response?.data?.published_pyramids)
            })
            .catch(error => console.error(error))
            .finally(() => {
                // délibération du vérrou à la fin de la requête (succès ou echéc)
                setIsLoading(false);
                onGoingRequest.current = false;
            });
    }

    useEffect(() => {
        getDashboardData()

        // listener pour savoir si l'onglet est actif ou pas
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                browserTabActive.current = false;
            } else {
                browserTabActive.current = true;
            }
        });

        refreshInterval = setInterval(() => {
            // document.hasFocus()
            getDashboardData()
        }, 5000)

        return () => {
            clearInterval(refreshInterval);
        }
    }, [])

    return (
        <>
            {
                isLoading ? (
                    <h1 className="text-center text-dark mt-5"><i className="fas fa-spinner fa-spin"></i></h1>
                ) : (
                    <>
                        <ActionsRequiredSection datastoreId={datastoreId} storedDataList={actionsRequired} />
                        <InProgressSection datastoreId={datastoreId} storedDataList={inProgress} />
                        <PublishedPyramidsSection datastoreId={datastoreId} storedDataList={publishedPyramids} />
                    </>
                )
            }
        </>
    )
}

$(function () {
    let datastoreId = $('#datastore-id').data('datastore-id');
    ReactDOM.render(<DatastoreDashboard datastoreId={datastoreId} />, document.getElementById('datastore-dashboard-content'))
})
