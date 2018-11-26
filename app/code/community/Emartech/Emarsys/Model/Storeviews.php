<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Storeviews
 */
class Emartech_Emarsys_Model_Storeviews extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handleGet($request)
    {
        $returnArray = [];
        $storeViews = Mage::app()->getStores(true);

        /** @var Mage_Core_Model_Store $storeView */
        foreach ($storeViews as $storeView) {
            $returnArray[] = [
                'id'             => $storeView->getId(),
                'code'           => $storeView->getCode(),
                'name'           => $storeView->getName(),
                'website_id'     => $storeView->getWebsiteId(),
                'store_group_id' => $storeView->getGroupId(),
            ];
        }

        return $returnArray;
    }
}
