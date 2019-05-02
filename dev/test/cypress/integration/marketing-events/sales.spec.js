'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Marketing Events - Sales', function() {
  const checkoutWithProduct = () => {
    cy.visit('/accessories/jewelry/swing-time-earrings.html');
    cy.get('.add-to-cart-wrapper .button.btn-cart').click();
    cy.get('.page-title .button.btn-proceed-checkout.btn-checkout').click();
  };

  const fillBillingData = () => {
    cy.get('select[id="billing:country_id"]').select('HU');
    cy.get('input[id="billing:street1"]').type('Adress street 42');
    cy.get('input[id="billing:city"]').type('Fancycity');
    cy.get('input[id="billing:postcode"]').type('1234');
    cy.get('input[id="billing:telephone"]').type('705551234');
    cy.get('#checkout-step-billing .buttons-set .button').click();
  };

  const finishOrder = () => {
    cy.get('#s_method_flatrate_flatrate').check({ force: true });
    cy.get('#checkout-step-shipping_method .buttons-set .button').click();

    cy.get('#checkout-step-payment .buttons-set .button').click();

    cy.get('#checkout-review-submit .buttons-set .button').click();
  };

  const createOrder = ({ email, password }) => {
    checkoutWithProduct();

    cy.get('#login-email').type(email);
    cy.get('#login-password').type(password);
    cy.get('.col-2 > .buttons-set > .button').click();

    fillBillingData();
    finishOrder();
    cy.wait(1000);
  };

  const createGuestOrder = ({ email, firstName, lastName }) => {
    checkoutWithProduct();

    cy.get('#onepage-guest-register-button').click();

    cy.get('input[id="billing:firstname"]').type(firstName);
    cy.get('input[id="billing:lastname"]').type(lastName);
    cy.get('input[id="billing:email"]').type(email);

    fillBillingData();
    finishOrder();
    cy.wait(1000);
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

  const shouldCreateAllSalesEvents = eventData => {
    cy.shouldCreateEvent('sales_email_order_template', eventData);
    cy.shouldCreateEvent('sales_email_invoice_template', eventData);
    cy.shouldCreateEvent('sales_email_shipment_template', eventData);
    cy.shouldCreateEvent('sales_email_creditmemo_template', eventData);
  };

  context('with collectMarketingEvents disabled', function() {
    before(() => {
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'disabled' } });
    });

    it('should not create customer_new_account_registered event', function() {
      const email = `cypress-marketing-off-sales${getUid()}@events.com`;
      const password = 'SalesEvents123';

      cy.registerCustomer({ email, password });
      createOrder({ email, password });
      fulfillLatestOrder();

      cy.shouldNotExistsEvents();
    });

    it('should create guest sales events', function() {
      const email = `cypress-marketing-off-sales${getUid()}@guest-events.com`;
      const firstName = 'User';
      const lastName = 'Guest';

      createGuestOrder({ email, firstName, lastName });
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

    context('store is not enabled', function() {
      before(() => {
        cy.task('setConfig', {
          websiteId: 1,
          config: {
            storeSettings: []
          }
        });
      });

      after(() => {
        cy.task('setConfig', {
          websiteId: 1,
          config: {
            storeSettings: [
              {
                storeId: 0,
                slug: 'cypress-testadminslug'
              },
              {
                storeId: 1,
                slug: 'cypress-testslug'
              }
            ]
          }
        });
      });

      it('should not create event', function() {
        const email = `cypress-marketing-on-nostore-sales${getUid()}@events.com`;
        const password = 'SalesEvents123';

        cy.registerCustomer({ email, password });
        createOrder({ email, password });
        fulfillLatestOrder();

        cy.shouldNotExistsEvents(['customer_new_account_registered']);
      });
    });

    it('should create sales events', function() {
      const email = `cypress-marketing-on-sales${getUid()}@events.com`;
      const password = 'SalesEvents123';

      cy.registerCustomer({ email, password });
      createOrder({ email, password });
      fulfillLatestOrder();

      shouldCreateAllSalesEvents({ customer: { email } });
    });

    it('should create guest sales events', function() {
      const email = `cypress-marketing-on-sales${getUid()}@guest-events.com`;
      const firstName = 'User';
      const lastName = 'Guest';

      createGuestOrder({ email, firstName, lastName });
      fulfillLatestOrder();

      const guestEventData = {
        customerEmail: email,
        customerName: `${firstName} ${lastName}`,
        is_guest: '1'
      };

      shouldCreateAllSalesEvents(guestEventData);
    });
  });
});
