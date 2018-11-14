<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_SubscriptionsController
 */
class Emartech_Emarsys_SubscriptionsController extends Emartech_Emarsys_Controller_AbstractController
    implements Emartech_Emarsys_Controller_GetControllerInterface, Emartech_Emarsys_Controller_PostControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Subscriptions
     */
    public function getModel()
    {
        return Mage::getModel('emartech_emarsys/subscriptions');;
    }

    /**
     * @return array
     */
    public function handleGet()
    {
        return $this->getModel()->handleGet($this->_apiRequest);
    }

    /**
     * @return array
     */
    public function handlePost()
    {
        return $this->getModel()->handlePost($this->_apiRequest);
    }
}
