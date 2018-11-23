'use strict';

const chaiSubset = require('chai-subset');
chai.use(chaiSubset);

Cypress.Commands.add('shouldCreateEvent', (type, expectedDataSubset) => {
  cy.task('getEventTypeFromDb', type).then((event) => {
    expect(event).to.not.null;
    expect(event.event_data).to.containSubset(expectedDataSubset);
  });
});

Cypress.Commands.add('shouldNotExistsEvents', () => {
  cy.task('getAllEvents').then((events) => {
    expect(events.length).to.be.empty;
  });
});

Cypress.Commands.add('registerCustomer', ({ email, password }) => {
  cy.visit('/customer/account/create/');
  cy.get('#firstname').type('Customer');
  cy.get('#lastname').type('Events');
  cy.get('#email_address').type(email);
  cy.get('#password').type(password);
  cy.get('#confirmation').type(password);
  cy.get('#is_subscribed').check();

  cy.get('.button[title="Register"]').click();

  cy.get('.success-msg');
});

// Cypress.Commands.add('logout', () => {
//   cy.visit('/');
//   cy.get('.customer-name').click();

//   cy.contains('Sign Out').click();
// });

// Cypress.Commands.add('shouldNotShowErrorMessage', (excludeErrorMessage) => {
//   if (excludeErrorMessage) {
//     return cy.get('[data-ui-id="message-error"]').invoke('text').should('contain', excludeErrorMessage);
//   } else {
//     return cy.get('[data-ui-id="message-error"]').should('not.be.visible');
//   }
// });

Cypress.Commands.add('clog', (logObject) => {
  cy.task('log', logObject);
});

// Cypress.Commands.add('isSubscribed', (email, doubleOptin) => {
//   const expectedStatus = doubleOptin ? 2 : 1;
//   cy.task('getSubscription', email).then((subscription) => {
//     expect(subscription.subscriber_status).to.be.equal(expectedStatus);
//   });
// });

// Cypress.Commands.add('isNotSubscribed', (email) => {
//   cy.task('getSubscription', email).then((subscription) => {
//     expect(subscription.subscriber_status).to.not.equal(1);
//     expect(subscription.subscriber_status).to.not.equal(2);
//   });
// });
