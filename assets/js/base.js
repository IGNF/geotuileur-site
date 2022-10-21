/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
const $ = require("jquery");
global.$ = global.jQuery = $;

require("bootstrap");

// bootbox avec boutons en francais
bootbox = require('bootbox');
bootbox.setDefaults({ 'locale': 'fr' });

// jquery overlayScrollbars
require("overlayscrollbars/js/jquery.overlayScrollbars.js");

// Charte graphique IGN 2020
require("../css/style-carto.css");
require("./charte/app.js");

// Surcharge style application
require("../scss/main.scss");

require("ol/ol.css");
require("geoportal-extensions-openlayers/dist/GpPluginOpenLayers.css");
