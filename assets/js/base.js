/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.css in this case)
import $ from "jquery";
window.$ = window.jQuery = $;

import 'bootstrap';

// bootbox avec boutons en francais
import bootbox from 'bootbox'
window.bootbox = bootbox.setDefaults({ 'locale': 'fr' });

// jquery overlayScrollbars
import "overlayscrollbars/js/jquery.overlayScrollbars.js";

// Charte graphique IGN 2020
import "../css/style-carto.css";
import "./charte/app.js";

// Surcharge style application
import "../scss/main.scss";

import "ol/ol.css";
import "geoportal-extensions-openlayers/dist/GpPluginOpenLayers.css";
