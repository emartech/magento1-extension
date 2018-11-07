<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

require_once(Mage::getModuleDir('controllers', 'Emartech_Emarsys') . DS . 'AbstractController.php');

/**
 * Class Emartech_Emarsys_CustomersController
 */
class Emartech_Emarsys_SubscriptionsController extends Emartech_Emarsys_AbstractController
{
    /**
     * @param array $params
     *
     * @return array
     */
    public function handleRequest($params)
    {
        /* @var Emartech_Emarsys_Model_Subscriptions $model */
        $model = Mage::getModel('emartech_emarsys/subscriptions');
        return $model->handleRequest($params);
    }
}
