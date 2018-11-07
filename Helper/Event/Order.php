<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Event_Subscription
 */
class Emartech_Emarsys_Helper_Event_Order extends Emartech_Emarsys_Helper_Event_Base
{
    const DEFAULT_TYPE = 'subscription/unknown';

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return bool
     * @throws Mage_Core_Model_Store_Exception
     */
    public function store(Mage_Sales_Model_Order $order)
    {
        $storeId = $order->getStoreId();

        if (!$this->isEnabledForStore($storeId)) {
            //@todo remove comment, after we made the config api
            //return false;
        }

        $websiteId = Mage::app()->getStore($storeId)->getWebsiteId();

        $orderData = $order->toArray();
        $orderData['id'] = $order->getId();
        $orderItems = $order->getAllItems();
        $orderData['items'] = [];
        $orderData['addresses'] = [];

        /** @var Mage_Sales_Model_Order_Item $item */
        foreach ($orderItems as $item) {
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

        $this->_saveEvent(
            $websiteId,
            $storeId,
            $this->_getOrderEventType($order->getState()),
            $order->getId(),
            $orderData
        );

        return true;
    }

    /**
     * @param string $state
     *
     * @return string
     */
    private function _getOrderEventType($state)
    {
        if ($state === 'new') {
            return 'orders/create';
        }

        if ($state === 'canceled') {
            return 'orders/cancelled';
        }

        if ($state === 'complete') {
            return 'orders/fulfilled';
        }

        return 'orders/' . $state;
    }
}
