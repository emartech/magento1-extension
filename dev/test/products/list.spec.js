'use strict';

describe('Products endpoint', function() {
  before(function() {});

  afterEach(async function() {});

  it('returns product count and products according to page and page_size', async function() {
    const page = 3;
    const limit = 10;

    const { products, productCount } = await this.magentoApi.execute('products', 'get', {
      page,
      limit,
      storeId: '0,1'
    });
    const product = products[0];

    expect(products.length).to.equal(10);
    expect(productCount).to.equal(593);

    expect(product.entity_id).to.equal('251');
    expect(product.type).to.equal('simple');
    expect(product.children_entity_ids).to.be.an('array');
    expect(product.categories[0]).to.be.equal('1/2/5/16');
    expect(product.sku).to.equal('mtk002');
    expect(product.qty).to.equal('25.0000');
    expect(product.is_in_stock).to.equal('1');
    expect(product.images).to.eql({
      image: `http://${this.hostname}/media/catalog/product/m/t/mtk002t_3.jpg`,
      small_image: `http://${this.hostname}/media/catalog/product/m/t/mtk002t_3.jpg`,
      thumbnail: `http://${this.hostname}/media/catalog/product/m/t/mtk002t_3.jpg`
    });

    const storeLevelProduct = product.store_data[0];
    expect(storeLevelProduct.name).to.equal('Chelsea Tee');
    expect(storeLevelProduct.price).to.equal('75.0000');
    expect(storeLevelProduct.link).to.include('/index.php/chelsea-tee.html');
    expect(storeLevelProduct.status).to.equal('1');
    expect(storeLevelProduct.description).to.equal('Ultrasoft, lightweight V-neck tee. 100% cotton. Machine wash.');
  });

  it('returns child entities for configurable products', async function() {
    const page = 151;
    const limit = 1;

    const { products } = await this.magentoApi.execute('products', 'get', { page, limit, storeId: '0,1' });
    const product = products[0];

    expect(product.type).to.equal('configurable');
    expect(product.children_entity_ids).to.eql(['231', '232', '233', '498', '499']);
  });
});
