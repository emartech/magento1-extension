'use strict';

describe('Store views endpoint', function() {
  it('should return store views information', async function() {
    const expectedStoreViews = [
      { id: 0, code: 'admin', name: 'Admin', website_id: 0, store_group_id: 0 },
      { id: 1, code: 'default', name: 'English', website_id: 1, store_group_id: 1 },
      { id: 2, code: 'french', name: 'French', website_id: 1, store_group_id: 1 },
      { id: 3, code: 'german', name: 'German', website_id: 1, store_group_id: 1 }
    ];

    const storeViews = await this.magentoApi.execute('storeViews', 'get');

    expect(storeViews).to.eql(expectedStoreViews);
  });
});
