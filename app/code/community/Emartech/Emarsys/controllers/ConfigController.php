<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_ConfigController
 */
class Emartech_Emarsys_ConfigController
    extends Emartech_Emarsys_Controller_AbstractController
    implements Emartech_Emarsys_Controller_PostControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Config
     */
    public function getModel()
    {
        return Mage::getModel('emartech_emarsys/config');
    }
    
    /**
     * @return void
     */
    public function setDefaultAction()
    {
        $this->indexAction();
    }

    /**
     * @return array
     */
    public function handlePost()
    {
        return $this->getModel()->handlePost($this->_apiRequest);
    }
}
