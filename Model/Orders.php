<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Orders
 */
class Emartech_Emarsys_Model_Orders extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{
    /**
     * @var null|Mage_Sales_Model_Resource_Order_Collection
     */
    private $_collection = null;

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     * @throws Exception
     */
    public function handleGet($request)
    {
        $storeId = $request->getParam('store_id', []);
        $sinceId = $request->getParam('since_id', 0);
        $page = $request->getParam('page', 0);
        $pageSize = $request->getParam('page_size', 1000);

        if (empty($storeId)) {
            throw new Exception('Store ID is required');
        }

        $this
            ->_initCollection()
            ->_filterStore($storeId)
            ->_filterSinceId($sinceId)
            ->_setPage($page, $pageSize);

        return [
            'current_page' => $this->_collection->getCurPage(),
            'last_page'    => $this->_collection->getLastPageNumber(),
            'page_size'    => $this->_collection->getPageSize(),
            'total_count'  => $this->_collection->getSize(),
            'items'        => $this->_handleOrders(),
        ];
    }

    /**
     * @return $this
     */
    private function _initCollection()
    {
        $this->_collection = Mage::getModel('sales/order')->getCollection();
        $this->_collection
            ->addAttributeToSelect('*')
            ->addAddressFields();

        return $this;
    }

    /**
     * @param int|string|null $storeId
     *
     * @return $this
     */
    private function _filterStore($storeId = null)
    {
        if ($storeId !== null) {
            if (!is_array($storeId)) {
                $storeId = explode(',', $storeId);
            }
            $this->_collection->addFieldToFilter('store_id', ['in' => $storeId]);
        }

        return $this;
    }

    /**
     * @param int $sinceId
     *
     * @return $this
     */
    private function _filterSinceId($sinceId = 0)
    {
        if ($sinceId) {
            $this->_collection
                ->addFieldToFilter('entity_id', ['gt' => $sinceId]);
        }

        return $this;
    }

    /**
     * @param int $page
     * @param int $pageSize
     *
     * @return $this
     */
    private function _setPage($page, $pageSize)
    {
        $this->_collection->setPage($page, $pageSize);

        return $this;
    }

    private function _handleOrders()
    {
        $returnArray = [];

        /** @var Mage_Sales_Model_Order $order */
        foreach ($this->_collection as $order) {
            $returnArray[] = array_merge(
                $order->getData(),
                [
                    'items'            => $this->_handleOrderItems($order),
                    'billing_address'  => $order->getBillingAddress()->getData(),
                    'payment'          => $order->getPayment()->getData(),
                    'status_histories' => $this->_handleStatusHistories($order),
                ]
            );

        }

        return $returnArray;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function _handleOrderItems($order)
    {
        $returnArray = [];

        /** @var Mage_Sales_Model_Order_Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $returnArray[] = $orderItem->getData();
        }

        return $returnArray;
    }

    /**
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    private function _handleStatusHistories($order)
    {
        $returnArray = [];

        /** @var Mage_Sales_Model_Order_Status_History $historyItem */
        foreach ($order->getAllStatusHistory() as $historyItem) {
            $returnArray[] = $historyItem->getData();
        }

        return $returnArray;
    }
}
