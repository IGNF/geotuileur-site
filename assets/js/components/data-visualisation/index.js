import axios from "axios";
import DataVisuTMS from "./DataVisuTMS";
import DataVisuWFS from "./DataVisuWFS";
import DataVisuWMTS from "./DataVisuWMTS";

var dataVisu = null;
var map = null;

$(async function () {
    if ($("#map-tms").length) {
        console.log("tms");

        map = $("#map-tms");
        let center = map.data("center").split(",");

        dataVisu = new DataVisuTMS({
            mapTarget: "map-tms",
            dataSourceUrl: map.data("tms-url").split('|')[1],
            styleUrl: await getStyleurl(
                map.data("datastore-id"),
                map.data("style-id")
            ),
            storedDataName: map.data("stored-data-name"),
            center: [parseInt(center[0]), parseInt(center[1])],
            zoom: parseInt(map.data("zoom")),
            getFeatureInfo: true,
        });
    } else if ($("#map-wmts").length) {
        console.log("wmts");

        map = $("#map-wmts");
        let center = map.data("center").split(",");

        dataVisu = new DataVisuWMTS({
            mapTarget: "map-wmts",
            dataSourceUrl: map.data("wmts-url"),
            dataLayers: map.data("wmts-layers"),
            storedDataName: map.data("stored-data-name"),
            center: [parseInt(center[0]), parseInt(center[1])],
            zoom: parseInt(map.data("zoom")),
            getFeatureInfo: false,
        });
    } else if ($("#map-wfs").length) {
        console.log("wfs");

        map = $("#map-wfs");
        let center = map.data("center").split(",");

        dataVisu = new DataVisuWFS({
            mapTarget: "map-wfs",
            dataSourceUrl: map.data("wfs-url"),
            styleUrl: map.data("style-url"),
            dataLayers: map.data("wfs-layers"),
            storedDataName: map.data("stored-data-name"),
            center: [parseInt(center[0]), parseInt(center[1])],
            zoom: parseInt(map.data("zoom")),
            getFeatureInfo: true,
        });
    } else console.log("nothing");

    $("#style_file_apply").on("click", async function () {
        const selectedStyle = $("#style_file option:selected");
        if (selectedStyle.val() == "") return;

        const selectedStyleId = selectedStyle.val();
        const styleUrl = await getStyleurl(
            map.data("datastore-id"),
            selectedStyleId
        );
        dataVisu.applyStyle(styleUrl);
    });
});

async function getStyleurl(datastoreId, annexeId) {
    let apiPlageAnnexeUrl = map.data("api-plage-annexe-url");

    // TODO

    // let url = Routing.generate("plage_ajax_get_style_annexe", {
    //     datastoreId: datastoreId,
    //     annexeId: annexeId,
    // });

    // try {
    //     const resp = await axios.get(url);
    //     return `${apiPlageAnnexeUrl}${resp?.data?.path}`;
    // } catch (error) {
    //     console.error(error);
    // }
}
