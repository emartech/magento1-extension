'use strict';

const dbKeys = {
  collectCustomerEvents: 'collect_customer_events',
  collectSalesEvents: 'collect_sales_events',
  collectMarketingEvents: 'collect_marketing_events',
  injectSnippet: 'inject_webextend_snippets',
  merchantId: 'merchant_id',
  webTrackingSnippetUrl: 'web_tracking_snippet_url',
  storeConfig: 'store_config'
};

const scopeId = 1;
describe('Config endpoint', function() {
  before(async function() {
    await this.clearStoreSettings(1);
  });

  afterEach(async function() {
    await this.setDefaultConfig(1);
  });

  after(async function() {
    await this.setDefaultStoreSettings(1);
  });

  describe('set', function() {
    it('should modify config values for website', async function() {
      const testConfig = {
        collectCustomerEvents: 'enabled',
        collectSalesEvents: 'enabled',
        collectMarketingEvents: 'enabled',
        injectSnippet: 'enabled',
        merchantId: '1234567',
        webTrackingSnippetUrl: 'https://path/to/snippet'
      };

      await this.magentoApi.execute('config', 'set', {
        websiteId: scopeId,
        config: testConfig
      });

      const config = await this.db
        .select()
        .from('core_config_data')
        .where('scope_id', scopeId)
        .andWhere('path', 'like', 'emartech_emarsys/config/%');

      for (const key in testConfig) {
        const configItem = config.find(item => item.path === `emartech_emarsys/config/${dbKeys[key]}`);
        expect(configItem.value).to.be.equal(testConfig[key]);
      }
    });

    it('should modify store config for website', async function() {
      const testConfig = {
        storeSettings: [{ id: 1, slug: 'test' }]
      };

      await this.magentoApi.execute('config', 'set', {
        websiteId: scopeId,
        config: testConfig
      });

      const config = await this.db
        .select()
        .from('core_config_data')
        .where('scope_id', scopeId)
        .andWhere('path', 'like', 'emartech_emarsys/config/store_settings')
        .first();

      expect(config.value).to.be.equal(JSON.stringify(testConfig.storeSettings));
    });
  });
});
