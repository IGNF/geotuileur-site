import React from "react"
import ReactDOM from "react-dom"
import StoredData from "../components/react/StoredData";
import axios from "axios";

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
});

function postUploadIntegrationProgress() {
    var url = Routing.generate("plage_upload_integration_progress", {
        datastoreId: datastoreId,
        uploadId: uploadId,
    });

    axios
        .post(url)
        .then((response) => {
            console.log(response.data);

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
                        icon = '<i class="icon-check"></i>';
                        break;

                    case "failure":
                        icon = '<i class="icon-close"></i>';
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
                let h2 = `<h2><i class="icon-layers"></i><br>Votre donnée est prête</h2>`

                $(`#upload_${uploadId}_status_heading`).empty().append(h2)
            }

            if (atLeastOneFailure) {
                let h2 = `<h2><i class="icon-layers"></i><br>Intégration de votre donnée a échoué</h2>`

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

            url = Routing.generate("plage_stored_data_get", {
                datastoreId: datastoreId,
                storedDataId: upload?.tags?.vectordb_id
            });

            axios
                .get(url)
                .then(response => {
                    let vectordb = response.data
                    if (vectordb?.tags?.pyramid_id) {

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
