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
     * @var array
     */
    private $integerFields = [
        'billing_address_id',
        'customer_group_id',
        'customer_is_guest',
        'customer_is_guest',
        'customer_note_notify',
        'edit_increment',
        'email_sent',
        'entity_id',
        'free_shipping',
        'increment_id',
        'is_nominal',
        'is_qty_decimal',
        'is_virtual',
        'item_id',
        'no_discount',
        'order_id',
        'parent_item_id',
        'product_id',
        'quote_id',
        'quote_item_id',
        'store_id',
        'total_item_count'
    ];
    /**
     * @var array
     */
    private $floatFields = [
        'amount_refunded',
        'base_amount_refunded',
        'base_cost',
        'base_discount_amount',
        'base_discount_canceled',
        'base_discount_invoiced',
        'base_discount_refunded',
        'base_grand_total',
        'base_hidden_tax_amount',
        'base_hidden_tax_invoiced',
        'base_hidden_tax_refunded',
        'base_original_price',
        'base_price_incl_tax',
        'base_price',
        'base_row_invoiced',
        'base_row_total_incl_tax',
        'base_row_total',
        'base_shipping_amount',
        'base_shipping_canceled',
        'base_shipping_discount_amount',
        'base_shipping_hidden_tax_amnt',
        'base_shipping_hidden_tax_amount',
        'base_shipping_incl_tax',
        'base_shipping_invoiced',
        'base_shipping_refunded',
        'base_shipping_tax_amount',
        'base_shipping_tax_refunded',
        'base_subtotal_canceled',
        'base_subtotal_incl_tax',
        'base_subtotal_invoiced',
        'base_subtotal_refunded',
        'base_subtotal',
        'base_tax_amount',
        'base_tax_before_discount',
        'base_tax_canceled',
        'base_tax_invoiced',
        'base_tax_refunded',
        'base_to_global_rate',
        'base_to_order_rate',
        'base_total_canceled',
        'base_total_due',
        'base_total_invoiced_cost',
        'base_total_invoiced',
        'base_total_offline_refunded',
        'base_total_online_refunded',
        'base_total_paid',
        'base_total_qty_ordered',
        'base_total_refunded',
        'discount_amount',
        'discount_canceled',
        'discount_invoiced',
        'discount_percent',
        'discount_refunded',
        'grand_total',
        'hidden_tax_amount',
        'hidden_tax_invoiced',
        'hidden_tax_refunded',
        'original_price',
        'payment_authorization_amount',
        'price_incl_tax',
        'price',
        'qty_backordered',
        'qty_canceled',
        'qty_invoiced',
        'qty_ordered',
        'qty_refunded',
        'qty_returned',
        'qty_shipped',
        'row_invoiced',
        'row_total_incl_tax',
        'row_total',
        'row_weight',
        'shipping_amount',
        'shipping_canceled',
        'shipping_discount_amount',
        'shipping_hidden_tax_amount',
        'shipping_incl_tax',
        'shipping_invoiced',
        'shipping_refunded',
        'shipping_tax_amount',
        'shipping_tax_refunded',
        'store_to_base_rate',
        'store_to_order_rate',
        'subtotal_canceled',
        'subtotal_incl_tax',
        'subtotal_invoiced',
        'subtotal_refunded',
        'subtotal',
        'tax_amount',
        'tax_before_discount',
        'tax_canceled',
        'tax_invoiced',
        'tax_percent',
        'tax_refunded',
        'total_canceled',
        'total_due',
        'total_invoiced',
        'total_offline_refunded',
        'total_online_refunded',
        'total_paid',
        'total_qty_ordered',
        'total_refunded',
        'weight'
    ];

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
            'page_size'    => (int) $this->_collection->getPageSize(),
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
            $orderData = $this->_castNumericFields($order->getData());
            $orderData['id'] = $orderData['entity_id'];
            $returnArray[] = array_merge(
                $orderData,
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
            $returnArray[] = $this->_castNumericFields($orderItem->getData());
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

    /**
     * @param array $data
     * @return array
     */
    private function _castNumericFields(array $data)
    {
        $castData = [];
        foreach ($data as $key => $value) {
            if ($this->_notNullAndInteger($key, $value)) {
                $value = (int) $value;
            } elseif ($this->_notNullAndFloat($key, $value)) {
                $value = (float) $value;
            }
            $castData[$key] = $value;
        }

        return $castData;
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    private function _notNullAndInteger($key, $value)
    {
        return $value !== null && in_array($key, $this->integerFields, true);
    }

    /**
     * @param $key
     * @param $value
     * @return bool
     */
    private function _notNullAndFloat($key, $value)
    {
        return $value !== null && in_array($key, $this->floatFields, true);
    }
}
