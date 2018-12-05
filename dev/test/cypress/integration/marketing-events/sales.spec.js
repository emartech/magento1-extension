'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Marketing Events - Sales', function() {
  const createTestOrder = ({ email, password }) => {
    cy.visit('/accessories/jewelry/swing-time-earrings.html');
    cy.get('.add-to-cart-wrapper .button.btn-cart').click();
    cy.get('.page-title .button.btn-proceed-checkout.btn-checkout').click();
    cy.get('#login-email').type(email);
    cy.get('#login-password').type(password);
    cy.get('.col-2 > .buttons-set > .button').click();

    cy.get('select[id="billing:country_id"]').select('HU');
    cy.get('input[id="billing:street1"]').type('Adress street 42');
    cy.get('input[id="billing:city"]').type('Fancycity');
    cy.get('input[id="billing:postcode"]').type('1234');
    cy.get('input[id="billing:telephone"]').type('705551234');
    cy.get('#checkout-step-billing .buttons-set .button').click();

    cy.get('#s_method_flatrate_flatrate').check();
    cy.get('#checkout-step-shipping_method .buttons-set .button').click();

    cy.get('#checkout-step-payment .buttons-set .button').click();

    cy.get('#checkout-review-submit .buttons-set .button').click();
    cy.wait(100);
  };

  const fulfillLatestOrder = () => {
    cy.visit('/admin');
    cy.get('#username').type('admin');
    cy.get('#login').type('magentorocks1');
    cy.get('input[value="Login"').click();
    cy.get('.message-popup-head a').click();

    cy.get('#nav > :nth-child(2) > ul > :nth-child(1) > a').click({ force: true });
    cy.get('#sales_order_grid_table > tbody > :nth-child(1)').click();

    cy.get('.main-col-inner > .content-header > .form-buttons > button[title="Invoice"]').click();
    cy.get('.scalable.save.submit-button[title="Submit Invoice"]').click();

    cy.get('.main-col-inner > .content-header > .form-buttons > button[title="Ship"]').click();
    cy.get('.scalable.save.submit-button[title="Submit Shipment"]').click();

    cy.get('.main-col-inner > .content-header > .form-buttons > button[title="Credit Memo"]').click();
    cy.get('.scalable.save.submit-button[title="Refund Offline"]').click();
  };

  context('with collectMarketingEvents disabled', function() {
    before(() => {
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'disabled' } });
    });

    it('should not create customer_new_account_registered event', function() {
      const email = `cypress-marketing-off-sales${getUid()}@events.com`;
      const password = 'SalesEvents123';

      cy.registerCustomer({ email, password });
      createTestOrder({ email, password });
      fulfillLatestOrder();

      cy.shouldNotExistsEvents();
    });
  });

  context('with collectMarketingEvents enabled', function() {
    before(() => {
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'enabled' } });
    });

    after(() => {
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'disabled' } });
    });

    it('should create sales events', function() {
      const email = `cypress-marketing-on-sales${getUid()}@events.com`;
      const password = 'SalesEvents123';

      cy.registerCustomer({ email, password });
      createTestOrder({ email, password });
      fulfillLatestOrder();

      cy.shouldCreateEvent('sales_email_order_template', { customer_email: email });
      cy.shouldCreateEvent('sales_email_invoice_template', { addresses: { shipping: { email } } });
      cy.shouldCreateEvent('sales_email_shipment_template', { addresses: { shipping: { email } } });
      cy.shouldCreateEvent('sales_email_creditmemo_template', { addresses: { shipping: { email } } });
    });
  });
});
