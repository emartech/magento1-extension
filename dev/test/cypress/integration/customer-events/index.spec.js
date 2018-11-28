'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Customer Events', function() {
  describe('setting is on', function() {
    const email = `cypress-customer-on${getUid()}@events.com`;
    const password = 'CustomerEvents123';

    before(function() {
      cy.task('setConfig', { websiteId: 1, config: { collectCustomerEvents: 'enabled' } });
    });

    it('should add subscription/create to events for guest', function() {
      const subscriberEmail = 'cypress-customer@events.com';

      cy.visit('/');
      cy.get('#newsletter').type(subscriberEmail);
      cy.get('.button[title="Subscribe"]').click();

      cy.shouldCreateEvent('subscription/update', { subscriber_email: subscriberEmail, customer_id: 0 });
    });

    it('should add customers/update to events for new customer', function() {
      cy.registerCustomer({ email, password, subscription: true });

      cy.shouldCreateEvent('customers/update', { email: email, accepts_marketing: '1' });
    });

    it('should add customers/update to events for existing customer modified', function() {
      cy.login({ email, password });

      cy.get(':nth-child(3) > .col2-set > .col-2 > .box > .box-title > a').click();
      cy.get('#subscription').uncheck();
      cy.get('.button[title="Save"').click();

      cy.shouldCreateEvent('customers/update', { email: email, accepts_marketing: '3' });
    });
  });

  describe('setting is off', function() {
    const email = `cypress-customer-off${getUid()}@events.com`;
    const password = 'CustomerEvents123';

    before(function() {
      cy.task('setConfig', { websiteId: 1, config: { collectCustomerEvents: 'disabled' } });
    });

    it('should not add subscription/create to events for guest', function() {
      const subscriberEmail = 'cypress-customer@events.com';

      cy.visit('/');
      cy.get('#newsletter').type(subscriberEmail);
      cy.get('.button[title="Subscribe"]').click();

      cy.shouldNotExistsEvents();
    });

    it('should not add customers/update to events for new customer', function() {
      cy.registerCustomer({ email, password });

      cy.shouldNotExistsEvents();
    });

    it('should not add customers/update to events for existing customer modified', function() {
      cy.login({ email, password });

      cy.get(':nth-child(3) > .col2-set > .col-2 > .box > .box-title > a').click();
      cy.get('#subscription').uncheck();
      cy.get('.button[title="Save"').click();

      cy.shouldNotExistsEvents();
    });
  });
});
