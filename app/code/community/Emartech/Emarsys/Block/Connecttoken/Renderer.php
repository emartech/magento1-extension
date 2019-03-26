<?php
/**
 * Copyright ©2018 Itegration Ltd., Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Class Emartech_Emarsys_Block_Connecttoken_Renderer
 */
class Emartech_Emarsys_Block_Connecttoken_Renderer extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml($element) {
        $connectorHelper = Mage::helper('emartech_emarsys/connector');
        $element
            ->setData('value', $connectorHelper->getConnectorTokenValue($element->getData('value')))
            ->setDisabled('disabled');

        return parent::_getElementHtml($element);
    }
}
