'use strict';

const createSubscriptionGetter = db => {
  return async email => {
    return await db
      .select()
      .from('newsletter_subscriber')
      .where({ subscriber_email: email })
      .first();
  };
};

const createSubscriptionSetter = db => {
  return async (customerId, email, status) => {
    return await db('newsletter_subscriber').insert({
      subscriber_email: email,
      subscriber_status: status,
      customer_id: customerId
    });
  };
};

const isSubscribed = subscription => {
  return subscription !== undefined && subscription.subscriber_status === 1;
};

const noCustomerEmail = 'no-customer@a.com';
const noCustomerEmail2 = 'still-no-customer@a.com';
const customerEmail = 'roni_cost@example.com';
const customerId = 1;
const websiteId = 1;
const storeId = 1;

describe('Subscriptions api', function() {
  let subscriptionFor;
  let createSubscription;

  before(function() {
    subscriptionFor = createSubscriptionGetter(this.db);
    createSubscription = createSubscriptionSetter(this.db);
  });

  describe('update', function() {
    afterEach(async function() {
      await this.db('newsletter_subscriber')
        .whereIn('subscriber_email', [noCustomerEmail, customerEmail])
        .delete();
    });

    describe('subscribe', function() {
      it('should not set subscription if it did not exist before', async function() {
        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [{ subscriber_email: noCustomerEmail, subscriber_status: true }]
        });
        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;
      });
      it('should update subscription if it exist', async function() {
        await createSubscription(customerId, customerEmail, 3);

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [{ subscriber_email: customerEmail, subscriber_status: true }]
        });
        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.true;
      });
    });

    describe('unsubscribe', function() {
      it('should not create unsubscribed record if it did not exist', async function() {
        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [{ subscriber_email: noCustomerEmail, subscriber_status: false }]
        });

        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;
      });
      it('should unsubscribe', async function() {
        await createSubscription(customerId, customerEmail, 1);
        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [{ subscriber_email: customerEmail, subscriber_status: false, customer_id: customerId }]
        });

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.false;
      });
    });
  });
  describe('get', function() {
    let customerEmail2;
    let customerId2;

    before(async function() {
      await this.db('newsletter_subscriber').delete();

      customerEmail2 = this.customer.email;
      customerId2 = this.customer.entityId;

      await this.db
        .insert([
          { subscriber_email: noCustomerEmail, subscriber_status: 1, store_id: storeId },
          { subscriber_email: noCustomerEmail2, subscriber_status: 3, store_id: storeId },
          { subscriber_email: customerEmail, subscriber_status: 1, customer_id: customerId, store_id: storeId },
          {
            subscriber_email: customerEmail2,
            subscriber_status: 3,
            customer_id: customerId2,
            store_id: storeId
          }
        ])
        .into('newsletter_subscriber');
    });

    it('should list all subscriber without filters', async function() {
      const expectedSubscriptions = {
        subscriptions: [
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail,
            subscriber_status: '1',
            website_id: websiteId
          },
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail2,
            subscriber_status: '3',
            website_id: websiteId
          },
          {
            customer_id: customerId,
            store_id: storeId,
            subscriber_email: customerEmail,
            subscriber_status: '1',
            website_id: websiteId
          },
          {
            customer_id: customerId2,
            store_id: storeId,
            subscriber_email: customerEmail2,
            subscriber_status: '3',
            website_id: websiteId
          }
        ],
        total_count: 4
      };

      const actualSubscriptions = await this.magentoApi.execute('subscriptions', 'list', { websiteId });

      expect(actualSubscriptions.total_count).to.be.eql(expectedSubscriptions.total_count);
      expect(actualSubscriptions.subscriptions).to.containSubset(expectedSubscriptions.subscriptions);
    });

    it('should filter with subscribed status true', async function() {
      const expectedSubscriptions = {
        subscriptions: [
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail,
            subscriber_status: '1',
            website_id: websiteId
          },
          {
            customer_id: customerId,
            store_id: storeId,
            subscriber_email: customerEmail,
            subscriber_status: '1',
            website_id: websiteId
          }
        ],
        total_count: 2,
        current_page: 1,
        last_page: 1,
        page_size: 1000
      };

      const actualSubscriptions = await this.magentoApi.execute('subscriptions', 'list', {
        subscribed: true,
        websiteId
      });

      expect(actualSubscriptions.subscriptions).to.be.containSubset(expectedSubscriptions.subscriptions);
    });

    it('should filter with subscribed false', async function() {
      const expectedSubscriptions = {
        subscriptions: [
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail2,
            subscriber_status: '3',
            website_id: websiteId
          },
          {
            customer_id: customerId2,
            store_id: storeId,
            subscriber_email: customerEmail2,
            subscriber_status: '3',
            website_id: websiteId
          }
        ],
        total_count: 2,
        current_page: 1,
        last_page: 1,
        page_size: 1000
      };

      const actualSubscriptions = await this.magentoApi.execute('subscriptions', 'list', {
        subscribed: false,
        onlyGuest: false,
        websiteId
      });

      expect(actualSubscriptions.subscriptions).to.be.containSubset(expectedSubscriptions.subscriptions);
    });

    it('should filter for not customers', async function() {
      const expectedSubscriptions = {
        subscriptions: [
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail,
            subscriber_status: '1'
          },
          {
            customer_id: 0,
            store_id: storeId,
            subscriber_email: noCustomerEmail2,
            subscriber_status: '3'
          }
        ],
        total_count: 2,
        current_page: 1,
        last_page: 1,
        page_size: 1000
      };

      const actualSubscriptions = await this.magentoApi.execute('subscriptions', 'list', {
        onlyGuest: true,
        websiteId
      });

      expect(actualSubscriptions.total_count).to.be.eql(expectedSubscriptions.total_count);
      expect(actualSubscriptions.subscriptions).to.containSubset(expectedSubscriptions.subscriptions);
    });
  });
});
