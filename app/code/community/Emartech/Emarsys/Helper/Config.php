<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Config
 */
class Emartech_Emarsys_Helper_Config extends Mage_Core_Helper_Abstract
{
    const XML_PATH_STORE_CONFIG_PRE_TAG = 'emartech/emarsys/config/';

    const CONFIG_ENABLED  = 'enabled';
    const CONFIG_DISABLED = 'disabled';
    const CONFIG_EMPTY    = null;

    const CUSTOMER_EVENTS           = 'collect_customer_events';
    const SALES_EVENTS              = 'collect_sales_events';
    const MARKETING_EVENTS          = 'collect_marketing_events';
    const INJECT_WEBEXTEND_SNIPPETS = 'inject_webextend_snippets';
    const MERCHANT_ID               = 'merchant_id';
    const SNIPPET_URL               = 'web_tracking_snippet_url';

    const STORE_SETTINGS = 'store_settings';

    const SCOPE_TYPE_DEFAULT = 'websites';

    /**
     * @param string   $key
     * @param null|int $websiteId
     *
     * @return string
     */
    public function getWebsiteConfigValue($key, $websiteId = null)
    {
        try {
            if (!$websiteId) {
                $websiteId = Mage::app()->getStore()->getWebsiteId();
            }

            return (string)Mage::app()->getWebsite($websiteId)->getConfig(self::XML_PATH_STORE_CONFIG_PRE_TAG . $key);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param string   $key
     * @param null|int $storeId
     *
     * @return string
     */
    public function getStoreConfigValue($key, $storeId = null)
    {
        try {
            if (!$storeId) {
                $storeId = Mage::app()->getStore()->getId();
            }

            return (string)Mage::app()->getStore($storeId)->getConfig(self::XML_PATH_STORE_CONFIG_PRE_TAG . $key);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param string   $key
     * @param null|int $websiteId
     *
     * @return bool
     */
    public function isEnabledForWebsite($key, $websiteId = null)
    {
        return $this->getWebsiteConfigValue($key, $websiteId) === self::CONFIG_ENABLED;
    }

    /**
     * @param string   $key
     * @param null|int $storeId
     *
     * @return bool
     */
    public function isEnabledForStore($key, $storeId = null)
    {
        try {
            if (!$storeId) {
                $storeId = Mage::app()->getStore()->getId();
            }

            $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

            if (!$this->isEnabledForWebsite($key, $websiteId)) {
                return false;
            }

            $stores = json_decode($this->getWebsiteConfigValue(self::STORE_SETTINGS, $websiteId), true);

            foreach ($stores as $store) {
                if ($store['id'] == $storeId) {
                    return true;
                }
            }
        } catch (\Exception $e) { //@codingStandardsIgnoreLine
        }

        return false;
    }
}
