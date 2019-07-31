<?php
/**
 * Copyright ©2019 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 * @author: Perencz Tamás <tamas.perencz@itegraion.com>
 */

/**
 * Class Emartech_Emarsys_Model_Resource_Product_Collection
 */
class Emartech_Emarsys_Model_Resource_Product_Collection extends Mage_Catalog_Model_Resource_Product_Collection
{
    /**
     * @return bool
     */
    public function isEnabledFlat()
    {
        return false;
    }
}

