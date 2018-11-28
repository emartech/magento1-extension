<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Observer_Order_Success
 */
class Emartech_Emarsys_Model_Observer_Order_Success
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function checkoutOnepageControllerSuccessAction(Varien_Event_Observer $observer)
    {
        $lastOrderId = $observer->getOrderIds();

        if ($lastOrderId) {
            Mage::getSingleton('customer/session')->setData(
                Emartech_Emarsys_Helper_Config::LAST_ORDER_ID_REGISTER_KEY,
                $lastOrderId
            );
        }
    }
}
