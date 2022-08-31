const { defineConfig } = require("cypress");

module.exports = defineConfig({
    e2e: {
        setupNodeEvents(on, config) {
            // implement node event listeners here
        },
        baseUrl: "http://localhost:8080",
        experimentalSessionAndOrigin: true,
    },
    // taille d'un macbook-15
    viewportHeight: 900,
    viewportWidth: 1440
});
