import React from "react"
import ReactDOM from "react-dom"
import StoredData from "../components/react/StoredData";
import axios from "axios";
import flash from "../components/flash-messages";

var datastoreId = "";
var uploadId = "";

var integrationInterval = null;

$(function () {
    datastoreId = $('#datastore-id').data('datastore-id');
    uploadId = $('#upload-id').data('upload-id');

    postUploadIntegrationProgress();
    integrationInterval = setInterval(function () {
        postUploadIntegrationProgress();
    }, 3000);

    /**
     * AUTHENTIFICATION EXPIRATION
     */
    let onGoingRequest = false;
    let loginExpiredMsgShown = false;

    setInterval(async () => {
        if (onGoingRequest) return;

        onGoingRequest = true;
        let response = null;

        try {
            response = await axios.get(Routing.generate("plage_security_check_auth"))
            console.log(response?.data?.is_authenticated);
        } catch (error) {
            console.error(error);
        } finally {
            onGoingRequest = false;
        }

        if (!response?.data?.is_authenticated) {
            if (!loginExpiredMsgShown) {
                const url = Routing.generate("plage_security_login", { 'side_login': true });
                let flashEl = flash.flashAdd(Translator.trans('login_expired'), 'error', true);

                flashEl.find(".btn-login").on('click', function () {
                    window.open(url, '_blank');
                    flashEl.remove();
                    loginExpiredMsgShown = false;
                });
                loginExpiredMsgShown = true
            }
        }

    }, 10000);
});

function postUploadIntegrationProgress() {
    var url = Routing.generate("plage_upload_integration_progress", {
        datastoreId: datastoreId,
        uploadId: uploadId,
    });

    axios
        .post(url)
        .then((response) => {

            var allSuccess = true;
            var atLeastOneFailure = false;

            for (const [stepName, status] of Object.entries(response.data)) {
                var icon = "";

                switch (status) {
                    case "waiting":
                        icon = '<i class="icon-clock"></i>';
                        break;

                    case "in_progress":
                        icon = '<i class="icon-timer"></i>';
                        break;

                    case "success":
                        icon = '<i class="icon-check text-success"></i>';
                        break;

                    case "failure":
                        icon = '<i class="icon-close text-danger"></i>';
                        atLeastOneFailure = true;
                        break;

                    default:
                        break;
                }

                $(`#${stepName}_status`).html(icon);

                if (status != "success") {
                    allSuccess = false;
                }
            }

            if (allSuccess || atLeastOneFailure) {
                clearInterval(integrationInterval);
                $("#btn-return").removeClass('invisible')

                mountStoredDataComponent();
            }

            if (allSuccess) {
                let h2 = `<h2><i class="icons-upload-success-2x"></i><br>Votre donnée est prête</h2>`

                $(`#upload_${uploadId}_status_heading`).empty().append(h2)
            }

            if (atLeastOneFailure) {
                let h2 = `<h2><i class="icons-upload-failure-2x"></i><br>L'intégration de votre donnée a échoué</h2>`

                $(`#upload_${uploadId}_status_heading`).empty().append(h2)
            }
        })
        .catch((error) => {
            console.error(error.response);
        });
}

function mountStoredDataComponent() {
    let url = Routing.generate("plage_upload_get", {
        datastoreId: datastoreId,
        uploadId: uploadId,
    });

    axios
        .get(url)
        .then(response => {
            let upload = response.data

            if (upload?.tags?.vectordb_id == undefined) return;

            url = Routing.generate("plage_stored_data_get", {
                datastoreId: datastoreId,
                storedDataId: upload?.tags?.vectordb_id
            });

            axios
                .get(url)
                .then(response => {
                    let vectordb = response.data
                    if (vectordb?.tags?.pyramid_id) {

                        if (vectordb?.tags?.pyramid_id == undefined) return;

                        url = Routing.generate("plage_stored_data_get", {
                            datastoreId: datastoreId,
                            storedDataId: vectordb?.tags?.pyramid_id
                        });

                        axios
                            .get(url)
                            .then(response => {
                                let pyramid = response.data
                                ReactDOM.render(<StoredData datastoreId={datastoreId} storedDataId={pyramid?._id} />, document.getElementById('stored-data-target'))
                            })
                            .catch(error => {
                                console.error(error.data);
                            })

                    } else {
                        ReactDOM.render(<StoredData datastoreId={datastoreId} storedDataId={vectordb?._id} autoRefresh={true} />, document.getElementById('stored-data-target'))
                    }
                })
                .catch(error => {
                    console.error(error.data);
                })
        })
        .catch(error => {
            console.error(error.data);
        })
}
