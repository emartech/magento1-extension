'use strict';

const MagentoAPI = require('magento-nodejs');
const magento = new MagentoAPI({
  host: 'magento1-test.local',
  port: 80,
  path: '/api/xmlrpc/',
  login: 'testuser',
  pass: 'magentorocks1'
});

class XMLRpcClient {
  constructor(client) {
    this.magento = client;
  }

  async execute(resource, method, args) {
    return new Promise((resolve) => {
      if (!this.magento[resource] || !this.magento[resource][method]) {
        throw new Error(`Unknown XML-RPC API action: ${resource}.${method}`);
      }
      this.magento[resource][method](args, function(err, result) {
        if (err) {
          throw err;
        }
        resolve(result);
      });
    });
  }

  static async create() {
    return new Promise((resolve) => {
      magento.login(function(err, sessId) {
        if (err) {
          throw err;
        }
        console.log('XML-RPC session ID: ', sessId);
        const xmlRpcClient = new XMLRpcClient(magento);
        resolve(xmlRpcClient);
      });
    });
  }
}

module.exports = XMLRpcClient;
