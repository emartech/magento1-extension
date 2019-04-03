'use strict';

describe('Connect', function() {
  it('should store hostname, token', async function() {
    const result = await this.db
      .select('value')
      .from('core_config_data')
      .where({ path: 'emartech_emarsys/general/connecttoken' })
      .first();

    expect(result.value).not.to.be.undefined;
  });
});
