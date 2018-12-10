'use strict';

const axios = require('axios');

const getEmarsysSnippetContents = async path => {
  const response = await axios.get(`http://magento1-test.local/index.php/${path}`);
  console.log(response.data.replace(/(?:\r\n|\r|\n)/g, ''));
  return response.data.replace(/(?:\r\n|\r|\n)/g, '');
};

describe('Webextend scripts', function() {
  describe('enabled', function() {
    beforeEach(async function() {
      await this.magentoApi.execute('config', 'set', {
        websiteId: 1,
        config: {
          injectSnippet: 'enabled',
          merchantId: '123',
          webTrackingSnippetUrl: 'http://yolo.hu/script'
        }
      });
    });

    it('should be in the HTML if injectsnippet is enabled', async function() {
      const emarsysSnippets = await getEmarsysSnippetContents('customer/account/login/');

      expect(emarsysSnippets.includes('<script src="http://yolo.hu/script"></script>')).to.be.true;

      expect(
        emarsysSnippets.includes(
          //eslint-disable-next-line
          `<script type="text/javascript">        var ScarabQueue = ScarabQueue || [];        (function(id) {            if (document.getElementById(id)) return;            var js = document.createElement('script'); js.id = id;            js.src = '//cdn.scarabresearch.com/js/123/scarab-v2.js';            var fs = document.getElementsByTagName('script')[0];            fs.parentNode.insertBefore(js, fs);        })('scarab-js-api');    </script>    <script src="http://yolo.hu/script"></script>`
        )
      ).to.be.true;

      expect(
        emarsysSnippets.includes(
          //eslint-disable-next-line
          '<script>        window.Emarsys.Magento1.track({"store":{"merchantId":"123"},"exchangeRate":1,"slug":"testslug"});    </script>'
        )
      ).to.be.true;
    });

    it('should include search term', async function() {
      const emarsysSnippets = await getEmarsysSnippetContents('catalogsearch/result/?q=yolo');
      expect(
        emarsysSnippets.includes(
          //eslint-disable-next-line
          '<script>        window.Emarsys.Magento1.track({"store":{"merchantId":"123"},"search":{"term":"yolo"},"exchangeRate":1,"slug":"testslug"});    </script>'
        )
      ).to.be.true;
    });

    it('should include category', async function() {
      const emarsysSnippets = await getEmarsysSnippetContents('men/shirts.html');
      expect(
        emarsysSnippets.includes(
          //eslint-disable-next-line
          '<script src="http://yolo.hu/script"></script>    <script>        window.Emarsys.Magento1.track({"category":{"names":["Men","Shirts"],"ids":["5","15"]},"store":{"merchantId":"123"},"exchangeRate":1,"slug":"testslug"});    </script>'
        )
      ).to.be.true;
    });

    it('should include product', async function() {
      const emarsysSnippets = await getEmarsysSnippetContents('accessories/jewelry/swing-time-earrings.html');
      expect(
        emarsysSnippets.includes(
          //eslint-disable-next-line
          '<script src="http://yolo.hu/script"></script>    <script>        window.Emarsys.Magento1.track({"product":{"sku":"acj004","id":"552"},"category":{"names":["Accessories","Jewelry"],"ids":["6","19"]},"store":{"merchantId":"123"},"exchangeRate":1,"slug":"testslug"});    </script>'
        )
      ).to.be.true;
    });

    describe('store is not enabled', function() {
      before(async function() {
        await this.clearStoreSettings(1);
      });

      after(async function() {
        await this.setDefaultStoreSettings(1);
      });

      it('should not be in the HTML', async function() {
        await this.setDefaultConfig(1);
        const emarsysSnippets = await getEmarsysSnippetContents('customer/account/login/');
        expect(emarsysSnippets.includes('window.Emarsys.Magento1.track')).to.be.false;
      });
    });
  });

  describe('disabled', function() {
    it('should not be in the HTML if injectsnippet setting is disabled', async function() {
      await this.setDefaultConfig(1);
      const emarsysSnippets = await getEmarsysSnippetContents('customer/account/login/');
      expect(emarsysSnippets.includes('window.Emarsys.Magento1.track')).to.be.false;
    });
  });
});
