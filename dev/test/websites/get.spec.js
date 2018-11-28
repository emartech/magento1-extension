'use strict';

describe('Websites endpoint', function() {
  it('should return websites information', async function() {
    const expectedWebsites = [
      { id: 0, code: 'admin', name: 'Admin', default_group_id: 0 },
      { id: 1, code: 'base', name: 'Main Website', default_group_id: 1 }
    ];

    const websites = await this.magentoApi.execute('websites', 'get');

    expect(websites).to.eql(expectedWebsites);
  });
});
