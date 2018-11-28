<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Model_Websites
 */
class Emartech_Emarsys_Model_Websites extends Emartech_Emarsys_Model_Abstract_Base implements Emartech_Emarsys_Model_Abstract_GetInterface
{

    /**
     * @param Emartech_Emarsys_Controller_Request_Http $request
     *
     * @return array
     */
    public function handleGet($request)
    {
        $returnArray = [];
        $websites = Mage::app()->getWebsites(true);

        /** @var Mage_Core_Model_Website $website */
        foreach ($websites as $website) {
            $returnArray[] = [
                'id'               => (int) $website->getId(),
                'code'             => $website->getCode(),
                'name'             => $website->getName(),
                'default_group_id' => (int) $website->getDefaultGroupId(),
            ];
        }

        return $returnArray;
    }
}
