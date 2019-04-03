'use strict';

const knex = require('knex');
const Magento2ApiClient = require('@emartech/magento2-api');

const getDbConnectionConfig = () => {
  if (process.env.CYPRESS_baseUrl) {
    return {
      host: '127.0.0.1',
      port: 13307,
      user: 'magento',
      password: 'magento',
      database: 'magento1_test'
    };
  }
  return {
    host: process.env.MYSQL_HOST,
    user: process.env.MYSQL_USER,
    password: process.env.MYSQL_PASSWORD,
    database: process.env.MYSQL_DATABASE
  };
};

const db = knex({
  client: 'mysql',
  connection: getDbConnectionConfig()
});

let magentoToken = null;
const getMagentoToken = async () => {
  if (!magentoToken) {
    const result = await db
      .select('value')
      .from('core_config_data')
      .where({ path: 'emartech_emarsys/general/connecttoken' })
      .first();

    const token = result.value;
    magentoToken = token;
    console.log('MAGENTO-TOKEN', magentoToken);
  }
  return magentoToken;
};

const getMagentoApi = async () => {
  const token = await getMagentoToken();

  return new Magento2ApiClient({
    baseUrl: process.env.CYPRESS_baseUrl || 'http://magento1-test.local',
    token,
    platform: 'magento1'
  });
};

const clearEvents = async () => {
  return await db.truncate('emarsys_events_data');
};

module.exports = (on) => {

  on('task', {
    clearDb: async () => {
      await clearEvents();
      return true;
    },
    clearEvents: async () => {
      await clearEvents();
      return true;
    },
    setConfig: async ({ websiteId = 1, config = {} }) => {
      const magentoApi = await getMagentoApi();
      const response = await magentoApi.execute('config', 'set', { websiteId, config });

      if (response.data.status !== 'ok') {
        throw new Error('Magento config set failed!');
      }
      return response.data;
    },
    setDoubleOptin: async (stateOn) => {
      if (stateOn) {
        return await db
          .insert({
            scope: 'default',
            scope_id: 0,
            path: 'newsletter/subscription/confirm',
            value: 1
          })
          .into('core_config_data');
      } else {
        return await db('core_config_data')
          .where({ path: 'newsletter/subscription/confirm' })
          .delete();
      }
    },
    getEventTypeFromDb: async (eventType) => {
      const event = await db
        .select()
        .from('emarsys_events_data')
        .where({
          event_type: eventType
        })
        .first();

      if (!event) {
        return null;
      }

      event.event_data = JSON.parse(event.event_data);
      return event;
    },
    getAllEvents: async () => {
      return await db.select().from('emarsys_events_data');
    },
    log: (logObject) => {
      console.log('LOG', logObject);
      return true;
    },
    getSubscription: async (email) => {
      return await db
        .select()
        .from('newsletter_subscriber')
        .where({ subscriber_email: email })
        .first();
    }
  });
};
