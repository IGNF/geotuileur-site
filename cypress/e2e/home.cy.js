describe("la page d'accueil", () => {

    it('contient comme titre principal et bouton commencer', () => {
        cy.visit('/')
        cy.get('.container-content > h1').contains("Transformez vos données géographiques en tuiles vectorielles simplement et diffusez-les n'importe où")
        cy.get('.container-content > div > a.btn').contains('Commencer').should('have.attr', 'href').and('include', '/login')
    })
})
