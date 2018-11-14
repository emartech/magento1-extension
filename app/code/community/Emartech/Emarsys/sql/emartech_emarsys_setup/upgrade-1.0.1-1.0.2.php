<?php
/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

try {
    $tableName = 'emarsys_events_data';

    $table = $installer->getConnection()
        ->newTable($tableName)
        ->addColumn('event_id', Varien_Db_Ddl_Table::TYPE_BIGINT, null, [
            'identity' => true,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => true,
        ], 'Event Id')
        ->addColumn('event_type', Varien_Db_Ddl_Table::TYPE_VARCHAR, 255, [
            'nullable' => false,
        ], 'Event Type')
        ->addColumn('event_data', Varien_Db_Ddl_Table::TYPE_BLOB, null, [
            'nullable' => false,
        ], 'Event Data')
        ->addColumn('created_at', Varien_Db_Ddl_Table::TYPE_DATETIME, null, [
            'identity' => false,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => false,
        ], 'Created time')
        ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
            'identity' => false,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => false,
        ], 'Website Id')
        ->addIndex($installer->getIdxName($tableName, ['website_id']),
            ['website_id'])
        ->addForeignKey($installer->getFkName($tableName, 'website_id', 'core/website', 'website_id'),
            'website_id', $installer->getTable('core/website'), 'website_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->addColumn('store_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
            'identity' => false,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => false,
        ], 'Store Id')
        ->addColumn('entity_id', Varien_Db_Ddl_Table::TYPE_INTEGER, null, [
            'identity' => false,
            'unsigned' => true,
            'nullable' => false,
            'primary'  => false,
        ], 'Entity Id')
        ->addIndex($installer->getIdxName($tableName, ['store_id']),
            ['store_id'])
        ->addForeignKey($installer->getFkName($tableName, 'store_id', 'core/store', 'store_id'),
            'store_id', $installer->getTable('core/store'), 'store_id',
            Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
        ->setComment('Emarsys Events Data');

    $installer->getConnection()->createTable($table);

} catch (Exception $e) {
    Mage::logException($e);
}

$installer->endSetup();