<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Connector
 */
class Emartech_Emarsys_Helper_Connector extends Mage_Core_Helper_Abstract
{
    CONST XML_PATH_CONNECTOR_TOKEN = 'emartech_emarsys/general/connecttoken';
    const VALID_AUTHORIZATION_TYPE = 'Bearer';
    const MAGENTO_VERSION = 1;

    /**
     * @return string
     */
    public function generateToken()
    {
        $hostname = $this->_getBaseUrl();
        $token = $this->_generateApiToken();
        $magento_version = self::MAGENTO_VERSION;

        $connectJson = json_encode(compact('hostname', 'token', 'magento_version'));
        $connectToken = base64_encode($connectJson);

        return $connectToken;
    }

    /**
     * @param string $authorizationHeader
     *
     * @return bool
     */
    public function validAuthorization($authorizationHeader)
    {
        return $this->_parseAuthorization($authorizationHeader) === $this->getApiToken();
    }

    /**
     * @return string
     */
    public function getApiToken()
    {
        $configData = $this->getToken();
        if (array_key_exists('token', $configData)) {
            return $configData['token'];
        }
        return '';
    }

    /**
     * @return string[]
     */
    public function getToken()
    {
        $returnArray = [
            'hostname'        => '',
            'token'           => '',
            'magento_version' => '',
        ];

        $token = Mage::getStoreConfig(self::XML_PATH_CONNECTOR_TOKEN);

        try {
            $token = base64_decode($token);
            $returnArray = json_decode($token, true);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $returnArray;
    }

    /**
     * @param string $authorizationHeader
     *
     * @return string
     */
    private function _parseAuthorization($authorizationHeader)
    {
        list($type, $token) = explode(' ', $authorizationHeader, 2);
        if ($type === self::VALID_AUTHORIZATION_TYPE) {
            return $token;
        }
        return '';
    }

    /**
     * @return string
     */
    private function _getBaseUrl()
    {
        $returnValue = '';
        $uri = Zend_Uri::factory(Mage::getBaseUrl());

        $returnValue = $uri->getHost();

        $port = $uri->getPort();
        if ($port && $port !== 80) {
            $returnValue .= ':' . $port;
        }

        return $returnValue;
    }

    /**
     * @return string
     */
    private function _generateApiToken()
    {
        /** @var $oauthHelper Mage_Oauth_Helper_Data */
        $oauthHelper = Mage::helper('oauth');
        return $oauthHelper->generateToken();
    }
}
