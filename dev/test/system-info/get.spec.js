'use strict';

describe('SystemInfo endpoint', function() {
  it('should return system information', async function() {
    const expectedInfo = {
      magento_version: '1.9.3.10',
      magento_edition: 'Community',
      php_version: '7.2.10',
      module_version: '1.0.2'
    };

    const info = await this.magentoApi.execute('systeminfo', 'get');

    expect(info).to.eql(expectedInfo);
  });
});
