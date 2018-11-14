<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_CategoriesController
 */
class Emartech_Emarsys_CategoriesController
    extends Emartech_Emarsys_Controller_AbstractController
    implements Emartech_Emarsys_Controller_GetControllerInterface
{
    /**
     * @return Emartech_Emarsys_Model_Categories
     */
    public function getModel()
    {
        return Mage::getModel('emartech_emarsys/categories');
    }

    /**
     * @return array
     * @throws Mage_Core_Exception
     */
    public function handleGet()
    {
        return $this->getModel()->handleGet($this->_apiRequest);
    }
}
