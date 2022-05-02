import { Wait } from "../utils";
import { ImportStyles } from '../components/import-styles'

// VARIABLES GLOBALES
window.flash    = require("../components/flash-messages");
window.wait     = new Wait({ id: 'styles' });

let importStyles = null;
$(function() {
    let pyramidDatas = $('#map-target').data();
    importStyles = new ImportStyles(pyramidDatas);
});