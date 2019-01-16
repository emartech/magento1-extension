<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Event_Marketing
 */
class Emartech_Emarsys_Helper_Event_Marketing extends Emartech_Emarsys_Helper_Event_Base
{
    const CUSTOMER = 'customers/update';

    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param int                          $websiteId
     * @param int                          $storeId
     * @param string                       $type
     *
     * @return bool
     */
    public function storeCustomer(Mage_Customer_Model_Customer $customer, $websiteId, $storeId, $type)
    {
        if (!$this->isEnabledForWebsite($websiteId, Emartech_Emarsys_Helper_Config::MARKETING_EVENTS)) {
            return false;
        }

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

    /**
     * @param Mage_Sales_Model_Order $order
     * @param string                 $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function storeOrder(Mage_Sales_Model_Order $order, $type)
    {
        $storeId = $order->getStoreId();

        if (!$this->isEnabledForStore($storeId, Emartech_Emarsys_Helper_Config::MARKETING_EVENTS)) {
            return false;
        }

        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $orderData = $order->toArray();
        $orderData['id'] = $order->getId();
        $orderData['items'] = [];
        $orderData['addresses'] = [];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllItems() as $item) {
            $arrayItem = $item->toArray();
            $parentItem = $item->getParentItem();
            if ($parentItem instanceof Mage_Sales_Model_Order_Item) {
                $arrayItem['parent_item'] = $parentItem->toArray();
            }
            $orderData['items'][] = $arrayItem;
        }

        if ($order->getShippingAddress()) {
            $orderData['addresses']['shipping'] = $order->getShippingAddress()->toArray();
        }

        $orderData['addresses']['billing'] = $order->getBillingAddress()->toArray();
        $orderData['payments'] = $order->getAllPayments();
        $orderData['shipments'] = $order->getShipmentsCollection()->toArray();
        $orderData['tracks'] = $order->getTracksCollection()->toArray();

        $this->_handleCustomerData($order, $orderData);

        $this->_saveEvent(
            $websiteId,
            $storeId,
            $type,
            $order->getId(),
            $orderData
        );

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @param string                         $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function storeInvoice(Mage_Sales_Model_Order_Invoice $invoice, $type)
    {
        return $this->_handleData($invoice, $type);
    }

    /**
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param string                          $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function storeShipment(Mage_Sales_Model_Order_Shipment $shipment, $type)
    {
        return $this->_handleData($shipment, $type);
    }

    /**
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     * @param string                            $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function storeCreditmemo(Mage_Sales_Model_Order_Creditmemo $creditmemo, $type)
    {
        return $this->_handleData($creditmemo, $type);
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscriber
     * @param string                           $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function storeSubscription(Mage_Newsletter_Model_Subscriber $subscriber, $type)
    {
        $storeId = $subscriber->getStoreId();

        if (!$this->isEnabledForStore($storeId, Emartech_Emarsys_Helper_Config::MARKETING_EVENTS)) {
            return false;
        }

        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $data = [
            'subscriber' => $subscriber->getData(),
        ];

        if ($subscriber->getCustomerId()) {
            $customer = Mage::getModel('customer/customer')->load($subscriber->getCustomerId());
            $data['customer'] = $customer->getData();
        }

        $this->_saveEvent(
            $websiteId,
            $storeId,
            $type,
            $subscriber->getId(),
            $data
        );

        return true;
    }

    /**
     * @param Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Shipment|Mage_Sales_Model_Order_Creditmemo $object
     * @param string                                                                                           $type
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    protected function _handleData($object, $type)
    {
        $storeId = $object->getStoreId();

        if (!$this->isEnabledForStore($storeId, Emartech_Emarsys_Helper_Config::MARKETING_EVENTS)) {
            return false;
        }

        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $data = $object->toArray();
        $data['id'] = $object->getId();
        $data['items'] = [];
        $data['addresses'] = [];

        /** @var Mage_Sales_Model_Order_Creditmemo_Item $item */
        foreach ($object->getAllItems() as $item) {
            $arrayItem = $item->toArray();
            $data['items'][] = $arrayItem;
        }

        if ($object->getShippingAddress()) {
            $data['addresses']['shipping'] = $object->getShippingAddress()->toArray();
        }

        $data['addresses']['billing'] = $object->getBillingAddress()->toArray();

        $this->_handleCustomerData($object->getOrder(), $data);

        $this->_saveEvent(
            $websiteId,
            $storeId,
            $type,
            $object->getOrderId(),
            $data
        );

        return true;
    }
}
