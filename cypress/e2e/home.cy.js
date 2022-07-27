// TODO revoir comment structurer les tests (describe, context, it)

describe("la page d'accueil", () => {
    it('contient comme titre principal', () => {
        cy.visit('/')
        cy.get('.container-content > h1').contains("Transformez vos données géographiques en tuiles vectorielles simplement et diffusez-les n'importe où")
    })

    context('utilisateur non-connecté', () => {
        it('contient comme bouton commencer', () => {
            cy.visit('/')
            cy.get('.container-content > div > a.btn').as('btn-commencer').contains('Commencer').should('have.attr', 'href').and('include', '/login')
        })
    })

    context('utilisateur de test connecté', () => {
        it('contient comme bouton commencer', () => {
            cy.fakeLogin()

            cy.visit('/')
            cy.get('.container-content > div > a.btn').as('btn-commencer').contains('Commencer').should('have.attr', 'href').and('include', '/datastores')

            cy.get('@btn-commencer').click()
            cy.url().should('include', '/datastores')
        })
    })
})
