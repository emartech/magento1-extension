'use strict';

describe('Customers endpoint', function() {
  it('returns customers according to page and page_size inlcuding last_page', async function() {
    const page = 1;
    const limit = 2;

    const { customers, lastPage } = await this.magentoApi.execute('customers', 'getAll', { page, limit, websiteId: 1 });

    const customer = customers[0];

    expect(customers.length).to.equal(2);
    expect(customer.id).to.equal(64);
    expect(lastPage).to.equal(22);

    expect(customer).to.have.property('id');
    expect(customer.email).to.be.a('string');
    expect(customer.firstname).to.be.a('string');
    expect(customer.lastname).to.be.a('string');
    expect(customer.billing_address).to.be.an('object');
    expect(customer.shipping_address).to.be.an('object');
    expect(customer).to.have.property('accepts_marketing');
    expect(customer).to.have.property('billing_address');
    expect(customer).to.have.property('shipping_address');
  });
});
