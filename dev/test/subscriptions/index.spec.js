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
      customer_id: customerId,
      store_id: storeId
    });
  };
};

const isSubscribed = subscription => {
  return subscription !== undefined && subscription.subscriber_status === 1;
};

let customerEmail;
let customerEmail2;
let customerId;
let customerId2;
const noCustomerEmail = 'no-customer@a.com';
const noCustomerEmail2 = 'still-no-customer@a.com';
const websiteId = 1;
const storeId = 1;

const GUEST_CUSTOMER_ID = 0;
const MAGENTO_SUBSCRIBE_STATUS = 1;
const MAGENTO_UNSUBSCRIBE_STATUS = 3;

describe('Subscriptions api', function() {
  let subscriptionFor;
  let createSubscription;

  before(async function() {
    subscriptionFor = createSubscriptionGetter(this.db);
    createSubscription = createSubscriptionSetter(this.db);

    const customer2 = await this.createCustomer({
      group_id: 0,
      dob: '1977-11-12',
      email: 'subscription-tester@yolo.net',
      firstname: 'Tester',
      lastname: 'Subscription',
      store_id: storeId,
      website_id: websiteId,
      password: 'Password1234'
    });

    customerEmail = this.customer.email;
    customerId = this.customer.entityId;
    customerEmail2 = customer2.email;
    customerId2 = customer2.entityId;
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
          subscriptions: [
            {
              subscriber_email: noCustomerEmail,
              subscriber_status: true,
              website_id: websiteId,
              customer_id: GUEST_CUSTOMER_ID
            }
          ]
        });

        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;
      });

      it('should create subscription for customer', async function() {
        expect(await subscriptionFor(customerEmail)).to.be.undefined;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            { subscriber_email: customerEmail, subscriber_status: true, customer_id: customerId, website_id: websiteId }
          ]
        });

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.true;
      });

      it('should subscribe all the subscriptions', async function() {
        await createSubscription(GUEST_CUSTOMER_ID, noCustomerEmail, MAGENTO_UNSUBSCRIBE_STATUS);

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            {
              subscriber_email: customerEmail,
              subscriber_status: true,
              customer_id: customerId,
              website_id: websiteId
            },
            {
              subscriber_email: customerEmail2,
              subscriber_status: true,
              customer_id: customerId2,
              website_id: websiteId
            },
            {
              subscriber_email: noCustomerEmail,
              subscriber_status: true,
              customer_id: GUEST_CUSTOMER_ID,
              website_id: websiteId
            }
          ]
        });

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.true;
        expect(isSubscribed(await subscriptionFor(customerEmail2))).to.be.true;
        expect(isSubscribed(await subscriptionFor(noCustomerEmail))).to.be.true;
      });
    });

    describe('unsubscribe', function() {
      it('should not create unsubscribed record if it did not exist', async function() {
        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            {
              subscriber_email: noCustomerEmail,
              subscriber_status: false,
              customer_id: GUEST_CUSTOMER_ID,
              website_id: websiteId
            }
          ]
        });

        expect(await subscriptionFor(noCustomerEmail)).to.be.undefined;
      });

      it('should update unsubscribed record for guest', async function() {
        await createSubscription(GUEST_CUSTOMER_ID, noCustomerEmail, MAGENTO_SUBSCRIBE_STATUS);
        expect(isSubscribed(await subscriptionFor(noCustomerEmail))).to.be.true;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            {
              subscriber_email: noCustomerEmail,
              subscriber_status: false,
              customer_id: GUEST_CUSTOMER_ID,
              website_id: websiteId
            }
          ]
        });

        expect(isSubscribed(await subscriptionFor(noCustomerEmail))).to.be.false;
      });

      it('should unsubscribe', async function() {
        await createSubscription(customerId, customerEmail, MAGENTO_SUBSCRIBE_STATUS);

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.true;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            {
              subscriber_email: customerEmail,
              subscriber_status: false,
              customer_id: customerId,
              website_id: websiteId
            }
          ]
        });

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.false;
      });

      it('should unsubscribe all the subscriptions', async function() {
        await createSubscription(customerId, customerEmail, MAGENTO_SUBSCRIBE_STATUS);
        await createSubscription(customerId2, customerEmail2, MAGENTO_SUBSCRIBE_STATUS);
        await createSubscription(GUEST_CUSTOMER_ID, noCustomerEmail, MAGENTO_SUBSCRIBE_STATUS);

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.true;
        expect(isSubscribed(await subscriptionFor(customerEmail2))).to.be.true;
        expect(isSubscribed(await subscriptionFor(noCustomerEmail))).to.be.true;

        await this.magentoApi.execute('subscriptions', 'update', {
          subscriptions: [
            {
              subscriber_email: customerEmail,
              subscriber_status: false,
              customer_id: customerId,
              website_id: websiteId
            },
            {
              subscriber_email: customerEmail2,
              subscriber_status: false,
              customer_id: customerId2,
              website_id: websiteId
            },
            {
              subscriber_email: noCustomerEmail,
              subscriber_status: false,
              customer_id: GUEST_CUSTOMER_ID,
              website_id: websiteId
            }
          ]
        });

        expect(isSubscribed(await subscriptionFor(customerEmail))).to.be.false;
        expect(isSubscribed(await subscriptionFor(customerEmail2))).to.be.false;
        expect(isSubscribed(await subscriptionFor(noCustomerEmail))).to.be.false;
      });
    });
  });
  describe('get', function() {
    before(async function() {
      await this.db('newsletter_subscriber').delete();

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
            customer_id: GUEST_CUSTOMER_ID,
            store_id: storeId,
            subscriber_email: noCustomerEmail,
            subscriber_status: '1',
            website_id: websiteId
          },
          {
            customer_id: GUEST_CUSTOMER_ID,
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
            customer_id: GUEST_CUSTOMER_ID,
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
            customer_id: GUEST_CUSTOMER_ID,
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
            customer_id: GUEST_CUSTOMER_ID,
            store_id: storeId,
            subscriber_email: noCustomerEmail,
            subscriber_status: '1'
          },
          {
            customer_id: GUEST_CUSTOMER_ID,
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
