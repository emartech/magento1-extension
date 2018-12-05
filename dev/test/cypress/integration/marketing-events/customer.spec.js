'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Marketing Events - Customer', function() {
  context('with collectMarketingEvents disabled', function() {
    before(() => {
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'disabled' } });
    });

    it('should not create customer_new_account_registered event', function() {
      const email = `cypress-marketing-off${getUid()}@events.com`;
      const password = 'CustomerEvents123';

      cy.registerCustomer({ email, password, subscription: true });

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

    it('should create customer_new_account_registered event', function() {
      const email = `cypress-marketing-on${getUid()}@events.com`;
      const password = 'CustomerEvents123';

      cy.registerCustomer({ email, password, subscription: true });

      cy.shouldCreateEvent('customer_new_account_registered', { email: email });
    });
  });
});
