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
    const MAGENTO_VERSION          = 1;

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
        return [
            'hostname'        => $this->_getBaseUrl(),
            'token'           => $this->_getTokenFromConfig(),
            'magento_version' => self::MAGENTO_VERSION,
        ];
    }

    /**
     * @return string
     */
    private function _getTokenFromConfig()
    {
        return (string)Mage::getStoreConfig(self::XML_PATH_CONNECTOR_TOKEN);
    }

    /**
     * @return string
     */
    private function _getOldToken()
    {
        try {
            $token = json_decode(base64_decode($this->_getTokenFromConfig()), true);
        } catch (\Exception $e) {
            $token = [];
        }

        if (array_key_exists('token', $token)) {
            $token = $token['token'];
        } else {
            $token = $this->_getTokenFromConfig();
        }

        return $token;
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
        try {
            $returnValue = Mage::app()
                ->getWebsite(true)
                ->getDefaultGroup()
                ->getDefaultStore()
                ->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK);

            $returnValue = preg_replace('/^https?:\/\//', '', $returnValue);
        } catch (Exception $e) {
            try {
                $uri = Zend_Uri::factory(Mage::getBaseUrl());
                $returnValue = $uri->getUri();
            } catch (Exception $e) {
                $returnValue = '';
            }
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

    /**
     * @return string
     */
    public function refreshToken()
    {
        return $this->_getOldToken();
    }

    /**
     * @param string $token
     *
     * @return string
     */
    public function getConnectorTokenValue($token)
    {
        $hostname = $this->_getBaseUrl();
        $magento_version = self::MAGENTO_VERSION;

        $connectJson = json_encode(compact('hostname', 'token', 'magento_version'), JSON_UNESCAPED_SLASHES);
        $connectToken = base64_encode($connectJson);

        return $connectToken;
    }
}
