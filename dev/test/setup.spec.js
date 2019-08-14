'use strict';

const chai = require('chai');
const chaiString = require('chai-string');
const chaiSubset = require('chai-subset');
const sinon = require('sinon');
const sinonChai = require('sinon-chai');
const knex = require('knex');
const DbCleaner = require('./db-cleaner');
const MagentoApiClient = require('@emartech/magento2-api');
const MagentoXmlRpcApiClient = require('./rpc');

chai.use(chaiString);
chai.use(chaiSubset);
chai.use(sinonChai);
global.expect = chai.expect;

const createCustomer = (xmlRpcApi, db) => async customer => {
  await xmlRpcApi.execute('customer', 'create', { customerData: customer });

  const { entity_id: entityId } = await db
    .select('entity_id')
    .from('customer_entity')
    .where({ email: customer.email })
    .first();

  return Object.assign({}, customer, { entityId });
};

const setSpecialPrice = xmlRpcApi => async ({ sku, specialPrice }) => {
  return await xmlRpcApi.execute('catalogProduct', 'setSpecialPrice', {
    id: sku,
    specialPrice,
    from: new Date(2001, 1, 1),
    to: new Date(2048, 1, 1)
  });
};

const setDefaultConfig = magentoApi => async websiteId => {
  return await magentoApi.execute('config', 'set', {
    websiteId,
    config: {
      collectCustomerEvents: 'disabled',
      collectSalesEvents: 'disabled',
      collectMarketingEvents: 'disabled',
      injectSnippet: 'disabled',
      merchantId: '',
      webTrackingSnippetUrl: 'https://path/to/snippet'
    }
  });
};

const setDefaultStoreSettings = magentoApi => async websiteId => {
  return await magentoApi.execute('config', 'set', {
    websiteId,
    config: {
      storeSettings: [
        {
          storeId: 0,
          slug: 'testadminslug'
        },
        {
          storeId: 1,
          slug: 'testslug'
        }
      ]
    }
  });
};

const clearStoreSettings = magentoApi => async websiteId => {
  return await magentoApi.execute('config', 'set', {
    websiteId,
    config: {
      storeSettings: []
    }
  });
};

before(async function() {
  this.timeout(30000);
  this.db = knex({
    client: 'mysql',
    connection: {
      host: process.env.MYSQL_HOST,
      user: process.env.MYSQL_USER,
      password: process.env.MYSQL_PASSWORD,
      database: process.env.MYSQL_DATABASE
    }
  });

  this.dbCleaner = DbCleaner.create(this.db);

  const result = await this.db
    .select('value')
    .from('core_config_data')
    .where({ path: 'emartech_emarsys/general/connecttoken' })
    .first();
  this.hostname = process.env.MAGENTO_HOST;
  this.token = result.value;
  console.log('host', this.hostname);
  console.log('Token: ' + this.token);

  this.xmlRpcApi = await MagentoXmlRpcApiClient.create();

  this.magentoApi = new MagentoApiClient({
    baseUrl: `http://${this.hostname}`,
    token: this.token,
    platform: 'magento1'
  });

  this.setDefaultConfig = setDefaultConfig(this.magentoApi);
  this.clearStoreSettings = clearStoreSettings(this.magentoApi);
  this.setDefaultStoreSettings = setDefaultStoreSettings(this.magentoApi);
  this.setDefaultStoreSettings(1);

  if (!process.env.QUICK_TEST) {
    this.createCustomer = createCustomer(this.xmlRpcApi, this.db);
    this.setSpecialPrice = setSpecialPrice(this.xmlRpcApi);

    try {
      this.customer = await this.createCustomer({
        group_id: 0,
        dob: '1977-11-12',
        email: 'default@yolo.net',
        firstname: 'Yolo',
        lastname: 'Default',
        store_id: 1,
        website_id: 1,
        password: 'Password1234'
      });
    } catch (e) {
      console.log('error', e);
    }
  }
});

beforeEach(async function() {
  this.sinon = sinon;
  this.sandbox = sinon.createSandbox();
});

afterEach(async function() {
  this.sandbox.restore();
  await this.dbCleaner.resetEmarsysEventsData();
});
