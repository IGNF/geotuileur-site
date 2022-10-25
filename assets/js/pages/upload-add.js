const { fileUpload } = require("../components/file-upload");
import flash from "../components/flash-messages";
import axios from 'axios';

$(function () {
    function onSend() {
        // On cache certains champs du formulaire
        $(".hidden-part").hide();

        $("#upload_pyramid_name").val("");
        $("#upload_file_data").val("");
    }

    function onDone(response, filename) {
        // Date d'aujourd'hui
        const date = new Date();
        let today = date.toISOString().split("T")[0];

        let parts = filename.split(".");
        let name = [parts[0], parts[1], today].join(" ");

        $("#upload_file_data").val(response.filename);
        $("#upload_pyramid_name").val(name);
        $("#upload_pyramid_name").parent().addClass('focused')
        if (response.srid) {
            $('#upload_srs').val(response.srid);
        }

        // Affichage des autres champs du formulaire
        $(".hidden-part").show();
    }

    function onFail() {
        $(".hidden-part").hide();
    }

    fileUpload("upload_file", onSend, onDone, onFail);

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
