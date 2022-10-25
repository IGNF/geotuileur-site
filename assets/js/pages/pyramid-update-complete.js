const { fileUpload } = require("../components/file-upload");
import flash from "../components/flash-messages";
import axios from 'axios';


$(function () {
    function onSend() {
        // On cache certains champs du formulaire
        $(".hidden-part").hide();
    }

    function onDone(response, filename) {
        $("#update_pyramid_file_data").val(response.filename);
        // Affichage des autres champs du formulaire
        $(".hidden-part").show();
    }

    function onFail() {
        $(".hidden-part").hide();
    }

    fileUpload("update_pyramid_file", onSend, onDone, onFail);

    $('#update_pyramid_pyramid_id').on('change', function () {
        setDefaultValues();
    })
    setDefaultValues();

    /**
     * AUTHENTIFICATION EXPIRATION
     */
    let onGoingRequest = false;
    let loginExpiredMsgShown = false;

    setInterval(async () => {
        if (onGoingRequest) return;

        onGoingRequest = true;
        let response = {
            data: { is_authenticated: true }
        }

        try {
            response = await axios.get(Routing.generate("plage_security_check_auth"))
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

// saisir automatiquement le nom et la projection
function setDefaultValues() {
    let storedDataId = $('#update_pyramid_pyramid_id').val();
    let datastoreId = $('#datastore-id').data('datastore-id');

    if (storedDataId.length == 0) return;

    let url = Routing.generate("plage_stored_data_get", {
        datastoreId: datastoreId,
        storedDataId: storedDataId
    });

    fetch(url)
        .then(response => response.json())
        .then(response => {
            $('#update_pyramid_name').focus();
            $('#update_pyramid_name').val(response.name + ' maj');

            // $(`#update_pyramid_srs option[value="${response.srs}"]`).prop('selected', true);
            // $(`#update_pyramid_srs > option[value="${response.srs}"]`).prop("selected", true);
            // $('#update_pyramid_srs option:selected').attr("selected", null);
            // $(`#update_pyramid_srs > option[value="${response.srs}"]`).attr("selected", "selected");

            $(`#update_pyramid_srs option[value="${response.srs}"]`).prop('selected', true); // TODO ça marche pas
        })
        .catch(error => {
            console.error(error)
        });
}
