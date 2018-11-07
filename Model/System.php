<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class Emartech_Emarsys_Model_System extends Emartech_Emarsys_Model_Abstract
{
    /**
     * @param array $params
     *
     * @return array
     */
    public function handleRequest($params)
    {
        return [
            'magento_version' => $this->_getMagentoVersion(),
            'magento_edition' => $this->_getMagentoEdition(),
            'php_version'     => $this->_getPHPVersion(),
            'module_version'  => $this->_getModuleVersion(),
        ];
    }

    /**
     * @return string
     */
    private function _getMagentoVersion()
    {
        return Mage::getVersion();
    }

    /**
     * @return string
     */
    private function _getMagentoEdition()
    {
        return Mage::getEdition();
    }

    /**
     * @return string
     */
    private function _getPHPVersion()
    {
        if (defined(PHP_VERSION)) {
            return PHP_VERSION;
        }

        return phpversion();
    }

    /**
     * @return string
     */
    private function _getModuleVersion()
    {
        return Mage::helper('emartech_emarsys')->getExtensionVersion();
    }
}