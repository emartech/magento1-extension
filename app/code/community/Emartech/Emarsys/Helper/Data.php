<?php

/**
 * Class Emartech_Emarsys_Helper_Data
 */
class Emartech_Emarsys_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * @return string
     */
    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Emartech_Emarsys->version;
    }
}
