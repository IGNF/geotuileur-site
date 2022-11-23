import { ImportStyles } from '../components/import-styles'

$(function () {
    let pyramidData = $('#map-target').data();
    new ImportStyles(pyramidData);
});
