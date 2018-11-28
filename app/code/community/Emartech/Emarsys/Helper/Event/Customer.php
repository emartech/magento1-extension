<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Event_Customer
 */
class Emartech_Emarsys_Helper_Event_Customer extends Emartech_Emarsys_Helper_Event_Base
{
    const DEFAULT_TYPE = 'customers/update';

    /**
     * @param int         $customerId
     * @param int         $websiteId
     * @param int         $storeId
     * @param null|string $type
     *
     * @return bool
     */
    public function store($customerId, $websiteId, $storeId, $type = null)
    {
        if (!$this->isEnabledForWebsite($websiteId, Emartech_Emarsys_Helper_Config::CUSTOMER_EVENTS)) {
            return false;
        }

        if (!$type) {
            $type = self::DEFAULT_TYPE;
        }

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = Mage::getModel('customer/customer')->load($customerId);

        $customerData = $customer->toArray();
        $customerData['id'] = $customerData['entity_id'];

        if ($customer->getDefaultBillingAddress()) {
            $customerData['billing_address'] = $customer->getDefaultBillingAddress()->toArray();
        }
        if ($customer->getDefaultShippingAddress()) {
            $customerData['shipping_address'] = $customer->getDefaultShippingAddress()->toArray();
        }

        /** @var Mage_Newsletter_Model_Subscriber $subscription */
        $subscription = Mage::getModel('newsletter/subscriber')->loadByCustomer($customer);
        $customerData['accepts_marketing'] = $subscription->getStatus();

        $this->_saveEvent($websiteId, $storeId, $type, $customer->getId(), $customerData);

        return true;
    }
}
