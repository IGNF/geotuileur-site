const flash = require("./flash-messages");
const { Progress } = require("./progress");

require("blueimp-file-upload/css/jquery.fileupload.css");
require("blueimp-file-upload/css/jquery.fileupload-ui.css");
require("blueimp-file-upload/js/jquery.fileupload.js");

const ACCEPTED_EXTENSIONS = ["zip", "csv", "gpkg"];

let progress = new Progress($("#progress-upload"));

export function fileUpload(
    uploadElementId,
    onSend,
    onDone,
    onFail,
    extensions = ACCEPTED_EXTENSIONS
) {
    $(`#${uploadElementId}`).fileupload({
        dropZone: "#dropzone",
        autoUpload: false,
        maxChunkSize: 16000000, // 16 MB
        maxFileSize: 2000000000, // 2 GB
        send: function (e, data) {
            progress.show();

            let filename = data.files[0].name;
            let fileExtension = filename.split(".").pop().toLowerCase();
            if (!extensions.includes(fileExtension)) {
                flash.flashAdd(
                    `Le type du fichier [${filename}] n'est pas correct`
                );
                return false;
            }
            $(".custom-file-label").html(filename);

            onSend();
        },
        add: function (e, data) {
            data.submit();
        },
        progress: function (e, data) {
            progress.setProgression(data.loaded, data.total);
        },
        done: function (e, data) {
            progress.hide();
            let filename = data.files[0].name;

            var response = JSON.parse(data.response().result);
            if (response.status === "ERROR") {
                flash.flashAdd(
                    `Le fichier ${filename} n'est pas conforme. ${response.error}`,
                    "danger"
                );
                return;
            }
            onDone(response, filename);
        },
        fail: function (e, data) {
            progress.hide();
            flash.flashAdd("le téléversement a échoué.", "danger");
            onFail();
        },
    });
}
