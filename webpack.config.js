const Encore = require("@symfony/webpack-encore");

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || "dev");
}

var publicPath = "/build";

require("dotenv").config({
    path: "./.env.local",
});

if ("" !== process.env.ENCORE_PUBLIC_PATH) {
    publicPath = process.env.ENCORE_PUBLIC_PATH;
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath("public/build/")
    // public path used by the web server to access the output path
    .setPublicPath(publicPath)
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix("build/")

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry("app", "./assets/js/base.js")
    .addEntry("flash-messages", "./assets/js/components/flash-messages.js")
    .addEntry("notifications-bar", "./assets/js/components/notifications-bar.js")
    .addEntry("file-tree", "./assets/js/components/file-tree.js")
    .addEntry("datastore-dashboard", "./assets/js/pages/datastore-dashboard.js")
    .addEntry("datastore-storage", "./assets/js/pages/datastore-storage.js")
    .addEntry("upload-add", "./assets/js/pages/upload-add.js")
    .addEntry("upload-integration", "./assets/js/pages/upload-integration.js")
    .addEntry("pyramid-add", "./assets/js/pages/pyramid-add.js")
    .addEntry("pyramid-publish", "./assets/js/pages/pyramid-publish.js")
    .addEntry("pyramid-share", "./assets/js/pages/pyramid-share.js")
    .addEntry("pyramid-style", "./assets/js/pages/pyramid-style.js")
    .addEntry("pyramid-update-complete", "./assets/js/pages/pyramid-update-complete.js")
    .addEntry("pyramid-update-compare", "./assets/js/pages/pyramid-update-compare.js")
    .addEntry("pyramid-check-sample", "./assets/js/pages/pyramid-check-sample.js")
    .addEntry("report", "./assets/js/pages/report.js")
    .addEntry("viewer", "./assets/js/pages/viewer.js")
    .addEntry("doc", "./assets/js/pages/doc.js")

    .copyFiles([
        {
            from: "./assets/css/doc",
            to: "docs/css/[name].css",
        },
        {
            from: "./assets/img",
            to: "img/[path][name].[ext]",
        },
        {
            from: "./docs/user",
            to: "docs/[path][name].[ext]",
        },
        {
            from: "./data",
            to: "data/[name].[ext]",
        },
    ])

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    // .enableStimulusBridge("./assets/controllers.json")

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    .configureBabel((config) => {
        config.plugins.push("@babel/plugin-proposal-class-properties");
    })

    // enables @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = "usage";
        config.corejs = 3;
    })

    // enables Sass/SCSS support
    .enableSassLoader()
    .enableLessLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    .enableReactPreset()

    .enablePostCssLoader();

// uncomment to get integrity="..." attributes on your script & link tags
// requires WebpackEncoreBundle 1.4 or higher
//.enableIntegrityHashes(Encore.isProduction())

// uncomment if you're having problems with a jQuery plugin
//.autoProvidejQuery();

module.exports = Encore.getWebpackConfig();
