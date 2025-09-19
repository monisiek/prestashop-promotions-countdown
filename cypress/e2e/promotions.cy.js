describe('Promotions Countdown', () => {
  beforeEach(() => {
    // Visita la homepage
    cy.visit('/')
  })

  it('should show countdown promotion when it\'s the best', () => {
    // Crea una promozione countdown del 15%
    cy.createPromotion({
      name: 'Test Countdown 15%',
      discount: 15,
      products: [1, 2, 3]
    })
    
    // Crea una SpecificPrice del 10% (inferiore)
    cy.createSpecificPrice({
      product: 1,
      discount: 10
    })
    
    // Visita la pagina del prodotto
    cy.visit('/prodotto-test-1.html')
    
    // Verifica che mostri la promozione countdown
    cy.get('.promotion-price-discount').should('be.visible')
    cy.get('.discount-percent').should('contain', '15%')
    cy.get('.promotion-banner').should('be.visible')
  })

  it('should NOT show countdown promotion when other is better', () => {
    // Crea una promozione countdown del 15%
    cy.createPromotion({
      name: 'Test Countdown 15%',
      discount: 15,
      products: [1, 2, 3]
    })
    
    // Crea una SpecificPrice del 40% (superiore)
    cy.createSpecificPrice({
      product: 1,
      discount: 40
    })
    
    // Visita la pagina del prodotto
    cy.visit('/prodotto-test-1.html')
    
    // Verifica che NON mostri la promozione countdown
    cy.get('.promotion-price-discount').should('not.exist')
    cy.get('.promotion-banner').should('not.exist')
    
    // Verifica che mostri la promozione migliore (40%)
    cy.get('.product-price').should('contain', '60.00') // 100 - 40%
  })

  it('should apply correct discount in cart', () => {
    // Setup: promozione countdown 15% vs SpecificPrice 40%
    cy.setupPromotionScenario({
      countdown: { discount: 15, products: [1] },
      specificPrice: { discount: 40, product: 1 }
    })
    
    // Aggiungi prodotto al carrello
    cy.visit('/prodotto-test-1.html')
    cy.get('#add-to-cart').click()
    
    // Verifica il carrello
    cy.visit('/carrello')
    cy.get('.cart-item-price').should('contain', '60.00') // Prezzo con 40% di sconto
    cy.get('.cart-item-discount').should('not.contain', '15%') // Non deve mostrare 15%
  })

  it('should show promotion in product list', () => {
    // Setup: solo promozione countdown 20%
    cy.setupPromotionScenario({
      countdown: { discount: 20, products: [1, 2, 3] }
    })
    
    // Visita la categoria
    cy.visit('/categoria-test.html')
    
    // Verifica che mostri le promozioni sui prodotti
    cy.get('.product-item').each(($item) => {
      cy.wrap($item).within(() => {
        cy.get('.promotion-banner').should('be.visible')
        cy.get('.discount-percent').should('contain', '20%')
      })
    })
  })
})
