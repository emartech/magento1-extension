<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Observer_Event
 */
class Emartech_Emarsys_Model_Observer_Event
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function customerSaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $observer->getCustomer();

        Mage::helper('emartech_emarsys/event_customer')->store(
            $customer->getId(),
            $customer->getWebsiteId(),
            $customer->getStoreId()
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function customerAddressSaveCommitAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Customer_Model_Address $customerAddress */
        $customerAddress = $observer->getCustomerAddress();

        /** @var Mage_Customer_Model_Customer $customer */
        $customer = $customerAddress->getCustomer();

        Mage::helper('emartech_emarsys/event_customer')->store(
            $customer->getId(),
            $customer->getWebsiteId(),
            $customer->getStoreId()
        );
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     * @throws Mage_Core_Model_Store_Exception
     */
    public function newsletterSubscriberSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Newsletter_Model_Subscriber $subscriber */
        $subscriber = $observer->getSubscriber();

        $store = Mage::app()->getStore($subscriber->getStoreId());

        if ($subscriber->getCustomerId()) {
            Mage::helper('emartech_emarsys/event_customer')->store(
                $subscriber->getCustomerId(),
                $store->getWebsiteId(),
                $store->getStoreId()
            );
        } else {
            /** @var Emartech_Emarsys_Helper_Event_Subscription $subscriptionEventHelper */
            $subscriptionEventHelper = Mage::helper('emartech_emarsys/event_subscription');
            $subscriptionEventHelper->store(
                $subscriber,
                $store->getWebsiteId(),
                $store->getId(),
                $subscriptionEventHelper->getEventType($observer->getEvent()->getName())
            );
        }
    }

    public function salesOrderSaveAfter(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getOrder();

        Mage::helper('emartech_emarsys/event_order')->store($order);
    }
}