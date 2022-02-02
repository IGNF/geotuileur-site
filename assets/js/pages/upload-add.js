const { fileUpload } = require("../components/file-upload");

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
});
