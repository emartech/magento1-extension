<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Subscriptions
 */
class Emartech_Emarsys_Model_Subscriptions extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{
    /**
     * @var null|Mage_Newsletter_Model_Resource_Subscriber_Collection
     */
    private $_collection = null;

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handleGet($request)
    {
        $websiteIds = $request->getParam('website_id', []);
        $storeIds = $request->getParam('store_id', []);
        $subscribed = $request->getParam('subscribed', null);
        $onlyGuest = $request->getParam('only_quest', false);
        $page = $request->getParam('page', 0);
        $pageSize = $request->getParam('page_size', 1000);

        try {
            $this
                ->_initCollection()
                ->_joinWebsite()
                ->_filterWebsite($websiteIds)
                ->_filterStore($storeIds)
                ->_filterSubscribed($subscribed)
                ->_filterCustomers($onlyGuest)
                ->_setPage($page, $pageSize);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return [
            'current_page'  => $this->_collection->getCurPage(),
            'last_page'     => $this->_collection->getLastPageNumber(),
            'page_size'     => $this->_collection->getPageSize(),
            'total_count'   => $this->_collection->getSize(),
            'subscriptions' => $this->_handleSubscriptions(),
        ];
    }

    /**
     * @return array
     */
    private function _handleSubscriptions()
    {
        $subscriptionArray = [];
        foreach ($this->_collection as $subscription) {
            $subscriptionArray[] = $this->_parseSubscription($subscription);
        }

        return $subscriptionArray;
    }

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscription
     *
     * @return string[]
     */
    private function _parseSubscription($subscription)
    {
        $returnArray = [];

        foreach ($subscription->getData() as $key => $value) {
            $returnArray[$key] = $value;
        }

        return $returnArray;
    }

    /**
     * @return $this
     */
    private function _joinWebsite()
    {
        $storeTable = Mage::getSingleton('core/resource')->getTableName('core_store');

        // @codingStandardsIgnoreLine
        $this->_collection->getSelect()->joinLeft(
            [$storeTable],
            $storeTable . '.store_id = main_table.store_id',
            ['website_id']
        );

        return $this;
    }

    /**
     * @param bool $subscribed
     *
     * @return $this
     */
    private function _filterSubscribed($subscribed = null)
    {
        if ($subscribed === true) {
            $this->_collection->addFieldToFilter('subscriber_status', ['eq' => 1]);
        } elseif ($subscribed === false) {
            $this->_collection->addFieldToFilter('subscriber_status', ['neq' => 1]);
        }
        return $this;
    }

    /**
     * @param int|string $websiteId
     *
     * @return $this
     */
    private function _filterWebsite($websiteId = null)
    {
        if ($websiteId !== null) {
            if (!is_array($websiteId)) {
                $websiteId = explode(',', $websiteId);
            }
            if ($websiteId) {
                $this->_collection->addFieldToFilter('website_id', ['in' => $websiteId]);
            }
        }

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
            if ($storeId) {
                $this->_collection->addFieldToFilter('store_id', ['in' => $storeId]);
            }
        }

        return $this;
    }

    /**
     * @param bool $onlyGuest
     *
     * @return $this
     */
    private function _filterCustomers($onlyGuest = null)
    {
        if ((bool)$onlyGuest) {
            $this->_collection->addFieldToFilter('customer_id', ['eq' => 0]);
        }
        return $this;
    }

    /**
     * @return $this
     */
    private function _initCollection()
    {
        /** @var Mage_Newsletter_Model_Resource_Subscriber_Collection _collection */
        $this->_collection = Mage::getModel('newsletter/subscriber')->getCollection();

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
        $this->_collection
            ->setCurPage($page)
            ->setPageSize($pageSize);
        return $this;
    }
}
