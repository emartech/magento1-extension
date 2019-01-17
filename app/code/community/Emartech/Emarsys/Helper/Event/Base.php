<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Event_Base
 */
class Emartech_Emarsys_Helper_Event_Base extends Mage_Core_Helper_Abstract
{
    /**
     * @var Emartech_Emarsys_Helper_Config
     */
    private $_configHelper = null;

    public function getConfigHelper()
    {
        if ($this->_configHelper === null) {
            $this->_configHelper = Mage::helper('emartech_emarsys/config');
        }

        return $this->_configHelper;
    }

    /**
     * @param int    $websiteId
     * @param int    $storeId
     * @param string $type
     * @param int    $entityId
     * @param array  $data
     *
     * @return void
     */
    protected function _saveEvent($websiteId, $storeId, $type, $entityId, $data)
    {
        $this->_removeOldEvents($type, $entityId, $storeId);

        $data = json_encode($data);

        /** @var Emartech_Emarsys_Model_Event $eventModel */
        $eventModel = Mage::getModel('emartech_emarsys/event');

        try {
            $eventModel
                ->setEntityId($entityId)
                ->setWebsiteId($websiteId)
                ->setStoreId($storeId)
                ->setEventType($type)
                ->setEventData($data)
                ->setCreatedAt(Mage::getSingleton('core/date')->gmtDate())
                ->save();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    private function _removeOldEvents($type, $entityId, $storeId)
    {
        /** @var Emartech_Emarsys_Model_Resource_Event_Collection $oldEventCollection */
        $oldEventCollection = Mage::getModel('emartech_emarsys/event')->getCollection();

        $oldEventCollection
            ->addFieldToFilter('entity_id', ['eq' => $entityId])
            ->addFieldToFilter('event_type', ['eq' => $type])
            ->addFieldToFilter('store_id', ['eq' => $storeId]);

        $oldEventCollection->walk('delete');
    }

    /**
     * @param int    $websiteId
     * @param string $eventType
     *
     * @return bool
     */
    protected function isEnabledForWebsite($websiteId, $eventType)
    {
        return $this->getConfigHelper()->isEnabledForWebsite($eventType, $websiteId);
    }

    /**
     * @param int    $storeId
     * @param string $eventType
     *
     * @return bool
     */
    protected function isEnabledForStore($storeId, $eventType)
    {
        return $this->getConfigHelper()->isEnabledForStore($eventType, $storeId);
    }

    /**
     * @param Mage_Sales_Model_Order $order
     * @param array $data
     *
     * @return void
     */
    protected function _handleCustomerData($order, &$data)
    {
        if ($order->getCustomerId()) {
            $data['customer'] = $this->_getCustomerData($order->getCustomerId());
        } else {
            $data['is_guest'] = '1';
            $data['customerName'] = $order->getCustomerName();
            $data['customerEmail'] = $order->getCustomerEmail();
        }
    }

    /**
     * @param int $customerId
     *
     * @return array
     */
    protected function _getCustomerData($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);

        return $customer->toArray();
    }
}
