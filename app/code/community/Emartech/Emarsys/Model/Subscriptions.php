<?php

/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Subscriptions
 */
class Emartech_Emarsys_Model_Subscriptions
    extends Emartech_Emarsys_Model_Abstract_Base
    implements Emartech_Emarsys_Model_Abstract_GetInterface, Emartech_Emarsys_Model_Abstract_PostInterface
{
    /**
     * @var null|Mage_Newsletter_Model_Resource_Subscriber_Collection
     */
    private $_collection = null;

    /**
     * @var null|Mage_Customer_Model_Config_Share
     */
    private $_sharingConfig = null;

    /**
     * @var array
     */
    private $numericFields = [
        'website_id',
        'store_id',
        'customer_id',
        'subscriber_id',
    ];

    /**
     * @return Mage_Customer_Model_Config_Share
     */
    private function _getSharingConfig()
    {
        if ($this->_sharingConfig === null) {
            $this->_sharingConfig = Mage::getSingleton('customer/config_share');
        }

        return $this->_sharingConfig;
    }

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
        $onlyGuest = $request->getParam('only_guest', false);
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
            'current_page'  => (int)$this->_collection->getCurPage(),
            'last_page'     => (int)$this->_collection->getLastPageNumber(),
            'page_size'     => (int)$this->_collection->getPageSize(),
            'total_count'   => (int)$this->_collection->getSize(),
            'subscriptions' => $this->_handleSubscriptions(),
        ];
    }

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handlePost($request)
    {
        $subscriptions = $request->getParam('subscriptions', []);

        foreach ($subscriptions as $subscription) {
            if (array_key_exists('subscriber_status', $subscription)) {
                $this->_changeSubscription(
                    $subscription,
                    (bool)$subscription['subscriber_status'] === true ?
                        Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED :
                        Mage_Newsletter_Model_Subscriber::STATUS_UNSUBSCRIBED
                );
            }
        }

        return ['status' => 'ok'];
    }

    /**
     * @param array $subscription
     * @param int   $type
     *
     * @return bool
     */
    private function _changeSubscription($subscription, $type)
    {
        $this->_initCollection();

        if (array_key_exists('subscriber_email', $subscription) && $subscription['subscriber_email']) {
            $subscriberEmail = $subscription['subscriber_email'];
            $subscriberCustomerId = $subscription['customer_id'];
            $subscriberWebsiteId = $subscription['website_id'];

            $this
                ->_filterEmail($subscriberEmail)
                ->_filterCustomer($subscriberCustomerId);

            if ($this->_getSharingConfig()->isWebsiteScope()) {
                $this
                    ->_joinWebsite()
                    ->_filterWebsite($subscriberWebsiteId);
            }

            /** @var Mage_Newsletter_Model_Subscriber $subscriber */
            $subscriber = $this->_collection->fetchItem();

            if (!$subscriber) {
                if ($type !== Mage_Newsletter_Model_Subscriber::STATUS_SUBSCRIBED || !$subscriberCustomerId) {
                    return false;
                }

                if (false === ($customer = $this->_getCustomerData($subscriberCustomerId))
                    || !in_array($subscriberWebsiteId, $customer->getSharedWebsiteIds())
                ) {
                    return false;
                }

                $subscriber = Mage::getModel('newsletter/subscriber');
                $subscriber->setStoreId($customer->getStoreId());
            }

            foreach ($subscription as $key => $value) {
                $subscriber->setData($key, $value);
            }

            $subscriber->setStatus($type);
            $subscriber->setIsStatusChanged(true);

            try {
                $subscriber->save();
            } catch (Exception $e) {
                return false;
            }

            return true;
        }
        return false;
    }

    /**
     * @param int $customerId
     *
     * @return Mage_Customer_Model_Customer|bool
     */
    private function _getCustomerData($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        return $customer->getId() ? $customer : false;
    }

    /**
     * @param string $email
     *
     * @return $this
     */
    private function _filterEmail($email)
    {
        $this->_collection->addFieldToFilter('subscriber_email', ['eq' => $email]);

        return $this;
    }

    /**
     * @param int $customerId
     *
     * @return $this
     */
    private function _filterCustomer($customerId)
    {
        $this->_collection->addFieldToFilter('customer_id', ['eq' => (int)$customerId]);

        return $this;
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
            if (in_array($key, $this->numericFields, true)) {
                $value = (int)$value;
            }
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
            ['store' => $storeTable],
            'store.store_id = main_table.store_id',
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
