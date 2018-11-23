'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Subscription Customer Events', function() {
  describe('setting is on', function() {
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
      const email = `cypress-customer-on${getUid()}@events.com`;
      const password = 'CustomerEvents123';

      cy.registerCustomer({ email, password });

      cy.shouldCreateEvent('customers/update', { email: email, accepts_marketing: '1' });
    });
  });

  describe('setting is off', function() {
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
      const email = `cypress-customer-off${getUid()}@events.com`;
      const password = 'CustomerEvents123';

      cy.registerCustomer({ email, password });

      cy.shouldNotExistsEvents();
    });
  });
});
