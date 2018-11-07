<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
class Emartech_Emarsys_Model_Customers extends Emartech_Emarsys_Model_Abstract
{
    /**
     * @var null|Mage_Customer_Model_Resource_Customer_Collection
     */
    private $_collection = null;

    /**
     * @var null|string
     */
    private $_subscriptionTable = null;

    /**
     * @var array
     */
    private $_customerFields = [
        'id',
        'email',
        'website_id',
        'group_id',
        'store_id',
        'is_active',
        'prefix',
        'firstname',
        'middlename',
        'lastname',
        'suffix',
        'dob',
        'taxvat',
        'gender',
        'created_at',
        'updated_at',
    ];

    /**
     * @var array
     */
    private $_addressFields = [
        'prefix',
        'firstname',
        'middlename',
        'lastname',
        'suffix',
        'company',
        'street',
        'city',
        'country_id',
        'region',
        'postcode',
        'telephone',
        'fax',
    ];

    /**
     * @param array $params
     *
     * @return array
     */
    public function handleRequest($params)
    {
        $websiteIds = array_key_exists('website_id', $params) ? $params['website_id'] : [];
        $storeIds = array_key_exists('store_id', $params) ? $params['store_id'] : [];
        $page = array_key_exists('page', $params) ? $params['page'] : 0;
        $pageSize = array_key_exists('page_size', $params) ? $params['page_size'] : 1000;

        try {
            $this
                ->_initCollection()
                ->_filterMixedParam($websiteIds, 'website_id')
                ->_filterMixedParam($storeIds, 'store_id')
                ->_joinAddress('billing')
                ->_joinAddress('shipping')
                ->_joinSubscriptionStatus()
                ->_setPage($page, $pageSize);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return [
            'current_page' => $this->_collection->getCurPage(),
            'last_page'    => $this->_collection->getLastPageNumber(),
            'page_size'    => $this->_collection->getPageSize(),
            'total_count'  => $this->_collection->getSize(),
            'customers'    => $this->_handleCustomers(),
        ];
    }

    /**
     * @return array
     */
    private function _handleCustomers()
    {
        $customerArray = [];
        foreach ($this->_collection as $customer) {
            $customerArray[] = $this->_parseCustomer($customer);
        }

        return $customerArray;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     *
     * @return array
     */
    private function _parseCustomer($customer)
    {
        $returnArray = [
            'id'               => $customer->getId(),
            'billing_address'  => $this->_getAddressFromCustomer($customer, 'billing'),
            'shipping_address' => $this->_getAddressFromCustomer($customer, 'shipping'),
        ];

        foreach ($customer->getData() as $key => $value) {
            $returnArray[$key] = $value;
        }

        return $returnArray;
    }

    /**
     * @param Mage_Customer_Model_Customer $customer
     * @param string                       $addressType
     *
     * @return string[]
     */
    private function _getAddressFromCustomer($customer, $addressType = 'billing')
    {
        $address = [];

        foreach ($customer->getData() as $originalKey => $value) {
            if (strpos($originalKey, $addressType) === 0) {
                $key = explode('__', $originalKey);
                $key = array_pop($key);

                $address[$key] = $value;
                $customer->unsetData($originalKey);
            }
        }


        return $address;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function _initCollection()
    {
        /** @var Mage_Customer_Model_Resource_Customer_Collection _collection */
        $this->_collection = Mage::getModel('customer/customer')->getCollection();
        $this->_collection->addAttributeToSelect($this->_customerFields);

        $this->_subscriptionTable = Mage::getSingleton('core/resource')->getTableName('newsletter_subscriber');

        return $this;
    }

    /**
     * @param string|string[] $param
     * @param string          $type
     *
     * @return $this
     */
    private function _filterMixedParam($param, $type)
    {
        if ($param) {
            if (!is_array($param)) {
                $param = explode(',', $param);
            }
            $this->_collection->addAttributeToFilter($type, ['in' => $param]);
        }
        return $this;
    }

    /**
     * @param string $addressType
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function _joinAddress($addressType = 'billing')
    {
        foreach ($this->_addressFields as $addressConstantKey => $addressField) {
            $this->_collection->joinAttribute(
                $addressType . '__' . $addressField,
                'customer_address/' . $addressField,
                'default_' . $addressType,
                null,
                'left'
            );
        }


        return $this;
    }

    /**
     * @return $this
     * @throws Mage_Core_Exception
     */
    private function _joinSubscriptionStatus()
    {
        $tableAlias = 'newsletter';

        $this->_collection->joinTable(
            [$tableAlias => $this->_subscriptionTable],
            'customer_id = entity_id',
            ['accepts_marketing' => 'subscriber_status'],
            null,
            'left'
        );

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
}