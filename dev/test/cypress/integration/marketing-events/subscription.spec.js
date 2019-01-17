'use strict';

const getUid = () => Math.round(Math.random() * 100000);

describe('Marketing Events - Subscription', function() {
  const unsubscribe = email => {
    cy.task('getSubscription', email).then(subscription => {
      cy.visit(`/newsletter/subscriber/unsubscribe?id=${subscription.subscriber_id}\
        &code=${subscription.subscriber_confirm_code}`);
    });
  };

  const subscribe = email => {
    cy.visit('/');
    cy.get('#newsletter').type(email);
    cy.get('button[title="Subscribe"]').click();
  };

  context('with collectMarketingEvents disabled', function() {
    before(() => {
      cy.task('setDoubleOptin', false);
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'disabled' } });
    });

    context('guest with double optin off', function() {
      it('should not create subscription events', function() {
        const guestEmail = `no-event.doptin-off${getUid()}@guest-cypress.com`;
        subscribe(guestEmail);

        cy.shouldNotExistsEvents();
        cy.isSubscribed(guestEmail);

        unsubscribe(guestEmail);

        cy.shouldNotExistsEvents();
        cy.isNotSubscribed(guestEmail);
      });
    });

    context('guest with double optin on', function() {
      before(() => {
        cy.task('setDoubleOptin', true);
        cy.task('setConfig', {
          websiteId: 1,
          config: { collectMarketingEvents: 'disabled', merchantId: `flushCache${getUid()}` }
        });
      });

      after(() => {
        cy.task('setDoubleOptin', false);
        cy.task('setConfig', {
          websiteId: 1,
          config: { collectMarketingEvents: 'disabled', merchantId: `flushCache${getUid()}` }
        });
      });

      it('should not create subscription events', function() {
        const guestEmail = `no-event.doptin-on${getUid()}@guest-cypress.com`;
        subscribe(guestEmail);

        cy.shouldNotExistsEvents();
        cy.isSubscribed(guestEmail, true);

        unsubscribe(guestEmail);

        cy.shouldNotExistsEvents();
        cy.isNotSubscribed(guestEmail);
      });
    });
  });

  context('with collectMarketingEvents enabled', function() {
    before(() => {
      cy.task('setDoubleOptin', false);
      cy.task('setConfig', { websiteId: 1, config: { collectMarketingEvents: 'enabled' } });
    });

    context('guest with double optin off', function() {
      it('should create subscription events', function() {
        const guestEmail = `event.doptin-off.sub${getUid()}@guest-cypress.com`;
        subscribe(guestEmail);

        cy.shouldCreateEvent('newsletter_send_confirmation_request_email', {
          subscriber: { subscriber_email: guestEmail, subscriber_status: 1 }
        });
        cy.isSubscribed(guestEmail);

        unsubscribe(guestEmail);

        // cy.shouldCreateEvent('newsletter_send_unsubscription_email', {
        //   subscriber: { subscriber_email: guestEmail }
        // });
        cy.isNotSubscribed(guestEmail);
      });
    });

    context('guest with double optin on', function() {
      before(() => {
        cy.task('setDoubleOptin', true);
        cy.task('setConfig', {
          websiteId: 1,
          config: { collectMarketingEvents: 'enabled', merchantId: `flushCache${getUid()}` }
        });
      });

      after(() => {
        cy.task('setDoubleOptin', false);
        cy.task('setConfig', {
          websiteId: 1,
          config: { collectMarketingEvents: 'disabled', merchantId: `flushCache${getUid()}` }
        });
      });

      it('should create newsletter_send_confirmation_request_email event', function() {
        const guestEmail = `event.doptin-on.sub${getUid()}@guest-cypress.com`;
        subscribe(guestEmail);

        cy.shouldCreateEvent('newsletter_send_confirmation_request_email', {
          subscriber: { subscriber_email: guestEmail, subscriber_status: 2 }
        });
        cy.isSubscribed(guestEmail, true);

        unsubscribe(guestEmail);

        // cy.shouldCreateEvent('newsletter_send_unsubscription_email', {
        //   subscriber: { subscriber_email: guestEmail }
        // });
        cy.isNotSubscribed(guestEmail);
      });
    });
  });
});
