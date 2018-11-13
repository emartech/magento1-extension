<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_SubscriptionsController
 */
class Emartech_Emarsys_SubscriptionsController extends Emartech_Emarsys_Controller_AbstractController
    implements Emartech_Emarsys_Controller_GetControllerInterface
{
    /**
     * @return array
     */
    public function handleGet()
    {
        /* @var Emartech_Emarsys_Model_Subscriptions $model */
        $model = Mage::getModel('emartech_emarsys/subscriptions');
        return $model->handleGet($this->_apiRequest);
    }
}
