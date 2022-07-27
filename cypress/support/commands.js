// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
Cypress.Commands.add('fakeLogin', () => {
    cy.session("simulation d'une connexion", () => {
        cy.setCookie('MOCKSESSID', 'd398fccecfa5fb0b0337e3b55f70daeeb6067445b225c0050164214467864ca0')
    })
})

Cypress.Commands.add('fakeLogout', () => {
    cy.session("simulation d'une déconnexion", () => {
        cy.clearCookie('MOCKSESSID')
    })
})
