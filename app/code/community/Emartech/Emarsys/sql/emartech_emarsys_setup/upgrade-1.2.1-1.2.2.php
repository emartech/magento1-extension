<?php
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

try {
    $installer->getConnection()->modifyColumn($this->getTable('emarsys_events_data'), 'event_data', 'mediumblob');
} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();