<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Resource_Event
 */
class Emartech_Emarsys_Model_Resource_Event extends Mage_Core_Model_Mysql4_Abstract
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('emartech_emarsys/event', 'event_id');
    }
}