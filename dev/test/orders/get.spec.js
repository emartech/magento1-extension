'use strict';

const orderCount = 44;

describe('Orders endpoint', function() {
  it('should return orders and paging info according to parameters', async function() {
    const limit = 2;
    const page = 1;
    const ordersResponse = await this.magentoApi.execute('orders', 'getSinceId', {
      page,
      limit,
      sinceId: 0,
      storeIds: [1]
    });

    expect(ordersResponse.orderCount).to.be.equal(orderCount);
    expect(ordersResponse.orders.length).to.be.equal(limit);
    expect(ordersResponse.lastPage).to.be.equal(orderCount / limit);
    expect(ordersResponse.pageSize).to.be.equal(limit);
    expect(ordersResponse.currentPage).to.be.equal(page);
    expect(ordersResponse.orders[0]).to.have.property('id');
    expect(ordersResponse.orders[0].store_id).to.equal(1);
  });

  it('should handle multiple store IDs', async function() {
    const limit = 2;
    const page = 1;
    const ordersResponse = await this.magentoApi.execute('orders', 'getSinceId', {
      page,
      limit,
      sinceId: 0,
      storeIds: [1, 2]
    });

    expect(ordersResponse.orderCount).to.be.equal(orderCount);
  });

  it('should filter for store IDs', async function() {
    const limit = 2;
    const page = 1;
    const ordersResponse = await this.magentoApi.execute('orders', 'getSinceId', {
      page,
      limit,
      sinceId: 0,
      storeIds: [2]
    });

    expect(ordersResponse.orderCount).to.be.equal(0);
  });

  it('should filter with sinceId', async function() {
    const limit = 2;
    const page = 2;
    const sinceId = 45;
    const ordersResponse = await this.magentoApi.execute('orders', 'getSinceId', {
      page,
      limit,
      sinceId,
      storeIds: [1]
    });

    expect(ordersResponse.orderCount).to.be.equal(40);
    expect(ordersResponse.lastPage).to.be.equal(40 / limit);
    expect(ordersResponse.currentPage).to.be.equal(page);
  });
});
