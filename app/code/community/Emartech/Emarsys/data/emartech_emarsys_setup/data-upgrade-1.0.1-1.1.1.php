<?php
/**
 * Product Description update for starter_kit
 *
 * @category  Aion
 * @package   Aion_Base
 * @author    Király Zoltán <kiraly.zoltan@aion.hu>
 * @copyright 2016 AionNext Kft. (http://www.aion.hu)
 * @license   http://aion.hu/license Aion License
 * @link      http://www.aion.hu
 */
/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;

/** @var Emartech_Emarsys_Helper_Connector $connectorHelper */
$connectorHelper = Mage::helper('emartech_emarsys/connector');
$installer->setConfigData(Emartech_Emarsys_Helper_Connector::XML_PATH_CONNECTOR_TOKEN, $connectorHelper->refreshToken());

Mage::app()->getCacheInstance()->cleanType('config');
