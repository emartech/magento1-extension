<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Event
 * @method Emartech_Emarsys_Model_Event setEntityId(int $entityId)
 * @method Emartech_Emarsys_Model_Event setWebsiteId(int $websiteId)
 * @method Emartech_Emarsys_Model_Event setStoreId(int $storeId)
 * @method Emartech_Emarsys_Model_Event setEventType(string $eventType)
 * @method Emartech_Emarsys_Model_Event setEventData(string $data)
 * @method Emartech_Emarsys_Model_Event setCreatedAt(string $createdAt)
 */
class Emartech_Emarsys_Model_Event extends Mage_Core_Model_Abstract
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emartech_emarsys/event');
    }
}