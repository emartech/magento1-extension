<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Helper_Event_Subscription
 */
class Emartech_Emarsys_Helper_Event_Subscription extends Emartech_Emarsys_Helper_Event_Base
{
    const DEFAULT_TYPE = 'subscription/unknown';

    /**
     * @param Mage_Newsletter_Model_Subscriber $subscription
     * @param int                              $websiteId
     * @param int                              $storeId
     * @param null|string                      $type
     *
     * @return bool
     */
    public function store(Mage_Newsletter_Model_Subscriber $subscription, $websiteId, $storeId, $type = null)
    {
        if (!$this->isEnabledForWebsite($websiteId, Emartech_Emarsys_Helper_Config::CUSTOMER_EVENTS)) {
            return false;
        }

        if (!$type) {
            $type = self::DEFAULT_TYPE;
        }

        $eventData = $subscription->getData();

        $this->_saveEvent($websiteId, $storeId, $type, $subscription->getId(), $eventData);

        return true;
    }

    /**
     * @param string $eventName
     *
     * @return string
     */
    public function getEventType($eventName)
    {
        switch ($eventName) {
            case 'newsletter_subscriber_save_after':
                $returnType = 'subscription/update';
                break;
            case 'newsletter_subscriber_delete_after':
                $returnType = 'subscription/delete';
                break;
            default:
                $returnType = self::DEFAULT_TYPE;
        }

        return $returnType;
    }
}
