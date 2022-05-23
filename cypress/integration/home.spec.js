describe("Test", () => {

    beforeEach(() => {
        // cy.disableSameSiteCookieRestrictions()
        Cypress.Cookies.preserveOnce('samesite')
        cy.setCookie('samesite', 'lax')
        Cypress.Cookies.defaults({
            preserve: "session_id"
        })

        cy.intercept('*')
    });

    it('tests home page', () => {
        cy.visit('/')
    })

    // it('tests datastore list page', () => {

    //     Cypress.Cookies.debug(true)

    //     // cy.intercept('/login', (req) => {

    //     //     req.redirect('/login/check?session_state=fe8465cd-1a65-4933-ba9b-b50489722f9d&code=525675cc-2b97-443e-90d5-374f609ad621.fe8465cd-1a65-4933-ba9b-b50489722f9d.5ee70d1e-bd79-4d85-834f-1606c87e5f8a')
    //     //     cy.log(req)
    //     // }).as('login')

    //     cy.visit('/datastores')

    //     // cy.wait('@login').then((interception) => {
    //     //     cy.log(interception)
    //     // })


    //     // cy.intercept('POST', `${iamUrl}/token`, (req) => {
    //     //     req.reply(accessTokenResponse)
    //     // })

    //     // cy.intercept('POST', `${iamUrl}/userinfo`, (req) => {
    //     //     req.reply(userInfoResponse)
    //     // })


    // })
})
