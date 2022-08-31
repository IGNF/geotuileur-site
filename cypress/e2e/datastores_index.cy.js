describe('la page de liste de datastores', () => {
    it("contient bien la liste d'espaces de travail", () => {
        cy.fakeLogin()
        cy.visit('/datastores')

        cy.get('.mea-container').children().should('have.length', 2)

        cy.get('.mea-container').children().each(($el, index, $list) => {
            cy.wrap($el).children().first().should('have.attr', 'href').and('match', /\/datastores\/[a-z0-9]{24}/gm)
        })

        cy.get('.mea-container').children().last().children().first().get('h3').should('contain', "Testez le service sur l'espace de test")
    })
})
