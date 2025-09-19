// Comandi personalizzati per Cypress

Cypress.Commands.add('createPromotion', (promotion) => {
  cy.request({
    method: 'POST',
    url: '/admin/promotions/create',
    body: {
      name: promotion.name,
      discount_percent: promotion.discount,
      start_date: '2024-01-01 00:00:00',
      end_date: '2024-12-31 23:59:59',
      products: promotion.products
    }
  })
})

Cypress.Commands.add('createSpecificPrice', (specificPrice) => {
  cy.request({
    method: 'POST',
    url: '/admin/specific-prices/create',
    body: {
      product_id: specificPrice.product,
      reduction_type: 'percentage',
      reduction: specificPrice.discount / 100,
      from: '2024-01-01 00:00:00',
      to: '2024-12-31 23:59:59'
    }
  })
})

Cypress.Commands.add('setupPromotionScenario', (scenario) => {
  // Pulisci le promozioni esistenti
  cy.request('DELETE', '/admin/promotions/clear')
  cy.request('DELETE', '/admin/specific-prices/clear')
  
  // Crea le promozioni secondo lo scenario
  if (scenario.countdown) {
    cy.createPromotion(scenario.countdown)
  }
  
  if (scenario.specificPrice) {
    cy.createSpecificPrice(scenario.specificPrice)
  }
  
  // Attiva il modulo
  cy.request('POST', '/admin/modules/enable', { module: 'promotionscountdown' })
})
